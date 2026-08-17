/**
 * SKA Hotels — hydrate dynamic sections on static/GitHub Pages hosts
 */
(function (global) {
  'use strict';

  var cfg = global.SKA_CONFIG;

  function asset(path) {
    return cfg && cfg.asset ? cfg.asset(path) : path;
  }

  async function hydratePromotions() {
    var track = document.getElementById('promoTrack');
    if (!track || track.dataset.skaHydrated === '1') return;

    try {
      var promos = await SkaApi.fetchPromotions();
      if (!promos.length) return;
      track.innerHTML = '';
      promos.forEach(function (p) {
        var a = document.createElement('a');
        a.className = 'lp-promo-card';
        a.href = p.booking_url || cfg.page('offers');
        a.innerHTML = '<img src="' + asset(p.image || 'assets/images/ska_naguru_home.jpeg') + '" alt="' + (p.title || '') + '" class="lp-promo-img" loading="lazy">' +
          '<div class="lp-promo-card-label"><span>' + (p.title || '') + '</span></div>';
        track.appendChild(a);
      });
      track.dataset.skaHydrated = '1';
      var dots = document.getElementById('promoDots');
      if (dots) {
        dots.innerHTML = '';
        promos.forEach(function (_, i) {
          var s = document.createElement('span');
          s.className = 'lp-promo-dot' + (i === 0 ? ' active' : '');
          dots.appendChild(s);
        });
      }
    } catch (e) {
      console.warn('[SKA] Promotions hydrate skipped', e);
    }
  }

  async function hydrateHeroSlides() {
    var inner = document.querySelector('#heroCarousel .carousel-inner');
    if (!inner || inner.dataset.skaHydrated === '1') return;
    var imgs = inner.querySelectorAll('img');
    var needsHydrate = imgs.length === 0 || Array.prototype.some.call(imgs, function (i) {
      return !i.getAttribute('src') || i.getAttribute('src').length < 5;
    });

    var slides = [];
    try {
      for (var i = 1; i <= 6; i++) {
        var img = await SkaApi.fetchSetting('hero_slide_' + i + '_image');
        if (!img) break;
        var alt = await SkaApi.fetchSetting('hero_slide_' + i + '_alt', 'SKA The Boutique');
        slides.push({ image: img, alt: alt });
      }
    } catch (e) { /* fall through to defaults */ }

    if (!slides.length) {
      slides = [
        { image: 'assets/images/ska_naguru_home.jpeg', alt: 'SKA Naguru boutique hotel in Kampala' },
        { image: 'assets/images/ska_munyonyo_home2.jpg', alt: 'SKA Munyonyo lakeside boutique retreat' }
      ];
    }

    if (!needsHydrate && slides.length < 2) return;

    inner.innerHTML = '';
    slides.forEach(function (s, idx) {
      var div = document.createElement('div');
      div.className = 'carousel-item' + (idx === 0 ? ' active' : '');
      div.innerHTML = '<img src="' + asset(s.image) + '" alt="' + s.alt + '" class="lp-hero-img" width="1920" height="1080"' +
        (idx === 0 ? ' fetchpriority="high"' : ' loading="lazy"') + '>';
      inner.appendChild(div);
    });
    inner.dataset.skaHydrated = '1';
  }

  async function hydrateGallery() {
    var section = document.getElementById('gallery');
    if (!section || section.dataset.skaHydrated === '1') return;
    var branch = section.dataset.branch || document.body.dataset.skaBranch;
    if (!branch) return;
    try {
      var images = await SkaApi.fetchGallery(branch);
      if (!images.length) return;
      var grid = section.querySelector('.ska-gallery-grid');
      if (!grid) return;
      grid.innerHTML = '';
      images.forEach(function (img, i) {
        var a = document.createElement('a');
        a.href = asset(img.path);
        a.className = 'ska-gallery-item';
        a.innerHTML = '<img src="' + asset(img.path) + '" alt="' + (img.caption || 'SKA') + '" loading="lazy">' +
          (img.caption ? '<span class="ska-gallery-caption">' + img.caption + '</span>' : '');
        grid.appendChild(a);
      });
      section.dataset.skaHydrated = '1';
      section.style.display = '';
    } catch (e) {
      console.warn('[SKA] Gallery hydrate skipped', e);
    }
  }

  async function init() {
    if (!cfg || !cfg.isStaticHost()) return;
    if (!global.SkaApi || !SkaApi.isAvailable()) return;
    await Promise.all([
      hydrateHeroSlides(),
      hydratePromotions(),
      hydrateGallery()
    ]);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  global.SkaLive = { init: init };
})(window);
