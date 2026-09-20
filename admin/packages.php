<?php
require_once '../config/db.php';
require_once '../config/cms.php';
require_once 'includes/auth.php';
ska_admin_require();

$msg     = '';
$editing = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id            = (int)($_POST['id'] ?? 0);
        $title         = $conn->real_escape_string($_POST['title'] ?? '');
        $tag           = $conn->real_escape_string($_POST['tag'] ?? '');
        $description   = $conn->real_escape_string($_POST['description'] ?? '');
        $inclusions    = $conn->real_escape_string($_POST['inclusions'] ?? '');
        $optionsRaw    = trim($_POST['options'] ?? '');
        $currency      = $conn->real_escape_string($_POST['currency'] ?? 'UGX');
        $price         = (float)($_POST['price'] ?? 0);
        $pricing_mode  = $conn->real_escape_string($_POST['pricing_mode'] ?? 'fixed');
        $branch        = $conn->real_escape_string($_POST['branch'] ?? 'Naguru');
        $booking_url   = $conn->real_escape_string($_POST['booking_url'] ?? '');
        $active        = (int)($_POST['active'] ?? 1);
        $valid_from    = !empty($_POST['valid_from']) ? $conn->real_escape_string($_POST['valid_from']) : null;
        $valid_to      = !empty($_POST['valid_to'])   ? $conn->real_escape_string($_POST['valid_to'])   : null;
        $sort_order    = (int)($_POST['sort_order'] ?? 0);

        $decoded = json_decode($optionsRaw, true);
        if (!is_array($decoded)) {
            $lines = [];
            foreach (preg_split('/\r\n|\r|\n/', $optionsRaw) as $line) {
                $line = trim($line);
                if ($line === '') continue;
                $p = array_map('trim', explode('|', $line));
                $lines[] = [
                    'label'   => $p[0],
                    'price'   => (float)($p[1] ?? $price),
                    'detail'  => $p[2] ?? '',
                    'pricing' => $p[3] ?? $pricing_mode,
                ];
            }
            $optionsRaw = json_encode($lines);
        }
        $optionsEsc = $conn->real_escape_string($optionsRaw);

        $vf = $valid_from ? "'$valid_from'" : 'NULL';
        $vt = $valid_to   ? "'$valid_to'"   : 'NULL';

        $uploadDir    = "../uploads/packages/";
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $maxSize      = 2 * 1024 * 1024;
        $imagePath    = $conn->real_escape_string($_POST['existing_image'] ?? '');

        if (!empty($_FILES['pkg_image']['name']) && $_FILES['pkg_image']['error'] === UPLOAD_ERR_OK) {
            $fileType = $_FILES['pkg_image']['type'];
            $fileSize = $_FILES['pkg_image']['size'];
            if (in_array($fileType, $allowedTypes, true) && $fileSize <= $maxSize) {
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $ext      = pathinfo($_FILES['pkg_image']['name'], PATHINFO_EXTENSION);
                $fileName = uniqid('pkg_', true) . '.' . $ext;
                move_uploaded_file($_FILES['pkg_image']['tmp_name'], $uploadDir . $fileName);
                $imagePath = $conn->real_escape_string('uploads/packages/' . $fileName);
            }
        }

        if ($id) {
            $conn->query("UPDATE packages SET
                title='$title', tag='$tag', description='$description', inclusions='$inclusions',
                options='$optionsEsc', currency='$currency', price=$price, pricing_mode='$pricing_mode',
                branch='$branch', image='$imagePath', booking_url='$booking_url', active=$active,
                valid_from=$vf, valid_to=$vt, sort_order=$sort_order,
                updated_at=CURRENT_TIMESTAMP
                WHERE id=$id");
            $msg = 'Package updated successfully.';
        } else {
            $conn->query("INSERT INTO packages
                (title, tag, description, inclusions, options, currency, price, pricing_mode,
                 branch, image, booking_url, active, valid_from, valid_to, sort_order)
                VALUES
                ('$title','$tag','$description','$inclusions','$optionsEsc','$currency',$price,'$pricing_mode',
                 '$branch','$imagePath','$booking_url',$active,$vf,$vt,$sort_order)");
            $msg = 'New package created successfully.';
        }
    }

    if ($action === 'toggle') {
        $id = (int)$_POST['id']; $active = (int)$_POST['active'];
        $conn->query("UPDATE packages SET active=$active, updated_at=CURRENT_TIMESTAMP WHERE id=$id");
        $msg = $active ? 'Package activated.' : 'Package hidden.';
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM packages WHERE id=$id");
        $msg = 'Package deleted.';
    }

    header('Location: packages.php?msg=' . urlencode($msg));
    exit;
}

if (isset($_GET['edit'])) {
    $id  = (int)$_GET['edit'];
    $res = $conn->query("SELECT * FROM packages WHERE id=$id LIMIT 1");
    $editing = $res ? $res->fetch_assoc() : null;
}

$pkgs = [];
$res  = $conn->query('SELECT * FROM packages ORDER BY sort_order ASC, id ASC');
if ($res) while ($row = $res->fetch_assoc()) $pkgs[] = $row;

$msg = $_GET['msg'] ?? $msg;

$activePage     = 'packages';
$pageTitle      = 'Packages';
$pageBreadcrumb = 'Property → Packages';
$topbarAction   = ['label' => 'Add Package', 'href' => '#pkgForm', 'icon' => 'fa-plus'];
if ($msg) {
    $toastMsg = $msg;
    $toastType = 'success';
    $includeToast = true;
}

$e = $editing ?? [];
$optionsDisplay = $e['options'] ?? '';
if (!empty($e['options'])) {
    $decoded = json_decode($e['options'], true);
    if (is_array($decoded)) {
        $lines = [];
        foreach ($decoded as $opt) {
            $lines[] = ($opt['label'] ?? '') . '|' . ($opt['price'] ?? 0) . '|' . ($opt['detail'] ?? '') . '|' . ($opt['pricing'] ?? '');
        }
        $optionsDisplay = implode("\n", $lines);
    }
}

include 'includes/layout-start.php';
?>

<?php if ($msg): ?>
    <div class="ska-alert ska-alert--success">
      <i class="fa-solid fa-circle-check"></i>
      <?= htmlspecialchars($msg) ?>
    </div>
<?php endif; ?>

    <div class="ska-card" id="pkgForm">
      <div class="ska-card__header">
        <div class="ska-card__title">
          <?= $editing ? 'Edit Package' : 'Create Package' ?>
          <span>Wedding, conference and other bookable packages per property</span>
        </div>
        <?php if ($editing): ?>
        <a href="packages.php" class="ska-btn ska-btn--outline">
          <i class="fa-solid fa-xmark"></i> Cancel Edit
        </a>
        <?php endif; ?>
      </div>
      <div class="ska-card__body">
        <form method="POST" action="packages.php" enctype="multipart/form-data">
          <input type="hidden" name="action" value="save">
          <input type="hidden" name="id" value="<?= (int)($e['id'] ?? 0) ?>">
          <input type="hidden" name="existing_image" value="<?= htmlspecialchars($e['image'] ?? '') ?>">

          <div class="row g-4">
            <div class="col-md-8">
              <label class="ska-label">Package Title *</label>
              <input name="title" class="ska-input" required
                     placeholder="e.g. Conference Package Menu"
                     value="<?= htmlspecialchars($e['title'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="ska-label">Tag / Badge</label>
              <input name="tag" class="ska-input" placeholder="e.g. Wedding"
                     value="<?= htmlspecialchars($e['tag'] ?? '') ?>">
            </div>
            <div class="col-12">
              <label class="ska-label">Description</label>
              <textarea name="description" class="ska-textarea" rows="3"><?= htmlspecialchars($e['description'] ?? '') ?></textarea>
            </div>
            <div class="col-12">
              <label class="ska-label">Inclusions (one per line)</label>
              <textarea name="inclusions" class="ska-textarea" rows="5"
                        placeholder="Dinner for all guests"><?= htmlspecialchars($e['inclusions'] ?? '') ?></textarea>
            </div>
            <div class="col-12">
              <label class="ska-label">Pricing options</label>
              <textarea name="options" class="ska-textarea" rows="6"
                        placeholder="Full Day Conference Package|120000|Per delegate, per day|per_person"><?= htmlspecialchars($optionsDisplay) ?></textarea>
              <p class="ska-hint">One option per line: Label | price | detail | pricing (fixed or per_person)</p>
            </div>
            <div class="col-md-3">
              <label class="ska-label">From price</label>
              <input name="price" type="number" step="1" min="0" class="ska-input"
                     value="<?= htmlspecialchars($e['price'] ?? '0') ?>">
            </div>
            <div class="col-md-3">
              <label class="ska-label">Currency</label>
              <select name="currency" class="ska-select">
                <?php foreach (['UGX','USD'] as $cur): ?>
                <option value="<?= $cur ?>" <?= (($e['currency'] ?? 'UGX') === $cur) ? 'selected' : '' ?>><?= $cur ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="ska-label">Default pricing</label>
              <select name="pricing_mode" class="ska-select">
                <option value="fixed" <?= (($e['pricing_mode'] ?? '') === 'fixed') ? 'selected' : '' ?>>Fixed package</option>
                <option value="per_person" <?= (($e['pricing_mode'] ?? '') === 'per_person') ? 'selected' : '' ?>>Per person</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="ska-label">Branch</label>
              <select name="branch" class="ska-select">
                <?php foreach (['Naguru','Munyonyo'] as $b): ?>
                <option value="<?= $b ?>" <?= (($e['branch'] ?? 'Naguru') === $b) ? 'selected' : '' ?>><?= $b ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="ska-label">Valid From</label>
              <input name="valid_from" type="date" class="ska-input" value="<?= htmlspecialchars($e['valid_from'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="ska-label">Valid To</label>
              <input name="valid_to" type="date" class="ska-input" value="<?= htmlspecialchars($e['valid_to'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="ska-label">Booking URL</label>
              <input name="booking_url" class="ska-input" placeholder="naguru.php?package=…#book"
                     value="<?= htmlspecialchars($e['booking_url'] ?? '') ?>">
            </div>
            <div class="col-md-2">
              <label class="ska-label">Sort</label>
              <input name="sort_order" type="number" min="0" class="ska-input" value="<?= htmlspecialchars($e['sort_order'] ?? '0') ?>">
            </div>
            <div class="col-md-2">
              <label class="ska-label">Status</label>
              <select name="active" class="ska-select">
                <option value="1" <?= (($e['active'] ?? 1) == 1) ? 'selected' : '' ?>>Active</option>
                <option value="0" <?= (($e['active'] ?? 1) == 0) ? 'selected' : '' ?>>Hidden</option>
              </select>
            </div>
            <div class="col-12">
              <label class="ska-label">Package Image</label>
              <?php if (!empty($e['image'])): ?>
              <p class="ska-hint">Current: <?= htmlspecialchars($e['image']) ?></p>
              <?php endif; ?>
              <input type="file" name="pkg_image" accept="image/jpeg,image/png,image/webp">
            </div>
          </div>
          <div class="d-flex gap-3 mt-4">
            <button type="submit" class="ska-btn ska-btn--gold">
              <i class="fa-solid fa-floppy-disk"></i>
              <?= $editing ? 'Update Package' : 'Save Package' ?>
            </button>
          </div>
        </form>
      </div>
    </div>

    <div class="ska-card">
      <div class="ska-card__header">
        <div class="ska-card__title">
          All Packages
          <span><?= count($pkgs) ?> configured — also listed next to Promotions</span>
        </div>
      </div>
      <div class="ska-card__body" style="padding:0">
        <?php if (empty($pkgs)): ?>
        <div class="ska-empty">
          <i class="fa-solid fa-box-open"></i>
          <p>No packages yet.<br>Create one using the form above.</p>
        </div>
        <?php else: ?>
        <div style="overflow-x:auto">
        <table class="ska-table">
          <thead>
            <tr>
              <th>Title</th><th>Tag</th><th>From</th><th>Branch</th><th>Status</th><th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($pkgs as $p): ?>
          <tr>
            <td><strong><?= htmlspecialchars($p['title']) ?></strong></td>
            <td><span class="ska-tag-chip"><?= htmlspecialchars($p['tag'] ?? '') ?></span></td>
            <td><?= htmlspecialchars($p['currency'] ?? 'UGX') ?> <?= number_format((float)$p['price'], 0) ?></td>
            <td><?= htmlspecialchars($p['branch']) ?></td>
            <td>
              <?php if ($p['active']): ?>
                <span class="ska-badge ska-badge--success">Active</span>
              <?php else: ?>
                <span class="ska-badge ska-badge--muted">Hidden</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="d-flex gap-2">
                <a href="packages.php?edit=<?= (int)$p['id'] ?>#pkgForm" class="ska-btn ska-btn--ghost-edit">
                  <i class="fa-regular fa-pen-to-square"></i> Edit
                </a>
                <form method="POST" action="packages.php" style="display:inline">
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                  <input type="hidden" name="active" value="<?= $p['active'] ? 0 : 1 ?>">
                  <button type="submit" class="ska-btn <?= $p['active'] ? 'ska-btn--ghost-toggle-off' : 'ska-btn--ghost-toggle-on' ?>">
                    <i class="fa-solid <?= $p['active'] ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                  </button>
                </form>
                <form method="POST" action="packages.php" style="display:inline"
                      onsubmit="return confirm('Delete this package?')">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                  <button type="submit" class="ska-btn ska-btn--ghost-del">
                    <i class="fa-regular fa-trash-can"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        </div>
        <?php endif; ?>
      </div>
    </div>
<?php include 'includes/layout-end.php'; ?>
