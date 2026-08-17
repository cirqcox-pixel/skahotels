<?php
require_once 'config/cms.php';

$cmsPage    = cms_page('offers');
$promotions = cms_promotions();
$blocks     = cms_blocks('offers');

$pageMeta = [
    'title'       => $cmsPage['page_title'] ?? 'Special Offers & Packages | SKA The Boutique',
    'description' => $cmsPage['meta_description'] ?? 'Exclusive direct-booking offers at SKA The Boutique Naguru and Munyonyo, Kampala.',
    'path'        => 'offers',
    'image'       => $cmsPage['hero_image'] ?? 'assets/images/ska_naguru_home.jpeg',
    'schema'      => [
        '@context' => 'https://schema.org',
        '@type'    => 'OfferCatalog',
        'name'     => 'SKA The Boutique Special Offers',
        'url'      => 'https://www.skaboutiquebnb.com/offers',
    ],
];
$pageStyles = ['assets/css/pages.css'];
$navActive  = 'offers';
$cmsSlug    = 'offers';
include 'includes/page-start.php';
?>

<?php include 'includes/cms-hero.php'; ?>

<section class="ska-page-body">
  <div class="container">

    <?php if (!empty($promotions)): ?>
    <div class="ska-grid-3">
      <?php foreach ($promotions as $promo): ?>
      <article class="ska-feature-card">
        <div class="ska-feature-card__img" style="background-image:url('<?= htmlspecialchars($promo['image'] ?: 'assets/images/ska_naguru_home.jpeg') ?>')"></div>
        <div class="ska-feature-card__body">
          <?php if (!empty($promo['tag'])): ?>
          <p class="ska-feature-card__tag"><?= htmlspecialchars($promo['tag']) ?></p>
          <?php endif; ?>
          <h2 class="ska-feature-card__title"><?= htmlspecialchars($promo['title']) ?></h2>
          <p class="ska-feature-card__text"><?= htmlspecialchars($promo['description']) ?></p>
          <a href="<?= htmlspecialchars($promo['booking_url'] ?: 'index.php#book-search') ?>" class="ska-btn-gold">
            Book Now <i class="fa-solid fa-arrow-right"></i>
          </a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php foreach ($blocks as $block): ?>
    <div class="ska-content-card" style="margin-top:<?= !empty($promotions) ? '48px' : '0' ?>">
      <?php if ($block['title']): ?><h2><?= htmlspecialchars($block['title']) ?></h2><?php endif; ?>
      <?php if ($block['body']): ?><p><?= nl2br(htmlspecialchars($block['body'])) ?></p><?php endif; ?>
      <?php if ($block['link_url']): ?>
      <a href="<?= htmlspecialchars($block['link_url']) ?>" class="ska-btn-gold"><?= htmlspecialchars($block['link_label'] ?: 'Learn More') ?></a>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <?php if (!empty($cmsPage['body_html'])): ?>
    <div class="ska-content-card"><?= $cmsPage['body_html'] ?></div>
    <?php endif; ?>

  </div>
</section>

<section class="ska-cta-band">
  <div class="container">
    <h2>Ready to experience SKA?</h2>
    <p>Two distinctive properties. One standard of excellence.</p>
    <div class="ska-cta-band__btns">
      <a href="naguru.php" class="ska-btn-gold">Explore Naguru</a>
      <a href="munyonyo.php" class="ska-btn-outline" style="border-color:#fff;color:#fff">Explore Munyonyo</a>
    </div>
  </div>
</section>

<?php include 'includes/page-end.php'; ?>
