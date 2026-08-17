<?php
require_once '../config/db.php';
require_once '../config/cms.php';
require_once 'includes/auth.php';
ska_admin_require();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['settings'] ?? [] as $key => $value) {
        $key = preg_replace('/[^a-z0-9_]/', '', $key);
        if ($key) cms_save_setting($key, trim($value), $_POST['groups'][$key] ?? 'general');
    }
    header('Location: settings.php?saved=1');
    exit;
}

$settings = cms_all_settings();
$groups = ['contact' => 'Contact Details', 'social' => 'Social Media', 'homepage' => 'Homepage Hero'];

$activePage = 'settings';
$pageTitle = 'Site Settings';
$pageBreadcrumb = 'Contact info, social links, homepage hero slides';
if (isset($_GET['saved'])) { $toastMsg = 'Settings saved.'; $toastType = 'success'; $includeToast = true; }
include 'includes/layout-start.php';
?>

<form method="POST">
<div class="ska-card">
  <div class="ska-card__header"><div class="ska-card__title">Global Settings</div></div>
  <div class="ska-card__body ska-card__body--padded">
    <?php foreach ($groups as $gKey => $gLabel): ?>
    <h3 class="ska-label" style="margin:24px 0 12px;font-size:13px;color:#c9a96e"><?= $gLabel ?></h3>
    <div class="row g-3">
      <?php foreach ($settings as $key => $row):
        if (($row['setting_group'] ?? '') !== $gKey) continue;
        $label = ucwords(str_replace('_', ' ', str_replace($gKey . '_', '', str_replace('site_', '', $key))));
      ?>
      <div class="col-md-6">
        <label class="ska-label"><?= htmlspecialchars($label) ?></label>
        <input type="text" name="settings[<?= htmlspecialchars($key) ?>]" class="ska-input" value="<?= htmlspecialchars($row['setting_value'] ?? '') ?>">
        <input type="hidden" name="groups[<?= htmlspecialchars($key) ?>]" value="<?= htmlspecialchars($gKey) ?>">
      </div>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
    <button type="submit" class="ska-btn ska-btn--gold mt-4"><i class="fa-solid fa-floppy-disk"></i> Save Settings</button>
  </div>
</div>
</form>

<?php include 'includes/layout-end.php'; ?>
