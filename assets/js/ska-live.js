/**
 * SKA Hotels — hydrate dynamic sections on static/GitHub Pages hosts
 */
(function (global) {
  'use strict';

  var cfg = global.SKA_CONFIG;

  var DEFAULT_PROMOS = [
    {
      title: 'Book Direct & Save',
      image: 'assets/images/ska_naguru_home.jpeg',
      booking_url: 'offers.html'
    },
    {
      title: 'Book 7 Days Early',
      image: 'assets/images/ska_art_home.jpg',
      booking_url: 'offers.html'
    },
    {
      title: 'Stay 3 Nights, Pay for 2',
      image: 'assets/images/ska_furniture_home.jpg',
      booking_url: 'offers.html'
    },
    {
      title: 'Direct Booking Bonus',
      image: 'assets/images/ska_munyonyo_home2.jpg',
      booking_url: 'offers.html'
    },
    {
      title: 'Munyonyo Lakeside Weekend',
      image: 'assets/images/ska_munyonyo_home2.jpg',
      booking_url: 'munyonyo.html#book'
    }
  ];

  function asset(path) {
    return cfg && cfg.asset ? cfg.asset(path) : path;
  }

  function renderPromoCards(track, promos) {
    track.innerHTML = '';
    promos.forEach(function (p) {
      var a = document.createElement('a');
      a.className = 'lp-promo-card';
      a.href = p.booking_url || (cfg ? cfg.page('offers') : 'offers.html');
      a.innerHTML =
        '<img src="' + asset(p.image || 'assets/images/ska_naguru_home.jpeg') + '" alt="' + (p.title || '') + '" class="lp-promo-img" loading="lazy">' +
        '<div class="lp-promo-card-label"><span>' + (p.title || '') + '</span><i class="fa-solid fa-chevron-right"></i></div>';
      track.appendChild(a);
    });

    var dots = document.getElementById('promoDots');
    if (dots) {
      dots.innerHTML = '';
      promos.forEach(function (_, i) {
        var s = document.createElement('span');
        s.className = 'lp-promo-dot' + (i === 0 ? ' active' : '');
        dots.appendChild(s);
      });
    }

    document.dispatchEvent(new CustomEvent('ska:promos-ready'));
  }

  async function hydratePromotions() {
    var track = document.getElementById('promoTrack');
    if (!track || track.dataset.skaHydrated === '1') return;

    var existing = track.querySelectorAll('.lp-promo-card').length;
    var promos = [];

    try {
      if (global.SkaApi && SkaApi.isAvailable()) {
        promos = await SkaApi.fetchPromotions();
      }
    } catch (e) {
      console.warn('[SKA] Promotions fetch failed, using defaults.', e);
    }

    // Never replace a full static carousel with a thin Supabase list
    if (promos.length >= 3) {
      renderPromoCards(track, promos);
      track.dataset.skaHydrated = '1';
      return;
    }

    if (existing >= 3) {
      track.dataset.skaHydrated = '1';
      return;
    }

    renderPromoCards(track, DEFAULT_PROMOS);
    track.dataset.skaHydrated = '1';
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
      images.forEach(function (img) {
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

  async function hydrateOffersPage() {
    var grid = document.getElementById('offersGrid');
    if (!grid || grid.dataset.skaHydrated === '1') return;

    var promos = [];
    try {
      if (global.SkaApi && SkaApi.isAvailable()) {
        promos = await SkaApi.fetchPromotions();
      }
    } catch (e) { /* use defaults */ }

    if (promos.length < 3) promos = DEFAULT_PROMOS;

    grid.innerHTML = promos.map(function (p) {
      return '<article class="ska-feature-card">' +
        '<div class="ska-feature-card__img" style="background-image:url(\'' + asset(p.image || 'assets/images/ska_naguru_home.jpeg') + '\')"></div>' +
        '<div class="ska-feature-card__body">' +
        (p.tag ? '<p class="ska-feature-card__tag">' + p.tag + '</p>' : '') +
        '<h2 class="ska-feature-card__title">' + (p.title || '') + '</h2>' +
        '<p class="ska-feature-card__text">' + (p.description || 'Book direct for exclusive savings at SKA The Boutique.') + '</p>' +
        '<a href="' + (p.booking_url || 'index.html#book-search') + '" class="ska-btn-gold">Book Now <i class="fa-solid fa-arrow-right"></i></a>' +
        '</div></article>';
    }).join('');
    grid.dataset.skaHydrated = '1';
  }

  async function init() {
    if (!cfg || !cfg.isStaticHost()) return;
    await Promise.all([
      hydrateHeroSlides(),
      hydratePromotions(),
      hydrateGallery(),
      hydrateOffersPage()
    ]);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  global.SkaLive = { init: init, defaultPromos: DEFAULT_PROMOS };
})(window);
