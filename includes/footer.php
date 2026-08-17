<?php require_once __DIR__ . '/../config/site.php'; ?>
<?php require_once __DIR__ . '/../config/cms.php'; ?>
<link rel="stylesheet" href="assets/css/footer.css">

<?php
$footerEmail   = cms_setting('site_email', SITE_EMAIL);
$footerPhone   = cms_setting('site_phone_main', '+256 200 98777');
$footerNaguru  = cms_setting('site_phone_naguru', '+256 741 186 891');
$footerInsta   = cms_setting('instagram_url', 'https://www.instagram.com/skanaguru/');
$footerFb      = cms_setting('facebook_url', 'https://www.facebook.com/skaboutiquebnb');
$footerWa      = cms_setting('whatsapp_url', 'https://wa.me/256741186891');
$footerPhoneHref = preg_replace('/\D+/', '', $footerPhone);
$footerNaguruHref = preg_replace('/\D+/', '', $footerNaguru);
?>

<footer class="ska-footer" role="contentinfo">

  <div class="ska-footer__main">

    <div class="ska-footer__brand">
      <a href="index.php" class="ska-footer__logo">SKA <span>The Boutique</span></a>
      <p class="ska-footer__tagline"><?= SITE_TAGLINE ?></p>
      <p class="ska-footer__brand-desc">
        A distinguished collection of elegant retreats redefining hospitality in Uganda — where boutique charm meets genuine warmth.
      </p>
      <div class="ska-footer__contacts">
        <a href="mailto:<?= htmlspecialchars($footerEmail) ?>" class="ska-footer__contact-item">
          <i class="fa-regular fa-envelope"></i><?= htmlspecialchars($footerEmail) ?>
        </a>
        <a href="tel:<?= htmlspecialchars($footerPhoneHref) ?>" class="ska-footer__contact-item">
          <i class="fa-solid fa-phone"></i><?= htmlspecialchars($footerPhone) ?>
        </a>
        <a href="tel:<?= htmlspecialchars($footerNaguruHref) ?>" class="ska-footer__contact-item">
          <i class="fa-solid fa-phone"></i><?= htmlspecialchars($footerNaguru) ?>
        </a>
        <span class="ska-footer__contact-item">
          <i class="fa-solid fa-location-dot"></i>Naguru &amp; Munyonyo, Kampala
        </span>
      </div>
    </div>

    <div>
      <h4 class="ska-footer__col-title">Explore</h4>
      <ul class="ska-footer__nav">
        <li><a href="about-us.php">Overview</a></li>
        <li><a href="naguru.php#rooms">Rooms &amp; Suites</a></li>
        <li><a href="naguru.php#gallery">Photo Gallery</a></li>
        <li><a href="naguru.php#services">Dining</a></li>
        <li><a href="naguru.php#experiences">Experiences</a></li>
        <li><a href="meetings-events.php">Meetings &amp; Events</a></li>
      </ul>
    </div>

    <div>
      <h4 class="ska-footer__col-title">Our Properties</h4>
      <ul class="ska-footer__nav">
        <li><a href="munyonyo.php">SKA Munyonyo</a></li>
        <li><a href="naguru.php">SKA Naguru — Hillside</a></li>
        <li><a href="index.php#book-search">Book a Room</a></li>
        <li><a href="offers.php">Special Offers</a></li>
        <li><a href="loyalty.php">SKA Rewards</a></li>
      </ul>
    </div>

    <div>
      <h4 class="ska-footer__col-title">Follow Us</h4>
      <div class="ska-footer__socials">
        <a href="<?= htmlspecialchars($footerInsta) ?>" target="_blank" rel="noopener noreferrer" class="ska-footer__social-link">
          <span class="ska-footer__social-icon"><i class="fab fa-instagram"></i></span>Instagram
        </a>
        <a href="<?= htmlspecialchars($footerFb) ?>" target="_blank" rel="noopener noreferrer" class="ska-footer__social-link">
          <span class="ska-footer__social-icon"><i class="fab fa-facebook-f"></i></span>Facebook
        </a>
        <a href="<?= htmlspecialchars($footerWa) ?>" target="_blank" rel="noopener noreferrer" class="ska-footer__social-link">
          <span class="ska-footer__social-icon"><i class="fab fa-whatsapp"></i></span>WhatsApp
        </a>
      </div>
    </div>

  </div>

  <hr class="ska-footer__divider">

  <div class="ska-footer__bottom">
    <p class="ska-footer__copy">
      &copy; <span id="skaYear"></span> <strong><?= SITE_NAME ?></strong>. All rights reserved.
    </p>
    <div class="ska-footer__legal">
      <a href="privacy-policy.php">Privacy Policy</a>
      <span class="ska-footer__legal-dot"></span>
      <a href="terms-of-use.php">Terms of Use</a>
      <span class="ska-footer__legal-dot"></span>
      <a href="cookie-policy.php">Cookie Policy</a>
    </div>
    <p class="ska-footer__credit">
      Crafted by <a href="<?= BUILDER_URL ?>" target="_blank" rel="noopener noreferrer"><?= BUILDER_NAME ?></a>
      — hospitality technology &amp; digital experience
    </p>
  </div>

</footer>

<script>document.getElementById('skaYear').textContent = new Date().getFullYear();</script>
