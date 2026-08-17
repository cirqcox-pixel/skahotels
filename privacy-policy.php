<?php
require_once 'config/cms.php';

$cmsPage = cms_page('privacy-policy');

$pageMeta = [
    'title'       => $cmsPage['page_title'] ?? 'Privacy Policy | SKA The Boutique',
    'description' => $cmsPage['meta_description'] ?? 'Privacy Policy for SKA The Boutique.',
    'path'        => 'privacy-policy',
];
$pageStyles = ['assets/css/pages.css'];
$cmsSlug    = 'privacy-policy';
include 'includes/page-start.php';
?>

<?php include 'includes/cms-hero.php'; ?>

<section class="ska-page-body">
  <div class="container">
    <div class="ska-content-card">
      <?php if (!empty($cmsPage['body_html'])): ?>
        <?= $cmsPage['body_html'] ?>
      <?php else: ?>
        <h2>1. Introduction</h2>
        <p>SKA The Boutique operates skaboutiquebnb.com. This policy explains how we collect, use, and safeguard your personal information.</p>
        <h3>2. Contact</h3>
        <p>Questions: <a href="mailto:<?= htmlspecialchars(cms_setting('site_email', SITE_EMAIL)) ?>"><?= htmlspecialchars(cms_setting('site_email', SITE_EMAIL)) ?></a></p>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php include 'includes/page-end.php'; ?>
