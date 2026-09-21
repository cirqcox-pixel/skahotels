/**
 * SKA Hotels — email notifications
 * Staff inboxes: naguru.booking@ / munyonyo.booking@ / info@
 * Visitors are CC'd on every mail, and also receive their own copy
 * (booking, confirm, cancel, inquiry reply). Fire-and-forget so the UI
 * never waits on FormSubmit.
 */
(function (global) {
  'use strict';

  var NAGURU_BOOKING = 'naguru.booking@skaboutiquebnb.com';
  var MUNYONYO_BOOKING = 'munyonyo.booking@skaboutiquebnb.com';
  var INFO_INBOX = 'info@skaboutiquebnb.com';

  function cfgNow() {
    return global.SKA_CONFIG || {};
  }

  function isMunyonyo(branch) {
    return /muny/i.test(String(branch || ''));
  }

  function staffInbox(type, branch) {
    if (type === 'inquiry' || type === 'inquiry_reply') {
      return (cfgNow().siteEmail && /@/.test(cfgNow().siteEmail))
        ? cfgNow().siteEmail
        : INFO_INBOX;
    }
    var emails = cfgNow().branchEmails || {};
    if (isMunyonyo(branch)) return emails.Munyonyo || MUNYONYO_BOOKING;
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

  function messageFor(type, data, reply) {
    if (type === 'inquiry_reply') {
      var body = 'SKA The Boutique replied:\n\n' + reply;
      if (data.message) body += '\n\n--- Your original message ---\n' + data.message;
      return body;
    }
    if (type === 'booking_confirmed') {
      return 'Your booking has been confirmed. We look forward to welcoming you.';
    }
    if (type === 'booking_cancelled') {
      return 'Your booking has been cancelled. Contact us if you have questions.';
    }
    return data.message || '';
  }

  function payloadFor(type, data, to, cc) {
    var guest = String(data.email || '').trim();
    var reply = String(data.reply || data.reply_message || '').trim();
    var staff = staffInbox(type, data.branch || '');
    return {
      _subject: subjectFor(type, data),
      _captcha: 'false',
      _template: 'table',
      _replyto: type === 'inquiry_reply' ? staff : (guest || staff),
      _cc: cc || '',
      name: data.name || '',
      email: guest,
      phone: data.phone || '',
      whatsapp: data.whatsapp || '',
      branch: data.branch || '',
      room_type: data.room_type || '',
      package_option: data.package_option || '',
      guests: data.guests || '',
      checkin: data.checkin || '',
      checkout: data.checkout || '',
      total: (data.currency || 'USD') + ' ' + (data.total || data.price || ''),
      staff_reply: reply,
      message: messageFor(type, data, reply),
      notify_inbox: to,
      site: cfgNow().siteName || 'SKA The Boutique'
    };
  }

  function postMail(to, payload) {
    if (!to || String(to).indexOf('@') < 0) return;
    fetch('https://formsubmit.co/ajax/' + encodeURIComponent(to), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json'
      },
      body: JSON.stringify(payload),
      keepalive: true
    }).catch(function () { /* background mail */ });
  }

  function notify(type, data) {
    var guest = String((data && data.email) || '').trim();
    var staff = staffInbox(type, (data && data.branch) || '');
    postMail(staff, payloadFor(type, data, staff, guest));
    if (guest && guest.toLowerCase() !== String(staff).toLowerCase()) {
      postMail(guest, payloadFor(type, data, guest, staff));
    }
    return Promise.resolve({ ok: true });
  }

  global.SkaNotify = {
    notify: notify,
    adminInbox: function (branch) { return staffInbox('booking', branch); }
  };
})(window);
