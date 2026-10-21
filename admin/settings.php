<?php
require_once '../config/db.php';
require_once '../config/cms.php';
require_once 'includes/auth.php';
ska_admin_require();

$imageFields = [
    'brand_logo_image' => ['Brand logo', 'Main navigation and hotel schema.', 'branding'],
    'brand_favicon_image' => ['Browser icon', 'Browser tabs and property navigation mark.', 'branding'],
    'brand_social_share_image' => ['Social sharing image', 'Default when a page is shared.', 'branding'],
    'hero_slide_1_image' => ['Homepage hero — slide 1', 'First full-width homepage image.', 'homepage'],
    'hero_slide_2_image' => ['Homepage hero — slide 2', 'Second full-width homepage image.', 'homepage'],
    'home_naguru_card_image' => ['Naguru property card', 'Home page → Properties → Naguru.', 'homepage_images'],
    'home_munyonyo_card_image' => ['Munyonyo property card', 'Home page → Properties → Munyonyo.', 'homepage_images'],
    'home_direct_booking_image' => ['Direct booking promotion', 'Home page fallback promotion card.', 'homepage_images'],
    'home_careers_image' => ['Careers callout', 'Home page careers section.', 'homepage_images'],
    'about_hero_image' => ['About page hero', 'Full-width image at the top of About.', 'page_images'],
    'offers_hero_image' => ['Offers page hero', 'Full-width image at the top of Offers.', 'page_images'],
    'meetings_events_hero_image' => ['Meetings & events hero', 'Full-width image at the top of Meetings & Events.', 'page_images'],
    'careers_hero_image' => ['Careers page hero', 'Full-width image at the top of Careers.', 'page_images'],
    'loyalty_hero_image' => ['Rewards page hero', 'Full-width image at the top of SKA Rewards.', 'page_images'],
    'meetings_business_image' => ['Business meetings card', 'Meetings & Events page → Business Meetings.', 'page_images'],
    'meetings_weddings_image' => ['Weddings card', 'Meetings & Events page → Weddings & Intimate Parties.', 'page_images'],
    'meetings_social_image' => ['Social events card', 'Meetings & Events page → Social Events.', 'page_images'],
    'about_intro_main_image' => ['About — main property image', 'About page “Who we are” large image.', 'page_images'],
    'about_intro_accent_image' => ['About — accent property image', 'About page “Who we are” overlap image.', 'page_images'],
    'about_story_image' => ['About — story image', 'About page story section.', 'page_images'],
    'contact_hero_image' => ['Contact page hero', 'Background at the top of Contact.', 'page_images'],
    'packages_hero_image' => ['Packages page hero', 'Background at the top of Packages.', 'page_images'],
    'naguru_overview_image' => ['Naguru overview image', 'Naguru page property overview.', 'property_images'],
    'munyonyo_overview_image' => ['Munyonyo overview image', 'Munyonyo page property overview.', 'property_images'],
    'naguru_dining_image' => ['Naguru dining image', 'Naguru page → Drink + Eat.', 'property_images'],
    'naguru_garden_image' => ['Naguru garden image', 'Naguru page → Experiences.', 'property_images'],
    'munyonyo_dining_image' => ['Munyonyo dining image', 'Munyonyo page → Drink + Eat.', 'property_images'],
    'munyonyo_garden_image' => ['Munyonyo garden image', 'Munyonyo page → Experiences.', 'property_images'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['settings'] ?? [] as $key => $value) {
        $key = preg_replace('/[^a-z0-9_]/', '', $key);
        if ($key) cms_save_setting($key, trim($value), $_POST['groups'][$key] ?? 'general');
    }
    $uploadDir = '../uploads/branding/';
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    foreach ($_FILES['images']['name'] ?? [] as $key => $name) {
        if (!isset($imageFields[$key]) || ($_FILES['images']['error'][$key] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
        if (($_FILES['images']['error'][$key] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || ($_FILES['images']['size'][$key] ?? 0) > 5 * 1024 * 1024) continue;
        $mime = function_exists('mime_content_type') ? mime_content_type($_FILES['images']['tmp_name'][$key]) : ($_FILES['images']['type'][$key] ?? '');
        if (!isset($allowed[$mime])) continue;
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $file = $key . '_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
        if (move_uploaded_file($_FILES['images']['tmp_name'][$key], $uploadDir . $file)) cms_save_setting($key, 'uploads/branding/' . $file, $imageFields[$key][2]);
    }
    header('Location: settings.php?saved=1'); exit;
}

$settings = cms_all_settings();
$groups = ['contact' => 'Contact & properties', 'social' => 'Social media'];
$imageGroups = ['branding' => 'Brand identity', 'homepage' => 'Homepage hero', 'homepage_images' => 'Homepage images', 'page_images' => 'General page images', 'property_images' => 'Property page images'];
$activePage = 'settings';
$pageTitle = 'Branding & Site Settings';
$pageBreadcrumb = 'Replace visible images, contact details, and social links';
if (isset($_GET['saved'])) { $toastMsg = 'Branding and settings saved.'; $toastType = 'success'; $includeToast = true; }
include 'includes/layout-start.php';
?>
<form method="POST" enctype="multipart/form-data">
  <div class="ska-card mb-4"><div class="ska-card__header"><div class="ska-card__title">Branding &amp; Website Images <span>Upload a replacement or paste an image URL/path, then save.</span></div></div>
    <div class="ska-card__body ska-card__body--padded"><p class="ska-hint mb-4">These are fixed public-site images. Room photos, gallery photos, offers, packages, and event cards have their own admin sections.</p>
      <?php foreach ($imageGroups as $group => $heading): ?><h3 class="ska-label ska-image-section-title"><?= htmlspecialchars($heading) ?></h3><div class="row g-3 mb-4">
        <?php foreach ($imageFields as $key => [$label, $where, $fieldGroup]): if ($fieldGroup !== $group) continue; $value = $settings[$key]['setting_value'] ?? ''; $preview = preg_match('#^https?://#i', $value) ? $value : '../' . $value; ?>
          <div class="col-xl-4 col-md-6"><div class="ska-image-setting"><img src="<?= htmlspecialchars($preview) ?>" alt="<?= htmlspecialchars($label) ?>" class="ska-image-setting__preview" onerror="this.classList.add('is-missing')"><div class="ska-image-setting__body"><label class="ska-label"><?= htmlspecialchars($label) ?></label><p class="ska-hint mb-2"><?= htmlspecialchars($where) ?></p><input type="text" name="settings[<?= htmlspecialchars($key) ?>]" class="ska-input mb-2" value="<?= htmlspecialchars($value) ?>" aria-label="<?= htmlspecialchars($label) ?> image path or URL"><input type="hidden" name="groups[<?= htmlspecialchars($key) ?>]" value="<?= htmlspecialchars($fieldGroup) ?>"><input type="file" name="images[<?= htmlspecialchars($key) ?>]" class="ska-input ska-input--file" accept="image/jpeg,image/png,image/webp"></div></div></div>
        <?php endforeach; ?></div><?php endforeach; ?>
    </div>
  </div>
  <div class="ska-card"><div class="ska-card__header"><div class="ska-card__title">Contact &amp; Social Details</div></div><div class="ska-card__body ska-card__body--padded">
    <?php foreach ($groups as $gKey => $gLabel): ?><h3 class="ska-label" style="margin:24px 0 12px;font-size:13px;color:#c9a96e"><?= $gLabel ?></h3><div class="row g-3">
      <?php foreach ($settings as $key => $row): if (($row['setting_group'] ?? '') !== $gKey) continue; $label = ucwords(str_replace('_', ' ', str_replace($gKey . '_', '', str_replace('site_', '', $key)))); ?><div class="col-md-6"><label class="ska-label"><?= htmlspecialchars($label) ?></label><input type="text" name="settings[<?= htmlspecialchars($key) ?>]" class="ska-input" value="<?= htmlspecialchars($row['setting_value'] ?? '') ?>"><input type="hidden" name="groups[<?= htmlspecialchars($key) ?>]" value="<?= htmlspecialchars($gKey) ?>"></div><?php endforeach; ?>
    </div><?php endforeach; ?><button type="submit" class="ska-btn ska-btn--gold mt-4"><i class="fa-solid fa-floppy-disk"></i> Save all changes</button>
  </div></div>
</form>
<?php include 'includes/layout-end.php'; ?>
