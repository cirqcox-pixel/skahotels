<?php
/* ══════════════════════════════════════════════════════
   MODALS INCLUDE  —  modals.php
   Include this file ONCE inside naguru.php, AFTER the
   $allRooms / $roomsJson block is already set.
══════════════════════════════════════════════════════ */

/* Season helpers (safe to call even if already defined) */
if (!function_exists('getSeason')) {
    function getSeason(int $month): string {
        return match(true) {
            in_array($month, [6,7,8,12,1])    => 'high',
            in_array($month, [3,4,5,9,10,11]) => 'shoulder',
            default                           => 'low',
        };
    }
}
if (!defined('SEASON_META')) {
    define('SEASON_META_DEFINED', true);
    $SEASON_META_LOCAL = [
        'high'     => ['label' => 'High Season',     'color' => '#c9a96e'],
        'shoulder' => ['label' => 'Shoulder Season', 'color' => '#6a8faf'],
        'low'      => ['label' => 'Low Season',      'color' => '#7bb87b'],
    ];
} else {
    $SEASON_META_LOCAL = SEASON_META;
}
?>

<!-- ══════════════════════════════════════
     MODAL 1 — LIGHTBOX
══════════════════════════════════════ -->
<div class="ska-backdrop" id="lbxBackdrop" aria-hidden="true" role="dialog">
  <div class="lbx-shell">

    <div class="lbx-topbar">
      <button class="lbx-icon-btn" id="lbxClose" aria-label="Close">
        <i class="fa-solid fa-xmark"></i>
      </button>
      <span class="lbx-topbar-name" id="lbxTopName"></span>
      <button class="lbx-vr-btn" id="lbxVR">View Rates</button>
    </div>

    <div class="lbx-stage">
      <img id="lbxImg" src="" alt="" loading="lazy">
      <button class="lbx-arr lbx-arr-prev" id="lbxPrev" aria-label="Previous image">
        <i class="fa-solid fa-chevron-left"></i>
      </button>
      <button class="lbx-arr lbx-arr-next" id="lbxNext" aria-label="Next image">
        <i class="fa-solid fa-chevron-right"></i>
      </button>
    </div>

    <div class="lbx-footer">
      <span id="lbxCount" class="lbx-count">1 of 1</span>
      <span class="lbx-pipe">|</span>
      <span id="lbxName" class="lbx-room-name"></span>
      <button class="lbx-det-btn" id="lbxDet">View Room Details</button>
    </div>

  </div>
</div>

<!-- ══════════════════════════════════════
     MODAL 2 — ROOM DETAILS
══════════════════════════════════════ -->
<div class="ska-backdrop" id="dmBackdrop" aria-hidden="true" role="dialog">
  <div class="dm-shell">

    <div class="dm-head">
      <h3 class="dm-title" id="dmTitle"></h3>
      <button class="dm-close-btn" id="dmClose" aria-label="Close">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <div class="dm-stage">
      <div class="dm-carousel" id="dmCarousel"></div>
      <button class="dm-arr dm-arr-prev" id="dmPrev"><i class="fa-solid fa-chevron-left"></i></button>
      <button class="dm-arr dm-arr-next" id="dmNext"><i class="fa-solid fa-chevron-right"></i></button>
      <div class="dm-dots" id="dmDots"></div>
    </div>

    <div class="dm-body">
      <div class="dm-body-left">
        <p class="dm-desc" id="dmDesc"></p>
        <div class="dm-amenities" id="dmAmen"></div>
      </div>
      <div class="dm-body-right">
        <p class="dm-price-line" id="dmPriceLine"></p>
        <button class="dm-vr-btn" id="dmVR">View Rates</button>
      </div>
    </div>

  </div>
</div>

<!-- ══════════════════════════════════════
     MODAL 3 — RATES / DATE PICKER
══════════════════════════════════════ -->
<div class="ska-backdrop" id="ratesBackdrop" aria-hidden="true" role="dialog">
  <div class="rates-shell">

    <button class="rates-close-btn" id="ratesClose" aria-label="Close">
      <i class="fa-solid fa-xmark"></i>
    </button>

    <h3 class="rates-room-title" id="ratesTitle"></h3>

    <!-- Season legend -->
    <div class="rates-legend">
      <?php foreach ($SEASON_META_LOCAL as $sk => $sm): ?>
        <div class="rl-item">
          <span class="rl-dot" style="background:<?= $sm['color'] ?>"></span>
          <span class="rl-label"><?= $sm['label'] ?></span>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Specific / Flexible toggle -->
    <div class="rates-toggle">
      <button class="rt-btn active" id="rtSpec">Specific Dates</button>
      <button class="rt-btn"        id="rtFlex">Flexible Dates</button>
    </div>

    <!-- ── Specific Dates: dual calendar ── -->
    <div id="specPanel">
      <div class="rates-cal-nav">
        <button class="cal-nav-arr" id="calPrev"><i class="fa-solid fa-chevron-left"></i></button>
        <div class="dual-cal">
          <div class="rcal-block">
            <div class="rcal-month-name" id="calLbl1"></div>
            <div class="rcal-grid" id="calGrid1"></div>
          </div>
          <div class="rcal-block">
            <div class="rcal-month-name" id="calLbl2"></div>
            <div class="rcal-grid" id="calGrid2"></div>
          </div>
        </div>
        <button class="cal-nav-arr" id="calNext"><i class="fa-solid fa-chevron-right"></i></button>
      </div>
    </div>

    <!-- ── Flexible Dates ── -->
    <div id="flexPanel" style="display:none">
      <div class="flex-nights-row">
        <span class="fn-label">How many nights?</span>
        <div class="fn-btns-wrap">
          <?php foreach ([1,2,3,4,5,6,7,14,21,30] as $n): ?>
            <button class="fn-btn" data-n="<?= $n ?>"><?= $n ?></button>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="flex-months-grid">
        <?php for ($m = 1; $m <= 12; $m++):
          $sk  = getSeason($m);
          $col = $SEASON_META_LOCAL[$sk]['color'];
          $lbl = $SEASON_META_LOCAL[$sk]['label'];
        ?>
          <button class="fm-btn" data-m="<?= $m ?>" style="--fm-col:<?= $col ?>"
                  title="<?= $lbl ?>">
            <?= date('M', mktime(0,0,0,$m,1)) ?>
            <span class="fm-sea-tag"><?= explode(' ', $lbl)[0] ?></span>
          </button>
        <?php endfor; ?>
      </div>
    </div>

    <!-- Price summary (appears after selection) -->
    <div class="rates-summary" id="ratesSummary" style="display:none">
      <div class="rs-dates"  id="rsDates"></div>
      <div class="rs-price"  id="rsPrice"></div>
      <div class="rs-season" id="rsSeason"></div>
    </div>

    <button class="rates-confirm-btn" id="ratesConfirm">Confirm &amp; Book</button>

  </div>
</div>


<!-- ══════════════════════════════════════
     JAVASCRIPT
══════════════════════════════════════ -->
<script>
/* ── ROOMS data already injected by PHP as `ROOMS` ── */

/* ── Season map (mirrors PHP) ── */
const SM = {
  high:     { months:[6,7,8,12,1],    label:'High Season',     color:'#c9a96e' },
  shoulder: { months:[3,4,5,9,10,11], label:'Shoulder Season', color:'#6a8faf' },
  low:      { months:[2],             label:'Low Season',       color:'#7bb87b' },
};
function seasonKey(m){
  for(const[k,s] of Object.entries(SM)) if(s.months.includes(m)) return k;
  return 'low';
}
function priceFor(room, month){
  const k = seasonKey(month);
  const col = k==='high'?'price_high': k==='shoulder'?'price_shoulder':'price_low';
  const v = parseFloat(room[col]);
  return (v && v>0) ? v : parseFloat(room.price||0);
}
const MN = ['January','February','March','April','May','June',
            'July','August','September','October','November','December'];

/* ══════════════════════════════════════
   MODAL OPEN / CLOSE
══════════════════════════════════════ */
function openM(id){
  const el = document.getElementById(id);
  el.classList.add('visible');
  el.setAttribute('aria-hidden','false');
  document.body.style.overflow = 'hidden';
}
function closeM(id){
  const el = document.getElementById(id);
  el.classList.remove('visible');
  el.setAttribute('aria-hidden','true');
  /* Only restore scroll when ALL modals are closed */
  if(!document.querySelector('.ska-backdrop.visible'))
    document.body.style.overflow = '';
}

/* Click backdrop to close */
document.querySelectorAll('.ska-backdrop').forEach(b =>
  b.addEventListener('click', e => { if(e.target===b) closeM(b.id); })
);
document.addEventListener('keydown', e => {
  if(e.key==='Escape')
    document.querySelectorAll('.ska-backdrop.visible').forEach(m => closeM(m.id));
});

/* ══════════════════════════════════════
   ACTIVE ROOM (set when a card is opened)
══════════════════════════════════════ */
let activeRoomIdx = null;   /* index into ROOMS[] */

/* Called by the rooms section: expand button or name click */
window.openLbx = function(roomIdx, imgIdx){
  activeRoomIdx = roomIdx;
  lbxI = imgIdx || 0;
  renderLbx();
  openM('lbxBackdrop');
};
window.openDetail = function(roomIdx){
  activeRoomIdx = roomIdx;
  dmI = 0;
  renderDm();
  openM('dmBackdrop');
};
window.openRates = function(roomIdx){
  activeRoomIdx = roomIdx;
  /* reset state */
  cIn = cOut = null; calStep = 0;
  calBase = { y: new Date().getFullYear(), m: new Date().getMonth()+1 };
  document.getElementById('ratesTitle').textContent = ROOMS[roomIdx].name;
  document.getElementById('ratesSummary').style.display = 'none';
  document.getElementById('rtSpec').classList.add('active');
  document.getElementById('rtFlex').classList.remove('active');
  document.getElementById('specPanel').style.display = 'block';
  document.getElementById('flexPanel').style.display  = 'none';
  renderCals();
  openM('ratesBackdrop');
};

/* ══════════════════════════════════════
   LIGHTBOX
══════════════════════════════════════ */
let lbxI = 0;

function renderLbx(){
  if(activeRoomIdx === null) return;
  const room = ROOMS[activeRoomIdx];
  const imgs = room.images || [];
  const src  = imgs[lbxI] || '';

  document.getElementById('lbxImg').src          = src;
  document.getElementById('lbxImg').alt          = room.name;
  document.getElementById('lbxTopName').textContent = room.name;
  document.getElementById('lbxName').textContent    = room.name;
  document.getElementById('lbxCount').textContent   =
    `${lbxI+1} of ${imgs.length||1}`;

  /* show/hide arrows */
  document.getElementById('lbxPrev').style.display = imgs.length > 1 ? '' : 'none';
  document.getElementById('lbxNext').style.display = imgs.length > 1 ? '' : 'none';
}

document.getElementById('lbxPrev').addEventListener('click', () => {
  const len = (ROOMS[activeRoomIdx]?.images||[]).length;
  if(len){ lbxI=(lbxI-1+len)%len; renderLbx(); }
});
document.getElementById('lbxNext').addEventListener('click', () => {
  const len = (ROOMS[activeRoomIdx]?.images||[]).length;
  if(len){ lbxI=(lbxI+1)%len; renderLbx(); }
});
document.getElementById('lbxClose').addEventListener('click', () => closeM('lbxBackdrop'));
document.getElementById('lbxVR').addEventListener('click', () => {
  closeM('lbxBackdrop');
  if(activeRoomIdx !== null) openRates(activeRoomIdx);
});
document.getElementById('lbxDet').addEventListener('click', () => {
  closeM('lbxBackdrop');
  if(activeRoomIdx !== null) openDetail(activeRoomIdx);
});

/* ══════════════════════════════════════
   DETAIL MODAL
══════════════════════════════════════ */
let dmI = 0;

function renderDm(){
  if(activeRoomIdx === null) return;
  const room = ROOMS[activeRoomIdx];
  const imgs = room.images || [];

  document.getElementById('dmTitle').textContent = room.name;
  document.getElementById('dmDesc').textContent  = room.description || '';

  /* current season price */
  const nowM   = new Date().getMonth()+1;
  const np     = priceFor(room, nowM);
  const sk     = seasonKey(nowM);
  document.getElementById('dmPriceLine').innerHTML =
    `USD ${np.toLocaleString()} <span style="font-weight:400;font-size:12px">/ night</span>
     <span style="color:${SM[sk].color};font-size:11px;font-weight:600;margin-left:4px">
       ${SM[sk].label}
     </span>`;

  /* carousel images */
  const car = document.getElementById('dmCarousel');
  if(imgs.length){
    car.innerHTML = imgs.map((src,i) =>
      `<img src="${src}" alt="${room.name}" class="dm-img${i===0?' active':''}">`
    ).join('');
  } else {
    car.innerHTML =
      `<div class="dm-img-ph"><i class="fa-regular fa-image"></i><span>Photo Coming Soon</span></div>`;
  }

  /* dots */
  const dots = document.getElementById('dmDots');
  dots.innerHTML = imgs.map((_,i) =>
    `<button class="dm-dot${i===0?' active':''}" data-i="${i}"></button>`
  ).join('');
  dots.querySelectorAll('.dm-dot').forEach(d =>
    d.addEventListener('click', () => { dmI=+d.dataset.i; syncDm(); })
  );

  /* amenities */
  const am = document.getElementById('dmAmen');
  am.innerHTML = (room.amenities||[]).map(a =>
    `<span class="dm-amen-item"><i class="${a.icon_class||a.icon||''}"></i>${a.name||''}</span>`
  ).join('');
}

function syncDm(){
  document.querySelectorAll('#dmCarousel .dm-img').forEach((el,i) =>
    el.classList.toggle('active', i===dmI));
  document.querySelectorAll('#dmDots .dm-dot').forEach((el,i) =>
    el.classList.toggle('active', i===dmI));
}

document.getElementById('dmPrev').addEventListener('click', () => {
  const len = (ROOMS[activeRoomIdx]?.images||[]).length;
  if(len){ dmI=(dmI-1+len)%len; syncDm(); }
});
document.getElementById('dmNext').addEventListener('click', () => {
  const len = (ROOMS[activeRoomIdx]?.images||[]).length;
  if(len){ dmI=(dmI+1)%len; syncDm(); }
});
document.getElementById('dmClose').addEventListener('click', () => closeM('dmBackdrop'));
document.getElementById('dmVR').addEventListener('click', () => {
  closeM('dmBackdrop');
  if(activeRoomIdx !== null) openRates(activeRoomIdx);
});

/* ══════════════════════════════════════
   RATES MODAL — CALENDAR
══════════════════════════════════════ */
let cIn = null, cOut = null;
let calBase = { y: new Date().getFullYear(), m: new Date().getMonth()+1 };
let calStep = 0;   /* 0=picking check-in, 1=picking check-out */
let flexN = 1, flexM = new Date().getMonth()+1;

function renderCals(){
  const { y, m } = calBase;
  const m2 = m===12 ? 1  : m+1;
  const y2 = m===12 ? y+1 : y;
  document.getElementById('calLbl1').textContent = `${MN[m-1]} ${y}`;
  document.getElementById('calLbl2').textContent = `${MN[m2-1]} ${y2}`;
  buildMonth('calGrid1', y,  m);
  buildMonth('calGrid2', y2, m2);
}

function pad(n){ return String(n).padStart(2,'0'); }
function fmtDate(d){ return d ? `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}` : ''; }
function fmtHuman(d){ return d.toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'}); }

function buildMonth(gridId, year, month){
  const grid    = document.getElementById(gridId);
  const today   = new Date(); today.setHours(0,0,0,0);
  const firstDay= new Date(year, month-1, 1).getDay();
  const dim     = new Date(year, month, 0).getDate();
  const prevDim = new Date(year, month-1, 0).getDate();
  const sk      = seasonKey(month);
  const sColor  = SM[sk].color;

  let html = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat']
    .map(d => `<div class="rch">${d}</div>`).join('');

  for(let i=firstDay-1; i>=0; i--)
    html += `<div class="rcc oth">${prevDim-i}</div>`;

  for(let d=1; d<=dim; d++){
    const dt   = new Date(year, month-1, d);
    const past = dt < today;
    const ds   = `${year}-${pad(month)}-${pad(d)}`;
    const iS   = cIn  && ds===fmtDate(cIn);
    const iE   = cOut && ds===fmtDate(cOut);
    const inR  = cIn && cOut && dt>cIn && dt<cOut;

    let cls = 'rcc';
    if(past)  cls += ' past';
    if(iS)    cls += ' sel start';
    if(iE)    cls += ' sel end';
    if(inR)   cls += ' in-range';

    /* tint today's season colour subtly on hover via CSS var */
    const style = `style="--s-col:${sColor}"`;
    const click = past ? '' : `onclick="onDay('${ds}',${month})"`;
    html += `<div class="${cls}" ${style} ${click}>${d}</div>`;
  }

  const rem = (firstDay+dim)%7===0 ? 0 : 7-((firstDay+dim)%7);
  for(let i=1; i<=rem; i++)
    html += `<div class="rcc oth">${i}</div>`;

  grid.innerHTML = html;
}

/* Global so inline onclick works */
window.onDay = function(ds, month){
  const clicked = new Date(ds+'T00:00:00');
  if(calStep===0 || (cIn && clicked<=cIn)){
    cIn=clicked; cOut=null; calStep=1;
  } else {
    cOut=clicked; calStep=0;
    showSpecSummary(month);
  }
  renderCals();
};

function showSpecSummary(month){
  if(!cIn || !cOut) return;
  const nights = Math.round((cOut-cIn)/86400000);
  if(nights<1) return;
  if(activeRoomIdx===null) return;

  const room    = ROOMS[activeRoomIdx];
  const sk      = seasonKey(month);
  const nightly = priceFor(room, month);
  const total   = nightly * nights;

  document.getElementById('rsDates').textContent  =
    `${fmtHuman(cIn)} → ${fmtHuman(cOut)}  (${nights} night${nights>1?'s':''})`;
  document.getElementById('rsPrice').innerHTML    =
    `<strong>USD ${total.toLocaleString()}</strong>  |  USD ${nightly.toLocaleString()} / night`;
  document.getElementById('rsSeason').innerHTML   =
    `<span style="color:${SM[sk].color}">● ${SM[sk].label}</span>`;
  document.getElementById('ratesSummary').style.display = 'flex';
}

/* Calendar prev / next */
document.getElementById('calPrev').addEventListener('click', () => {
  calBase.m--; if(calBase.m<1){calBase.m=12;calBase.y--;} renderCals();
});
document.getElementById('calNext').addEventListener('click', () => {
  calBase.m++; if(calBase.m>12){calBase.m=1;calBase.y++;} renderCals();
});

/* Specific / Flexible toggle */
document.getElementById('rtSpec').addEventListener('click', function(){
  this.classList.add('active'); document.getElementById('rtFlex').classList.remove('active');
  document.getElementById('specPanel').style.display = 'block';
  document.getElementById('flexPanel').style.display = 'none';
});
document.getElementById('rtFlex').addEventListener('click', function(){
  this.classList.add('active'); document.getElementById('rtSpec').classList.remove('active');
  document.getElementById('specPanel').style.display = 'none';
  document.getElementById('flexPanel').style.display = 'block';
  initFlex();
});

/* ── Flexible panel ── */
function initFlex(){
  document.querySelectorAll('.fn-btn').forEach(b => {
    b.classList.toggle('active', +b.dataset.n===flexN);
    b.onclick = function(){
      flexN = +this.dataset.n;
      document.querySelectorAll('.fn-btn').forEach(x=>x.classList.remove('active'));
      this.classList.add('active');
      showFlexSummary();
    };
  });
  document.querySelectorAll('.fm-btn').forEach(b => {
    b.classList.toggle('active', +b.dataset.m===flexM);
    b.onclick = function(){
      flexM = +this.dataset.m;
      document.querySelectorAll('.fm-btn').forEach(x=>x.classList.remove('active'));
      this.classList.add('active');
      showFlexSummary();
    };
  });
}

function showFlexSummary(){
  if(activeRoomIdx===null) return;
  const room    = ROOMS[activeRoomIdx];
  const sk      = seasonKey(flexM);
  const nightly = priceFor(room, flexM);
  const total   = nightly * flexN;

  document.getElementById('rsDates').textContent  =
    `${flexN} night${flexN>1?'s':''} in ${MN[flexM-1]}`;
  document.getElementById('rsPrice').innerHTML    =
    `<strong>USD ${total.toLocaleString()}</strong>  |  USD ${nightly.toLocaleString()} / night`;
  document.getElementById('rsSeason').innerHTML   =
    `<span style="color:${SM[sk].color}">● ${SM[sk].label}</span>`;
  document.getElementById('ratesSummary').style.display = 'flex';
}

/* Rates close */
document.getElementById('ratesClose').addEventListener('click', () => closeM('ratesBackdrop'));

/* Confirm → pre-fill booking form */
document.getElementById('ratesConfirm').addEventListener('click', () => {
  if(activeRoomIdx===null) return;
  const room = ROOMS[activeRoomIdx];

  const sel = document.getElementById('room_type');
  if(sel) sel.value = room.name;

  if(cIn  && document.getElementById('checkin'))
    document.getElementById('checkin').value  = fmtDate(cIn);
  if(cOut && document.getElementById('checkout'))
    document.getElementById('checkout').value = fmtDate(cOut);

  const bookM = cIn ? cIn.getMonth()+1 : flexM;
  const fp = document.getElementById('formPrice');
  if(fp) fp.value = priceFor(room, bookM);
  const fs = document.getElementById('formSeason');
  if(fs) fs.value = seasonKey(bookM);

  if(typeof calcFormTotal === 'function') calcFormTotal();

  closeM('ratesBackdrop');
  const bk = document.getElementById('book');
  if(bk) bk.scrollIntoView({ behavior:'smooth' });
});
</script>


<!-- ══════════════════════════════════════
     CSS  (scoped — no conflicts)
══════════════════════════════════════ -->
<style>
/* ── Shared backdrop ─────────────────────────────────── */
.ska-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.78);
  backdrop-filter: blur(4px);
  -webkit-backdrop-filter: blur(4px);
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
  /* HIDDEN by default */
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.3s ease;
}
.ska-backdrop.visible {
  opacity: 1;
  pointer-events: all;
}
/* body lock — ONLY overflow, NOT position (avoids jump) */
body.modal-open { overflow: hidden; }

/* ── LIGHTBOX ────────────────────────────────────────── */
.lbx-shell {
  width: 100%;
  max-width: 1200px;
  max-height: 92vh;
  background: #111;
  border-radius: 14px;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  transform: scale(0.96);
  transition: transform 0.3s ease;
}
.ska-backdrop.visible .lbx-shell { transform: scale(1); }

.lbx-topbar {
  display: flex;
  align-items: center;
  padding: 14px 18px;
  gap: 12px;
  background: #0d0d0d;
  flex-shrink: 0;
}
.lbx-topbar-name {
  flex: 1;
  color: rgba(255,255,255,0.7);
  font-size: 14px;
  font-family: 'Montserrat', sans-serif;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.lbx-icon-btn {
  width: 36px; height: 36px;
  background: rgba(255,255,255,0.12);
  border: none; border-radius: 50%;
  color: #fff; font-size: 15px; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  transition: background 0.2s;
}
.lbx-icon-btn:hover { background: rgba(255,255,255,0.25); }
.lbx-vr-btn {
  padding: 9px 22px;
  background: #2d2d2d;
  border: 1px solid rgba(255,255,255,0.18);
  color: #fff; border-radius: 40px;
  font-size: 13px; font-weight: 600;
  cursor: pointer; flex-shrink: 0;
  font-family: 'Montserrat', sans-serif;
  transition: background 0.2s;
}
.lbx-vr-btn:hover { background: #2d7ab5; border-color: #2d7ab5; }

.lbx-stage {
  flex: 1;
  position: relative;
  background: #000;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 320px;
  overflow: hidden;
}
.lbx-stage img {
  max-width: 100%;
  max-height: 70vh;
  object-fit: contain;
  display: block;
}
.lbx-arr {
  position: absolute;
  top: 50%; transform: translateY(-50%);
  width: 48px; height: 48px;
  background: rgba(255,255,255,0.14);
  border: none; border-radius: 50%;
  color: #fff; font-size: 17px; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: background 0.2s;
}
.lbx-arr:hover { background: rgba(255,255,255,0.30); }
.lbx-arr-prev { left: 18px; }
.lbx-arr-next { right: 18px; }

.lbx-footer {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 13px 18px;
  background: rgba(0,0,0,0.55);
  font-size: 14px;
  color: rgba(255,255,255,0.72);
  flex-shrink: 0;
  font-family: 'Montserrat', sans-serif;
}
.lbx-pipe { opacity: 0.35; }
.lbx-room-name { font-weight: 600; }
.lbx-det-btn {
  margin-left: auto;
  background: none; border: none;
  color: #fff; font-size: 13px; font-weight: 600;
  text-decoration: underline; cursor: pointer;
  font-family: 'Montserrat', sans-serif;
  transition: color 0.2s;
}
.lbx-det-btn:hover { color: #c9a96e; }
.lbx-count { opacity: 0.7; }

/* ── DETAIL MODAL ────────────────────────────────────── */
.dm-shell {
  width: 100%;
  max-width: 860px;
  max-height: 92vh;
  background: #fff;
  border-radius: 14px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transform: translateY(18px);
  transition: transform 0.3s ease;
}
.ska-backdrop.visible .dm-shell { transform: translateY(0); }

.dm-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 24px;
  border-bottom: 1px solid #e8e2d9;
  flex-shrink: 0;
}
.dm-title {
  font-family: 'Cormorant Garamond', Georgia, serif;
  font-size: 1.45rem;
  font-weight: 600;
  margin: 0;
  color: #1e1e1e;
}
.dm-close-btn {
  width: 32px; height: 32px;
  background: #f0ece6; border: none; border-radius: 50%;
  color: #444; font-size: 14px; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0; transition: background 0.2s;
}
.dm-close-btn:hover { background: #e0dbd3; }

.dm-stage {
  position: relative;
  background: #1a1a1a;
  aspect-ratio: 16 / 7;
  overflow: hidden;
  flex-shrink: 0;
}
.dm-carousel {
  position: relative;
  width: 100%; height: 100%;
}
.dm-img {
  position: absolute;
  inset: 0;
  width: 100%; height: 100%;
  object-fit: cover;
  opacity: 0;
  transition: opacity 0.35s ease;
}
.dm-img.active { opacity: 1; }
.dm-img-ph {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  color: rgba(255,255,255,0.4);
  font-size: 14px;
  height: 100%;
  font-family: 'Montserrat', sans-serif;
}
.dm-img-ph i { font-size: 30px; }

.dm-arr {
  position: absolute;
  top: 50%; transform: translateY(-50%);
  width: 42px; height: 42px;
  background: rgba(255,255,255,0.16);
  border: none; border-radius: 50%;
  color: #fff; font-size: 15px; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  z-index: 2; transition: background 0.2s;
}
.dm-arr:hover { background: rgba(255,255,255,0.32); }
.dm-arr-prev { left: 14px; }
.dm-arr-next { right: 14px; }

.dm-dots {
  position: absolute;
  bottom: 12px; left: 50%;
  transform: translateX(-50%);
  display: flex; gap: 7px; z-index: 2;
}
.dm-dot {
  width: 7px; height: 7px;
  border-radius: 50%;
  background: rgba(255,255,255,0.38);
  border: none; cursor: pointer;
  transition: background 0.2s; padding: 0;
}
.dm-dot.active { background: #fff; }

.dm-body {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  padding: 22px 24px;
  gap: 20px;
  overflow-y: auto;
}
.dm-body-left { flex: 1; }
.dm-desc {
  font-family: 'Montserrat', sans-serif;
  font-size: 13px;
  line-height: 1.75;
  color: #5a5a5a;
  margin: 0 0 14px;
}
.dm-amenities {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}
.dm-amen-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: #444;
  font-family: 'Montserrat', sans-serif;
}
.dm-amen-item i { color: #c9a96e; font-size: 14px; }

.dm-body-right {
  flex-shrink: 0;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 12px;
}
.dm-price-line {
  font-family: 'Montserrat', sans-serif;
  font-size: 15px;
  font-weight: 700;
  color: #1e1e1e;
  margin: 0;
  text-align: right;
}
.dm-vr-btn {
  padding: 11px 26px;
  background: #2d7ab5; color: #fff;
  border: none; border-radius: 40px;
  font-family: 'Montserrat', sans-serif;
  font-size: 13px; font-weight: 600;
  cursor: pointer; transition: background 0.2s;
}
.dm-vr-btn:hover { background: #1f5d8a; }

/* ── RATES MODAL ─────────────────────────────────────── */
.rates-shell {
  width: 100%;
  max-width: 920px;
  max-height: 92vh;
  overflow-y: auto;
  background: #fff;
  border-radius: 14px;
  padding: 34px 38px 30px;
  position: relative;
  transform: translateY(22px);
  transition: transform 0.3s ease;
}
.ska-backdrop.visible .rates-shell { transform: translateY(0); }

.rates-close-btn {
  position: absolute; top: 15px; right: 16px;
  width: 30px; height: 30px;
  background: #f0ece6; border: none; border-radius: 50%;
  font-size: 13px; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  color: #444; transition: background 0.2s;
}
.rates-close-btn:hover { background: #e0dbd3; }

.rates-room-title {
  font-family: 'Cormorant Garamond', Georgia, serif;
  font-size: 1.35rem; font-weight: 600;
  color: #1e1e1e; margin: 0 0 14px;
}

/* Season legend */
.rates-legend {
  display: flex; gap: 18px; flex-wrap: wrap; margin-bottom: 18px;
}
.rl-item { display: flex; align-items: center; gap: 7px; font-size: 12px; color: #5a5a5a; font-family: 'Montserrat', sans-serif; }
.rl-dot  { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.rl-label { font-weight: 500; }

/* Toggle */
.rates-toggle {
  display: inline-flex;
  background: #f0ece6;
  border-radius: 40px;
  padding: 4px;
  margin-bottom: 24px;
}
.rt-btn {
  padding: 9px 26px;
  border: none; background: transparent; border-radius: 40px;
  font-family: 'Montserrat', sans-serif;
  font-size: 13px; font-weight: 500; color: #5a5a5a;
  cursor: pointer; transition: all 0.25s ease;
}
.rt-btn.active { background: #2d7ab5; color: #fff; font-weight: 600; }

/* Calendar nav wrapper */
.rates-cal-nav {
  display: flex;
  align-items: flex-start;
  gap: 8px;
}
.cal-nav-arr {
  background: none; border: none;
  color: #5a5a5a; font-size: 14px; cursor: pointer;
  padding: 8px 6px; margin-top: 4px;
  transition: color 0.2s; flex-shrink: 0;
}
.cal-nav-arr:hover { color: #2d7ab5; }

/* Dual calendars */
.dual-cal {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 36px;
  flex: 1;
}
.rcal-block {}
.rcal-month-name {
  font-family: 'Montserrat', sans-serif;
  font-size: 13px; font-weight: 700;
  letter-spacing: 0.1em;
  color: #1e1e1e;
  margin-bottom: 14px;
  text-align: center;
}
.rcal-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 3px;
}
.rch {
  font-family: 'Montserrat', sans-serif;
  font-size: 10px; font-weight: 600;
  color: #999; text-align: center; padding: 5px 0;
}
.rcc {
  text-align: center;
  font-family: 'Montserrat', sans-serif;
  font-size: 13px; color: #1e1e1e;
  border-radius: 50%; cursor: pointer;
  padding: 7px 2px; line-height: 1;
  aspect-ratio: 1;
  display: flex; align-items: center; justify-content: center;
  transition: background 0.15s, color 0.15s;
}
.rcc:hover:not(.past):not(.oth) {
  background: var(--s-col, #d9eaf5);
  color: #1e1e1e;
  opacity: 0.85;
}
.rcc.oth  { color: #ccc; cursor: default; }
.rcc.past { color: #d0d0d0; cursor: default; }
.rcc.sel  { background: #1e1e1e; color: #fff; border-radius: 50%; }
.rcc.start{ border-radius: 40px 0 0 40px; }
.rcc.end  { border-radius: 0 40px 40px 0; }
.rcc.in-range { background: #d9eaf5; color: #2d7ab5; border-radius: 0; }

/* Flexible panel */
.flex-nights-row {
  display: flex; align-items: center; gap: 16px;
  flex-wrap: wrap; margin-bottom: 20px;
}
.fn-label {
  font-family: 'Montserrat', sans-serif;
  font-size: 14px; font-weight: 500; color: #1e1e1e; white-space: nowrap;
}
.fn-btns-wrap { display: flex; flex-wrap: wrap; gap: 8px; }
.fn-btn {
  width: 42px; height: 42px; border-radius: 50%;
  border: 1.5px solid #e0d8ce; background: #fff;
  font-family: 'Montserrat', sans-serif;
  font-size: 13px; font-weight: 600; cursor: pointer;
  transition: all 0.2s;
}
.fn-btn:hover, .fn-btn.active {
  background: #2d7ab5; border-color: #2d7ab5; color: #fff;
}

.flex-months-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 10px;
}
.fm-btn {
  display: flex; flex-direction: column; align-items: center; gap: 4px;
  padding: 13px 8px; border: 1.5px solid #e0d8ce; border-radius: 6px;
  background: #fff; font-family: 'Montserrat', sans-serif;
  font-size: 14px; font-weight: 600; color: #1e1e1e;
  cursor: pointer; position: relative; transition: all 0.2s;
}
.fm-btn::before {
  content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
  background: var(--fm-col, #c9a96e); border-radius: 4px 4px 0 0;
}
.fm-btn.active { border-color: #2d7ab5; background: #e8f2fb; }
.fm-sea-tag { font-size: 10px; font-weight: 400; color: #999; }

/* Price summary */
.rates-summary {
  display: flex; flex-wrap: wrap; align-items: center; gap: 14px;
  background: #f8f4ef;
  border-left: 3px solid #c9a96e;
  border-radius: 4px;
  padding: 14px 18px;
  margin-top: 18px;
  font-family: 'Montserrat', sans-serif;
  font-size: 14px;
}
#rsDates  { color: #5a5a5a; }
#rsPrice  { font-weight: 700; color: #1e1e1e; }
#rsSeason { font-weight: 600; }

/* Confirm button */
.rates-confirm-btn {
  display: block;
  width: 100%; max-width: 280px;
  margin: 22px auto 0;
  padding: 14px 40px;
  background: #2d7ab5; color: #fff;
  border: none; border-radius: 40px;
  font-family: 'Montserrat', sans-serif;
  font-size: 14px; font-weight: 700;
  letter-spacing: 0.05em; cursor: pointer;
  transition: background 0.2s;
}
.rates-confirm-btn:hover { background: #1f5d8a; }

/* ── Responsive ──────────────────────────────────────── */
@media (max-width: 860px) {
  .dual-cal { grid-template-columns: 1fr; gap: 24px; }
  /* hide second calendar on small screens */
  .dual-cal .rcal-block:last-child { display: none; }
}
@media (max-width: 640px) {
  .rates-shell { padding: 26px 16px 22px; }
  .flex-months-grid { grid-template-columns: repeat(3,1fr); }
  .dm-body { flex-direction: column; }
  .dm-body-right { align-items: flex-start; }
  .lbx-footer { font-size: 12px; gap: 8px; }
}
</style>