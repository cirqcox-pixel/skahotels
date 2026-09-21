/**
 * SKA Hotels — Formspree email notifications (GitHub Pages)
 * Naguru  → https://formspree.io/f/myegbgjy  → naguru.booking@
 * Munyonyo → https://formspree.io/f/xzezenyo → munyonyo.booking@
 * (Munyonyo falls back to myegbgjy if xzezenyo is unavailable)
 */
(function (global) {
  'use strict';

  var NAGURU_BOOKING = 'naguru.booking@skaboutiquebnb.com';
  var MUNYONYO_BOOKING = 'munyonyo.booking@skaboutiquebnb.com';
  var NAGURU_FORM = 'myegbgjy';
  var MUNYONYO_FORM = 'xzezenyo';
  var SHARED_FORM = NAGURU_FORM;

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

  /** Only accept real Formspree legacy hashes (6–10 chars). Rejects skaMunyonyoBooking etc. */
  function validFormId(id) {
    return /^[a-z0-9]{6,10}$/i.test(String(id || '').trim());
  }

  function formIdForBranch(branch) {
    var f = cfgNow().formspree || {};
    if (isMunyonyo(branch)) {
      var m = String(f.bookingMunyonyo || '').trim();
      return validFormId(m) ? m : MUNYONYO_FORM;
    }
    var n = String(f.booking || f.endpoint || '').trim();
    return validFormId(n) ? n : NAGURU_FORM;
  }

  function formUrl(id) {
    if (!id) return '';
    if (id.indexOf('http') === 0) return id;
    return 'https://formspree.io/f/' + id;
  }

  function formspreeUrlForBranch(branch) {
    return formUrl(formIdForBranch(branch));
  }

  function formspreeUrl(key) {
    var f = cfgNow().formspree || {};
    var id = f[key] || f.endpoint || '';
    if (!validFormId(id)) id = NAGURU_FORM;
    return formUrl(id);
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
      throw new Error('Formspree ' + res.status + ': ' + text.slice(0, 180));
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
    var muny = isMunyonyo(branch);
    var ccList = muny
      ? [guest].filter(Boolean)
      : [adminInbox(branch), guest].filter(Boolean);
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
      notify_email: adminInbox(branch),
      site: cfgNow().siteName || 'SKA The Boutique'
    };
  }

  async function sendBookingFormspree(type, data) {
    var branch = data.branch || '';
    var payload = bookingFormspreePayload(type, data);
    var primaryId = formIdForBranch(branch);
    var primaryUrl = formUrl(primaryId);

    try {
      await postJson(primaryUrl, payload);
      return true;
    } catch (primaryErr) {
      if (!isMunyonyo(branch) || primaryId === SHARED_FORM) {
        throw primaryErr;
      }
      console.warn('[SKA Notify] Munyonyo form unavailable, using Naguru form fallback:', primaryErr.message || primaryErr);
      await postJson(formUrl(SHARED_FORM), payload);
      return true;
    }
  }

  async function sendFormspree(type, data) {
    if (type === 'booking' || type === 'booking_confirmed' || type === 'booking_cancelled') {
      return sendBookingFormspree(type, data);
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

  async function notify(type, data) {
    await sendFormspree(type, data);
    return { formspree: true };
  }

  global.SkaNotify = {
    notify: notify,
    sendFormspree: sendFormspree,
    adminInbox: adminInbox,
    validFormId: validFormId
  };
})(window);
