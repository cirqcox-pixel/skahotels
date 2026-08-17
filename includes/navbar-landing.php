<?php
/**
 * Landing page navigation — include after page-start.php opens <body>
 */
$navActive = $navActive ?? '';
?>
<nav class="ska-navbar" id="skaNavbar" aria-label="Main navigation">
  <div class="ska-nav-inner">

    <div class="ska-nav-left">
      <a href="index.php" class="ska-logo" aria-label="SKA The Boutique — Home">
        <img src="assets/images/ska_logo1.png" alt="SKA The Boutique">
      </a>
    </div>

    <ul class="ska-nav-center">
      <li><a href="index.php#book-search" class="<?= $navActive === 'book' ? 'active' : '' ?>">Book</a></li>
      <li><a href="offers.php" class="<?= $navActive === 'offers' ? 'active' : '' ?>">Offers</a></li>
      <li><a href="index.php#properties" class="<?= $navActive === 'properties' ? 'active' : '' ?>">Properties</a></li>
      <li><a href="about-us.php" class="<?= $navActive === 'about' ? 'active' : '' ?>">About</a></li>
      <li><a href="meetings-events.php" class="<?= $navActive === 'events' ? 'active' : '' ?>">Meetings &amp; Events</a></li>
      <li><a href="contact.php" class="<?= $navActive === 'contact' ? 'active' : '' ?>">Contact</a></li>
    </ul>

    <div class="ska-nav-right">
      <a href="help.php" class="ska-hide-tablet ska-hide-mobile"><i class="fa-regular fa-circle-question"></i> Help</a>
      <a href="loyalty.php" class="ska-login-btn"><i class="fa-regular fa-gem"></i> SKA Rewards</a>
      <button class="ska-hamburger" id="skaHamburger" aria-label="Toggle menu" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>
    </div>

  </div>
</nav>

<div class="ska-mobile-menu" id="skaMobileMenu" aria-hidden="true">
  <div class="ska-mobile-inner">
    <ul class="ska-mobile-nav">
      <li><a href="index.php#book-search">Book</a></li>
      <li><a href="offers.php">Offers</a></li>
      <li><a href="index.php#properties">Properties</a></li>
      <li><a href="about-us.php">About</a></li>
      <li><a href="meetings-events.php">Meetings &amp; Events</a></li>
      <li><a href="contact.php">Contact</a></li>
    </ul>
    <div class="ska-mobile-utils">
      <a href="help.php"><i class="fa-regular fa-circle-question"></i> Help</a>
      <a href="naguru.php"><i class="fa-solid fa-location-dot"></i> Naguru</a>
      <a href="munyonyo.php"><i class="fa-solid fa-location-dot"></i> Munyonyo</a>
    </div>
    <a href="loyalty.php" class="ska-mobile-cta"><i class="fa-regular fa-gem"></i> SKA Rewards</a>
  </div>
</div>

<script>
(function () {
  const hamburger  = document.getElementById('skaHamburger');
  const mobileMenu = document.getElementById('skaMobileMenu');
  const navbar     = document.getElementById('skaNavbar');
  if (!hamburger || !mobileMenu) return;

  function closeMenu() {
    mobileMenu.classList.remove('open');
    hamburger.classList.remove('open');
    hamburger.setAttribute('aria-expanded', 'false');
    mobileMenu.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  hamburger.addEventListener('click', function () {
    const isOpen = mobileMenu.classList.toggle('open');
    hamburger.classList.toggle('open', isOpen);
    hamburger.setAttribute('aria-expanded', isOpen);
    mobileMenu.setAttribute('aria-hidden', !isOpen);
    document.body.style.overflow = isOpen ? 'hidden' : '';
  });

  mobileMenu.querySelectorAll('a').forEach(function (link) {
    link.addEventListener('click', closeMenu);
  });

  document.addEventListener('click', function (e) {
    if (!navbar.contains(e.target) && !mobileMenu.contains(e.target)) closeMenu();
  });

  window.addEventListener('scroll', function () {
    navbar.classList.toggle('scrolled', window.scrollY > 20);
  }, { passive: true });
})();
</script>
