<?php
/**
 * delete_image.php
 * Handles image deletion — called on form submit via the edit_room.php form.
 * Can also be called via fetch() for single-image AJAX delete if needed.
 *
 * POST params:
 *   id          (int)   — single image id (AJAX / legacy)
 *   ids[]       (int[]) — batch of image ids (form submit from edit_room)
 */
require_once '../config/db.php';
require_once 'includes/auth.php';
ska_admin_require();

// Collect IDs — support both single (id) and batch (ids[])
$ids = [];

if (!empty($_POST['id'])) {
    $ids[] = intval($_POST['id']);
}

if (!empty($_POST['ids']) && is_array($_POST['ids'])) {
    foreach ($_POST['ids'] as $v) {
        $ids[] = intval($v);
    }
}

$ids = array_unique(array_filter($ids));

if (empty($ids)) {
    http_response_code(400);
    echo json_encode(['error' => 'No image IDs provided']);
    exit;
}

$deleted = 0;
$errors  = [];

foreach ($ids as $imgId) {
    // Fetch the image record
    $stmt = $conn->prepare("SELECT image_path FROM room_images WHERE id = ?");
    $stmt->bind_param("i", $imgId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) {
        $errors[] = "Image $imgId not found.";
        continue;
    }

    // Delete physical file
    $filePath = "../" . $row['image_path'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    // Delete DB record
    $stmtDel = $conn->prepare("DELETE FROM room_images WHERE id = ?");
    $stmtDel->bind_param("i", $imgId);
    $stmtDel->execute();

    $deleted++;
}

// If called via fetch (AJAX), return JSON
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
    header('Content-Type: application/json');
    echo json_encode(['deleted' => $deleted, 'errors' => $errors]);
    exit;
}

// Otherwise redirect back (fallback)
header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'dashboard.php'));
exit;