<?php
require_once 'config/cms.php';

$cmsPage = cms_page('meetings-events');
$blocks  = cms_blocks('meetings-events');

$pageMeta = [
    'title'       => $cmsPage['page_title'] ?? 'Meetings & Events | SKA The Boutique Kampala',
    'description' => $cmsPage['meta_description'] ?? 'Intimate meetings, weddings and social events at SKA The Boutique.',
    'path'        => 'meetings-events',
    'image'       => $cmsPage['hero_image'] ?? 'assets/images/ska_munyonyo_home2.jpg',
];
$pageStyles = ['assets/css/pages.css'];
$navActive  = 'events';
$cmsSlug    = 'meetings-events';
include 'includes/page-start.php';
?>

<?php /* Static-friendly content is also built into docs via tools/partials/pages */ ?>
<?php include 'includes/cms-hero.php'; ?>

<section class="ska-page-body">
  <div class="container">

    <?php if (!empty($cmsPage['body_html'])): ?>
    <div class="ska-content-card" id="overview"><?= $cmsPage['body_html'] ?></div>
    <?php else: ?>
    <div class="ska-content-card" id="overview">
      <h2>Events Overview</h2>
      <p>SKA The Boutique provides thoughtfully designed spaces for gatherings of up to 40 guests. Our team handles every detail — from AV setup and catering to guest room blocks.</p>
    </div>
    <?php endif; ?>

    <?php if (!empty($blocks)): ?>
    <div class="ska-grid-3">
      <?php foreach ($blocks as $block): ?>
      <article class="ska-feature-card" id="<?= htmlspecialchars($block['block_key']) ?>">
        <div class="ska-feature-card__img" style="background-image:url('<?= htmlspecialchars($block['image'] ?: 'assets/images/ska_naguru_home.jpeg') ?>')"></div>
        <div class="ska-feature-card__body">
          <?php if ($block['tag']): ?><p class="ska-feature-card__tag"><?= htmlspecialchars($block['tag']) ?></p><?php endif; ?>
          <h2 class="ska-feature-card__title"><?= htmlspecialchars($block['title']) ?></h2>
          <p class="ska-feature-card__text"><?= htmlspecialchars($block['body']) ?></p>
          <?php if ($block['link_url']): ?>
          <a href="<?= htmlspecialchars($block['link_url']) ?>" class="ska-btn-gold"><?= htmlspecialchars($block['link_label'] ?: 'Enquire') ?></a>
          <?php endif; ?>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="ska-content-card" id="groups" style="margin-top:40px">
      <h2>Group Bookings</h2>
      <p>Travelling with a group? We offer block reservations across both properties with coordinated check-in, group dining, and transport arrangements.</p>
      <ul>
        <li>Room blocks from 5 rooms</li>
        <li>Dedicated group coordinator</li>
        <li>Custom catering menus</li>
        <li>Airport transfer coordination</li>
      </ul>
      <a href="contact.php?subject=Group+Booking" class="ska-btn-gold">Request Group Proposal</a>
    </div>

  </div>
</section>

<?php include 'includes/page-end.php'; ?>
