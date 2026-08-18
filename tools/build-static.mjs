#!/usr/bin/env node
/**
 * Build valid static HTML for GitHub Pages → docs/
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const OUT = path.join(ROOT, 'docs');
const PARTIALS = path.join(__dirname, 'partials');

const HERO_SLIDES = `
      <div class="carousel-item active">
        <img src="assets/images/ska_naguru_home.jpeg" alt="SKA Naguru boutique hotel in Kampala" class="lp-hero-img" width="1920" height="1080" fetchpriority="high">
      </div>
      <div class="carousel-item">
        <img src="assets/images/ska_munyonyo_home2.jpg" alt="SKA Munyonyo lakeside boutique retreat" class="lp-hero-img" width="1920" height="1080" loading="lazy">
      </div>`;

const PROMO_CARDS = `
        <a href="offers.html" class="lp-promo-card">
          <img src="assets/images/ska_naguru_home.jpeg" alt="Book Direct and Save" class="lp-promo-img" loading="lazy">
          <div class="lp-promo-card-label"><span>Book Direct &amp; Save</span><i class="fa-solid fa-chevron-right"></i></div>
        </a>
        <a href="offers.html" class="lp-promo-card">
          <img src="assets/images/ska_art_home.jpg" alt="Book 7 Days Early" class="lp-promo-img" loading="lazy">
          <div class="lp-promo-card-label"><span>Book 7 Days Early</span><i class="fa-solid fa-chevron-right"></i></div>
        </a>
        <a href="offers.html" class="lp-promo-card">
          <img src="assets/images/ska_furniture_home.jpg" alt="Stay 3 Nights Pay for 2" class="lp-promo-img" loading="lazy">
          <div class="lp-promo-card-label"><span>Stay 3 Nights, Pay for 2</span><i class="fa-solid fa-chevron-right"></i></div>
        </a>
        <a href="munyonyo.html#book" class="lp-promo-card">
          <img src="assets/images/ska_munyonyo_home2.jpg" alt="Munyonyo Lakeside Weekend" class="lp-promo-img" loading="lazy">
          <div class="lp-promo-card-label"><span>Munyonyo Lakeside Weekend</span><i class="fa-solid fa-chevron-right"></i></div>
        </a>`;

const PROPERTY_DEFAULTS = {
  naguru: {
    branch: 'Naguru',
    video: 'assets/video/ska_naguru.mp4',
    heroImage: 'assets/images/ska_naguru_home.jpeg',
    dining: {
      tag: 'RESTAURANT',
      title: 'Fine Dining',
      body: 'Savor refined cuisine crafted with precision and artistry throughout your stay.',
      image: 'assets/images/naguru/restaurant.jpg',
      link: '#contact',
      label: 'Learn More',
    },
    garden: {
      tag: 'GARDENS',
      title: 'Serene Settings',
      body: 'Wander through lush gardens and unwind in tranquil greenery.',
      image: 'assets/images/naguru/garden.jpg',
      link: '#contact',
      label: 'Learn More',
    },
    gallery: [
      'assets/images/naguru/IMG_1044.jpg',
      'assets/images/naguru/IMG_1066.jpg',
      'assets/images/naguru/IMG_1069.jpg',
      'assets/images/naguru/IMG_1093.jpg',
      'assets/images/naguru/IMG_1120.jpg',
      'assets/images/naguru/IMG_1157.jpg',
    ],
  },
  munyonyo: {
    branch: 'Munyonyo',
    video: 'assets/video/ska_munyonyo.mp4',
    heroImage: 'assets/images/ska_munyonyo_home2.jpg',
    dining: {
      tag: 'RESTAURANT',
      title: 'Fine Dining',
      body: 'Exceptional dining experiences with lake-view ambiance.',
      image: 'assets/images/naguru/restaurant.jpg',
      link: '#contact',
      label: 'Learn More',
    },
    garden: {
      tag: 'GARDENS',
      title: 'Serene Settings',
      body: 'Lakeside gardens perfect for relaxation and events.',
      image: 'assets/images/naguru/garden.jpg',
      link: '#contact',
      label: 'Learn More',
    },
    gallery: [
      'assets/images/munyonyo/IMG_0879.jpg',
      'assets/images/munyonyo/IMG_0883.jpg',
      'assets/images/munyonyo/IMG_0912.jpg',
      'assets/images/munyonyo/IMG_0973.jpg',
      'assets/images/munyonyo/Armenities-4.jpg',
      'assets/images/munyonyo/ska_about.jpg',
    ],
  },
};

const PAGES = {
  index: {
    title: 'SKA The Boutique | Luxury Boutique Hotel in Kampala, Uganda',
    description: 'Book direct at SKA The Boutique — Naguru and Munyonyo, Kampala.',
    css: ['assets/css/home.css'],
    nav: 'landing',
    bodyClass: 'has-landing-nav',
  },
  offers: { title: 'Special Offers | SKA The Boutique', description: 'Exclusive offers at SKA.', css: ['assets/css/pages.css'], nav: 'landing' },
  'about-us': { title: 'About Us | SKA The Boutique', description: 'Our story.', css: ['assets/css/style.css', 'assets/css/pages.css'], nav: 'landing' },
  'meetings-events': { title: 'Meetings & Events | SKA', description: 'Events at SKA.', css: ['assets/css/pages.css'], nav: 'landing' },
  contact: { title: 'Contact Us | SKA', description: 'Get in touch.', css: ['assets/css/pages.css'], nav: 'landing' },
  help: { title: 'Help Centre | SKA', description: 'Help and FAQs.', css: ['assets/css/pages.css'], nav: 'landing' },
  careers: { title: 'Careers | SKA', description: 'Join our team.', css: ['assets/css/pages.css'], nav: 'landing' },
  loyalty: { title: 'SKA Rewards', description: 'Loyalty programme.', css: ['assets/css/pages.css'], nav: 'landing' },
  'privacy-policy': { title: 'Privacy Policy | SKA', description: 'Privacy policy.', css: ['assets/css/pages.css'], nav: 'landing' },
  'terms-of-use': { title: 'Terms of Use | SKA', description: 'Terms of use.', css: ['assets/css/pages.css'], nav: 'landing' },
  'cookie-policy': { title: 'Cookie Policy | SKA', description: 'Cookie policy.', css: ['assets/css/pages.css'], nav: 'landing' },
  naguru: {
    title: 'SKA Naguru | Boutique Hotel Kampala',
    description: 'Book SKA Naguru.',
    css: ['assets/css/branch.css', 'assets/css/rooms-section.css'],
    nav: 'property',
    property: 'naguru',
    branch: 'Naguru',
    bodyClass: '',
    extraScripts: '<script src="assets/js/ska-rooms.js"></script>',
  },
  munyonyo: {
    title: 'SKA Munyonyo | Lakeside Boutique Hotel',
    description: 'Book SKA Munyonyo.',
    css: ['assets/css/branch.css', 'assets/css/rooms-section.css'],
    nav: 'property',
    property: 'munyonyo',
    branch: 'Munyonyo',
    bodyClass: '',
    extraScripts: '<script src="assets/js/ska-rooms.js"></script>',
  },
};

const COPY_DIRS = ['assets'];
const COPY_FILES = ['robots.txt', 'sitemap.xml', 'google0f5359f7f26f1d03.html'];

function readPartial(name) {
  return fs.readFileSync(path.join(PARTIALS, name), 'utf8');
}

function rmrf(dir) {
  if (fs.existsSync(dir)) fs.rmSync(dir, { recursive: true, force: true });
}

function copyDir(src, dest) {
  if (!fs.existsSync(src)) return;
  fs.mkdirSync(dest, { recursive: true });
  for (const entry of fs.readdirSync(src, { withFileTypes: true })) {
    const s = path.join(src, entry.name);
    const d = path.join(dest, entry.name);
    if (entry.isDirectory()) copyDir(s, d);
    else fs.copyFileSync(s, d);
  }
}

function stripPhp(content) {
  return content.replace(/<\?php[\s\S]*?\?>/g, '').replace(/<\?=[\s\S]*?\?>/g, '');
}

function fixPaths(html) {
  html = html.replace(/\.php/g, '.html');
  html = html.replace(/action="forms\/process_[^"]+"/g, 'action="#" data-ska-form="booking"');
  html = html.replace(/action="forms\/process_inquiry\.php"/g, 'action="#" data-ska-form="inquiry"');
  return html;
}

function fixIndex(body) {
  body = body.replace(
    /<div class="carousel-inner">[\s\S]*?<\/div>\s*\n\s*<button class="carousel-control-prev"/,
    `<div class="carousel-inner">\n${HERO_SLIDES}\n    </div>\n    <button class="carousel-control-prev"`
  );
  body = body.replace(
    /<div class="lp-promo-track" id="promoTrack">[\s\S]*?<\/div>\s*\n\s*<button class="lp-promo-next"/,
    `<div class="lp-promo-track" id="promoTrack">\n${PROMO_CARDS}\n      </div>\n      <button class="lp-promo-next"`
  );
  body = body.replace(
    /<div class="lp-promo-dots" id="promoDots">[\s\S]*?<\/div>/,
    `<div class="lp-promo-dots" id="promoDots"><span class="lp-promo-dot active"></span><span class="lp-promo-dot"></span><span class="lp-promo-dot"></span><span class="lp-promo-dot"></span></div>`
  );
  return body;
}

function gallerySection(branch, images) {
  const items = images.map((src, i) =>
    `      <a href="${src}" class="ska-gallery-item" data-gallery-index="${i}">
        <img src="${src}" alt="SKA ${branch}" loading="lazy">
      </a>`
  ).join('\n');
  return `
<section class="ska-gallery-section" id="gallery" data-branch="${branch}">
  <div class="container">
    <div class="ska-gallery-header">
      <p class="ska-gallery-eyebrow">PHOTO GALLERY</p>
      <h2 class="ska-gallery-title">Explore ${branch}</h2>
    </div>
    <div class="ska-gallery-grid">
${items}
    </div>
  </div>
</section>
<style>
.ska-gallery-section { padding: 72px 0; background: #f9f7f3; }
.ska-gallery-header { text-align: center; margin-bottom: 40px; }
.ska-gallery-eyebrow { font-size: 11px; letter-spacing: 0.2em; color: #c9a96e; font-weight: 600; margin-bottom: 8px; }
.ska-gallery-title { font-family: 'Cormorant Garamond', serif; font-size: 2.2rem; font-weight: 300; color: #1a1a1a; margin: 0; }
.ska-gallery-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
.ska-gallery-item { position: relative; aspect-ratio: 4/3; overflow: hidden; border-radius: 8px; display: block; }
.ska-gallery-item img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
.ska-gallery-item:hover img { transform: scale(1.06); }
@media (max-width: 992px) { .ska-gallery-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 576px) { .ska-gallery-grid { grid-template-columns: 1fr; } }
</style>`;
}

function moreWaysSection(dining, garden) {
  return `<section class="section more-ways-section">
  <div class="container">
    <h2 class="more-ways-title text-center">More Ways to Enjoy Your Stay</h2>
    <div class="more-ways-grid">
      <div class="mw-card">
        <div class="mw-img-side">
          <img src="${dining.image}" alt="${dining.title}" loading="lazy">
        </div>
        <div class="mw-text-side">
          <span class="mw-eyebrow">${dining.tag}</span>
          <h3 class="mw-title">${dining.title}</h3>
          <p class="mw-body">${dining.body}</p>
          <a href="${dining.link}" class="mw-link">${dining.label} →</a>
        </div>
      </div>
      <div class="mw-card mw-card-reverse">
        <div class="mw-text-side">
          <span class="mw-eyebrow">${garden.tag}</span>
          <h3 class="mw-title">${garden.title}</h3>
          <p class="mw-body">${garden.body}</p>
          <a href="${garden.link}" class="mw-link">${garden.label} →</a>
        </div>
        <div class="mw-img-side">
          <img src="${garden.image}" alt="${garden.title}" loading="lazy">
        </div>
      </div>
    </div>
  </div>
</section>`;
}

function fixProperty(body, propertyKey) {
  const cfg = PROPERTY_DEFAULTS[propertyKey];
  if (!cfg) return body;

  body = body.replace(
    /<source src="" type="video\/mp4">/,
    `<source src="${cfg.video}" type="video/mp4">`
  );

  body = body.replace(
    /<div class="rs-season-pill" style="--sp-color:">[\s\S]*?<\/div>/,
    `<div class="rs-season-pill" style="--sp-color:#c9a96e">
          <span class="rs-season-dot" style="background:#c9a96e"></span>
          <span class="rs-season-label">High Season</span>
        </div>`
  );

  body = body.replace(
    /<div class="rs-track" id="roomsTrack">[\s\S]*?<\/div>\s*\n\s*<\/div>\s*\n\s*<\/div>\s*\n<\/section>/,
    `<div class="rs-track" id="roomsTrack" data-ska-branch="${cfg.branch}"><p style="padding:24px;color:#888">Loading rooms…</p></div>\n    </div>\n\n  </div>\n</section>`
  );

  body = body.replace(
    /<section class="section more-ways-section">[\s\S]*?<\/section>/,
    moreWaysSection(cfg.dining, cfg.garden)
  );

  if (!body.includes('id="gallery"')) {
    body = body.replace(
      /<!-- ═+\s*\n\s*BOOKING FORM/,
      `${gallerySection(cfg.branch, cfg.gallery)}\n\n<!-- ══════════════════════════════════════════\n     BOOKING FORM`
    );
  }

  body = body.replace(/const ROOMS = ;/g, 'const ROOMS = window.SKA_ROOMS || [];');
  body = body.replace(/const PROMOTIONS = ;/g, 'const PROMOTIONS = window.SKA_PROMOTIONS || [];');
  body = body.replace(/const ROOMS = \s*\|\| \[\]/g, 'const ROOMS = window.SKA_ROOMS || []');
  body = body.replace(/const ROOMS = window\.SKA_ROOMS \|\| \[\];/g, '/* rooms via ska-rooms.js */');

  body = body.replace(
    /<div class="booking-notice booking-notice--success">[\s\S]*?<\/div>\s*\n\s*<div class="booking-notice booking-notice--warn">[\s\S]*?<\/div>\s*\n\s*<div class="booking-notice booking-notice--error">[\s\S]*?<\/div>/,
    ''
  );

  body = body.replace(/\bROOMS\.length\b/g, '(window.SKA_ROOMS||[]).length');
  body = body.replace(/\bROOMS\.find\b/g, '(window.SKA_ROOMS||[]).find');
  body = body.replace(/\bROOMS\[/g, '(window.SKA_ROOMS||[])[');

  body = body.replace(
    /requestAnimationFrame\(updateSlider\);/,
    'requestAnimationFrame(updateSlider);\n    window.__skaUpdateSlider = updateSlider;'
  );

  return body;
}

function propertyNav(slug) {
  const base = `${slug}.html`;
  return `<div class="ska-property-header" id="skaPropertyHeader"><div class="container"><div class="ska-tabs">
    <div class="ska-brand"><a href="index.html"><img src="assets/images/favicon.png" alt="SKA"></a></div>
    <nav class="ska-tab-links" id="skaTabLinks">
      <a href="${base}" class="active">Overview</a>
      <a href="${base}#gallery">Photos</a>
      <a href="${base}#rooms">Rooms</a>
      <a href="${base}#services">Drink + Eat</a>
      <a href="${base}#book">Book</a>
    </nav></div></div></div>`;
}

function buildPage(name, meta) {
  const src = path.join(ROOT, `${name}.php`);
  if (!fs.existsSync(src)) return;

  let body = stripPhp(fs.readFileSync(src, 'utf8')).trim();
  body = fixPaths(body);
  if (name === 'index') body = fixIndex(body);
  if (meta.property) body = fixProperty(body, meta.property);

  const extraCss = (meta.css || [])
    .map((c) => `<link rel="stylesheet" href="${c}">`)
    .join('\n');

  const head = readPartial('head.html')
    .replace('{{TITLE}}', meta.title)
    .replace('{{DESCRIPTION}}', meta.description || meta.title)
    .replace('{{EXTRA_CSS}}', extraCss)
    .replace('{{BODY_CLASS}}', meta.bodyClass != null ? meta.bodyClass : 'has-landing-nav');

  let nav = '';
  if (meta.nav === 'landing') nav = readPartial('nav-landing.html');
  if (meta.nav === 'property') nav = propertyNav(meta.property || name);

  const footer = readPartial('footer.html').replace('{{EXTRA_SCRIPTS}}', meta.extraScripts || '');
  fs.writeFileSync(path.join(OUT, `${name}.html`), head + nav + '\n' + body + '\n' + footer);
  console.log(`Built ${name}.html`);
}

function buildAdmin() {
  const adminOut = path.join(OUT, 'admin');
  fs.mkdirSync(path.join(adminOut, 'assets'), { recursive: true });

  fs.writeFileSync(path.join(adminOut, 'login.html'), `<!DOCTYPE html>
<html lang="en" data-ska-static="true"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin | SKA</title><link rel="stylesheet" href="../admin/assets/login.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"></head>
<body class="ska-login"><div class="ska-login-wrap"><div class="login-card">
<h4>SKA Admin</h4><div id="loginError" class="error" style="display:none"></div>
<form id="adminLoginForm"><label>Email</label><input type="email" id="adminEmail" required><br><br>
<label>Password</label><input type="password" id="adminPass" required><br><br>
<button type="submit">Sign In</button></form>
</div></div>
<script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
<script src="../assets/js/ska-config.js"></script>
<script src="../assets/js/ska-api.js"></script>
<script>
document.getElementById('adminLoginForm').onsubmit=async function(e){e.preventDefault();
try{await SkaApi.adminSignIn(adminEmail.value,adminPass.value);location.href='dashboard.html';}
catch(ex){loginError.style.display='block';loginError.textContent=ex.message;}};
</script></body></html>`);

  console.log('Built admin pages');
}

console.log('Building static site → docs/');
rmrf(OUT);
fs.mkdirSync(OUT, { recursive: true });

for (const [name, meta] of Object.entries(PAGES)) buildPage(name, meta);
buildAdmin();

for (const dir of COPY_DIRS) copyDir(path.join(ROOT, dir), path.join(OUT, dir));
copyDir(path.join(ROOT, 'admin', 'assets'), path.join(OUT, 'admin', 'assets'));
for (const file of COPY_FILES) {
  const src = path.join(ROOT, file);
  if (fs.existsSync(src)) fs.copyFileSync(src, path.join(OUT, file));
}
fs.writeFileSync(path.join(OUT, '.nojekyll'), '');
console.log('Done.');
