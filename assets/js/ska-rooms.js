/**
 * SKA Hotels — rooms, rates/details, and property offers on static pages
 */
(function (global) {
  'use strict';

  var SEASON_META = {
    high: { label: 'High Season', color: '#c9a96e' },
    shoulder: { label: 'Shoulder Season', color: '#6a8faf' },
    low: { label: 'Low Season', color: '#7bb87b' }
  };

  var FALLBACK_ROOMS = {
    Naguru: [
      { id: 1, name: 'Standard Room', price: 150, price_low: 130, price_shoulder: 150, price_high: 170,
        description: 'Cosy ensuite room with garden views — ideal for solo travellers and short stays.',
        branch: 'Naguru', images: ['assets/images/standard_naguru.jpeg'], amenities: [] },
      { id: 2, name: 'Deluxe Room', price: 180, price_low: 160, price_shoulder: 180, price_high: 200,
        description: 'Spacious deluxe room with premium linens, smart TV and boutique ensuite.',
        branch: 'Naguru', images: ['assets/images/deluxe_naguru.jpeg'], amenities: [] },
      { id: 3, name: 'Deluxe Twin', price: 190, price_low: 170, price_shoulder: 190, price_high: 210,
        description: 'Twin deluxe configuration — perfect for friends or colleagues travelling together.',
        branch: 'Naguru', images: ['assets/images/deluxe_twin_naguru.jpeg'], amenities: [] },
      { id: 4, name: 'Superior Room', price: 220, price_low: 200, price_shoulder: 220, price_high: 250,
        description: 'Our finest Naguru category with elevated views, extra space and curated amenities.',
        branch: 'Naguru', images: ['assets/images/superior_naguru.jpeg'], amenities: [] }
    ],
    Munyonyo: [
      { id: 5, name: 'Standard Double', price: 180, price_low: 160, price_shoulder: 180, price_high: 200,
        description: 'Comfortable lakeside double room with ensuite and garden access.',
        branch: 'Munyonyo', images: ['assets/images/munyonyo/standard_double_munyonyo.jpg'], amenities: [] },
      { id: 6, name: 'Deluxe Room', price: 210, price_low: 190, price_shoulder: 210, price_high: 230,
        description: 'Deluxe lakeside room with refined finishes and tranquil views.',
        branch: 'Munyonyo', images: ['assets/images/deluxe_munyonyo.jpg'], amenities: [] },
      { id: 7, name: 'Superior Room', price: 240, price_low: 220, price_shoulder: 240, price_high: 270,
        description: 'Superior category with generous space and premium Munyonyo outlook.',
        branch: 'Munyonyo', images: ['assets/images/superior_munyonyo.jpg'], amenities: [] },
      { id: 8, name: 'Dube Suite', price: 280, price_low: 260, price_shoulder: 280, price_high: 320,
        description: 'Signature suite — the ultimate lakeside boutique escape at SKA Munyonyo.',
        branch: 'Munyonyo', images: ['assets/images/dube_munyonyo.jpg'], amenities: [] }
    ]
  };

  function getSeason(month) {
    if ([6, 7, 8, 12, 1].indexOf(month) >= 0) return 'high';
    if ([3, 4, 5, 9, 10, 11].indexOf(month) >= 0) return 'shoulder';
    return 'low';
  }

  function asset(path) {
    if (!path) return '';
    if (/^https?:\/\//i.test(path) || path.indexOf('data:') === 0) return path;
    if (global.SKA_CONFIG && SKA_CONFIG.asset) return SKA_CONFIG.asset(path);
    return path;
  }

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s || '';
    return d.innerHTML;
  }

  function seasonPrice(room) {
    var month = new Date().getMonth() + 1;
    var season = getSeason(month);
    var col = { low: 'price_low', shoulder: 'price_shoulder', high: 'price_high' }[season];
    var val = room[col];
    return val != null ? parseFloat(val) : parseFloat(room.price || 0);
  }

  function roomsList() {
    return global.SKA_ROOMS || global.ROOMS || [];
  }

  function renderRoomCard(room, idx, meta) {
    var img = (room.images && room.images[0]) ? asset(room.images[0]) : '';
    var price = room.price_now != null ? room.price_now : seasonPrice(room);
    return '<div class="rs-card" data-idx="' + idx + '">' +
      '<div class="rs-card-img">' +
      (img ? '<img src="' + esc(img) + '" alt="' + esc(room.name) + '" class="rs-img" loading="lazy">' :
        '<div class="rs-img-ph"><i class="fa-regular fa-image"></i><span>Photo Coming Soon</span></div>') +
      '<button type="button" class="rs-expand" data-idx="' + idx + '" title="View all photos"><i class="fa-solid fa-expand"></i></button>' +
      '</div>' +
      '<div class="rs-card-body">' +
      '<button type="button" class="rs-room-name" data-idx="' + idx + '">' + esc(room.name) + '<span class="rs-chevron">›</span></button>' +
      '<div class="rs-divider"></div>' +
      '<p class="rs-price">USD ' + Number(price).toFixed(0) +
      '<span class="rs-ppn">/ night</span>' +
      '<span class="rs-season-tag" style="background:' + meta.color + '22;color:' + meta.color + '">' + esc(meta.label) + '</span></p>' +
      '<button type="button" class="rs-vr-btn btn-vr" data-idx="' + idx + '">View Rates</button>' +
      '</div></div>';
  }

  function populateRoomSelect(rooms) {
    var sel = document.getElementById('room_type');
    if (!sel) return;
    while (sel.options.length > 1) sel.remove(1);
    rooms.forEach(function (r) {
      var o = document.createElement('option');
      var price = r.price_now != null ? r.price_now : seasonPrice(r);
      o.value = r.name;
      o.textContent = r.name + ' — USD ' + Number(price).toFixed(0) + '/night';
      o.dataset.price = price;
      sel.appendChild(o);
    });
  }

  function refreshSlider(count) {
    var totalEl = document.getElementById('rrTotal');
    if (totalEl) totalEl.textContent = String(count).padStart(2, '0');
    if (typeof global.__skaUpdateSlider === 'function') global.__skaUpdateSlider();
    document.dispatchEvent(new CustomEvent('ska:rooms-ready', { detail: { rooms: roomsList() } }));
  }

  function cardIdx(el) {
    var n = parseInt(el && el.getAttribute('data-idx'), 10);
    return isNaN(n) ? -1 : n;
  }

  function bindRoomClicks() {
    if (document.documentElement.dataset.skaRoomsBound === '1') return;
    document.documentElement.dataset.skaRoomsBound = '1';
    document.addEventListener('click', function (e) {
      var expand = e.target.closest && e.target.closest('.rs-expand');
      var name = e.target.closest && e.target.closest('.rs-room-name');
      var rates = e.target.closest && e.target.closest('.rs-vr-btn');
      if (expand) {
        e.preventDefault();
        if (typeof global.openLbx === 'function') global.openLbx(cardIdx(expand), 0);
      } else if (name) {
        e.preventDefault();
        if (typeof global.openDetail === 'function') global.openDetail(cardIdx(name));
      } else if (rates) {
        e.preventDefault();
        if (typeof global.openRates === 'function') global.openRates(cardIdx(rates));
      }
    });
  }

  function money(amount, currency) {
    return (currency || 'USD') + ' ' + Number(amount || 0).toLocaleString();
  }

  function renderOfferCard(p, branch) {
    var href = p.booking_url || ((branch || 'Naguru').toLowerCase() === 'munyonyo' ? 'munyonyo.html#book' : 'naguru.html#book');
    return '<article class="ska-feature-card">' +
      '<div class="ska-feature-card__img" style="background-image:url(\'' + esc(asset(p.image || 'assets/images/ska_naguru_home.jpeg')) + '\')"></div>' +
      '<div class="ska-feature-card__body">' +
      (p.tag ? '<p class="ska-feature-card__tag">' + esc(p.tag) + '</p>' : '') +
      '<h3 class="ska-feature-card__title">' + esc(p.title || 'Offer') + '</h3>' +
      '<p class="ska-feature-card__text">' + esc(p.description || 'Book direct for exclusive savings.') + '</p>' +
      '<a href="' + esc(href) + '" class="ska-btn-gold">Book now <i class="fa-solid fa-arrow-right"></i></a>' +
      '</div></article>';
  }

  function renderPackageCard(pkg) {
    var slug = (pkg.branch || 'Naguru').toLowerCase() === 'munyonyo' ? 'munyonyo.html' : 'naguru.html';
    var href = pkg.booking_url || (slug + '?package=' + pkg.id + '#book');
    if (href.indexOf('.php') >= 0) href = href.replace(/\.php/g, '.html');
    return '<article class="ska-feature-card">' +
      '<div class="ska-feature-card__img" style="background-image:url(\'' + esc(asset(pkg.image || 'assets/images/ska_naguru_home.jpeg')) + '\')"></div>' +
      '<div class="ska-feature-card__body">' +
      (pkg.tag ? '<p class="ska-feature-card__tag">' + esc(pkg.tag) + '</p>' : '') +
      '<h3 class="ska-feature-card__title">' + esc(pkg.title || 'Package') + '</h3>' +
      '<p class="ska-feature-card__text">From ' + money(pkg.price, pkg.currency || 'UGX') + '</p>' +
      '<a href="' + esc(href) + '" class="ska-btn-gold">View package <i class="fa-solid fa-arrow-right"></i></a>' +
      '</div></article>';
  }

  async function loadPropertyExtras(branch) {
    var offers = document.getElementById('propertyOffers');
    var packagesEl = document.getElementById('propertyPackages');
    if (!offers && !packagesEl) return;
    var promos = [];
    var pkgs = [];
    try {
      if (global.SkaApi && SkaApi.isAvailable()) {
        promos = await SkaApi.fetchPromotions();
        pkgs = await SkaApi.fetchPackages(branch);
      }
    } catch (e) {
      console.warn('[SKA Rooms] extras fetch failed', e);
    }
    promos = (promos || []).filter(function (p) {
      return !p.branch || p.branch === 'Both' || p.branch === 'All' || p.branch === branch;
    });
    if (offers) {
      offers.innerHTML = promos.length
        ? promos.map(function (p) { return renderOfferCard(p, branch); }).join('')
        : '<p class="ska-muted">No current offers for this property.</p>';
    }
    if (packagesEl) {
      packagesEl.innerHTML = pkgs.length
        ? pkgs.map(renderPackageCard).join('')
        : '<p class="ska-muted">No packages listed for this property yet.</p>';
    }
  }

  async function loadPropertyRooms() {
    bindRoomClicks();
    var track = document.getElementById('roomsTrack');
    var branch = (track && track.dataset.skaBranch) ||
      (location.pathname.indexOf('munyonyo') >= 0 ? 'Munyonyo' : 'Naguru');

    var rooms = [];
    var promos = [];
    if (global.SkaApi && SkaApi.isAvailable()) {
      try {
        rooms = await SkaApi.fetchRooms(branch);
        promos = await SkaApi.fetchPromotions();
      } catch (e) {
        console.warn('[SKA Rooms] Supabase fetch failed, using fallback data.', e);
      }
    }

    if (!rooms.length && FALLBACK_ROOMS[branch]) {
      rooms = FALLBACK_ROOMS[branch].map(function (r) {
        return Object.assign({}, r, { price_now: seasonPrice(r) });
      });
    } else {
      rooms.forEach(function (r) {
        r.price_now = global.SkaApi ? SkaApi.seasonPrice(r) : seasonPrice(r);
        r.images = (r.images || []).map(asset);
      });
    }

    global.SKA_ROOMS = rooms;
    global.ROOMS = rooms;
    global.SKA_PROMOTIONS = promos;
    global.PROMOTIONS = promos;

    var month = new Date().getMonth() + 1;
    var meta = SEASON_META[getSeason(month)];
    var pill = document.querySelector('.rs-season-pill');
    if (pill) {
      pill.style.setProperty('--sp-color', meta.color);
      var dot = pill.querySelector('.rs-season-dot');
      var lbl = pill.querySelector('.rs-season-label');
      if (dot) dot.style.background = meta.color;
      if (lbl) lbl.textContent = meta.label;
    }

    if (track) {
      if (!rooms.length) {
        track.innerHTML = '<p style="padding:20px;color:#999;">No rooms available at ' + esc(branch) + ' right now.</p>';
      } else {
        track.innerHTML = rooms.map(function (r, i) { return renderRoomCard(r, i, meta); }).join('');
        populateRoomSelect(rooms);
        refreshSlider(rooms.length);
      }
    }

    bindBookingCalc();
    calculateBooking();

    await loadPropertyExtras(branch);
  }

  function nightsBetween(ci, co) {
    if (!ci || !co) return 0;
    var a = new Date(String(ci) + 'T12:00:00');
    var b = new Date(String(co) + 'T12:00:00');
    if (isNaN(a.getTime()) || isNaN(b.getTime())) return 0;
    return Math.max(0, Math.round((b.getTime() - a.getTime()) / 86400000));
  }

  function nightlyFromSelect(sel) {
    if (!sel) return 0;
    var opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.value) return 0;
    var p = parseFloat(opt.getAttribute('data-price') || 0);
    if (p > 0) return p;
    var m = String(opt.textContent || '').match(/USD\s*([\d,.]+)/i);
    if (m) return parseFloat(m[1].replace(/,/g, '')) || 0;
    var rooms = global.SKA_ROOMS || global.ROOMS || [];
    var room = rooms.find(function (r) { return r.name === opt.value; });
    if (!room) return 0;
    return parseFloat(room.price_now != null ? room.price_now : (seasonPrice(room) || room.price || 0)) || 0;
  }

  function calculateBooking() {
    var ciEl = document.getElementById('checkin');
    var coEl = document.getElementById('checkout');
    var sel = document.getElementById('room_type');
    var disp = document.getElementById('totalPrice');
    if (!sel) return;
    var nights = nightsBetween(ciEl && ciEl.value, coEl && coEl.value);
    var nightly = nightlyFromSelect(sel);
    var total = nights > 0 && nightly > 0 ? nightly * nights : 0;
    if (disp) disp.innerHTML = 'Total: <strong>USD ' + Number(total).toLocaleString() + '</strong>';
    var fp = document.getElementById('formPrice');
    if (fp) fp.value = nightly || '';
    var form = document.getElementById('bookingForm');
    var totalField = document.getElementById('formTotal');
    if (!totalField && form) {
      totalField = document.createElement('input');
      totalField.type = 'hidden';
      totalField.name = 'total';
      totalField.id = 'formTotal';
      form.appendChild(totalField);
    }
    if (totalField) totalField.value = total;
    var fs = document.getElementById('formSeason');
    if (fs && ciEl && ciEl.value) {
      var month = new Date(String(ciEl.value) + 'T12:00:00').getMonth() + 1;
      fs.value = getSeason(month);
    }
  }

  function bindBookingCalc() {
    if (document.documentElement.dataset.skaCalcBound === '1') return;
    document.documentElement.dataset.skaCalcBound = '1';
    ['checkin', 'checkout', 'room_type'].forEach(function (id) {
      var el = document.getElementById(id);
      if (!el) return;
      el.addEventListener('change', calculateBooking);
      el.addEventListener('input', calculateBooking);
    });
    global.calculateBooking = calculateBooking;
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadPropertyRooms);
  } else {
    loadPropertyRooms();
  }

  global.SkaRooms = { load: loadPropertyRooms, fallback: FALLBACK_ROOMS, calculateBooking: calculateBooking };
})(window);
