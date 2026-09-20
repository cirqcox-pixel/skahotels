<?php
require_once 'config/cms.php';

$packages = cms_packages();

$pageMeta = [
    'title'       => 'Packages | SKA The Boutique',
    'description' => 'Wedding, conference and group packages at SKA Naguru and Munyonyo. Book a package and our team will confirm by email.',
    'path'        => 'packages',
    'image'       => 'assets/images/packages/wedding-package.jpg',
];
$pageStyles = ['assets/css/pages.css'];
$navActive  = 'packages';
include 'includes/page-start.php';
?>

<section class="ska-page-hero">
  <div class="ska-page-hero__bg" style="background-image:url('assets/images/packages/conference-package.jpg');opacity:.45"></div>
  <div class="container">
    <p class="ska-page-hero__eyebrow">Weddings &amp; Conferences</p>
    <h1 class="ska-page-hero__title">Packages, not just rooms</h1>
    <p class="ska-page-hero__sub">Choose a property package, pick your dates, and send a request. The Naguru or Munyonyo team is notified instantly.</p>
  </div>
</section>

<section class="ska-page-body">
  <div class="container" style="max-width:1140px">
    <div class="ska-grid-3" id="packagesGrid">
      <?php if (empty($packages)): ?>
      <div class="ska-content-card">
        <h2>Packages coming soon</h2>
        <p>Ask us about wedding buyouts and conference days at either property.</p>
        <a href="contact.php?subject=Package+Enquiry" class="ska-btn-gold">Enquire</a>
      </div>
      <?php else: ?>
      <?php foreach ($packages as $pkg):
        $opts = cms_package_options($pkg);
        $from = $pkg['currency'] . ' ' . number_format((float)$pkg['price'], 0);
        $slug = strtolower($pkg['branch'] ?? 'Naguru') === 'munyonyo' ? 'munyonyo.php' : 'naguru.php';
        $book = $pkg['booking_url'] ?: ($slug . '?package=' . (int)$pkg['id'] . '#book');
      ?>
      <article class="ska-feature-card">
        <div class="ska-feature-card__img" style="background-image:url('<?= htmlspecialchars($pkg['image'] ?: 'assets/images/ska_naguru_home.jpeg') ?>')"></div>
        <div class="ska-feature-card__body">
          <?php if (!empty($pkg['tag'])): ?>
          <p class="ska-feature-card__tag"><?= htmlspecialchars($pkg['tag']) ?> · <?= htmlspecialchars($pkg['branch']) ?></p>
          <?php endif; ?>
          <h2 class="ska-feature-card__title"><?= htmlspecialchars($pkg['title']) ?></h2>
          <p class="ska-feature-card__text"><?= htmlspecialchars($pkg['description']) ?></p>
          <p class="ska-feature-card__text"><strong>From <?= htmlspecialchars($from) ?></strong></p>
          <?php if (!empty($pkg['inclusions'])): ?>
          <ul class="ska-package-inc">
            <?php foreach (preg_split('/\r\n|\r|\n/', $pkg['inclusions']) as $line):
              if (trim($line) === '') continue; ?>
            <li><?= htmlspecialchars($line) ?></li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>
          <?php if (count($opts) > 1): ?>
          <p class="ska-feature-card__text" style="font-size:13px;color:#6b6b6b">
            <?= count($opts) ?> pricing options — choose when you book
          </p>
          <?php endif; ?>
          <a href="<?= htmlspecialchars($book) ?>" class="ska-btn-gold">
            Book this package <i class="fa-solid fa-arrow-right"></i>
          </a>
        </div>
      </article>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="ska-cta-band">
  <div class="container">
    <h2>Need a custom group rate?</h2>
    <p>Tell us the date and headcount — we will shape a package around your agenda.</p>
    <div class="ska-cta-band__btns">
      <a href="contact.php?subject=Custom+Package" class="ska-btn-gold">Talk to us</a>
      <a href="offers.php" class="ska-btn-outline" style="border-color:#fff;color:#fff">View offers</a>
    </div>
  </div>
</section>

<?php include 'includes/page-end.php'; ?>
