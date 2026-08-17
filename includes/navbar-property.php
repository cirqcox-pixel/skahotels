<?php
/**
 * Property sub-navigation — set $propertyBranch before include.
 * Values: 'naguru' | 'munyonyo'
 */
require_once __DIR__ . '/../config/site.php';
$propertyBranch = $propertyBranch ?? 'naguru';
$propertyTab    = $propertyTab ?? 'overview';
$branch         = ska_branch($propertyBranch);
$base           = $branch['page'];
?>
<div class="ska-property-header" id="skaPropertyHeader">
  <div class="container">
    <div class="ska-tabs">
      <div class="ska-brand">
        <a href="index.php" aria-label="Back to SKA home">
          <img src="assets/images/favicon.png" alt="SKA The Boutique">
        </a>
      </div>
      <nav class="ska-tab-links" id="skaTabLinks" aria-label="Property sections">
        <a href="<?= $base ?>" class="<?= $propertyTab === 'overview' ? 'active' : '' ?>">Overview</a>
        <a href="<?= $base ?>#gallery" class="<?= $propertyTab === 'gallery' ? 'active' : '' ?>">Photos</a>
        <a href="<?= $base ?>#rooms" class="<?= $propertyTab === 'rooms' ? 'active' : '' ?>">Rooms</a>
        <a href="<?= $base ?>#services" class="<?= $propertyTab === 'services' ? 'active' : '' ?>">Drink + Eat</a>
        <a href="<?= $base ?>#experiences" class="<?= $propertyTab === 'experiences' ? 'active' : '' ?>">Experiences</a>
        <a href="<?= $base ?>#events" class="<?= $propertyTab === 'events' ? 'active' : '' ?>">Events</a>
        <a href="<?= $base ?>#book" class="<?= $propertyTab === 'book' ? 'active' : '' ?>">Book</a>
      </nav>
    </div>
  </div>
</div>

<script>
(function () {
  const tabs = document.querySelectorAll('#skaTabLinks a[href*="#"]');
  const sections = ['gallery','rooms','services','experiences','events','book'];
  function setActiveFromScroll() {
    let current = 'overview';
    sections.forEach(function (id) {
      const el = document.getElementById(id);
      if (el && el.getBoundingClientRect().top <= 120) current = id;
    });
    document.querySelectorAll('#skaTabLinks a').forEach(function (a) {
      a.classList.toggle('active', a.getAttribute('href').includes('#' + current) || (current === 'overview' && !a.getAttribute('href').includes('#')));
    });
  }
  window.addEventListener('scroll', setActiveFromScroll, { passive: true });
})();
</script>
