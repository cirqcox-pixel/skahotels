<?php
require_once '../config/db.php';
require_once 'includes/auth.php';
ska_admin_require();

$id     = intval($_GET['id'] ?? 0);
$errors = [];

// ── Fetch room ──────────────────────────────────────────────────────────────
$stmt = $conn->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$room = $stmt->get_result()->fetch_assoc();

if (!$room) { header("Location: dashboard.php"); exit; }

// ── Fetch images ────────────────────────────────────────────────────────────
$stmtImg = $conn->prepare("SELECT * FROM room_images WHERE room_id = ?");
$stmtImg->bind_param("i", $id);
$stmtImg->execute();
$images = $stmtImg->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Fetch amenities ─────────────────────────────────────────────────────────
$stmtAmen = $conn->prepare("SELECT icon_class FROM room_amenities WHERE room_id = ?");
$stmtAmen->bind_param("i", $id);
$stmtAmen->execute();
$resAmen = $stmtAmen->get_result();

$selectedAmenities = [];
while ($a = $resAmen->fetch_assoc()) {
    $selectedAmenities[] = $a['icon_class'];
}

// ── Handle update ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name           = trim($_POST['name']           ?? '');
    $price          = (float)($_POST['price']        ?? 0);
    $price_low      = (float)($_POST['price_low']    ?? 0);
    $price_shoulder = (float)($_POST['price_shoulder'] ?? 0);
    $price_high     = (float)($_POST['price_high']   ?? 0);
    $desc           = trim($_POST['description']     ?? '');
    $branch         = trim($_POST['branch']          ?? '');

    if (!$name)   $errors[] = 'Room name is required.';
    if (!$price)  $errors[] = 'Base price is required.';
    if (!$branch) $errors[] = 'Branch is required.';

    if (empty($errors)) {

        $stmtU = $conn->prepare(
            "UPDATE rooms SET name=?, price=?, price_low=?, price_shoulder=?, price_high=?, description=?, branch=? WHERE id=?"
        );
        $stmtU->bind_param("sddddssi", $name, $price, $price_low, $price_shoulder, $price_high, $desc, $branch, $id);
        $stmtU->execute();

        // ── Delete marked images ──────────────────────────────────────────
        if (!empty($_POST['delete_images']) && is_array($_POST['delete_images'])) {
            $stmtFetch = $conn->prepare("SELECT image_path FROM room_images WHERE id = ? AND room_id = ?");
            $stmtDelImg = $conn->prepare("DELETE FROM room_images WHERE id = ? AND room_id = ?");

            foreach ($_POST['delete_images'] as $imgId) {
                $imgId = intval($imgId);
                if ($imgId <= 0) continue;

                $stmtFetch->bind_param("ii", $imgId, $id);
                $stmtFetch->execute();
                $row = $stmtFetch->get_result()->fetch_assoc();

                if ($row) {
                    $filePath = "../" . $row['image_path'];
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                    $stmtDelImg->bind_param("ii", $imgId, $id);
                    $stmtDelImg->execute();
                }
            }
        }

// ── Upload new images ──
$uploadDir    = "../uploads/rooms/";
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
$maxSize      = 2 * 1024 * 1024;

// Create directory if it doesn't exist
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (!empty($_FILES['images']['name'][0])) {
    $stmtImgIns = $conn->prepare("INSERT INTO room_images (room_id, image_path) VALUES (?, ?)");
    foreach ($_FILES['images']['tmp_name'] as $k => $tmp) {
        if ($_FILES['images']['error'][$k] !== UPLOAD_ERR_OK) continue;
        if (!in_array($_FILES['images']['type'][$k], $allowedTypes)) continue;
        if ($_FILES['images']['size'][$k] > $maxSize) continue;

        $ext      = strtolower(pathinfo($_FILES['images']['name'][$k], PATHINFO_EXTENSION));
        $fileName = uniqid('room_', true) . '.' . $ext;
        $destPath = $uploadDir . $fileName;

        if (move_uploaded_file($tmp, $destPath)) {
            $path = "uploads/rooms/" . $fileName;
            $stmtImgIns->bind_param("is", $id, $path);
            $stmtImgIns->execute();
        }
    }
}

        // ── Amenities ──
        $conn->query("DELETE FROM room_amenities WHERE room_id = $id");

        if (!empty($_POST['amenities'])) {
            $stmtAmenIns = $conn->prepare("INSERT INTO room_amenities (room_id, icon_class, name) VALUES (?, ?, ?)");
            foreach ($_POST['amenities'] as $icon => $label) {
                $stmtAmenIns->bind_param("iss", $id, $icon, $label);
                $stmtAmenIns->execute();
            }
        }

        header("Location: dashboard.php?updated=1");
        exit;
    }

    // Re-populate $room with POST values on error
    $room = array_merge($room, [
        'name'           => $_POST['name']           ?? $room['name'],
        'price'          => $_POST['price']          ?? $room['price'],
        'price_low'      => $_POST['price_low']      ?? $room['price_low'],
        'price_shoulder' => $_POST['price_shoulder'] ?? $room['price_shoulder'],
        'price_high'     => $_POST['price_high']     ?? $room['price_high'],
        'description'    => $_POST['description']    ?? $room['description'],
        'branch'         => $_POST['branch']         ?? $room['branch'],
    ]);
}

// ── Page config ─────────────────────────────────────────────────────────────
$activePage     = 'rooms';
$pageTitle      = 'Edit Room';
$pageBreadcrumb = 'Property → Rooms → Edit';

$allAmenities = [
    'fas fa-wifi'           => 'WiFi',
    'fas fa-tv'             => 'Smart TV',
    'fas fa-bed'            => 'King Bed',
    'fas fa-snowflake'      => 'Air Conditioning',
    'fas fa-warehouse'      => 'Wardrobe',
    'fas fa-chair'          => 'Seating Area',
    'fas fa-toilet'         => 'Private Bathroom',
    'fas fa-bath'           => 'Bathtub',
    'fas fa-concierge-bell' => 'Room Service',
    'fas fa-coffee'         => 'Coffee Maker',
    'fas fa-shield-alt'     => 'Safe',
    'fas fa-parking'        => 'Parking',
];
include 'includes/layout-start.php';
?>

<?php if (!empty($errors)): ?>
    <div class="ska-alert ska-alert--danger">
      <i class="fa-solid fa-triangle-exclamation me-2"></i>
      <?= implode('<br>', array_map('htmlspecialchars', $errors)) ?>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="editRoomForm">

      <!-- ── Basic Details ── -->
      <div class="ska-card">
        <div class="ska-card__header">
          <div class="ska-card__title">
            Room Details
            <span>Basic information about this room</span>
          </div>
        </div>
        <div class="ska-card__body">
          <div class="row g-4">

            <div class="col-md-8">
              <div class="ska-input-group">
                <label class="ska-label">Room Name *</label>
                <input name="name" class="ska-input"
                       placeholder="e.g. Deluxe King Suite"
                       value="<?= htmlspecialchars($room['name']) ?>" required>
              </div>
            </div>

            <div class="col-md-4">
              <div class="ska-input-group">
                <label class="ska-label">Branch *</label>
                <select name="branch" class="ska-select" required>
                  <option value="">Select branch…</option>
                  <?php foreach (['Naguru','Munyonyo','Kololo'] as $b): ?>
                  <option value="<?= $b ?>" <?= ($room['branch'] === $b) ? 'selected' : '' ?>><?= $b ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="col-12">
              <div class="ska-input-group" style="margin-bottom:0">
                <label class="ska-label">Description</label>
                <textarea name="description" class="ska-textarea"
                          placeholder="Describe the room's atmosphere, views, and highlights…"><?= htmlspecialchars($room['description'] ?? '') ?></textarea>
              </div>
            </div>

          </div>
        </div>
      </div>

      <!-- ── Seasonal Pricing ── -->
      <div class="ska-card">
        <div class="ska-card__header">
          <div class="ska-card__title">
            Seasonal Pricing
            <span>All rates in USD per night</span>
          </div>
        </div>
        <div class="ska-card__body">
          <div class="row g-4">

            <div class="col-md-6 col-lg-3">
              <div class="ska-season-pill ska-season-pill--base"><i class="fa-solid fa-circle"></i> Base Rate</div>
              <label class="ska-label">Standard Price *</label>
              <input name="price" type="number" step="0.01" min="0" class="ska-input"
                     placeholder="0.00" value="<?= htmlspecialchars($room['price']) ?>" required>
              <p class="ska-price-hint">Default / fallback rate</p>
            </div>

            <div class="col-md-6 col-lg-3">
              <div class="ska-season-pill ska-season-pill--low"><i class="fa-solid fa-circle"></i> Low Season</div>
              <label class="ska-label">Feb Rate</label>
              <input name="price_low" type="number" step="0.01" min="0" class="ska-input"
                     placeholder="0.00" value="<?= htmlspecialchars($room['price_low'] ?? '') ?>">
              <p class="ska-price-hint">February — lowest demand</p>
            </div>

            <div class="col-md-6 col-lg-3">
              <div class="ska-season-pill ska-season-pill--shoulder"><i class="fa-solid fa-circle"></i> Shoulder</div>
              <label class="ska-label">Mar–May, Sep–Nov</label>
              <input name="price_shoulder" type="number" step="0.01" min="0" class="ska-input"
                     placeholder="0.00" value="<?= htmlspecialchars($room['price_shoulder'] ?? '') ?>">
              <p class="ska-price-hint">Shoulder season (~+10%)</p>
            </div>

            <div class="col-md-6 col-lg-3">
              <div class="ska-season-pill ska-season-pill--high"><i class="fa-solid fa-circle"></i> High Season</div>
              <label class="ska-label">Jun–Aug, Dec–Jan</label>
              <input name="price_high" type="number" step="0.01" min="0" class="ska-input"
                     placeholder="0.00" value="<?= htmlspecialchars($room['price_high'] ?? '') ?>">
              <p class="ska-price-hint">Peak season (~+30%)</p>
            </div>

          </div>
        </div>
      </div>

      <!-- ── Images ─────────────────────────────────────── -->
      <div class="ska-card">
        <div class="ska-card__header">
          <div class="ska-card__title">
            Room Images
            <span>Manage existing photos and upload new ones</span>
          </div>
        </div>
        <div class="ska-card__body">

          <!-- Existing images -->
          <?php if (!empty($images)): ?>
          <p class="ska-img-count">
            <strong><?= count($images) ?></strong> existing photo<?= count($images) !== 1 ? 's' : '' ?> —
            hover and click <i class="fa-solid fa-trash-can" style="font-size:10px;color:var(--danger)"></i> to remove
          </p>
          <div class="ska-img-grid" id="existingGrid">
            <?php foreach ($images as $img): ?>
            <div class="ska-img-existing" id="img-wrap-<?= $img['id'] ?>">
              <!-- Badge shown when marked -->
              <span class="ska-img-existing__badge">Will delete</span>

              <img src="../<?= htmlspecialchars($img['image_path']) ?>"
                   alt="Room photo"
                   loading="lazy"
                   onerror="this.src='../assets/images/placeholder.jpg'">

              <div class="ska-img-existing__overlay">
                <button type="button"
                        class="ska-img-existing__del"
                        title="Delete image"
                        onclick="markImageDelete(<?= $img['id'] ?>, this)">
                  <i class="fa-solid fa-trash-can"></i>
                </button>
              </div>

              <!-- Hidden input — populated by JS when marked for deletion -->
              <input type="hidden" name="delete_images[]" id="del-<?= $img['id'] ?>" value="" disabled>
            </div>
            <?php endforeach; ?>
          </div>
          <hr class="ska-section-divider">
          <?php else: ?>
          <div class="ska-img-grid">
            <div class="ska-img-empty">
              <i class="fa-regular fa-image" style="font-size:24px;display:block;margin-bottom:8px;opacity:0.4"></i>
              No images uploaded yet — add some below.
            </div>
          </div>
          <hr class="ska-section-divider">
          <?php endif; ?>

          <!-- Upload new images -->
          <p class="ska-label" style="margin-bottom:10px;">Upload New Images</p>
          <div class="ska-upload-zone" id="uploadZone" onclick="document.getElementById('imageInput').click()">
            <i class="fa-solid fa-cloud-arrow-up"></i>
            <p>Click to upload or drag &amp; drop</p>
            <span>JPEG · PNG · WebP · max 2 MB per file</span>
          </div>
          <input type="file" name="images[]" id="imageInput" multiple accept="image/jpeg,image/png,image/webp">
          <div class="ska-img-preview-grid" id="previewGrid"></div>

        </div>
      </div>

      <!-- ── Amenities ── -->
      <div class="ska-card">
        <div class="ska-card__header">
          <div class="ska-card__title">
            Amenities
            <span>Select everything this room offers</span>
          </div>
        </div>
        <div class="ska-card__body">
          <div class="ska-amenity-grid">
            <?php foreach ($allAmenities as $icon => $label): ?>
            <?php $isChecked = in_array($icon, $selectedAmenities); ?>
            <label class="ska-amenity-item <?= $isChecked ? 'checked' : '' ?>">
              <input type="checkbox"
                     name="amenities[<?= htmlspecialchars($icon) ?>]"
                     value="<?= htmlspecialchars($label) ?>"
                     <?= $isChecked ? 'checked' : '' ?>>
              <div class="ska-amenity-icon"><i class="<?= htmlspecialchars($icon) ?>"></i></div>
              <span class="ska-amenity-label"><?= htmlspecialchars($label) ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- ── Submit ── -->
      <div class="d-flex gap-3 mb-5">
        <button type="submit" class="ska-btn ska-btn--gold" style="font-size:13px; padding: 10px 28px;">
          <i class="fa-solid fa-floppy-disk"></i> Save Changes
        </button>
        <a href="dashboard.php" class="ska-btn ska-btn--outline" style="font-size:13px; padding: 10px 22px;">
          Cancel
        </a>
      </div>

    </form>
<?php include 'includes/layout-end.php'; ?>
