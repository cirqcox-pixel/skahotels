<?php
require_once 'config/cms.php';

$cmsPage = cms_page('loyalty');
$blocks  = cms_blocks('loyalty');

$pageMeta = [
    'title'       => $cmsPage['page_title'] ?? 'SKA Rewards | Loyalty Programme',
    'description' => $cmsPage['meta_description'] ?? 'Join SKA Rewards for member rates and exclusive offers.',
    'path'        => 'loyalty',
    'image'       => $cmsPage['hero_image'] ?? 'assets/images/ska_art_home.jpg',
];
$pageStyles = ['assets/css/pages.css'];
$cmsSlug    = 'loyalty';
include 'includes/page-start.php';
?>

<?php include 'includes/cms-hero.php'; ?>

<section class="ska-page-body">
  <div class="container">
    <?php if (!empty($blocks)): ?>
    <div class="ska-grid-3">
      <?php
      $icons = ['fa-gem', 'fa-bell', 'fa-champagne-glasses'];
      foreach ($blocks as $i => $block):
      ?>
      <div class="ska-content-card text-center">
        <i class="fa-solid <?= $icons[$i] ?? 'fa-star' ?> fa-2x mb-3" style="color:#c9a96e"></i>
        <?php if ($block['title']): ?><h2><?= htmlspecialchars($block['title']) ?></h2><?php endif; ?>
        <?php if ($block['body']): ?><p><?= nl2br(htmlspecialchars($block['body'])) ?></p><?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($cmsPage['body_html'])): ?>
    <div class="ska-content-card" style="margin-top:40px"><?= $cmsPage['body_html'] ?></div>
    <?php else: ?>
    <div class="ska-content-card" style="margin-top:40px">
      <h2>How It Works</h2>
      <ol>
        <li>Complete your first direct booking at SKA Naguru or Munyonyo</li>
        <li>Receive your SKA Rewards welcome email with your member number</li>
        <li>Earn recognition on every subsequent stay — benefits grow with each visit</li>
      </ol>
      <p>Membership is complimentary for all guests who book direct.</p>
      <a href="index.php#book-search" class="ska-btn-gold">Book Your First Stay</a>
      &nbsp;
      <a href="contact.php?subject=SKA+Rewards" class="ska-btn-outline">Already a Guest? Enrol Me</a>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php include 'includes/page-end.php'; ?>
