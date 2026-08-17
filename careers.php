<?php
require_once 'config/cms.php';

$cmsPage = cms_page('careers');
$blocks  = cms_blocks('careers');

$pageMeta = [
    'title'       => $cmsPage['page_title'] ?? 'Careers at SKA The Boutique',
    'description' => $cmsPage['meta_description'] ?? 'Join SKA The Boutique — hospitality careers in Kampala.',
    'path'        => 'careers',
    'image'       => $cmsPage['hero_image'] ?? 'assets/images/ska_art_home.jpg',
];
$pageStyles = ['assets/css/pages.css'];
$cmsSlug    = 'careers';
include 'includes/page-start.php';
?>

<?php include 'includes/cms-hero.php'; ?>

<section class="ska-page-body">
  <div class="container">
    <?php if (!empty($cmsPage['body_html'])): ?>
    <div class="ska-content-card"><?= $cmsPage['body_html'] ?></div>
    <?php else: ?>
    <div class="ska-content-card">
      <h2>Why SKA?</h2>
      <p>At SKA The Boutique, every team member shapes the guest experience. We invest in training, fair compensation, and a culture where hospitality is a craft — not a checklist.</p>
    </div>
    <?php endif; ?>

    <?php if (!empty($blocks)): ?>
    <div class="ska-grid-3">
      <?php foreach ($blocks as $block): ?>
      <div class="ska-content-card">
        <?php if ($block['title']): ?><h2><?= htmlspecialchars($block['title']) ?></h2><?php endif; ?>
        <?php if ($block['body']): ?><p><?= nl2br(htmlspecialchars($block['body'])) ?></p><?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="ska-content-card" style="margin-top:40px">
      <h2>Apply Now</h2>
      <p>Send your CV and a brief cover letter telling us why SKA resonates with you.</p>
      <a href="mailto:<?= htmlspecialchars(cms_setting('site_email', SITE_EMAIL)) ?>?subject=Career%20Application" class="ska-btn-gold"><?= htmlspecialchars(cms_setting('site_email', SITE_EMAIL)) ?></a>
      &nbsp;
      <a href="contact.php?subject=Career+Application" class="ska-btn-outline">Apply via Form</a>
    </div>
  </div>
</section>

<?php include 'includes/page-end.php'; ?>
