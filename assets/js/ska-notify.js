/**
 * SKA Hotels — email notifications (Formspree + optional Resend webhook)
 * Runs after a successful Supabase save on GitHub Pages.
 */
(function (global) {
  'use strict';

  var cfg = global.SKA_CONFIG || {};

  function formspreeUrl(key) {
    var f = cfg.formspree || {};
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
      throw new Error('Notify failed (' + res.status + '): ' + text.slice(0, 160));
    }
    return true;
  }

  /** Formspree — no secret needed; form ID is public */
  async function sendFormspree(type, data) {
    var url = formspreeUrl(type) || formspreeUrl('inquiry');
    if (!url) return false;

    var payload;
    if (type === 'booking') {
      payload = {
        _subject: 'SKA Booking Request — ' + (data.branch || 'Property'),
        _replyto: data.email,
        type: 'booking',
        name: data.name,
        email: data.email,
        phone: data.phone || '',
        whatsapp: data.whatsapp || '',
        branch: data.branch || '',
        room_type: data.room_type || '',
        checkin: data.checkin || '',
        checkout: data.checkout || '',
        price: data.price || '',
        total: data.total || '',
        season: data.season || '',
        message: data.message || '',
        site: cfg.siteName || 'SKA The Boutique'
      };
    } else {
      payload = {
        _subject: 'SKA Contact: ' + (data.subject || 'General Inquiry'),
        _replyto: data.email,
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

  /**
   * Optional Resend path: POST to your Edge Function / webhook.
   * Function should call Resend server-side with RESEND_API_KEY.
   */
  async function sendWebhook(type, data) {
    var url = (cfg.notify && cfg.notify.webhookUrl) || cfg.resendWebhook || '';
    if (!url) return false;

    await postJson(url, {
      type: type,
      to: (cfg.notify && cfg.notify.to) || cfg.siteEmail || 'info@skaboutiquebnb.com',
      data: data,
      site: cfg.siteName || 'SKA The Boutique'
    });
    return true;
  }

  /**
   * Fire email alerts. Never throws to the caller — booking/inquiry already saved.
   */
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
      console.info('[SKA Notify] No email provider configured. Add Formspree IDs in ska-config.js');
    }
    return results;
  }

  global.SkaNotify = {
    notify: notify,
    sendFormspree: sendFormspree,
    sendWebhook: sendWebhook
  };
})(window);
