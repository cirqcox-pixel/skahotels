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
        <a href="offers.html" class="lp-promo-card">
          <img src="assets/images/ska_munyonyo_home2.jpg" alt="Direct Booking Bonus" class="lp-promo-img" loading="lazy">
          <div class="lp-promo-card-label"><span>Direct Booking Bonus</span><i class="fa-solid fa-chevron-right"></i></div>
        </a>
        <a href="munyonyo.html#book" class="lp-promo-card">
          <img src="assets/images/ska_munyonyo_home2.jpg" alt="Munyonyo Lakeside Weekend" class="lp-promo-img" loading="lazy">
          <div class="lp-promo-card-label"><span>Munyonyo Lakeside Weekend</span><i class="fa-solid fa-chevron-right"></i></div>
        </a>`;

const OFFERS_PAGE_BODY = `
<section class="ska-page-hero">
  <div class="ska-page-hero__bg" style="background-image:url('assets/images/ska_naguru_home.jpeg');opacity:.45"></div>
  <div class="container">
    <p class="ska-page-hero__eyebrow">Deals &amp; Packages</p>
    <h1 class="ska-page-hero__title">Get Away, Get More</h1>
    <p class="ska-page-hero__sub">Book direct for our best rates — free breakfast, Wi-Fi, and flexible check-in included with every reservation.</p>
  </div>
</section>
<section class="ska-page-body">
  <div class="container" style="max-width:1140px">
    <div class="ska-grid-3" id="offersGrid">
      <article class="ska-feature-card">
        <div class="ska-feature-card__img" style="background-image:url('assets/images/ska_naguru_home.jpeg')"></div>
        <div class="ska-feature-card__body">
          <p class="ska-feature-card__tag">Best Rate Guarantee</p>
          <h2 class="ska-feature-card__title">Book Direct &amp; Save</h2>
          <p class="ska-feature-card__text">Our lowest prices are always here. Free Wi-Fi, breakfast, and flexible cancellation when you book on our website.</p>
          <a href="index.html#book-search" class="ska-btn-gold">Book Now <i class="fa-solid fa-arrow-right"></i></a>
        </div>
      </article>
      <article class="ska-feature-card">
        <div class="ska-feature-card__img" style="background-image:url('assets/images/ska_art_home.jpg')"></div>
        <div class="ska-feature-card__body">
          <p class="ska-feature-card__tag">Early Bird</p>
          <h2 class="ska-feature-card__title">Book 7 Days Early</h2>
          <p class="ska-feature-card__text">Plan ahead and unlock exclusive savings when you reserve at least seven days before arrival.</p>
          <a href="naguru.html#book" class="ska-btn-gold">Book Now <i class="fa-solid fa-arrow-right"></i></a>
        </div>
      </article>
      <article class="ska-feature-card">
        <div class="ska-feature-card__img" style="background-image:url('assets/images/ska_furniture_home.jpg')"></div>
        <div class="ska-feature-card__body">
          <p class="ska-feature-card__tag">Extended Stay</p>
          <h2 class="ska-feature-card__title">Stay 3 Nights, Pay for 2</h2>
          <p class="ska-feature-card__text">Celebrate longer stays — enjoy three nights and only pay for two at either property.</p>
          <a href="index.html#book-search" class="ska-btn-gold">Book Now <i class="fa-solid fa-arrow-right"></i></a>
        </div>
      </article>
      <article class="ska-feature-card">
        <div class="ska-feature-card__img" style="background-image:url('assets/images/ska_munyonyo_home2.jpg')"></div>
        <div class="ska-feature-card__body">
          <p class="ska-feature-card__tag">Member Perk</p>
          <h2 class="ska-feature-card__title">Direct Booking Bonus</h2>
          <p class="ska-feature-card__text">Extra value when you book with us — complimentary upgrades subject to availability and welcome treats.</p>
          <a href="loyalty.html" class="ska-btn-gold">Learn More <i class="fa-solid fa-arrow-right"></i></a>
        </div>
      </article>
      <article class="ska-feature-card">
        <div class="ska-feature-card__img" style="background-image:url('assets/images/ska_munyonyo_home2.jpg')"></div>
        <div class="ska-feature-card__body">
          <p class="ska-feature-card__tag">Weekend Escape</p>
          <h2 class="ska-feature-card__title">Munyonyo Lakeside Weekend</h2>
          <p class="ska-feature-card__text">Unwind by the lake with a weekend package at SKA Munyonyo — serene gardens and boutique comfort.</p>
          <a href="munyonyo.html#book" class="ska-btn-gold">Book Now <i class="fa-solid fa-arrow-right"></i></a>
        </div>
      </article>
    </div>
  </div>
</section>
<section class="ska-cta-band">
  <div class="container">
    <h2>Ready to experience SKA?</h2>
    <p>Two distinctive properties. One standard of excellence.</p>
    <div class="ska-cta-band__btns">
      <a href="naguru.html" class="ska-btn-gold">Explore Naguru</a>
      <a href="munyonyo.html" class="ska-btn-outline" style="border-color:#fff;color:#fff">Explore Munyonyo</a>
    </div>
  </div>
</section>
`;

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
    description: 'Book direct at SKA The Boutique — refined boutique bed & breakfast in Naguru and Munyonyo, Kampala. Best rates, free breakfast, Wi-Fi and flexible check-in.',
    image: 'assets/images/ska_naguru_home.jpeg',
    css: ['assets/css/home.css'],
    nav: 'landing',
    bodyClass: 'has-landing-nav',
  },
  offers: {
    title: 'Special Offers | SKA The Boutique Kampala',
    description: 'Exclusive direct-booking offers at SKA Naguru and Munyonyo — early-bird savings, stay-longer packages, and member perks. Book on our site for the best rate.',
    image: 'assets/images/ska_naguru_home.jpeg',
    css: ['assets/css/pages.css'],
    nav: 'landing',
  },
  'about-us': {
    title: 'About Us | SKA The Boutique Kampala',
    description: 'Discover SKA The Boutique — two distinctive properties in Naguru and Munyonyo redefining boutique hospitality in Kampala, Uganda.',
    image: 'assets/images/dube_munyonyo.jpg',
    css: ['assets/css/pages.css'],
    nav: 'landing',
  },
  'meetings-events': {
    title: 'Meetings & Events | SKA The Boutique Kampala',
    description: 'Intimate meetings, weddings and social events at SKA Naguru and Munyonyo. Boutique venues in Kampala with gardens, dining and dedicated hosting.',
    image: 'assets/images/ska_art_home.jpg',
    css: ['assets/css/pages.css'],
    nav: 'landing',
    staticBody: 'meetings-events',
  },
  contact: {
    title: 'Contact Us | SKA The Boutique Kampala',
    description: 'Contact SKA Naguru and SKA Munyonyo for reservations, events and enquiries. Call, WhatsApp or write to the boutique hotels in Kampala.',
    image: 'assets/images/ska_naguru_home.jpeg',
    css: ['assets/css/pages.css'],
    nav: 'landing',
    staticBody: 'contact',
  },
  help: {
    title: 'Help Centre | SKA The Boutique Kampala',
    description: 'How to book SKA The Boutique, transfers from Entebbe Airport, weather, roads, and what to see near Naguru and Munyonyo.',
    image: 'assets/images/ska_naguru_home.jpeg',
    css: ['assets/css/pages.css'],
    nav: 'landing',
    staticBody: 'help',
  },
  careers: {
    title: 'Careers | SKA The Boutique Kampala',
    description: 'Join the team at SKA The Boutique in Kampala — hospitality careers across Naguru and Munyonyo.',
    image: 'assets/images/ska_art_home.jpg',
    css: ['assets/css/pages.css'],
    nav: 'landing',
  },
  loyalty: {
    title: 'SKA Rewards | Loyalty Programme',
    description: 'SKA Rewards member rates and exclusive offers when you book direct at SKA Naguru and Munyonyo.',
    image: 'assets/images/ska_naguru_home.jpeg',
    css: ['assets/css/pages.css'],
    nav: 'landing',
    staticBody: 'loyalty',
  },
  'privacy-policy': {
    title: 'Privacy Policy | SKA The Boutique',
    description: 'How SKA The Boutique collects, uses and protects personal information when you book or contact us.',
    css: ['assets/css/pages.css'],
    nav: 'landing',
  },
  'terms-of-use': {
    title: 'Terms of Use | SKA The Boutique',
    description: 'Terms of use for the SKA The Boutique website and direct booking services.',
    css: ['assets/css/pages.css'],
    nav: 'landing',
  },
  'cookie-policy': {
    title: 'Cookie Policy | SKA The Boutique',
    description: 'How SKA The Boutique uses cookies and similar technologies on this website.',
    css: ['assets/css/pages.css'],
    nav: 'landing',
  },
  naguru: {
    title: 'SKA Naguru | Boutique Hotel Hillside Kampala',
    description: 'Book SKA Naguru — an elegant boutique B&B in Naguru, Kampala. Seasonal rates, direct booking benefits, free breakfast and Wi-Fi.',
    image: 'assets/images/ska_naguru_home.jpeg',
    css: ['assets/css/branch.css', 'assets/css/rooms-section.css', 'assets/css/booking-form.css'],
    nav: 'property',
    property: 'naguru',
    branch: 'Naguru',
    bodyClass: '',
    extraScripts: '<script src="assets/js/ska-rooms.js"></script>',
  },
  munyonyo: {
    title: 'SKA Munyonyo | Lakeside Boutique Hotel Kampala',
    description: 'Book SKA Munyonyo — a serene boutique escape in Munyonyo, Kampala near Lake Victoria. Direct booking, free breakfast and seasonal rates.',
    image: 'assets/images/ska_munyonyo_home2.jpg',
    css: ['assets/css/branch.css', 'assets/css/rooms-section.css', 'assets/css/booking-form.css'],
    nav: 'property',
    property: 'munyonyo',
    branch: 'Munyonyo',
    bodyClass: '',
    extraScripts: '<script src="assets/js/ska-rooms.js"></script>',
  },
};

const COPY_DIRS = ['assets'];
const COPY_FILES = ['robots.txt', 'sitemap.xml', 'google0f5359f7f26f1d03.html', 'CNAME', 'humans.txt', 'llms.txt', 'site.webmanifest'];

const SITE_URL = 'https://www.skaboutiquebnb.com';
const BUILDER = {
  name: 'Cirqco Systems',
  url: 'https://cirqco.com/',
  founder: 'Maxwell Odoi',
  founderTitle: 'Founder & CEO',
  description: 'Cirqco Systems is a technology company that designs and engineers software, digital products, and web platforms.',
  credit: 'Engineered by Cirqco Systems, a technology company. Founded by Maxwell Odoi, CEO.',
};

function esc(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function pageUrl(name) {
  return name === 'index' ? `${SITE_URL}/` : `${SITE_URL}/${name}.html`;
}

function generateSeoHead(name, meta) {
  const title = meta.title;
  const description = meta.description || title;
  const canonical = pageUrl(name);
  const imagePath = meta.image || 'assets/images/ska_naguru_home.jpeg';
  const imageAbs = imagePath.startsWith('http') ? imagePath : `${SITE_URL}/${imagePath}`;
  const robots = 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
  const siteId = `${SITE_URL}/#website`;
  const hotelId = `${SITE_URL}/#hotel`;
  const pageId = `${canonical}#webpage`;
  const builderId = `${BUILDER.url.replace(/\/$/, '')}/#organization`;
  const founderId = `${BUILDER.url.replace(/\/$/, '')}/#founder`;

  const graph = {
    '@context': 'https://schema.org',
    '@graph': [
      {
        '@type': 'Organization',
        '@id': builderId,
        name: BUILDER.name,
        legalName: BUILDER.name,
        url: BUILDER.url,
        description: BUILDER.description,
        founder: { '@id': founderId },
        sameAs: [BUILDER.url, 'https://www.linkedin.com/company/cirqco'],
      },
      {
        '@type': 'Person',
        '@id': founderId,
        name: BUILDER.founder,
        jobTitle: BUILDER.founderTitle,
        worksFor: { '@id': builderId },
        sameAs: ['https://www.linkedin.com/in/sir-maxwell-odoi-37474495'],
      },
      {
        '@type': 'Hotel',
        '@id': hotelId,
        name: 'SKA The Boutique',
        alternateName: 'SKA The Boutique B&B',
        url: SITE_URL,
        logo: `${SITE_URL}/assets/images/ska_logo1.png`,
        image: `${SITE_URL}/assets/images/ska_naguru_home.jpeg`,
        email: 'info@skaboutiquebnb.com',
        telephone: ['+256200987770', '+256741186891'],
        description: 'Boutique bed and breakfast with properties in Naguru and Munyonyo, Kampala, Uganda.',
        sameAs: [
          'https://www.instagram.com/skanaguru/',
          'https://www.facebook.com/skaboutiquebnb',
        ],
        address: { '@type': 'PostalAddress', addressLocality: 'Kampala', addressCountry: 'UG' },
        geo: { '@type': 'GeoCoordinates', latitude: 0.3476, longitude: 32.5825 },
        amenityFeature: [
          { '@type': 'LocationFeatureSpecification', name: 'Free WiFi', value: true },
          { '@type': 'LocationFeatureSpecification', name: 'Free Breakfast', value: true },
        ],
        starRating: { '@type': 'Rating', ratingValue: '4.5', bestRating: '5' },
        priceRange: '$$',
        checkinTime: '14:00',
        checkoutTime: '12:00',
      },
      {
        '@type': 'WebSite',
        '@id': siteId,
        url: SITE_URL,
        name: 'SKA The Boutique',
        inLanguage: 'en-UG',
        publisher: { '@id': hotelId },
        creator: { '@id': builderId },
        author: { '@id': builderId },
        copyrightHolder: { '@id': hotelId },
        sourceOrganization: { '@id': builderId },
        creditText: BUILDER.credit,
      },
      {
        '@type': 'WebPage',
        '@id': pageId,
        url: canonical,
        name: title,
        description,
        isPartOf: { '@id': siteId },
        about: { '@id': hotelId },
        primaryImageOfPage: imageAbs,
        inLanguage: 'en-UG',
        creator: { '@id': builderId },
        author: { '@id': builderId },
        publisher: { '@id': hotelId },
        creditText: BUILDER.credit,
        breadcrumb: {
          '@type': 'BreadcrumbList',
          itemListElement: name === 'index'
            ? [{ '@type': 'ListItem', position: 1, name: 'Home', item: SITE_URL }]
            : [
                { '@type': 'ListItem', position: 1, name: 'Home', item: SITE_URL },
                { '@type': 'ListItem', position: 2, name: title, item: canonical },
              ],
        },
      },
    ],
  };

  return [
    '<meta charset="UTF-8">',
    '<meta http-equiv="X-UA-Compatible" content="IE=edge">',
    '<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">',
    `<title>${esc(title)}</title>`,
    `<meta name="description" content="${esc(description)}">`,
    `<meta name="author" content="${esc(BUILDER.name)}">`,
    `<meta name="creator" content="${esc(BUILDER.name)}">`,
    `<meta name="designer" content="${esc(BUILDER.name)}">`,
    `<meta name="web_author" content="${esc(BUILDER.founder)}, ${esc(BUILDER.name)}">`,
    `<meta name="generator" content="${esc(BUILDER.name)}">`,
    '<meta name="publisher" content="SKA The Boutique">',
    `<meta name="robots" content="${robots}">`,
    `<meta name="googlebot" content="${robots}">`,
    `<link rel="canonical" href="${canonical}">`,
    `<link rel="author" href="${BUILDER.url}" title="${esc(BUILDER.name)}">`,
    `<link rel="author" type="text/plain" href="${SITE_URL}/humans.txt">`,
    `<link rel="alternate" hreflang="en" href="${canonical}">`,
    `<link rel="alternate" hreflang="x-default" href="${canonical}">`,
    '<meta name="theme-color" content="#0d1b2e">',
    '<meta name="geo.region" content="UG-102">',
    '<meta name="geo.placename" content="Kampala, Uganda">',
    '<meta name="ICBM" content="0.3476, 32.5825">',
    '<meta property="og:site_name" content="SKA The Boutique">',
    `<meta property="og:title" content="${esc(title)}">`,
    `<meta property="og:description" content="${esc(description)}">`,
    '<meta property="og:type" content="website">',
    `<meta property="og:url" content="${canonical}">`,
    `<meta property="og:image" content="${imageAbs}">`,
    '<meta property="og:image:alt" content="SKA The Boutique boutique hotel in Kampala, Uganda">',
    '<meta property="og:image:width" content="1200">',
    '<meta property="og:image:height" content="630">',
    '<meta property="og:locale" content="en_UG">',
    `<meta property="og:see_also" content="${BUILDER.url}">`,
    '<meta name="twitter:card" content="summary_large_image">',
    `<meta name="twitter:title" content="${esc(title)}">`,
    `<meta name="twitter:description" content="${esc(description)}">`,
    `<meta name="twitter:image" content="${imageAbs}">`,
    '<link rel="icon" href="assets/images/favicon.png" type="image/png">',
    '<link rel="apple-touch-icon" href="assets/images/favicon.png">',
    `<link rel="manifest" href="${SITE_URL}/site.webmanifest">`,
    `<script type="application/ld+json">${JSON.stringify(graph)}</script>`,
  ].join('\n');
}

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
    `<div class="lp-promo-dots" id="promoDots"><span class="lp-promo-dot active"></span><span class="lp-promo-dot"></span><span class="lp-promo-dot"></span><span class="lp-promo-dot"></span><span class="lp-promo-dot"></span></div>`
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
    /if \(!ci \|\| !co \|\| !roomName \|\| typeof ROOMS === 'undefined'\) return;/g,
    'if (!ci || !co || !roomName) return;'
  );

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
  if (name === 'offers') body = OFFERS_PAGE_BODY;
  if (meta.staticBody) {
    const pagePartial = path.join(PARTIALS, 'pages', `${meta.staticBody}.html`);
    if (fs.existsSync(pagePartial)) body = fs.readFileSync(pagePartial, 'utf8');
  }
  if (meta.property) body = fixProperty(body, meta.property);

  // About page: ensure hero image + copy survive PHP stripping
  if (name === 'about-us') {
    body = body.replace(/background-image:\s*url\(''\);/g, "background-image: url('assets/images/dube_munyonyo.jpg');");
    body = body.replace(
      /<p class="ska-hero__eyebrow"><\/p>\s*<h1 class="ska-hero__title"><\/h1>\s*<p class="ska-hero__subtitle"><\/p>/,
      `<p class="ska-hero__eyebrow">Our Story</p>
      <h1 class="ska-hero__title">Where Every<br><em>Detail</em> Matters</h1>
      <p class="ska-hero__subtitle">Boutique charm, homely comfort, and genuine care — in the heart of Kampala.</p>`
    );
    body = body.replace(/href="\/"/g, 'href="index.html"');
  }

  const extraCss = (meta.css || [])
    .map((c) => `<link rel="stylesheet" href="${c}">`)
    .join('\n');

  const head = readPartial('head.html')
    .replace('{{SEO_HEAD}}', generateSeoHead(name, meta))
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
  const adminStatic = path.join(__dirname, 'admin-static');
  const partialsDir = path.join(adminStatic, 'partials');
  const pagesDir = path.join(adminStatic, 'pages');

  fs.mkdirSync(path.join(adminOut, 'assets'), { recursive: true });

  const loginSrc = path.join(adminStatic, 'login.html');
  if (fs.existsSync(loginSrc)) {
    fs.copyFileSync(loginSrc, path.join(adminOut, 'login.html'));
  }

  const headTpl = fs.readFileSync(path.join(partialsDir, 'head.html'), 'utf8');
  const sidebarTpl = fs.readFileSync(path.join(partialsDir, 'sidebar.html'), 'utf8');
  const scriptsTpl = fs.readFileSync(path.join(partialsDir, 'scripts.html'), 'utf8');

  const adminPages = [
    { file: 'dashboard.html', page: 'dashboard', title: 'Dashboard' },
    { file: 'bookings.html', page: 'bookings', title: 'Bookings' },
    { file: 'rooms.html', page: 'rooms', title: 'Rooms' },
    { file: 'promotions.html', page: 'promotions', title: 'Promotions' },
    { file: 'inquiries.html', page: 'inquiries', title: 'Inquiries' },
  ];

  for (const meta of adminPages) {
    const contentPath = path.join(pagesDir, meta.file);
    if (!fs.existsSync(contentPath)) continue;

    const sidebar = sidebarTpl
      .replace(/\{\{ACTIVE_DASHBOARD\}\}/g, meta.page === 'dashboard' ? ' active' : '')
      .replace(/\{\{ACTIVE_ROOMS\}\}/g, meta.page === 'rooms' ? ' active' : '')
      .replace(/\{\{ACTIVE_PROMOTIONS\}\}/g, meta.page === 'promotions' ? ' active' : '')
      .replace(/\{\{ACTIVE_BOOKINGS\}\}/g, meta.page === 'bookings' ? ' active' : '')
      .replace(/\{\{ACTIVE_INQUIRIES\}\}/g, meta.page === 'inquiries' ? ' active' : '');

    const html = headTpl
      .replace(/\{\{TITLE\}\}/g, meta.title)
      .replace(/\{\{PAGE\}\}/g, meta.page)
      + '\n'
      + sidebar
      + '\n'
      + fs.readFileSync(contentPath, 'utf8')
      + '\n'
      + scriptsTpl;

    fs.writeFileSync(path.join(adminOut, meta.file), html);
  }

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
