<?php
require_once 'config/cms.php';
$cmsAbout = cms_page('about-us');

$pageMeta = [
    'title'       => $cmsAbout['page_title'] ?? 'About Us | SKA The Boutique Kampala',
    'description' => $cmsAbout['meta_description'] ?? 'Discover SKA The Boutique — two distinctive properties in Naguru and Munyonyo redefining boutique hospitality in Kampala, Uganda.',
    'path'        => 'about-us',
    'image'       => $cmsAbout['hero_image'] ?? 'assets/images/dube_munyonyo.jpg',
];
$pageStyles = ['assets/css/style.css', 'assets/css/pages.css'];
$navActive  = 'about';
include 'includes/page-start.php';
?>

  <style>
    /* ══════════════════════════════════════════════
       DESIGN TOKENS
    ══════════════════════════════════════════════ */
    :root {
      --ivory:       #f9f6f0;
      --sand:        #ede8df;
      --gold:        #c9a96e;
      --gold-deep:   #a8814a;
      --charcoal:    #1c1c1c;
      --mid:         #4a4a4a;
      --light-text:  #888;
      --white:       #ffffff;
      --accent-blue: #1e5b84;

      --ff-display: 'Cormorant Garamond', Georgia, serif;
      --ff-body:    'Jost', sans-serif;

      --ease-out:   cubic-bezier(0.22, 1, 0.36, 1);
      --transition: 0.4s var(--ease-out);
    }

    *, *::before, *::after { box-sizing: border-box; }

    body {
      font-family: var(--ff-body);
      background: var(--ivory);
      color: var(--charcoal);
      overflow-x: hidden;
    }

    /* ══════════════════════════════════════════════
       HERO
    ══════════════════════════════════════════════ */
    .ska-hero {
      position: relative;
      width: 100%;
      height: 92vh;
      min-height: 560px;
      overflow: hidden;
      display: flex;
      align-items: flex-end;
    }

    .ska-hero__bg {
      position: absolute;
      inset: 0;
      background-image: url('assets/images/dube_munyonyo.jpg');
      background-size: cover;
      background-position: center 30%;
      transform: scale(1.06);
      animation: heroZoom 10s var(--ease-out) forwards;
    }

    @keyframes heroZoom {
      to { transform: scale(1); }
    }

    /* Multi-layer overlay: dark bottom, subtle vignette */
    .ska-hero__overlay {
      position: absolute;
      inset: 0;
      background:
        linear-gradient(to top,  rgba(15,12,8,0.82) 0%,  rgba(15,12,8,0.25) 50%, transparent 100%),
        linear-gradient(to right, rgba(0,0,0,0.25) 0%, transparent 60%);
    }

    .ska-hero__content {
      position: relative;
      z-index: 2;
      padding: 0 6vw 80px;
      max-width: 860px;
    }

    .ska-hero__eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 12px;
      font-family: var(--ff-body);
      font-size: 11px;
      font-weight: 600;
      letter-spacing: 0.22em;
      text-transform: uppercase;
      color: var(--gold);
      margin-bottom: 20px;
      opacity: 0;
      animation: fadeUp 0.8s var(--ease-out) 0.3s forwards;
    }

    .ska-hero__eyebrow::before {
      content: '';
      display: block;
      width: 36px;
      height: 1px;
      background: var(--gold);
    }

    .ska-hero__title {
      font-family: var(--ff-display);
      font-size: clamp(48px, 8vw, 96px);
      font-weight: 300;
      line-height: 1.02;
      color: var(--white);
      margin-bottom: 24px;
      opacity: 0;
      animation: fadeUp 0.9s var(--ease-out) 0.5s forwards;
    }

    .ska-hero__title em {
      font-style: italic;
      color: var(--gold);
    }

    .ska-hero__subtitle {
      font-size: 16px;
      font-weight: 300;
      color: rgba(255,255,255,0.72);
      letter-spacing: 0.04em;
      max-width: 480px;
      line-height: 1.7;
      opacity: 0;
      animation: fadeUp 0.9s var(--ease-out) 0.75s forwards;
    }

    /* Scroll indicator */
    .ska-hero__scroll {
      position: absolute;
      bottom: 32px;
      right: 6vw;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 8px;
      z-index: 2;
      opacity: 0;
      animation: fadeIn 1s ease 1.4s forwards;
    }

    .ska-hero__scroll span {
      font-size: 10px;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      color: rgba(255,255,255,0.5);
      writing-mode: vertical-rl;
    }

    .ska-hero__scroll-line {
      width: 1px;
      height: 48px;
      background: linear-gradient(to bottom, rgba(255,255,255,0.5), transparent);
      animation: scrollPulse 2s ease-in-out 1.5s infinite;
    }

    @keyframes scrollPulse {
      0%, 100% { transform: scaleY(1); opacity: 0.5; }
      50%       { transform: scaleY(0.6); opacity: 1; }
    }

    /* ══════════════════════════════════════════════
       STATS BAND
    ══════════════════════════════════════════════ */
    .ska-stats {
      background: var(--charcoal);
      padding: 0;
    }

    .ska-stats__inner {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      border-left: 1px solid rgba(255,255,255,0.06);
    }

    .ska-stat {
      padding: 40px 32px;
      border-right: 1px solid rgba(255,255,255,0.06);
      text-align: center;
      position: relative;
      overflow: hidden;
      transition: background var(--transition);
    }

    .ska-stat::before {
      content: '';
      position: absolute;
      bottom: 0; left: 0;
      width: 100%;
      height: 2px;
      background: var(--gold);
      transform: scaleX(0);
      transform-origin: left;
      transition: transform 0.5s var(--ease-out);
    }

    .ska-stat:hover::before { transform: scaleX(1); }
    .ska-stat:hover { background: rgba(255,255,255,0.03); }

    .ska-stat__number {
      font-family: var(--ff-display);
      font-size: clamp(36px, 4vw, 52px);
      font-weight: 300;
      color: var(--gold);
      line-height: 1;
      margin-bottom: 8px;
    }

    .ska-stat__label {
      font-size: 11px;
      font-weight: 500;
      letter-spacing: 0.16em;
      text-transform: uppercase;
      color: rgba(255,255,255,0.45);
    }

    /* ══════════════════════════════════════════════
       INTRO / STORY — SPLIT LAYOUT
    ══════════════════════════════════════════════ */
    .ska-intro {
      padding: 120px 0 80px;
      background: var(--ivory);
    }

    .ska-intro__wrap {
      max-width: 1240px;
      margin: 0 auto;
      padding: 0 40px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 80px;
      align-items: center;
    }

    .ska-intro__text-col {}

    .ska-intro__tag {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      font-size: 11px;
      font-weight: 600;
      letter-spacing: 0.2em;
      text-transform: uppercase;
      color: var(--gold-deep);
      margin-bottom: 24px;
    }

    .ska-intro__tag::after {
      content: '';
      display: block;
      width: 40px;
      height: 1px;
      background: var(--gold-deep);
    }

    .ska-intro__heading {
      font-family: var(--ff-display);
      font-size: clamp(34px, 3.5vw, 52px);
      font-weight: 300;
      line-height: 1.18;
      color: var(--charcoal);
      margin-bottom: 28px;
    }

    .ska-intro__heading em {
      font-style: italic;
      color: var(--gold-deep);
    }

    .ska-intro__body {
      font-size: 15.5px;
      font-weight: 300;
      line-height: 1.85;
      color: var(--mid);
      margin-bottom: 36px;
    }

    .ska-intro__cta {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      font-size: 12px;
      font-weight: 600;
      letter-spacing: 0.15em;
      text-transform: uppercase;
      color: var(--charcoal);
      text-decoration: none;
      padding-bottom: 4px;
      border-bottom: 1px solid var(--charcoal);
      transition: color var(--transition), border-color var(--transition);
    }

    .ska-intro__cta:hover {
      color: var(--gold-deep);
      border-color: var(--gold-deep);
    }

    .ska-intro__cta i {
      transition: transform 0.3s ease;
    }

    .ska-intro__cta:hover i {
      transform: translateX(4px);
    }

    /* Image column — overlapping card composition */
    .ska-intro__img-col {
      position: relative;
    }

    .ska-intro__img-main {
      width: 100%;
      aspect-ratio: 4/5;
      object-fit: cover;
      display: block;
      border-radius: 2px;
      box-shadow: 24px 32px 72px rgba(0,0,0,0.14);
    }

    .ska-intro__img-accent {
      position: absolute;
      bottom: -40px;
      left: -40px;
      width: 52%;
      aspect-ratio: 1/1;
      object-fit: cover;
      border: 6px solid var(--ivory);
      border-radius: 2px;
      box-shadow: 12px 16px 40px rgba(0,0,0,0.16);
    }

    .ska-intro__badge {
      position: absolute;
      top: 32px;
      right: -24px;
      background: var(--gold);
      color: var(--white);
      width: 96px;
      height: 96px;
      border-radius: 50%;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      box-shadow: 0 8px 24px rgba(201,169,110,0.4);
    }

    .ska-intro__badge strong {
      font-family: var(--ff-display);
      font-size: 26px;
      font-weight: 600;
      line-height: 1;
    }

    .ska-intro__badge span {
      font-size: 9px;
      font-weight: 600;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      opacity: 0.88;
    }

    /* ══════════════════════════════════════════════
       PULL QUOTE
    ══════════════════════════════════════════════ */
    .ska-quote {
      background: var(--sand);
      padding: 100px 40px;
      text-align: center;
      position: relative;
      overflow: hidden;
    }

    .ska-quote::before {
      content: '\201C';
      position: absolute;
      top: -20px;
      left: 50%;
      transform: translateX(-50%);
      font-family: var(--ff-display);
      font-size: 240px;
      color: rgba(201,169,110,0.12);
      line-height: 1;
      pointer-events: none;
      user-select: none;
    }

    .ska-quote__text {
      position: relative;
      font-family: var(--ff-display);
      font-size: clamp(22px, 3vw, 38px);
      font-weight: 300;
      font-style: italic;
      color: var(--charcoal);
      max-width: 800px;
      margin: 0 auto 24px;
      line-height: 1.5;
    }

    .ska-quote__attr {
      font-size: 11px;
      font-weight: 600;
      letter-spacing: 0.2em;
      text-transform: uppercase;
      color: var(--gold-deep);
    }

    /* ══════════════════════════════════════════════
       SECOND STORY BLOCK (reversed, dark bg)
    ══════════════════════════════════════════════ */
    .ska-story {
      background: var(--charcoal);
      padding: 120px 0;
    }

    .ska-story__wrap {
      max-width: 1240px;
      margin: 0 auto;
      padding: 0 40px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 80px;
      align-items: center;
    }

    .ska-story__img-col {
      position: relative;
    }

    .ska-story__img-main {
      width: 100%;
      aspect-ratio: 3/4;
      object-fit: cover;
      display: block;
      border-radius: 2px;
    }

    /* Decorative gold border offset */
    .ska-story__img-col::after {
      content: '';
      position: absolute;
      top: 24px;
      left: 24px;
      right: -24px;
      bottom: -24px;
      border: 1px solid rgba(201,169,110,0.3);
      border-radius: 2px;
      pointer-events: none;
      z-index: -1;
    }

    .ska-story__text-col {}

    .ska-story__tag {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      font-size: 11px;
      font-weight: 600;
      letter-spacing: 0.2em;
      text-transform: uppercase;
      color: var(--gold);
      margin-bottom: 24px;
    }

    .ska-story__tag::after {
      content: '';
      display: block;
      width: 40px;
      height: 1px;
      background: var(--gold);
    }

    .ska-story__heading {
      font-family: var(--ff-display);
      font-size: clamp(30px, 3vw, 46px);
      font-weight: 300;
      line-height: 1.22;
      color: var(--white);
      margin-bottom: 28px;
    }

    .ska-story__body {
      font-size: 15px;
      font-weight: 300;
      line-height: 1.9;
      color: rgba(255,255,255,0.6);
      margin-bottom: 20px;
    }

    /* ══════════════════════════════════════════════
       VALUES / PILLARS
    ══════════════════════════════════════════════ */
    .ska-values {
      background: var(--ivory);
      padding: 120px 0;
    }

    .ska-values__wrap {
      max-width: 1240px;
      margin: 0 auto;
      padding: 0 40px;
    }

    .ska-values__header {
      text-align: center;
      margin-bottom: 72px;
    }

    .ska-values__eyebrow {
      font-size: 11px;
      font-weight: 600;
      letter-spacing: 0.22em;
      text-transform: uppercase;
      color: var(--gold-deep);
      margin-bottom: 16px;
    }

    .ska-values__heading {
      font-family: var(--ff-display);
      font-size: clamp(30px, 3.5vw, 48px);
      font-weight: 300;
      color: var(--charcoal);
    }

    .ska-values__heading em {
      font-style: italic;
      color: var(--gold-deep);
    }

    .ska-values__grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 2px;
      background: var(--sand);
    }

    .ska-value-card {
      background: var(--white);
      padding: 52px 40px;
      position: relative;
      overflow: hidden;
      transition: transform 0.35s var(--ease-out), box-shadow 0.35s var(--ease-out);
    }

    .ska-value-card::after {
      content: '';
      position: absolute;
      bottom: 0; left: 0;
      width: 100%;
      height: 3px;
      background: var(--gold);
      transform: scaleX(0);
      transform-origin: left;
      transition: transform 0.45s var(--ease-out);
    }

    .ska-value-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 20px 48px rgba(0,0,0,0.1);
      z-index: 1;
    }

    .ska-value-card:hover::after {
      transform: scaleX(1);
    }

    .ska-value-card__icon {
      width: 52px;
      height: 52px;
      background: var(--sand);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 28px;
      transition: background var(--transition);
    }

    .ska-value-card:hover .ska-value-card__icon {
      background: var(--gold);
    }

    .ska-value-card__icon i {
      font-size: 18px;
      color: var(--gold-deep);
      transition: color var(--transition);
    }

    .ska-value-card:hover .ska-value-card__icon i {
      color: var(--white);
    }

    .ska-value-card__title {
      font-family: var(--ff-display);
      font-size: 22px;
      font-weight: 400;
      color: var(--charcoal);
      margin-bottom: 14px;
    }

    .ska-value-card__body {
      font-size: 14.5px;
      font-weight: 300;
      line-height: 1.8;
      color: var(--mid);
    }

    /* ══════════════════════════════════════════════
       LOCATIONS STRIP
    ══════════════════════════════════════════════ */
    .ska-locations {
      background: var(--sand);
      padding: 80px 0;
    }

    .ska-locations__wrap {
      max-width: 1240px;
      margin: 0 auto;
      padding: 0 40px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 40px;
    }

    .ska-locations__label {
      font-size: 11px;
      font-weight: 600;
      letter-spacing: 0.2em;
      text-transform: uppercase;
      color: var(--light-text);
      flex-shrink: 0;
    }

    .ska-locations__list {
      display: flex;
      gap: 40px;
      align-items: center;
      flex-wrap: wrap;
    }

    .ska-location-item {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .ska-location-item i {
      color: var(--gold);
      font-size: 16px;
    }

    .ska-location-item__text {}

    .ska-location-item__name {
      font-family: var(--ff-display);
      font-size: 18px;
      font-weight: 400;
      color: var(--charcoal);
      line-height: 1.2;
    }

    .ska-location-item__sub {
      font-size: 12px;
      color: var(--light-text);
      font-weight: 300;
    }

    .ska-locations__divider {
      width: 1px;
      height: 40px;
      background: rgba(0,0,0,0.12);
    }

    /* ══════════════════════════════════════════════
       SCROLL REVEAL ANIMATIONS
    ══════════════════════════════════════════════ */
    .ska-reveal {
      opacity: 0;
      transform: translateY(32px);
      transition: opacity 0.8s var(--ease-out), transform 0.8s var(--ease-out);
    }

    .ska-reveal.ska-visible {
      opacity: 1;
      transform: translateY(0);
    }

    .ska-reveal-delay-1 { transition-delay: 0.1s; }
    .ska-reveal-delay-2 { transition-delay: 0.22s; }
    .ska-reveal-delay-3 { transition-delay: 0.36s; }
    .ska-reveal-delay-4 { transition-delay: 0.5s; }
    .ska-reveal-delay-5 { transition-delay: 0.64s; }
    .ska-reveal-delay-6 { transition-delay: 0.78s; }

    /* ══════════════════════════════════════════════
       KEYFRAMES
    ══════════════════════════════════════════════ */
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(24px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to   { opacity: 1; }
    }

    /* ══════════════════════════════════════════════
       RESPONSIVE
    ══════════════════════════════════════════════ */
    @media (max-width: 1024px) {
      .ska-intro__img-accent { display: none; }
      .ska-intro__badge      { right: 12px; }
      .ska-story__img-col::after { display: none; }
    }

    @media (max-width: 900px) {
      .ska-stats__inner {
        grid-template-columns: repeat(2, 1fr);
      }

      .ska-stat {
        border-bottom: 1px solid rgba(255,255,255,0.06);
      }

      .ska-intro__wrap,
      .ska-story__wrap {
        grid-template-columns: 1fr;
        gap: 48px;
      }

      .ska-intro__img-col   { order: -1; }
      .ska-intro__img-main  { aspect-ratio: 16/9; }
      .ska-story__img-main  { aspect-ratio: 16/9; }

      .ska-values__grid {
        grid-template-columns: 1fr;
      }

      .ska-locations__wrap {
        flex-direction: column;
        align-items: flex-start;
      }

      .ska-locations__divider { display: none; }
    }

    @media (max-width: 600px) {
      .ska-hero__content { padding: 0 24px 60px; }

      .ska-intro__wrap,
      .ska-story__wrap,
      .ska-values__wrap,
      .ska-locations__wrap { padding: 0 20px; }

      .ska-intro   { padding: 72px 0 60px; }
      .ska-story   { padding: 72px 0; }
      .ska-values  { padding: 72px 0; }
      .ska-quote   { padding: 72px 24px; }

      .ska-stats__inner { grid-template-columns: repeat(2, 1fr); }

      .ska-value-card { padding: 36px 28px; }

      .ska-locations__list { gap: 24px; }

      .ska-intro__badge {
        width: 76px;
        height: 76px;
        top: 16px; right: 12px;
      }
      .ska-intro__badge strong { font-size: 20px; }
    }
  </style>

  <!-- ══════════════════════════════════════════════
       HERO
  ══════════════════════════════════════════════ -->
  <header class="ska-hero">
    <div class="ska-hero__bg"></div>
    <div class="ska-hero__overlay"></div>
    <div class="ska-hero__content">
      <p class="ska-hero__eyebrow">Our Story</p>
      <h1 class="ska-hero__title">Where Every<br><em>Detail</em> Matters</h1>
      <p class="ska-hero__subtitle">Boutique charm, homely comfort, and genuine care — in the heart of Kampala.</p>
    </div>
    <div class="ska-hero__scroll">
      <div class="ska-hero__scroll-line"></div>
      <span>Scroll</span>
    </div>
  </header>


  <!-- ══════════════════════════════════════════════
       STATS BAND
  ══════════════════════════════════════════════ -->
  <section class="ska-stats">
    <div class="ska-stats__inner">
      <div class="ska-stat ska-reveal">
        <div class="ska-stat__number">2</div>
        <div class="ska-stat__label">Properties</div>
      </div>
      <div class="ska-stat ska-reveal ska-reveal-delay-2">
        <div class="ska-stat__number">40+</div>
        <div class="ska-stat__label">Rooms &amp; Suites</div>
      </div>
      <div class="ska-stat ska-reveal ska-reveal-delay-3">
        <div class="ska-stat__number">5★</div>
        <div class="ska-stat__label">Guest Rating</div>
      </div>
      <div class="ska-stat ska-reveal ska-reveal-delay-4">
        <div class="ska-stat__number">24/7</div>
        <div class="ska-stat__label">Dedicated Service</div>
      </div>
    </div>
  </section>


  <!-- ══════════════════════════════════════════════
       INTRO / FIRST STORY BLOCK
  ══════════════════════════════════════════════ -->
  <section class="ska-intro">
    <div class="ska-intro__wrap">

      <!-- Text -->
      <div class="ska-intro__text-col">
        <p class="ska-intro__tag ska-reveal">Who We Are</p>
        <h2 class="ska-intro__heading ska-reveal ska-reveal-delay-1">
          A Distinguished<br>Collection of <em>Elegant Retreats</em>
        </h2>
        <p class="ska-intro__body ska-reveal ska-reveal-delay-2">
          SKA Boutique Hotels redefines hospitality in Kampala. Rooted in Munyonyo's tranquil lakeside charm and expanding with our latest addition — Hillside Escape in Naguru — SKA offers a seamless blend of contemporary sophistication, timeless comfort, and authentic Ugandan warmth.
        </p>
        <a href="index.html" class="ska-intro__cta ska-reveal ska-reveal-delay-3">
          Explore Our Properties <i class="fa-solid fa-arrow-right fa-xs"></i>
        </a>
      </div>

      <!-- Image -->
      <div class="ska-intro__img-col ska-reveal ska-reveal-delay-2">
        <img src="assets/images/ska_naguru_home.jpeg" alt="SKA Naguru — Hillside Escape" class="ska-intro__img-main">
        <img src="assets/images/ska_munyonyo_home2.jpg" alt="SKA Munyonyo" class="ska-intro__img-accent">
        <div class="ska-intro__badge">
          <strong>4★</strong>
          <span>Rated<br>Boutique</span>
        </div>
      </div>

    </div>
  </section>


  <!-- ══════════════════════════════════════════════
       PULL QUOTE
  ══════════════════════════════════════════════ -->
  <section class="ska-quote">
    <p class="ska-quote__text ska-reveal">
      We don't just offer accommodation — we curate escapes where every detail is crafted with unwavering commitment to quality, cleanliness, and bespoke service.
    </p>
    <p class="ska-quote__attr ska-reveal ska-reveal-delay-1">— The SKA Philosophy</p>
  </section>


  <!-- ══════════════════════════════════════════════
       SECOND STORY BLOCK
  ══════════════════════════════════════════════ -->
  <section class="ska-story">
    <div class="ska-story__wrap">

      <!-- Image first on desktop -->
      <div class="ska-story__img-col ska-reveal">
        <img src="assets/images/ska_munyonyo_home2.jpg" alt="SKA Munyonyo Lakeside" class="ska-story__img-main">
      </div>

      <!-- Text -->
      <div class="ska-story__text-col">
        <p class="ska-story__tag ska-reveal">Two Worlds, One Spirit</p>
        <h2 class="ska-story__heading ska-reveal ska-reveal-delay-1">
          Kampala's Most Intimate Escapes
        </h2>
        <p class="ska-story__body ska-reveal ska-reveal-delay-2">
          Each property is thoughtfully designed to deliver more than just accommodation. From elegantly appointed rooms with personalized touches to serene gardens framed by tropical beauty, every detail reflects our unwavering commitment to quality and bespoke service.
        </p>
        <p class="ska-story__body ska-reveal ska-reveal-delay-3">
          Feel the calming breeze of Lake Victoria in Munyonyo, or take in sweeping skyline views from Naguru. SKA offers boutique comfort, thoughtful innovation, and genuine hospitality — wherever you choose to stay.
        </p>
      </div>

    </div>
  </section>


  <!-- ══════════════════════════════════════════════
       VALUES / PILLARS
  ══════════════════════════════════════════════ -->
  <section class="ska-values">
    <div class="ska-values__wrap">

      <div class="ska-values__header ska-reveal">
        <p class="ska-values__eyebrow">What Guides Us</p>
        <h2 class="ska-values__heading">Our <em>Core Commitments</em></h2>
      </div>

      <div class="ska-values__grid">

        <div class="ska-value-card ska-reveal">
          <div class="ska-value-card__icon">
            <i class="fa-solid fa-star"></i>
          </div>
          <h3 class="ska-value-card__title">Boutique Excellence</h3>
          <p class="ska-value-card__body">Every room, every corner, every interaction is crafted to exceed expectations. We set the standard for intimate luxury in Uganda.</p>
        </div>

        <div class="ska-value-card ska-reveal ska-reveal-delay-2">
          <div class="ska-value-card__icon">
            <i class="fa-solid fa-heart"></i>
          </div>
          <h3 class="ska-value-card__title">Genuine Warmth</h3>
          <p class="ska-value-card__body">Hospitality here is not a service — it's a feeling. Our team goes beyond duty to ensure each guest feels truly at home.</p>
        </div>

        <div class="ska-value-card ska-reveal ska-reveal-delay-4">
          <div class="ska-value-card__icon">
            <i class="fa-solid fa-leaf"></i>
          </div>
          <h3 class="ska-value-card__title">Authentic Uganda</h3>
          <p class="ska-value-card__body">Rooted in local culture and natural beauty, our properties celebrate the spirit of Kampala — its warmth, energy, and breathtaking landscapes.</p>
        </div>

      </div>
    </div>
  </section>


  <!-- ══════════════════════════════════════════════
       LOCATIONS STRIP
  ══════════════════════════════════════════════ -->
  <section class="ska-locations">
    <div class="ska-locations__wrap">
      <p class="ska-locations__label">Our Locations</p>

      <div class="ska-locations__list">
        <div class="ska-location-item ska-reveal">
          <i class="fa-solid fa-location-dot"></i>
          <div class="ska-location-item__text">
            <div class="ska-location-item__name">Munyonyo</div>
            <div class="ska-location-item__sub">Lakeside, Kampala</div>
          </div>
        </div>

        <div class="ska-locations__divider"></div>

        <div class="ska-location-item ska-reveal ska-reveal-delay-2">
          <i class="fa-solid fa-location-dot"></i>
          <div class="ska-location-item__text">
            <div class="ska-location-item__name">Naguru</div>
            <div class="ska-location-item__sub">Hillside Escape, Kampala</div>
          </div>
        </div>
      </div>
    </div>
  </section>


<script>
  /* ── Scroll Reveal ─────────────────────────────── */
  (function () {
    const els = document.querySelectorAll('.ska-reveal');

    const observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('ska-visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    els.forEach(function (el) { observer.observe(el); });
  })();

  /* ── Animated stat counters ────────────────────── */
  (function () {
    const statNumbers = document.querySelectorAll('.ska-stat__number');

    const counterObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        const el = entry.target;
        const raw = el.textContent.trim();
        const num = parseInt(raw);
        if (isNaN(num)) { counterObserver.unobserve(el); return; }

        const suffix = raw.replace(String(num), '');
        let current = 0;
        const duration = 1200;
        const step = Math.ceil(num / (duration / 16));
        const timer = setInterval(function () {
          current = Math.min(current + step, num);
          el.textContent = current + suffix;
          if (current >= num) clearInterval(timer);
        }, 16);

        counterObserver.unobserve(el);
      });
    }, { threshold: 0.5 });

    statNumbers.forEach(function (el) { counterObserver.observe(el); });
  })();
</script>

<?php include 'includes/page-end.php'; ?>