<?php
require_once '../config/db.php';
require_once '../config/cms.php';
require_once 'includes/auth.php';
ska_admin_require();

$uploadDir = '../uploads/gallery/';
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
$maxSize = 3 * 1024 * 1024;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $c = cms_conn();

    if ($action === 'upload') {
        $branch = trim($_POST['branch'] ?? 'Naguru');
        $caption = trim($_POST['caption'] ?? '');
        if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            if (in_array($_FILES['image']['type'], $allowedTypes) && $_FILES['image']['size'] <= $maxSize) {
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $file = uniqid('gal_', true) . '.' . strtolower($ext);
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $file)) {
                    $path = 'uploads/gallery/' . $file;
                    $sort = (int)($_POST['sort_order'] ?? 0);
                    $stmt = $c->prepare("INSERT INTO property_gallery (branch, image_path, caption, sort_order) VALUES (?,?,?,?)");
                    $stmt->bind_param('sssi', $branch, $path, $caption, $sort);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
    }
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $c->prepare("SELECT image_path FROM property_gallery WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row && file_exists('../' . $row['image_path'])) unlink('../' . $row['image_path']);
        $del = $c->prepare("DELETE FROM property_gallery WHERE id = ?");
        $del->bind_param('i', $id);
        $del->execute();
        $del->close();
    }
    if ($action === 'toggle') {
        $id = (int)$_POST['id'];
        $active = (int)$_POST['active'];
        $c->query("UPDATE property_gallery SET active = $active WHERE id = $id");
    }
    header('Location: gallery.php?branch=' . urlencode($_POST['branch'] ?? 'Naguru') . '&saved=1');
    exit;
}

$branch = $_GET['branch'] ?? 'Naguru';
$c = cms_conn();
$stmt = $c->prepare("SELECT * FROM property_gallery WHERE branch = ? ORDER BY sort_order ASC, id DESC");
$stmt->bind_param('s', $branch);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$activePage = 'gallery';
$pageTitle = 'Photo Gallery';
$pageBreadcrumb = 'Property gallery images (+ room photos show automatically)';
if (isset($_GET['saved'])) { $toastMsg = 'Gallery updated.'; $toastType = 'success'; $includeToast = true; }
include 'includes/layout-start.php';
?>

<div class="ska-card mb-4">
  <div class="ska-card__header">
    <div class="ska-card__title">Upload Image <span>Branch: <?= htmlspecialchars($branch) ?></span></div>
    <div class="ska-filter-bar">
      <a href="?branch=Naguru" class="ska-btn ska-btn--outline <?= $branch === 'Naguru' ? 'active' : '' ?>">Naguru</a>
      <a href="?branch=Munyonyo" class="ska-btn ska-btn--outline <?= $branch === 'Munyonyo' ? 'active' : '' ?>">Munyonyo</a>
    </div>
  </div>
  <div class="ska-card__body ska-card__body--padded">
    <form method="POST" enctype="multipart/form-data" class="row g-3">
      <input type="hidden" name="action" value="upload">
      <input type="hidden" name="branch" value="<?= htmlspecialchars($branch) ?>">
      <div class="col-md-4"><label class="ska-label">Image</label><input type="file" name="image" class="ska-input" accept="image/*" required></div>
      <div class="col-md-4"><label class="ska-label">Caption</label><input type="text" name="caption" class="ska-input" placeholder="Optional caption"></div>
      <div class="col-md-2"><label class="ska-label">Sort</label><input type="number" name="sort_order" class="ska-input" value="0"></div>
      <div class="col-md-2 d-flex align-items-end"><button type="submit" class="ska-btn ska-btn--gold w-100"><i class="fa-solid fa-upload"></i> Upload</button></div>
    </form>
    <p class="ska-hint mt-2">Room photos from Rooms admin also appear in the public gallery automatically.</p>
  </div>
</div>

<div class="ska-img-preview-grid">
  <?php foreach ($items as $item): ?>
  <div class="ska-img-thumb">
    <img src="../<?= htmlspecialchars($item['image_path']) ?>" alt="">
    <div class="p-2" style="font-size:11px">
      <?= htmlspecialchars($item['caption'] ?: 'No caption') ?>
      <div class="d-flex gap-1 mt-2">
        <form method="POST" style="display:inline"><input type="hidden" name="action" value="toggle"><input type="hidden" name="branch" value="<?= htmlspecialchars($branch) ?>"><input type="hidden" name="id" value="<?= $item['id'] ?>"><input type="hidden" name="active" value="<?= $item['active'] ? 0 : 1 ?>"><button class="ska-btn ska-btn--outline" style="font-size:10px;padding:4px 8px"><?= $item['active'] ? 'Hide' : 'Show' ?></button></form>
        <form method="POST" style="display:inline" onsubmit="return confirm('Delete?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="branch" value="<?= htmlspecialchars($branch) ?>"><input type="hidden" name="id" value="<?= $item['id'] ?>"><button class="ska-btn ska-btn--ghost-del" style="font-size:10px;padding:4px 8px"><i class="fa-regular fa-trash-can"></i></button></form>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php if (empty($items)): ?><p class="ska-muted">No gallery uploads yet for <?= htmlspecialchars($branch) ?> — room images still display on site.</p><?php endif; ?>
</div>

<?php include 'includes/layout-end.php'; ?>
