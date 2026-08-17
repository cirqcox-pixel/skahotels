<?php
require_once 'config/cms.php';

$cmsPage = cms_page('cookie-policy');

$pageMeta = [
    'title'       => $cmsPage['page_title'] ?? 'Cookie Policy | SKA The Boutique',
    'description' => $cmsPage['meta_description'] ?? 'Cookie Policy for skaboutiquebnb.com.',
    'path'        => 'cookie-policy',
];
$pageStyles = ['assets/css/pages.css'];
$cmsSlug    = 'cookie-policy';
include 'includes/page-start.php';
?>

<?php include 'includes/cms-hero.php'; ?>

<section class="ska-page-body">
  <div class="container">
    <div class="ska-content-card">
      <?php if (!empty($cmsPage['body_html'])): ?>
        <?= $cmsPage['body_html'] ?>
      <?php else: ?>
        <h2>What Are Cookies?</h2>
        <p>Cookies are small text files stored on your device when you visit a website.</p>
        <h3>Cookies We Use</h3>
        <ul>
          <li><strong>Essential cookies</strong> — required for the website to function</li>
          <li><strong>Analytics cookies</strong> — help us understand visitor behaviour</li>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php include 'includes/page-end.php'; ?>
