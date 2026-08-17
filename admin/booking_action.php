<?php
require_once '../config/db.php';
require_once 'includes/auth.php';
ska_admin_require();
require_once '../config/SkaMailer.php';

/* INPUTS */
$bookingId = (int)($_POST['booking_id'] ?? 0);
$action    = trim($_POST['action'] ?? '');
$reason    = trim($_POST['reason'] ?? '');

if ($bookingId <= 0 || !in_array($action, ['confirm', 'cancel'])) {
    header("Location: bookings.php?error=invalid");
    exit;
}

/* FETCH BOOKING */
$stmt = $conn->prepare("SELECT * FROM bookings WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $bookingId);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$booking) {
    header("Location: bookings.php?error=notfound");
    exit;
}

/* DETERMINE STATUS */
$newStatus = ($action === 'confirm') ? 'confirmed' : 'cancelled';

if (!$reason) {
    $reason = ($action === 'confirm')
        ? 'Your reservation has been confirmed.'
        : 'We are unable to accommodate your request.';
}

/* UPDATE */
$upd = $conn->prepare("
    UPDATE bookings 
    SET status = ?, status_reason = ?, status_updated_at = NOW()
    WHERE id = ?
");

$upd->bind_param('ssi', $newStatus, $reason, $bookingId);

if (!$upd->execute()) {
    header("Location: bookings.php?error=" . urlencode($upd->error));
    exit;
}

if ($upd->affected_rows === 0) {
    header("Location: bookings.php?error=nochange");
    exit;
}

$upd->close();

/* SEND EMAILS */
$mailer = new SkaMailer();

try {
    if ($newStatus === 'confirmed') {
        $mailer->sendBookingConfirmed($booking);
    } else {
        $mailer->sendBookingCancelled($booking, $reason);
    }
} catch (Throwable $e) {
    error_log("Guest mail failed: " . $e->getMessage());
}

/* REDIRECT */
header('Location: bookings.php?updated=' . ($newStatus === 'confirmed' ? 'confirmed' : 'cancelled'));
exit;




