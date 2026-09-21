/**
 * SKA Hotels — booking / inquiry email (no Formspree)
 * Naguru bookings  → naguru.booking@skaboutiquebnb.com
 * Munyonyo bookings → munyonyo.booking@skaboutiquebnb.com
 * Inquiries         → info@skaboutiquebnb.com
 * Staff replies     → visitor email
 */
(function (global) {
  'use strict';

  var NAGURU_BOOKING = 'naguru.booking@skaboutiquebnb.com';
  var MUNYONYO_BOOKING = 'munyonyo.booking@skaboutiquebnb.com';
  var INFO_INBOX = 'info@skaboutiquebnb.com';
  var MAIL_TIMEOUT_MS = 8000;

  function cfgNow() {
    return global.SKA_CONFIG || {};
  }

  function isMunyonyo(branch) {
    return /muny/i.test(String(branch || ''));
  }

  function adminInbox(type, branch) {
    if (type === 'inquiry') {
      return (cfgNow().siteEmail && /@/.test(cfgNow().siteEmail))
        ? cfgNow().siteEmail
        : INFO_INBOX;
    }
    var emails = cfgNow().branchEmails || {};
    if (isMunyonyo(branch)) {
      return emails.Munyonyo || MUNYONYO_BOOKING;
    }
    return emails.Naguru || NAGURU_BOOKING;
  }

  function subjectFor(type, data) {
    var branch = data.branch || 'Property';
    if (type === 'booking_confirmed') return 'SKA Booking Confirmed — ' + branch;
    if (type === 'booking_cancelled') return 'SKA Booking Cancelled — ' + branch;
    if (type === 'booking') return 'SKA Booking Request — ' + branch;
    if (type === 'inquiry_reply') return 'Re: ' + (data.subject || 'Your SKA inquiry');
    return 'SKA Contact: ' + (data.subject || 'General Inquiry');
  }

  function payloadFor(type, data, to) {
    var guest = String(data.email || '').trim();
    var reply = data.reply || data.reply_message || '';
    return {
      _subject: subjectFor(type, data),
      _captcha: 'false',
      _template: 'table',
      _replyto: type === 'inquiry_reply' ? (cfgNow().siteEmail || INFO_INBOX) : (guest || to),
      _cc: type === 'inquiry_reply' ? (cfgNow().siteEmail || INFO_INBOX) : (guest || ''),
      type: type,
      name: data.name,
      email: guest,
      phone: data.phone || '',
      whatsapp: data.whatsapp || '',
      branch: data.branch || '',
      room_type: data.room_type || '',
      package_option: data.package_option || '',
      guests: data.guests || '',
      currency: data.currency || 'USD',
      checkin: data.checkin || '',
      checkout: data.checkout || '',
      price: data.price || '',
      total: data.total || '',
      season: data.season || '',
      message: reply || data.message || '',
      notify_inbox: to,
      site: cfgNow().siteName || 'SKA The Boutique'
    };
  }

  async function postJson(url, body, extraHeaders) {
    var ctrl = typeof AbortController !== 'undefined' ? new AbortController() : null;
    var timer = ctrl ? setTimeout(function () { ctrl.abort(); }, MAIL_TIMEOUT_MS) : null;
    var headers = {
      'Content-Type': 'application/json',
      Accept: 'application/json'
    };
    if (extraHeaders) {
      Object.keys(extraHeaders).forEach(function (k) { headers[k] = extraHeaders[k]; });
    }
    try {
      var res = await fetch(url, {
        method: 'POST',
        headers: headers,
        body: JSON.stringify(body),
        signal: ctrl ? ctrl.signal : undefined
      });
      var text = await res.text().catch(function () { return ''; });
      var parsed = null;
      try { parsed = JSON.parse(text); } catch (e) { /* not json */ }
      if (!res.ok) {
        throw new Error('Notify failed (' + res.status + '): ' + text.slice(0, 180));
      }
      if (parsed && String(parsed.success) === 'false') {
        throw new Error(parsed.message || 'Mailer needs inbox activation');
      }
      if (parsed && parsed.ok === false) {
        throw new Error(parsed.error || 'Notify failed');
      }
      return true;
    } finally {
      if (timer) clearTimeout(timer);
    }
  }

  async function sendDirect(type, data) {
    var guest = String(data.email || '').trim();
    var to = type === 'inquiry_reply' && guest ? guest : adminInbox(type, data.branch || '');
    var url = 'https://formsubmit.co/ajax/' + encodeURIComponent(to);
    await postJson(url, payloadFor(type, data, to));
    return true;
  }

  async function notify(type, data) {
    return sendDirect(type, data);
  }

  global.SkaNotify = {
    notify: notify,
    adminInbox: function (branch) { return adminInbox('booking', branch); }
  };
})(window);
