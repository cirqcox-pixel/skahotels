/**
 * SKA Hotels — email notifications (Formspree + optional Resend webhook)
 * Runs after a successful Supabase save on GitHub Pages.
 *
 * Naguru mail works today because Formspree form myegbgjy delivers to
 * naguru.booking@ and sends a confirmation to the guest `email` field.
 * Munyonyo failed when `_cc` included munyonyo.booking@ (Formspree rejects
 * the whole post). Guest + property copies are sent as separate posts.
 */
(function (global) {
  'use strict';

  var NAGURU_BOOKING = 'naguru.booking@skaboutiquebnb.com';
  var MUNYONYO_BOOKING = 'munyonyo.booking@skaboutiquebnb.com';

  function cfgNow() {
    return global.SKA_CONFIG || {};
  }

  var cfg = cfgNow();

  function adminInbox(branch) {
    cfg = cfgNow();
    var b = String(branch || '').toLowerCase();
    var map = cfg.branchEmails || {};
    var isMuny = b.indexOf('muny') >= 0;
    var fallback = isMuny ? MUNYONYO_BOOKING : NAGURU_BOOKING;
    var configured = isMuny
      ? (map.Munyonyo || map.munyonyo)
      : (map.Naguru || map.naguru || map[branch]);
    if (configured && /@skaboutiquebnb\.com/i.test(String(configured))) {
      return String(configured).trim();
    }
    return fallback;
  }

  function formspreeUrl(key) {
    var f = cfg.formspree || {};
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

  function bookingSubject(type, branch) {
    var prefix = type === 'booking_confirmed'
      ? 'SKA Booking Confirmed — '
      : type === 'booking_cancelled'
        ? 'SKA Booking Cancelled — '
        : 'SKA Booking Request — ';
    return prefix + (branch || 'Property');
  }

  function bookingFields(type, data) {
    return {
      type: type.indexOf('booking') === 0 ? 'booking' : type,
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
      site: cfg.siteName || 'SKA The Boutique'
    };
  }

  /**
   * Same payload Naguru already receives: Formspree owner + guest confirmation.
   * `_cc` is only the guest — extra inboxes are separate posts.
   */
  function guestFormspreePayload(type, data) {
    var fields = bookingFields(type, data);
    return Object.assign({}, fields, {
      _subject: bookingSubject(type, data.branch),
      _replyto: data.email,
      _cc: data.email || '',
      email: data.email
    });
  }

  /** Second post so Formspree confirmation goes to the property inbox. */
  function deskFormspreePayload(type, data, inbox) {
    var fields = bookingFields(type, data);
    return Object.assign({}, fields, {
      _subject: bookingSubject(type, data.branch),
      _replyto: data.email,
      _cc: inbox,
      email: inbox,
      name: (data.name || 'Guest') + ' (notify ' + (data.branch || 'property') + ')',
      message: [
        'Property inbox copy for ' + inbox,
        'Guest: ' + (data.name || '') + ' <' + (data.email || '') + '>',
        'Phone: ' + (data.phone || '—'),
        'Room / package: ' + (data.room_type || '—'),
        'Dates: ' + (data.checkin || '') + ' → ' + (data.checkout || ''),
        'Total: ' + (data.currency || '') + ' ' + (data.total || data.price || ''),
        data.message ? ('Message: ' + data.message) : ''
      ].filter(Boolean).join('\n')
    });
  }

  async function sendFormspree(type, data) {
    cfg = cfgNow();
    var url = formspreeUrl(type) || formspreeUrl('inquiry');
    if (!url) return false;

    if (type === 'booking' || type === 'booking_confirmed' || type === 'booking_cancelled') {
      await postJson(url, guestFormspreePayload(type, data));
      var inbox = adminInbox(data.branch);
      var guest = String(data.email || '').toLowerCase();
      if (inbox && inbox.toLowerCase() !== guest) {
        try {
          await postJson(url, deskFormspreePayload(type, data, inbox));
        } catch (e) {
          console.warn('[SKA Notify] Property Formspree copy:', e.message || e);
        }
      }
      return true;
    }

    if (type === 'inquiry_reply') {
      var info = (cfg.siteEmail || (cfg.notify && cfg.notify.info) || 'info@skaboutiquebnb.com');
      await postJson(url, {
        _subject: 'Re: ' + (data.subject || 'Your SKA inquiry'),
        _replyto: info,
        _cc: data.email || '',
        type: 'inquiry_reply',
        name: data.name,
        email: data.email,
        message: data.reply || data.reply_message || data.message || '',
        site: cfg.siteName || 'SKA The Boutique'
      });
      return true;
    }

    await postJson(url, {
      _subject: 'SKA Contact: ' + (data.subject || 'General Inquiry'),
      _replyto: data.email,
      _cc: data.email || '',
      type: 'inquiry',
      name: data.name,
      email: data.email,
      phone: data.phone || '',
      subject: data.subject || 'General Inquiry',
      message: data.message || '',
      site: cfg.siteName || 'SKA The Boutique'
    });
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
    var timer = ctrl ? setTimeout(function () { ctrl.abort(); }, 8000) : null;
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
      console.info('[SKA Notify] No email went out. Deploy notify-email or check Formspree.');
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
