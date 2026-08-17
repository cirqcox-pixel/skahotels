<?php
/**
 * FILE: forms/process_contact_munyonyo.php
 * Handles the Munyonyo booking form submission:
 *   1. Validates input
 *   2. Saves booking to the `bookings` table (status = 'pending', branch = 'Munyonyo')
 *   3. Emails the guest a "request received" confirmation
 *   4. Emails the Munyonyo admin team a "new booking" notification
 *   5. Redirects back to the booking form with a status flag
 */

include '../config/db.php';
require_once '../config/SkaMailer.php';

/* ══════════════════════════════════════════════════
   1. COLLECT & SANITISE
══════════════════════════════════════════════════ */
$name     = trim($_POST['name']      ?? '');
$email    = trim($_POST['email']     ?? '');
$whatsapp = trim($_POST['whatsapp']  ?? '');
$phone    = trim($_POST['phone']     ?? '');
$room     = trim($_POST['room_type'] ?? '');
$price    = (float)($_POST['price']  ?? 0);   // price per night
$checkin  = trim($_POST['checkin']   ?? '');
$checkout = trim($_POST['checkout']  ?? '');
$message  = trim($_POST['message']   ?? '');
$season   = trim($_POST['season']    ?? 'low');
$branch   = 'Munyonyo';                        // hard-coded for this form

/* ══════════════════════════════════════════════════
   2. VALIDATE
══════════════════════════════════════════════════ */
$errors = [];
if (empty($name))     $errors[] = 'Full name is required.';
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL))
                      $errors[] = 'A valid email address is required.';
if (empty($phone))    $errors[] = 'Phone number is required.';
if (empty($room))     $errors[] = 'Please select a room type.';
if (empty($checkin))  $errors[] = 'Check-in date is required.';
if (empty($checkout)) $errors[] = 'Check-out date is required.';

if (!empty($errors)) {
    $msg = urlencode(implode(' ', $errors));
    header("Location: ../munyonyo.php?error={$msg}#book");
    exit;
}

/* ══════════════════════════════════════════════════
   3. CALCULATE NIGHTS & TOTAL
══════════════════════════════════════════════════ */
$nights = 1;
try {
    $ci = new DateTime($checkin);
    $co = new DateTime($checkout);
    if ($co > $ci) {
        $nights = (int)$co->diff($ci)->days;
    }
} catch (Exception $e) {}

$total = $price * $nights;

/* ══════════════════════════════════════════════════
   4. SAVE TO DATABASE
══════════════════════════════════════════════════ */
$dbSaved   = false;
$bookingId = null;

$stmt = $conn->prepare("
    INSERT INTO bookings
        (name, email, phone, whatsapp, room_type, price,
         checkin, checkout, total, message, season, branch, status, created_at)
    VALUES
        (?, ?, ?, ?, ?, ?,
         ?, ?, ?, ?, ?, ?, 'pending', NOW())
");

if ($stmt) {
    $stmt->bind_param(
        'sssssdssdsss',
        $name, $email, $phone, $whatsapp, $room, $price,
        $checkin, $checkout, $total, $message, $season, $branch
    );
    if ($stmt->execute()) {
        $dbSaved   = true;
        $bookingId = $conn->insert_id;
    }
    $stmt->close();
}

/* ══════════════════════════════════════════════════
   5. BUILD BOOKING ARRAY FOR MAILER
══════════════════════════════════════════════════ */
$booking = [
    'id'        => $bookingId,
    'name'      => $name,
    'email'     => $email,
    'phone'     => $phone,
    'whatsapp'  => $whatsapp,
    'room_type' => $room,
    'price'     => $price,
    'checkin'   => $checkin,
    'checkout'  => $checkout,
    'total'     => $total,
    'message'   => $message,
    'season'    => $season,
    'branch'    => $branch,
    'status'    => 'pending',
];

/* ══════════════════════════════════════════════════
   6. SEND EMAILS
   a) Guest  — "we received your request"
   b) Admins — "new booking to review" (Munyonyo addresses)
══════════════════════════════════════════════════ */
$mailer    = new SkaMailer();
$guestSent = false;

if ($dbSaved) {
    $guestSent = $mailer->sendBookingPlaced($booking);
    $mailer->sendAdminNewBooking($booking);   // branch-aware — routes to Munyonyo emails
}

/* ══════════════════════════════════════════════════
   7. REDIRECT
══════════════════════════════════════════════════ */
if ($dbSaved) {
    $flag = $guestSent ? 'success' : 'saved';
    header("Location: ../munyonyo.php?booking={$flag}#book");
} else {
    $err = urlencode('We could not record your booking. Please call us directly on +256 200 904 877.');
    header("Location: ../munyonyo.php?error={$err}#book");
}
exit;