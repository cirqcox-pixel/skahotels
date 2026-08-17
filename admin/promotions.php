<?php
require_once '../config/db.php';
require_once 'includes/auth.php';
ska_admin_require();

$msg     = '';
$editing = null;

// ── Handle POST ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id             = (int)($_POST['id'] ?? 0);
        $title          = $conn->real_escape_string($_POST['title']);
        $tag            = $conn->real_escape_string($_POST['tag']);
        $description    = $conn->real_escape_string($_POST['description']);
        $discount_type  = $conn->real_escape_string($_POST['discount_type']);
        $discount_value = (float)$_POST['discount_value'];
        $min_nights     = (int)($_POST['min_nights'] ?? 1);
        $branch         = $conn->real_escape_string($_POST['branch']);
        $booking_url    = $conn->real_escape_string($_POST['booking_url']);
        $active         = (int)($_POST['active'] ?? 1);
        $valid_from     = !empty($_POST['valid_from']) ? $conn->real_escape_string($_POST['valid_from']) : null;
        $valid_to       = !empty($_POST['valid_to'])   ? $conn->real_escape_string($_POST['valid_to'])   : null;
        $sort_order     = (int)($_POST['sort_order'] ?? 0);

        $vf = $valid_from ? "'$valid_from'" : 'NULL';
        $vt = $valid_to   ? "'$valid_to'"   : 'NULL';

        // ── Image Upload ──────────────────────────────────
        $uploadDir    = "../uploads/promotions/";
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $maxSize      = 2 * 1024 * 1024;
        $imagePath    = $conn->real_escape_string($_POST['existing_image'] ?? '');

        if (!empty($_FILES['promo_image']['name']) && $_FILES['promo_image']['error'] === UPLOAD_ERR_OK) {
            $fileType = $_FILES['promo_image']['type'];
            $fileSize = $_FILES['promo_image']['size'];

            if (in_array($fileType, $allowedTypes) && $fileSize <= $maxSize) {
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $ext      = pathinfo($_FILES['promo_image']['name'], PATHINFO_EXTENSION);
                $fileName = uniqid('promo_', true) . '.' . $ext;
                move_uploaded_file($_FILES['promo_image']['tmp_name'], $uploadDir . $fileName);
                $imagePath = $conn->real_escape_string("uploads/promotions/" . $fileName);
            }
        }

        if ($id) {
            $conn->query("UPDATE promotions SET
                title='$title', tag='$tag', description='$description',
                discount_type='$discount_type', discount_value=$discount_value,
                min_nights=$min_nights, branch='$branch', image='$imagePath',
                booking_url='$booking_url', active=$active,
                valid_from=$vf, valid_to=$vt, sort_order=$sort_order,
                updated_at=CURRENT_TIMESTAMP
                WHERE id=$id");
            $msg = 'Promotion updated successfully.';
        } else {
            $conn->query("INSERT INTO promotions
                (title, tag, description, discount_type, discount_value,
                 min_nights, branch, image, booking_url, active, valid_from, valid_to, sort_order)
                VALUES
                ('$title','$tag','$description','$discount_type',$discount_value,
                 $min_nights,'$branch','$imagePath','$booking_url',$active,$vf,$vt,$sort_order)");
            $msg = 'New promotion created successfully.';
        }
    }

    if ($action === 'toggle') {
        $id = (int)$_POST['id']; $active = (int)$_POST['active'];
        $conn->query("UPDATE promotions SET active=$active, updated_at=CURRENT_TIMESTAMP WHERE id=$id");
        $msg = $active ? 'Promotion activated.' : 'Promotion hidden.';
    }

    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM promotions WHERE id=$id");
        $msg = 'Promotion deleted.';
    }

    header("Location: promotions.php?msg=" . urlencode($msg));
    exit;
}

// ── Edit mode ────────────────────────────────────────────
if (isset($_GET['edit'])) {
    $id  = (int)$_GET['edit'];
    $res = $conn->query("SELECT * FROM promotions WHERE id=$id LIMIT 1");
    $editing = $res ? $res->fetch_assoc() : null;
}

// ── Load all promotions ──────────────────────────────────
$promos = [];
$res    = $conn->query("SELECT * FROM promotions ORDER BY sort_order ASC, id ASC");
if ($res) while ($row = $res->fetch_assoc()) $promos[] = $row;

$msg = $_GET['msg'] ?? $msg;

// ── Page config ──────────────────────────────────────────
$activePage     = 'promotions';
$pageTitle      = 'Promotions';
$pageBreadcrumb = 'Property → Promotions';
$topbarAction   = ['label' => 'Add Promotion', 'href' => '#promoForm', 'icon' => 'fa-plus'];
if ($msg) {
    $toastMsg = $msg;
    $toastType = 'success';
    $includeToast = true;
}

$e = $editing ?? [];
include 'includes/layout-start.php';
?>

<?php if ($msg): ?>
    <div class="ska-alert ska-alert--success">
      <i class="fa-solid fa-circle-check"></i>
      <?= htmlspecialchars($msg) ?>
    </div>
    <?php endif; ?>

    <!-- ══ FORM CARD ══════════════════════════════════════ -->
    <div class="ska-card" id="promoForm">
      <div class="ska-card__header">
        <div class="ska-card__title">
          <?= $editing ? 'Edit Promotion' : 'Create New Promotion' ?>
          <span><?= $editing ? 'Update the details below' : 'Fill in the details to publish a new offer' ?></span>
        </div>
        <?php if ($editing): ?>
        <a href="promotions.php" class="ska-btn ska-btn--outline">
          <i class="fa-solid fa-xmark"></i> Cancel Edit
        </a>
        <?php endif; ?>
      </div>
      <div class="ska-card__body">

        <form method="POST" action="promotions.php" enctype="multipart/form-data">
          <input type="hidden" name="action" value="save">
          <input type="hidden" name="id" value="<?= (int)($e['id'] ?? 0) ?>">
          <input type="hidden" name="existing_image" value="<?= htmlspecialchars($e['image'] ?? '') ?>">

          <div class="row g-4">

            <!-- Title -->
            <div class="col-md-8">
              <label class="ska-label">Promotion Title *</label>
              <input name="title" class="ska-input" required
                     placeholder="e.g. Early Bird Offer"
                     value="<?= htmlspecialchars($e['title'] ?? '') ?>">
            </div>

            <!-- Tag -->
            <div class="col-md-4">
              <label class="ska-label">Tag / Badge</label>
              <input name="tag" class="ska-input"
                     placeholder="e.g. Limited Time"
                     value="<?= htmlspecialchars($e['tag'] ?? '') ?>">
              <p class="ska-hint">Short label shown on the promotion card</p>
            </div>

            <!-- Description -->
            <div class="col-12">
              <label class="ska-label">Description</label>
              <textarea name="description" class="ska-textarea"
                        placeholder="Describe what guests get and any special conditions…"><?= htmlspecialchars($e['description'] ?? '') ?></textarea>
            </div>

            <!-- Discount Type -->
            <div class="col-md-4">
              <label class="ska-label">Discount Type *</label>
              <select name="discount_type" class="ska-select" id="discountType" required>
                <?php foreach (['percent' => '% Off per night', 'free_night' => 'Free Night(s)', 'fixed' => 'Fixed $ Off per night'] as $v => $l): ?>
                <option value="<?= $v ?>" <?= (($e['discount_type'] ?? '') === $v) ? 'selected' : '' ?>><?= $l ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Discount Value -->
            <div class="col-md-4">
              <label class="ska-label">Discount Value *</label>
              <input name="discount_value" type="number" step="0.01" min="0"
                     class="ska-input" required placeholder="e.g. 10"
                     value="<?= htmlspecialchars($e['discount_value'] ?? '') ?>">
              <p class="ska-hint" id="discountHint">e.g. 10 = 10% off per night</p>
            </div>

            <!-- Min Nights -->
            <div class="col-md-4">
              <label class="ska-label">Minimum Nights</label>
              <input name="min_nights" type="number" min="1" class="ska-input"
                     placeholder="1" value="<?= htmlspecialchars($e['min_nights'] ?? '1') ?>">
              <p class="ska-hint">Minimum stay to qualify</p>
            </div>

            <!-- Branch -->
            <div class="col-md-4">
              <label class="ska-label">Branch</label>
              <select name="branch" class="ska-select">
                <option value="All" <?= (($e['branch'] ?? '') === 'All') ? 'selected' : '' ?>>All Branches</option>
                <?php foreach (['Naguru','Munyonyo','Kololo'] as $b): ?>
                <option value="<?= $b ?>" <?= (($e['branch'] ?? '') === $b) ? 'selected' : '' ?>><?= $b ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Valid From -->
            <div class="col-md-4">
              <label class="ska-label">Valid From</label>
              <input name="valid_from" type="date" class="ska-input"
                     value="<?= htmlspecialchars($e['valid_from'] ?? '') ?>">
            </div>

            <!-- Valid To -->
            <div class="col-md-4">
              <label class="ska-label">Valid To</label>
              <input name="valid_to" type="date" class="ska-input"
                     value="<?= htmlspecialchars($e['valid_to'] ?? '') ?>">
            </div>

            <!-- Booking URL -->
            <div class="col-md-8">
              <label class="ska-label">Booking URL</label>
              <input name="booking_url" type="url" class="ska-input"
                     placeholder="https://…"
                     value="<?= htmlspecialchars($e['booking_url'] ?? '') ?>">
            </div>

            <!-- Sort Order -->
            <div class="col-md-2">
              <label class="ska-label">Sort Order</label>
              <input name="sort_order" type="number" min="0" class="ska-input"
                     placeholder="0" value="<?= htmlspecialchars($e['sort_order'] ?? '0') ?>">
            </div>

            <!-- Status -->
            <div class="col-md-2">
              <label class="ska-label">Status</label>
              <select name="active" class="ska-select">
                <option value="1" <?= (($e['active'] ?? 1) == 1) ? 'selected' : '' ?>>Active</option>
                <option value="0" <?= (($e['active'] ?? 1) == 0) ? 'selected' : '' ?>>Hidden</option>
              </select>
            </div>

            <!-- ── Promotion Image ────────────────────── -->
            <div class="col-12">
              <label class="ska-label">Promotion Image</label>

              <?php if (!empty($e['image'])): ?>
              <!-- Edit mode: show current image with Replace button -->
              <div class="ska-current-img-wrap" id="currentImgWrap">
                <img src="../<?= htmlspecialchars($e['image']) ?>" alt="Current image">
                <span class="ska-current-img-wrap__badge">Current Image</span>
                <button type="button" class="ska-current-img-wrap__replace" onclick="showUploadZone()">
                  <i class="fa-solid fa-arrow-up-from-bracket" style="font-size:10px;"></i> Replace
                </button>
              </div>
              <div id="uploadZoneWrap" style="display:none;">
              <?php else: ?>
              <div id="uploadZoneWrap">
              <?php endif; ?>

                <!-- Upload zone -->
                <div class="ska-upload-zone" id="promoUploadZone"
                     onclick="document.getElementById('promoImageInput').click()">
                  <i class="fa-solid fa-cloud-arrow-up"></i>
                  <p>Click to upload or drag &amp; drop image</p>
                  <span>JPEG · PNG · WebP · max 2 MB</span>
                </div>
                <input type="file" name="promo_image" id="promoImageInput"
                       accept="image/jpeg,image/png,image/webp">

                <!-- Preview of newly chosen image -->
                <div id="newImgThumb" class="ska-new-img-thumb" style="display:none;">
                  <img id="newImgPreview" src="" alt="Preview">
                  <button type="button" class="ska-new-img-thumb__del"
                          onclick="clearNewImage()" title="Remove">&times;</button>
                </div>

              </div><!-- /uploadZoneWrap -->
            </div>

          </div><!-- /row -->

          <div class="d-flex gap-3 mt-4">
            <button type="submit" class="ska-btn ska-btn--gold" style="font-size:13px; padding: 10px 28px;">
              <i class="fa-solid fa-floppy-disk"></i>
              <?= $editing ? 'Update Promotion' : 'Save Promotion' ?>
            </button>
            <?php if ($editing): ?>
            <a href="promotions.php" class="ska-btn ska-btn--outline" style="font-size:13px; padding: 10px 22px;">
              Cancel
            </a>
            <?php endif; ?>
          </div>

        </form>
      </div>
    </div>

    <!-- ══ TABLE CARD ══════════════════════════════════════ -->
    <div class="ska-card">
      <div class="ska-card__header">
        <div class="ska-card__title">
          All Promotions
          <span><?= count($promos) ?> promotion<?= count($promos) !== 1 ? 's' : '' ?> configured</span>
        </div>
      </div>
      <div class="ska-card__body" style="padding: 0;">

        <?php if (empty($promos)): ?>
        <div class="ska-empty">
          <i class="fa-solid fa-tag"></i>
          <p>No promotions yet.<br>Create one using the form above.</p>
        </div>
        <?php else: ?>
        <div style="overflow-x: auto;">
        <table class="ska-table">
          <thead>
            <tr>
              <th>Image</th>
              <th>Title</th>
              <th>Tag</th>
              <th>Discount</th>
              <th>Min Nights</th>
              <th>Branch</th>
              <th>Valid</th>
              <th>Status</th>
              <th>Order</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($promos as $p):
            $discount_label = match($p['discount_type']) {
              'percent'    => $p['discount_value'] . '% off',
              'free_night' => $p['discount_value'] . ' free night(s)',
              'fixed'      => '$' . number_format($p['discount_value'], 2) . ' off',
              default      => $p['discount_value'],
            };
            $has_dates  = $p['valid_from'] || $p['valid_to'];
            $date_range = $has_dates
              ? (($p['valid_from'] ? date('d M Y', strtotime($p['valid_from'])) : '—')
                 . ' → '
                 . ($p['valid_to']   ? date('d M Y', strtotime($p['valid_to']))   : '—'))
              : null;
          ?>
          <tr>
            <td>
              <?php if (!empty($p['image'])): ?>
                <img src="../<?= htmlspecialchars($p['image']) ?>" alt="" class="ska-table-thumb">
              <?php else: ?>
                <div class="ska-table-thumb-empty"><i class="fa-regular fa-image"></i></div>
              <?php endif; ?>
            </td>
            <td><strong style="font-size:13.5px;"><?= htmlspecialchars($p['title']) ?></strong></td>
            <td><span class="ska-tag-chip"><?= htmlspecialchars($p['tag']) ?></span></td>
            <td><span class="ska-discount-chip"><?= htmlspecialchars($discount_label) ?></span></td>
            <td class="muted"><?= (int)$p['min_nights'] ?> night<?= $p['min_nights'] != 1 ? 's' : '' ?></td>
            <td class="muted"><?= htmlspecialchars($p['branch']) ?></td>
            <td class="muted" style="font-size:12px; white-space:nowrap;">
              <?= $date_range ?? '<span style="font-style:italic;">Always active</span>' ?>
            </td>
            <td>
              <?php if ($p['active']): ?>
                <span class="ska-badge ska-badge--success">Active</span>
              <?php else: ?>
                <span class="ska-badge ska-badge--muted">Hidden</span>
              <?php endif; ?>
            </td>
            <td class="muted"><?= (int)$p['sort_order'] ?></td>
            <td>
              <div class="d-flex gap-2">
                <a href="promotions.php?edit=<?= (int)$p['id'] ?>#promoForm"
                   class="ska-btn ska-btn--ghost-edit">
                  <i class="fa-regular fa-pen-to-square"></i> Edit
                </a>

                <form method="POST" action="promotions.php" style="display:inline">
                  <input type="hidden" name="action" value="toggle">
                  <input type="hidden" name="id"     value="<?= (int)$p['id'] ?>">
                  <input type="hidden" name="active" value="<?= $p['active'] ? 0 : 1 ?>">
                  <button type="submit"
                    class="ska-btn <?= $p['active'] ? 'ska-btn--ghost-toggle-off' : 'ska-btn--ghost-toggle-on' ?>"
                    title="<?= $p['active'] ? 'Hide' : 'Activate' ?>">
                    <i class="fa-solid <?= $p['active'] ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                  </button>
                </form>

                <form method="POST" action="promotions.php" style="display:inline"
                      onsubmit="return confirm('Delete this promotion? This cannot be undone.')">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id"     value="<?= (int)$p['id'] ?>">
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
