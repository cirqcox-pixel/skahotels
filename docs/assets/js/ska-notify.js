/**
 * SKA Hotels — email notifications via Formspree (GitHub Pages)
 * Naguru → formspree.io/f/myegbgjy  → naguru.booking@
 * Munyonyo → formspree.io/f/xzezenyo → munyonyo.booking@
 * Same payload for both: property inbox + guest CC.
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

  function formspreeUrlForBranch(branch) {
    var f = cfgNow().formspree || {};
    var id;
    if (isMunyonyo(branch)) {
      id = String(f.bookingMunyonyo || f.munyonyo || '').trim();
    } else {
      id = String(f.booking || f.endpoint || '').trim();
    }
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
    return {
      _subject: bookingSubject + branch,
      _replyto: guest,
      _cc: [adminInbox(branch), guest].filter(Boolean).join(','),
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

  async function notify(type, data) {
    await sendFormspree(type, data);
    return { formspree: true };
  }

  global.SkaNotify = {
    notify: notify,
    sendFormspree: sendFormspree,
    adminInbox: adminInbox
  };
})(window);
