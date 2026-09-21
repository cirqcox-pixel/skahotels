/**
 * SKA Hotels — email notifications (Formspree + optional Resend webhook)
 * Naguru: legacy form hash → naguru.booking@ (property + guest CC).
 * Munyonyo: Formspree CLI project form → munyonyo.booking@ (guest CC only).
 */
(function (global) {
  'use strict';

  var NAGURU_BOOKING = 'naguru.booking@skaboutiquebnb.com';
  var MUNYONYO_BOOKING = 'munyonyo.booking@skaboutiquebnb.com';

  function cfgNow() {
    return global.SKA_CONFIG || {};
  }

  function isMunyonyo(branch) {
    return /muny/i.test(String(branch || ''));
  }

  function adminInbox(branch) {
    if (isMunyonyo(branch)) return MUNYONYO_BOOKING;
    return NAGURU_BOOKING;
  }

  function munyonyoFormspreeEndpoint() {
    var f = cfgNow().formspree || {};
    var project = String(f.munyonyoProject || '').trim();
    var formKey = String(f.bookingMunyonyo || '').trim();
    if (!project || !formKey) return '';
    return 'https://formspree.io/p/' + encodeURIComponent(project) + '/f/' + encodeURIComponent(formKey);
  }

  function naguruFormspreeEndpoint() {
    var f = cfgNow().formspree || {};
    var id = String(f.booking || f.endpoint || '').trim();
    if (!id) return '';
    if (id.indexOf('http') === 0) return id;
    return 'https://formspree.io/f/' + id;
  }

  function formspreeUrlForBranch(branch) {
    if (isMunyonyo(branch)) {
      return munyonyoFormspreeEndpoint();
    }
    return naguruFormspreeEndpoint();
  }

  function formspreeUrl(key) {
    var f = cfgNow().formspree || {};
    var id = f[key] || f.endpoint || '';
    if (!id) return '';
    if (id.indexOf('http') === 0) return id;
    return 'https://formspree.io/f/' + id;
  }

  async function postJson(url, payload) {
    var res = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json'
      },
      body: JSON.stringify(payload)
    });
    if (!res.ok) {
      var text = await res.text().catch(function () { return ''; });
      throw new Error('Notify failed (' + res.status + '): ' + text.slice(0, 200));
    }
    return true;
  }

  function bookingFormspreePayload(type, data) {
    var branch = String(data.branch || 'Property');
    var bookingSubject = type === 'booking_confirmed'
      ? 'SKA Booking Confirmed — '
      : type === 'booking_cancelled'
        ? 'SKA Booking Cancelled — '
        : 'SKA Booking Request — ';
    var guest = String(data.email || '').trim();
    var ccList;
    if (isMunyonyo(branch) && munyonyoFormspreeEndpoint()) {
      /* Munyonyo CLI form already emails munyonyo.booking@ — CC guest only */
      ccList = guest ? [guest] : [];
    } else {
      ccList = [adminInbox(branch), guest].filter(Boolean);
    }
    return {
      _subject: bookingSubject + branch,
      _replyto: guest,
      _cc: ccList.join(','),
      type: 'booking',
      name: data.name,
      email: data.email,
      phone: data.phone || '',
      whatsapp: data.whatsapp || '',
      branch: branch,
      room_type: data.room_type || '',
      package_option: data.package_option || '',
      guests: data.guests || '',
      currency: data.currency || '',
      checkin: data.checkin || '',
      checkout: data.checkout || '',
      price: data.price || '',
      total: data.total || '',
      season: data.season || '',
      message: data.message || '',
      site: cfgNow().siteName || 'SKA The Boutique'
    };
  }

  async function sendFormspree(type, data) {
    if (type === 'booking' || type === 'booking_confirmed' || type === 'booking_cancelled') {
      var url = formspreeUrlForBranch(data.branch || '');
      if (!url) {
        throw new Error('Formspree is not configured for ' + (data.branch || 'this property'));
      }
      await postJson(url, bookingFormspreePayload(type, data));
      return true;
    }

    var url = formspreeUrl(type) || formspreeUrl('inquiry');
    if (!url) return false;
    var cfg = cfgNow();
    var payload;

    if (type === 'inquiry_reply') {
      var info = (cfg.siteEmail || (cfg.notify && cfg.notify.info) || 'info@skaboutiquebnb.com');
      payload = {
        _subject: 'Re: ' + (data.subject || 'Your SKA inquiry'),
        _replyto: info,
        _cc: data.email || '',
        type: 'inquiry_reply',
        name: data.name,
        email: data.email,
        message: data.reply || data.reply_message || data.message || '',
        site: cfg.siteName || 'SKA The Boutique'
      };
    } else {
      payload = {
        _subject: 'SKA Contact: ' + (data.subject || 'General Inquiry'),
        _replyto: data.email,
        _cc: [cfg.siteEmail || 'info@skaboutiquebnb.com', data.email].filter(Boolean).join(','),
        type: 'inquiry',
        name: data.name,
        email: data.email,
        phone: data.phone || '',
        subject: data.subject || 'General Inquiry',
        message: data.message || '',
        site: cfg.siteName || 'SKA The Boutique'
      };
    }

    await postJson(url, payload);
    return true;
  }

  async function sendWebhook(type, data) {
    var cfg = cfgNow();
    var url = (cfg.notify && cfg.notify.webhookUrl) || cfg.resendWebhook || '';
    if (!url && cfg.supabaseUrl) {
      url = String(cfg.supabaseUrl).replace(/\/$/, '') + '/functions/v1/notify-email';
    }
    if (!url) return false;

    var headers = {};
    if (cfg.supabaseAnonKey) {
      headers.Authorization = 'Bearer ' + cfg.supabaseAnonKey;
      headers.apikey = cfg.supabaseAnonKey;
    }

    var res = await fetch(url, {
      method: 'POST',
      headers: Object.assign({
        'Content-Type': 'application/json',
        Accept: 'application/json'
      }, headers),
      body: JSON.stringify({
        type: type,
        to: adminInbox(data.branch),
        data: data,
        site: cfg.siteName || 'SKA The Boutique'
      })
    });
    if (!res.ok) return false;
    return true;
  }

  async function notify(type, data) {
    var results = { formspree: false, webhook: false };
    try {
      results.formspree = await sendFormspree(type, data);
    } catch (e) {
      console.error('[SKA Notify] Formspree:', e.message || e);
    }
    try {
      results.webhook = await sendWebhook(type, data);
    } catch (e) {
      console.warn('[SKA Notify] Webhook:', e.message || e);
    }
    if (!results.formspree && !results.webhook) {
      console.error('[SKA Notify] Booking saved but no email was sent. Check Formspree settings.');
    }
    return results;
  }

  global.SkaNotify = {
    notify: notify,
    sendFormspree: sendFormspree,
    sendWebhook: sendWebhook,
    adminInbox: adminInbox
  };
})(window);
