<?php
require_once '../config/db.php';
require_once 'includes/auth.php';
ska_admin_require();

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name        = trim($_POST['name']        ?? '');
    $price       = (float)($_POST['price']    ?? 0);
    $price_low   = (float)($_POST['price_low']       ?? 0);
    $price_shoulder = (float)($_POST['price_shoulder'] ?? 0);
    $price_high  = (float)($_POST['price_high']      ?? 0);
    $desc        = trim($_POST['description'] ?? '');
    $branch      = trim($_POST['branch']      ?? '');

    if (!$name)   $errors[] = 'Room name is required.';
    if (!$price)  $errors[] = 'Base price is required.';
    if (!$branch) $errors[] = 'Branch is required.';

    if (empty($errors)) {
        $stmt = $conn->prepare(
            "INSERT INTO rooms (name, price, price_low, price_shoulder, price_high, description, branch)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("sddddss", $name, $price, $price_low, $price_shoulder, $price_high, $desc, $branch);
        $stmt->execute();
        $room_id = $stmt->insert_id;

// ── Images ──
$uploadDir    = "../uploads/rooms/";
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
$maxSize      = 2 * 1024 * 1024;

// Create directory if it doesn't exist
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (!empty($_FILES['images']['name'][0])) {
    $stmtImg = $conn->prepare("INSERT INTO room_images (room_id, image_path) VALUES (?, ?)");
    foreach ($_FILES['images']['tmp_name'] as $key => $tmp) {
        if ($_FILES['images']['error'][$key] !== UPLOAD_ERR_OK) continue;
        if (!in_array($_FILES['images']['type'][$key], $allowedTypes)) continue;
        if ($_FILES['images']['size'][$key] > $maxSize) continue;

        $ext      = strtolower(pathinfo($_FILES['images']['name'][$key], PATHINFO_EXTENSION));
        $fileName = uniqid('room_', true) . '.' . $ext;
        $destPath = $uploadDir . $fileName;

        if (move_uploaded_file($tmp, $destPath)) {
            $path = "uploads/rooms/" . $fileName;
            $stmtImg->bind_param("is", $room_id, $path);
            $stmtImg->execute();
        }
    }
}

        // ── Amenities ──
        if (!empty($_POST['amenities'])) {
            $stmtA = $conn->prepare(
                "INSERT INTO room_amenities (room_id, icon_class, name) VALUES (?, ?, ?)"
            );
            foreach ($_POST['amenities'] as $icon => $label) {
                $stmtA->bind_param("iss", $room_id, $icon, $label);
                $stmtA->execute();
            }
        }

        header("Location: dashboard.php?created=1");
        exit;
    }
}

// ── Page config ──
$activePage     = 'rooms';
$pageTitle      = 'Add New Room';
$pageBreadcrumb = 'Property → Rooms → Add';
$topbarAction   = ['label' => 'Back to Dashboard', 'href' => 'dashboard.php', 'icon' => 'fa-arrow-left'];

$allAmenities = [
    'fas fa-wifi'      => 'WiFi',
    'fas fa-tv'        => 'Smart TV',
    'fas fa-bed'       => 'King Bed',
    'fas fa-snowflake' => 'Air Conditioning',
    'fas fa-warehouse' => 'Wardrobe',
    'fas fa-chair'     => 'Seating Area',
    'fas fa-toilet'    => 'Private Bathroom',
    'fas fa-bath'      => 'Bathtub',
    'fas fa-concierge-bell' => 'Room Service',
    'fas fa-coffee'    => 'Coffee Maker',
    'fas fa-shield-alt'=> 'Safe',
    'fas fa-parking'   => 'Parking',
];
$contentClass = 'ska-content--narrow';
include 'includes/layout-start.php';
?>

<?php if (!empty($errors)): ?>
    <div class="ska-alert ska-alert--danger">
      <i class="fa-solid fa-triangle-exclamation me-2"></i>
      <?= implode('<br>', array_map('htmlspecialchars', $errors)) ?>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

      <!-- ── Basic Info ── -->
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
                <input name="name" class="ska-input" placeholder="e.g. Deluxe King Suite"
                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
              </div>
            </div>

            <div class="col-md-4">
              <div class="ska-input-group">
                <label class="ska-label">Branch *</label>
                <select name="branch" class="ska-select" required>
                  <option value="">Select branch…</option>
                  <?php foreach (['Naguru','Munyonyo','Kololo'] as $b): ?>
                  <option value="<?= $b ?>" <?= (($_POST['branch'] ?? '') === $b) ? 'selected' : '' ?>><?= $b ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="col-12">
              <div class="ska-input-group" style="margin-bottom:0">
                <label class="ska-label">Description</label>
                <textarea name="description" class="ska-textarea"
                          placeholder="Describe the room's atmosphere, views, and highlights…"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
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
            <span>Set rates for each season — all in USD per night</span>
          </div>
        </div>
        <div class="ska-card__body">
          <div class="row g-4">

            <div class="col-md-6 col-lg-3">
              <div class="ska-season-pill ska-season-pill--base"><i class="fa-solid fa-circle"></i> Base Rate</div>
              <label class="ska-label">Standard Price *</label>
              <input name="price" type="number" step="0.01" min="0" class="ska-input"
                     placeholder="0.00" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" required>
              <p class="ska-price-hint">Default / fallback rate</p>
            </div>

            <div class="col-md-6 col-lg-3">
              <div class="ska-season-pill ska-season-pill--low"><i class="fa-solid fa-circle"></i> Low Season</div>
              <label class="ska-label">Feb Rate</label>
              <input name="price_low" type="number" step="0.01" min="0" class="ska-input"
                     placeholder="0.00" value="<?= htmlspecialchars($_POST['price_low'] ?? '') ?>">
              <p class="ska-price-hint">February — lowest demand</p>
            </div>

            <div class="col-md-6 col-lg-3">
              <div class="ska-season-pill ska-season-pill--shoulder"><i class="fa-solid fa-circle"></i> Shoulder</div>
              <label class="ska-label">Mar–May, Sep–Nov</label>
              <input name="price_shoulder" type="number" step="0.01" min="0" class="ska-input"
                     placeholder="0.00" value="<?= htmlspecialchars($_POST['price_shoulder'] ?? '') ?>">
              <p class="ska-price-hint">Shoulder season (~+10%)</p>
            </div>

            <div class="col-md-6 col-lg-3">
              <div class="ska-season-pill ska-season-pill--high"><i class="fa-solid fa-circle"></i> High Season</div>
              <label class="ska-label">Jun–Aug, Dec–Jan</label>
              <input name="price_high" type="number" step="0.01" min="0" class="ska-input"
                     placeholder="0.00" value="<?= htmlspecialchars($_POST['price_high'] ?? '') ?>">
              <p class="ska-price-hint">Peak season (~+30%)</p>
            </div>

          </div>
        </div>
      </div>

      <!-- ── Images ── -->
      <div class="ska-card">
        <div class="ska-card__header">
          <div class="ska-card__title">
            Room Images
            <span>Upload up to 10 photos — JPEG, PNG or WebP, max 2 MB each</span>
          </div>
        </div>
        <div class="ska-card__body">
          <div class="ska-upload-zone" onclick="document.getElementById('imageInput').click()">
            <i class="fa-solid fa-cloud-arrow-up"></i>
            <p>Click to upload or drag &amp; drop images</p>
            <span>JPEG · PNG · WebP · max 2 MB per file</span>
          </div>
          <input type="file" name="images[]" id="imageInput" multiple accept="image/*">
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
            <label class="ska-amenity-item" id="am-<?= md5($icon) ?>">
              <input type="checkbox" name="amenities[<?= htmlspecialchars($icon) ?>]"
                     value="<?= htmlspecialchars($label) ?>">
              <div class="ska-amenity-icon"><i class="<?= htmlspecialchars($icon) ?>"></i></div>
              <span class="ska-amenity-label"><?= htmlspecialchars($label) ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- ── Submit ── -->
      <div class="d-flex gap-3 mb-4">
        <button type="submit" class="ska-btn ska-btn--gold" style="font-size:13px; padding: 10px 28px;">
          <i class="fa-solid fa-floppy-disk"></i> Save Room
        </button>
        <a href="dashboard.php" class="ska-btn ska-btn--outline" style="font-size:13px; padding: 10px 22px;">
          Cancel
        </a>
      </div>

    </form>
<?php include 'includes/layout-end.php'; ?>
