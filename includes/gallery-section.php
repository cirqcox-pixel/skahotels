<?php
/**
 * Property photo gallery section — expects $galleryBranch set
 */
$galleryBranch = $galleryBranch ?? 'Naguru';
require_once __DIR__ . '/../config/cms.php';
$galleryImages = cms_gallery($galleryBranch);
if (empty($galleryImages)) return;
?>
<section class="ska-gallery-section" id="gallery" data-branch="<?= htmlspecialchars($galleryBranch) ?>">
  <div class="container">
    <div class="ska-gallery-header">
      <p class="ska-gallery-eyebrow">PHOTO GALLERY</p>
      <h2 class="ska-gallery-title">Explore <?= htmlspecialchars($galleryBranch) ?></h2>
    </div>
    <div class="ska-gallery-grid">
      <?php foreach ($galleryImages as $i => $img): ?>
      <a href="<?= htmlspecialchars($img['path']) ?>" class="ska-gallery-item" data-gallery-index="<?= $i ?>">
        <img src="<?= htmlspecialchars($img['path']) ?>" alt="<?= htmlspecialchars($img['caption'] ?: 'SKA ' . $galleryBranch) ?>" loading="lazy">
        <?php if (!empty($img['caption'])): ?>
        <span class="ska-gallery-caption"><?= htmlspecialchars($img['caption']) ?></span>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<style>
.ska-gallery-section { padding: 72px 0; background: #f9f7f3; }
.ska-gallery-header { text-align: center; margin-bottom: 40px; }
.ska-gallery-eyebrow { font-size: 11px; letter-spacing: 0.2em; color: #c9a96e; font-weight: 600; margin-bottom: 8px; }
.ska-gallery-title { font-family: 'Cormorant Garamond', serif; font-size: 2.2rem; font-weight: 300; color: #1a1a1a; margin: 0; }
.ska-gallery-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
.ska-gallery-item { position: relative; aspect-ratio: 4/3; overflow: hidden; border-radius: 8px; display: block; }
.ska-gallery-item img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease; }
.ska-gallery-item:hover img { transform: scale(1.06); }
.ska-gallery-caption { position: absolute; bottom: 0; left: 0; right: 0; padding: 12px; background: linear-gradient(transparent, rgba(0,0,0,0.7)); color: #fff; font-size: 12px; }
@media (max-width: 992px) { .ska-gallery-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 576px) { .ska-gallery-grid { grid-template-columns: 1fr; } }
</style>
