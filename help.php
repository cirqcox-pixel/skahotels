<?php
require_once 'config/cms.php';

$cmsPage = cms_page('help');
$blocks  = cms_blocks('help');

$pageMeta = [
    'title'       => $cmsPage['page_title'] ?? 'Help Centre | SKA The Boutique',
    'description' => $cmsPage['meta_description'] ?? 'Answers to common questions about booking and stays at SKA The Boutique.',
    'path'        => 'help',
];
$pageStyles = ['assets/css/pages.css'];
$cmsSlug    = 'help';
$navActive  = 'help';
include 'includes/page-start.php';
?>

<?php include 'includes/cms-hero.php'; ?>

<section class="ska-page-body">
  <div class="container">
    <?php if (!empty($blocks)): ?>
    <div class="ska-content-card">
      <h2>Booking &amp; Reservations</h2>
      <?php foreach ($blocks as $block): ?>
      <?php if ($block['title']): ?><h3><?= htmlspecialchars($block['title']) ?></h3><?php endif; ?>
      <?php if ($block['body']): ?><p><?= nl2br(htmlspecialchars($block['body'])) ?></p><?php endif; ?>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($cmsPage['body_html'])): ?>
    <div class="ska-content-card"><?= $cmsPage['body_html'] ?></div>
    <?php else: ?>
    <div class="ska-content-card">
      <h2>Check-in &amp; Stay</h2>
      <h3>What are check-in and check-out times?</h3>
      <p>Check-in from 2:00 PM · Check-out by 12:00 PM. Early check-in and late checkout subject to availability.</p>
      <h3>What's included?</h3>
      <p>Complimentary breakfast, high-speed Wi-Fi, daily housekeeping, and 24-hour front desk assistance at both properties.</p>
      <h3>Do you offer airport transfers?</h3>
      <p>Yes — arrange transfers when booking or contact us at least 24 hours before arrival.</p>
    </div>
    <?php endif; ?>

    <div class="ska-content-card">
      <h2>Still Need Help?</h2>
      <p>Our reservations team is available 7 days a week.</p>
      <a href="contact.php" class="ska-btn-gold">Contact Us</a>
      &nbsp;
      <a href="tel:<?= preg_replace('/\D+/', '', cms_setting('site_phone_naguru', '+256741186891')) ?>" class="ska-btn-outline">Call Naguru</a>
    </div>
  </div>
</section>

<?php include 'includes/page-end.php'; ?>
