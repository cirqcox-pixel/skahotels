<?php
/**
 * includes/sidebar.php
 * ─────────────────────────────────────────────
 * Reusable sidebar for the SKA Admin Portal.
 *
 * Usage:
 *   <?php include '../includes/sidebar.php'; ?>
 *
 * Expects $_SESSION['admin'] to be set by the
 * calling page before this file is included.
 *
 * Pass $activePage to highlight the correct link:
 *   $activePage = 'dashboard'; // or 'rooms', 'promotions', etc.
 * ─────────────────────────────────────────────
 */
$activePage = $activePage ?? 'dashboard';
$adminRole = (string) ($_SESSION['admin_role'] ?? 'super_admin');

function skaNavActive(string $page, string $current): string {
    return $page === $current ? ' active' : '';
}

function skaNavVisible(string $page): bool {
    return !function_exists('ska_admin_can') || ska_admin_can($page);
}
?>

<!-- ── Overlay (mobile) ───────────────────────────── -->
<div class="ska-overlay" id="skaOverlay"></div>

<!-- ══════════════════════════════════════════════════
     SIDEBAR
══════════════════════════════════════════════════ -->
<aside class="ska-sidebar" id="skaSidebar">

  <div class="ska-sidebar__brand">
    <div class="ska-sidebar__brand-name">SKA <span>The Boutique</span></div>
    <div class="ska-sidebar__brand-sub">Admin Portal</div>
  </div>

  <nav class="ska-sidebar__nav">

    <div class="ska-nav-label">Main</div>
    <a href="../index.php" class="ska-nav-link" target="_blank" rel="noopener">
      <i class="fa-solid fa-arrow-up-right-from-square"></i> View Website
    </a>
    <?php if (skaNavVisible('dashboard')): ?>
    <a href="dashboard.php" class="ska-nav-link<?= skaNavActive('dashboard', $activePage) ?>">
      <i class="fa-solid fa-gauge-high"></i> Dashboard
    </a>
    <?php endif; ?>

    <div class="ska-nav-label">Property</div>
    <?php if (skaNavVisible('rooms')): ?>
    <a href="rooms.php" class="ska-nav-link<?= skaNavActive('rooms', $activePage) ?>">
      <i class="fa-solid fa-bed"></i> Rooms
    </a>
    <?php endif; ?>
    <?php if (skaNavVisible('promotions')): ?>
    <a href="promotions.php" class="ska-nav-link<?= skaNavActive('promotions', $activePage) ?>">
      <i class="fa-solid fa-tag"></i> Promotions
    </a>
    <?php endif; ?>
    <?php if (skaNavVisible('packages')): ?>
    <a href="packages.php" class="ska-nav-link<?= skaNavActive('packages', $activePage) ?>">
      <i class="fa-solid fa-gift"></i> Packages
    </a>
    <?php endif; ?>
    <?php if (skaNavVisible('bookings')): ?>
    <a href="bookings.php" class="ska-nav-link<?= skaNavActive('bookings', $activePage) ?>">
      <i class="fa-solid fa-calendar-days"></i> Bookings
    </a>
    <?php endif; ?>

    <div class="ska-nav-label">Content</div>
    <?php if (skaNavVisible('inquiries')): ?>
    <a href="inquiries.php" class="ska-nav-link<?= skaNavActive('inquiries', $activePage) ?>">
      <i class="fa-solid fa-envelope"></i> Inquiries
    </a>
    <?php endif; ?>
    <?php if (skaNavVisible('pages')): ?>
    <a href="pages.php" class="ska-nav-link<?= skaNavActive('pages', $activePage) ?>">
      <i class="fa-solid fa-file-lines"></i> Pages & Content
    </a>
    <?php endif; ?>
    <?php if (skaNavVisible('gallery')): ?>
    <a href="gallery.php" class="ska-nav-link<?= skaNavActive('gallery', $activePage) ?>">
      <i class="fa-solid fa-images"></i> Gallery
    </a>
    <?php endif; ?>
    <?php if (skaNavVisible('settings')): ?>
    <a href="settings.php" class="ska-nav-link<?= skaNavActive('settings', $activePage) ?>">
      <i class="fa-solid fa-gear"></i> Site Settings
    </a>
    <?php endif; ?>

    <div class="ska-nav-label">Settings</div>
    <?php if (skaNavVisible('staff')): ?>
    <a href="staff.php" class="ska-nav-link<?= skaNavActive('staff', $activePage) ?>">
      <i class="fa-solid fa-users"></i> Users &amp; Roles
    </a>
    <?php endif; ?>
    <a href="logout.php" class="ska-nav-link">
      <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
    </a>

  </nav>

  <div class="ska-sidebar__footer">
    <div class="ska-sidebar__avatar"><i class="fa-solid fa-user"></i></div>
    <div>
      <div class="ska-sidebar__user-name"><?= htmlspecialchars($_SESSION['admin'] ?? 'Administrator') ?></div>
      <div class="ska-sidebar__user-role"><?= htmlspecialchars(function_exists('ska_admin_role_label') ? ska_admin_role_label($adminRole) : $adminRole) ?></div>
    </div>
    <a href="logout.php" class="ska-sidebar__logout" title="Logout">
      <i class="fa-solid fa-arrow-right-from-bracket"></i>
    </a>
  </div>

</aside>

<script>
  (function () {
    const sidebar   = document.getElementById('skaSidebar');
    const overlay   = document.getElementById('skaOverlay');
    const hamburger = document.getElementById('skaHamburger');

    function openSidebar() {
      sidebar.classList.add('open');
      overlay.classList.add('open');
      document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
      sidebar.classList.remove('open');
      overlay.classList.remove('open');
      document.body.style.overflow = '';
    }

    hamburger.addEventListener('click', function () {
      sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
    });

    overlay.addEventListener('click', closeSidebar);

    window.addEventListener('resize', function () {
      if (window.innerWidth > 992) closeSidebar();
    }, { passive: true });
  })();
</script>