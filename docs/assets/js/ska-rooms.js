/**

 * SKA Hotels — load & render rooms on static property pages (GitHub Pages)

 */

(function (global) {

  'use strict';



  var SEASON_META = {

    high:     { label: 'High Season',     color: '#c9a96e' },

    shoulder: { label: 'Shoulder Season', color: '#6a8faf' },

    low:      { label: 'Low Season',      color: '#7bb87b' }

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



  function renderRoomCard(room, idx, meta) {

    var img = (room.images && room.images[0]) ? asset(room.images[0]) : '';

    var price = room.price_now != null ? room.price_now : seasonPrice(room);

    return '<div class="rs-card" data-idx="' + idx + '">' +

      '<div class="rs-card-img">' +

      (img ? '<img src="' + esc(img) + '" alt="' + esc(room.name) + '" class="rs-img" loading="lazy">' :

        '<div class="rs-img-ph"><i class="fa-regular fa-image"></i><span>Photo Coming Soon</span></div>') +

      '<button class="rs-expand" data-idx="' + idx + '" title="View all photos"><i class="fa-solid fa-expand"></i></button>' +

      '</div>' +

      '<div class="rs-card-body">' +

      '<button class="rs-room-name" data-idx="' + idx + '">' + esc(room.name) + '<span class="rs-chevron">›</span></button>' +

      '<div class="rs-divider"></div>' +

      '<p class="rs-price">USD ' + Number(price).toFixed(0) +

      '<span class="rs-ppn">/ night</span>' +

      '<span class="rs-season-tag" style="background:' + meta.color + '22;color:' + meta.color + '">' + esc(meta.label) + '</span></p>' +

      '<button class="rs-vr-btn btn-vr" data-idx="' + idx + '">View Rates</button>' +

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
    if (typeof global.__skaUpdateSlider === 'function') {
      global.__skaUpdateSlider();
    }
    document.dispatchEvent(new CustomEvent('ska:rooms-ready', { detail: { rooms: global.SKA_ROOMS || [] } }));

  }



  async function loadPropertyRooms() {

    var track = document.getElementById('roomsTrack');

    if (!track) return;



    var branch = track.dataset.skaBranch ||

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



    if (!rooms.length) {

      track.innerHTML = '<p style="padding:20px;color:#999;">No rooms available at ' + esc(branch) + ' right now.</p>';

      return;

    }



    track.innerHTML = rooms.map(function (r, i) {

      return renderRoomCard(r, i, meta);

    }).join('');



    populateRoomSelect(rooms);

    refreshSlider(rooms.length);

  }



  if (document.readyState === 'loading') {

    document.addEventListener('DOMContentLoaded', loadPropertyRooms);

  } else {

    loadPropertyRooms();

  }



  global.SkaRooms = { load: loadPropertyRooms, fallback: FALLBACK_ROOMS };

})(window);


