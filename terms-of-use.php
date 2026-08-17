<?php
require_once 'config/cms.php';

$cmsPage = cms_page('terms-of-use');

$pageMeta = [
    'title'       => $cmsPage['page_title'] ?? 'Terms of Use | SKA The Boutique',
    'description' => $cmsPage['meta_description'] ?? 'Terms governing use of skaboutiquebnb.com.',
    'path'        => 'terms-of-use',
];
$pageStyles = ['assets/css/pages.css'];
$cmsSlug    = 'terms-of-use';
include 'includes/page-start.php';
?>

<?php include 'includes/cms-hero.php'; ?>

<section class="ska-page-body">
  <div class="container">
    <div class="ska-content-card">
      <?php if (!empty($cmsPage['body_html'])): ?>
        <?= $cmsPage['body_html'] ?>
      <?php else: ?>
        <h2>1. Acceptance</h2>
        <p>By accessing skaboutiquebnb.com, you agree to these Terms of Use.</p>
        <h2>2. Reservations</h2>
        <p>Online submissions constitute reservation requests. Confirmation is sent within 24 hours.</p>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php include 'includes/page-end.php'; ?>
