/**
 * SKA Hotels — apply CMS property settings to footer, maps, and notify inboxes
 */
(function (global) {
  'use strict';

  function telHref(value) {
    var n = String(value || '').replace(/[^\d+]/g, '');
    return n ? 'tel:' + n : '#';
  }

  function setText(el, value) {
    if (!el || value == null || value === '') return;
    el.textContent = value;
  }

  function applyFooter(map) {
    var email = document.querySelector('[data-ska-footer="email"]');
    if (email && map.site_email) {
      email.textContent = map.site_email;
      email.setAttribute('href', 'mailto:' + map.site_email);
    }
    var phone = document.querySelector('[data-ska-footer="phone"]');
    if (phone && map.site_phone_main) {
      phone.textContent = map.site_phone_main;
      phone.setAttribute('href', telHref(map.site_phone_main));
    }
    var phone2 = document.querySelector('[data-ska-footer="phone2"]');
    if (phone2 && map.site_phone_naguru) {
      phone2.textContent = map.site_phone_naguru;
      phone2.setAttribute('href', telHref(map.site_phone_naguru));
    }
    var addr = document.querySelector('[data-ska-footer="address"]');
    setText(addr, map.site_address);
    var tag = document.querySelector('[data-ska-footer="tagline"]');
    setText(tag, map.footer_tagline);
    var blurb = document.querySelector('[data-ska-footer="blurb"]');
    setText(blurb, map.footer_blurb);
    var ig = document.querySelector('[data-ska-footer="instagram"]');
    if (ig && map.instagram_url) ig.setAttribute('href', map.instagram_url);
    var fb = document.querySelector('[data-ska-footer="facebook"]');
    if (fb && map.facebook_url) fb.setAttribute('href', map.facebook_url);
    var wa = document.querySelector('[data-ska-footer="whatsapp"]');
    if (wa && map.whatsapp_url) wa.setAttribute('href', map.whatsapp_url);
  }

  function applyLocation(map) {
    var section = document.querySelector('[data-ska-location]');
    if (!section) return;
    var key = (section.getAttribute('data-ska-location') || 'Naguru').toLowerCase();
    var prefix = key.indexOf('muny') >= 0 ? 'munyonyo_' : 'naguru_';
    var address = section.querySelector('[data-ska-field="address"]');
    var phone = section.querySelector('[data-ska-field="phone"]');
    var email = section.querySelector('[data-ska-field="email"]');
    var emailAlt = section.querySelector('[data-ska-field="email_alt"]');
    var mapFrame = section.querySelector('[data-ska-field="map"]');
    var nameBits = [map[prefix + 'name'], map[prefix + 'address']].filter(Boolean);
    if (address && nameBits.length) address.innerHTML = nameBits.join('<br>');
    if (phone && map[prefix + 'phone']) {
      var span = phone.querySelector('[data-ska-text]') || phone;
      if (span.tagName === 'P') {
        phone.innerHTML = '<i class="fa-solid fa-phone"></i> ' + map[prefix + 'phone'];
      } else {
        span.textContent = map[prefix + 'phone'];
      }
    }
    if (email && map[prefix + 'email']) {
      var a = email.querySelector('a') || email;
      a.textContent = map[prefix + 'email'];
      a.setAttribute('href', 'mailto:' + map[prefix + 'email']);
    }
    if (emailAlt && map[prefix + 'email_alt']) {
      var a2 = emailAlt.querySelector('a') || emailAlt;
      a2.textContent = map[prefix + 'email_alt'];
      a2.setAttribute('href', 'mailto:' + map[prefix + 'email_alt']);
    }
    if (mapFrame && map[prefix + 'map_embed']) {
      mapFrame.setAttribute('src', map[prefix + 'map_embed']);
    }
  }

  async function init() {
    if (!global.SkaApi || !SkaApi.isAvailable()) return;
    try {
      var map = await SkaApi.fetchSettings();
      SkaApi.applyPublicSettings(map);
      applyFooter(map);
      applyLocation(map);
    } catch (e) {
      console.warn('[SKA Site] settings skipped', e);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})(window);
