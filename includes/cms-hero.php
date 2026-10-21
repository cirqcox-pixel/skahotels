<?php
/**
 * CMS page hero — set $cmsSlug before include
 */
$cmsSlug = $cmsSlug ?? '';
require_once __DIR__ . '/../config/cms.php';
$pg = cms_page($cmsSlug);
if (!$pg) return;
$heroSettingKey = str_replace('-', '_', $cmsSlug) . '_hero_image';
$heroImage = cms_setting($heroSettingKey, $pg['hero_image'] ?? '');
?>
<section class="ska-page-hero" <?= !empty($heroImage) ? 'style="padding-top:0"' : '' ?>>
  <?php if (!empty($heroImage)): ?>
  <div class="ska-page-hero__bg" style="background-image:url('<?= htmlspecialchars($heroImage) ?>')"></div>
  <?php endif; ?>
  <div class="container">
    <?php if ($pg['hero_eyebrow']): ?><p class="ska-page-hero__eyebrow"><?= htmlspecialchars($pg['hero_eyebrow']) ?></p><?php endif; ?>
    <?php if ($pg['hero_title']): ?><h1 class="ska-page-hero__title"><?= htmlspecialchars($pg['hero_title']) ?></h1><?php endif; ?>
    <?php if ($pg['hero_subtitle']): ?><p class="ska-page-hero__sub"><?= htmlspecialchars($pg['hero_subtitle']) ?></p><?php endif; ?>
  </div>
</section>
