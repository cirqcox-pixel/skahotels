<?php
/**
 * includes/topbar.php
 */
$pageTitle      = $pageTitle      ?? 'Dashboard';
$pageBreadcrumb = $pageBreadcrumb ?? '';
$topbarAction   = $topbarAction   ?? null;

$topbarUnreadInquiries = 0;
if (file_exists(__DIR__ . '/../../config/cms.php')) {
    require_once __DIR__ . '/../../config/cms.php';
    $inqRes = cms_conn()->query("SELECT COUNT(*) AS n FROM inquiries WHERE is_read = 0");
    if ($inqRes) $topbarUnreadInquiries = (int)$inqRes->fetch_assoc()['n'];
}
?>

<!-- ══════════════════════════════════════════════════
     TOPBAR
══════════════════════════════════════════════════ -->
<header class="ska-topbar">
  <div class="d-flex align-items-center gap-3">
    <button class="ska-hamburger" id="skaHamburger" aria-label="Toggle menu">
      <span></span><span></span><span></span>
    </button>
    <div class="ska-topbar__left">
      <div class="ska-topbar__title"><?= htmlspecialchars($pageTitle) ?></div>
      <?php if ($pageBreadcrumb): ?>
        <div class="ska-topbar__breadcrumb"><?= htmlspecialchars($pageBreadcrumb) ?></div>
      <?php endif; ?>
    </div>
  </div>

  <div class="ska-topbar__right">
    <a href="inquiries.php?filter=unread" class="ska-topbar__icon-btn" title="<?= $topbarUnreadInquiries ?> unread inquiries">
      <i class="fa-regular fa-envelope"></i>
      <?php if ($topbarUnreadInquiries > 0): ?><span class="ska-topbar__notif-dot"></span><?php endif; ?>
    </a>
    <a href="bookings.php?status=pending" class="ska-topbar__icon-btn" title="Pending bookings">
      <i class="fa-regular fa-bell"></i>
      <span class="ska-topbar__notif-dot"></span>
    </a>
    <a href="../help.php" target="_blank" class="ska-topbar__icon-btn" title="Help">
      <i class="fa-regular fa-circle-question"></i>
    </a>
    <a href="../index.php" target="_blank" class="ska-topbar__icon-btn" title="View website">
      <i class="fa-solid fa-arrow-up-right-from-square"></i>
    </a>
    <?php if ($topbarAction): ?>
      <a href="<?= htmlspecialchars($topbarAction['href']) ?>"
         class="ska-btn ska-btn--gold"
         style="font-size:13px; padding: 8px 18px;">
        <i class="fa-solid <?= htmlspecialchars($topbarAction['icon']) ?>"></i>
        <?= htmlspecialchars($topbarAction['label']) ?>
      </a>
    <?php endif; ?>
  </div>
</header>