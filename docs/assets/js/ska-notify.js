/**
 * SKA Hotels — email notifications (Formspree + optional Resend webhook)
 * Runs after a successful Supabase save on GitHub Pages.
 *
 * Naguru: Formspree form myegbgjy is owned by naguru.booking@ — one post,
 * _cc = property inbox + guest (identical payload every time).
 *
 * Munyonyo: must use formspree.bookingMunyonyo (duplicate form in Formspree
 * with notification email munyonyo.booking@). CC'ing munyonyo.booking@ on the
 * Naguru form lands in Formspree spam and never delivers.
 */
(function (global) {
  'use strict';

  var NAGURU_BOOKING = 'naguru.booking@skaboutiquebnb.com';
  var MUNYONYO_BOOKING = 'munyonyo.booking@skaboutiquebnb.com';

  function cfgNow() {
    return global.SKA_CONFIG || {};
  }

  var cfg = cfgNow();

  function isMunyonyo(branch) {
    return /muny/i.test(String(branch || ''));
  }

  function adminInbox(branch) {
    cfg = cfgNow();
    if (isMunyonyo(branch)) return MUNYONYO_BOOKING;
    return NAGURU_BOOKING;
  }

  function formspreeIdForBranch(branch) {
    cfg = cfgNow();
    var f = cfg.formspree || {};
    if (isMunyonyo(branch)) {
      return f.bookingMunyonyo || f.munyonyo || '';
    }
    return f.booking || f.endpoint || '';
  }

  function formspreeUrlForBranch(branch) {
    var id = formspreeIdForBranch(branch);
    if (!id) return '';
    if (id.indexOf('http') === 0) return id;
    return 'https://formspree.io/f/' + id;
  }

  function formspreeUrl(key) {
    var f = cfgNow().formspree || {};
    var id = f[key] || f.endpoint || '';
    if (!id) return '';
    if (id.indexOf('http') === 0) return id;
    return 'https://formspree.io/f/' + id;
  }

  async function postJson(url, payload, extraHeaders) {
    var headers = {
      'Content-Type': 'application/json',
      Accept: 'application/json'
    };
    if (extraHeaders) {
      Object.keys(extraHeaders).forEach(function (k) { headers[k] = extraHeaders[k]; });
    }
    var res = await fetch(url, {
      method: 'POST',
      headers: headers,
      body: JSON.stringify(payload)
    });
    if (!res.ok) {
      var text = await res.text().catch(function () { return ''; });
      throw new Error('Notify failed (' + res.status + '): ' + text.slice(0, 160));
    }
    return true;
  }

  /** Identical booking payload Naguru has used successfully since e113ffc. */
  function bookingFormspreePayload(type, data) {
    var bookingSubject = type === 'booking_confirmed'
      ? 'SKA Booking Confirmed — '
      : type === 'booking_cancelled'
        ? 'SKA Booking Cancelled — '
        : 'SKA Booking Request — ';
    return {
      _subject: bookingSubject + (data.branch || 'Property'),
      _replyto: data.email,
      _cc: [adminInbox(data.branch), data.email].filter(Boolean).join(','),
      type: 'booking',
      name: data.name,
      email: data.email,
      phone: data.phone || '',
      whatsapp: data.whatsapp || '',
      branch: data.branch || '',
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
    cfg = cfgNow();
    if (type === 'booking' || type === 'booking_confirmed' || type === 'booking_cancelled') {
      var branch = data.branch || '';
      var url = formspreeUrlForBranch(branch);
      var payload = bookingFormspreePayload(type, data);
      if (isMunyonyo(branch) && !url) {
        /* Naguru form owner still receives the submission; CC munyonyo.booking@ → spam. */
        url = formspreeUrl(type) || formspreeUrl('inquiry');
        payload._cc = String(data.email || '').trim();
        console.warn(
          '[SKA Notify] Add formspree.bookingMunyonyo (duplicate Ska Hotels form → munyonyo.booking@) ' +
          'for Munyonyo admin + guest delivery like Naguru.'
        );
      }
      if (!url) return false;
      await postJson(url, payload);
      return true;
    }

    var url = formspreeUrl(type) || formspreeUrl('inquiry');
    if (!url) return false;

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
    cfg = cfgNow();
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

    var to;
    if (type.indexOf('booking') === 0) to = adminInbox(data.branch);
    else to = data.site_email || cfg.siteEmail || (cfg.notify && cfg.notify.to) || 'info@skaboutiquebnb.com';

    var ctrl = typeof AbortController !== 'undefined' ? new AbortController() : null;
    var timer = ctrl ? setTimeout(function () { ctrl.abort(); }, 6000) : null;
    try {
      var res = await fetch(url, {
        method: 'POST',
        headers: Object.assign({
          'Content-Type': 'application/json',
          Accept: 'application/json'
        }, headers),
        body: JSON.stringify({
          type: type,
          to: to,
          data: data,
          site: cfg.siteName || 'SKA The Boutique'
        }),
        signal: ctrl ? ctrl.signal : undefined
      });
      if (!res.ok) {
        var text = await res.text().catch(function () { return ''; });
        throw new Error('Notify failed (' + res.status + '): ' + text.slice(0, 160));
      }
      return true;
    } finally {
      if (timer) clearTimeout(timer);
    }
  }

  async function notify(type, data) {
    var results = { formspree: false, webhook: false };
    try {
      results.formspree = await sendFormspree(type, data);
    } catch (e) {
      console.warn('[SKA Notify] Formspree:', e.message || e);
    }
    try {
      results.webhook = await sendWebhook(type, data);
    } catch (e) {
      console.warn('[SKA Notify] Webhook/Resend:', e.message || e);
    }
    if (!results.formspree && !results.webhook) {
      console.info('[SKA Notify] No email went out. Check Formspree bookingMunyonyo form ID or deploy notify-email.');
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
