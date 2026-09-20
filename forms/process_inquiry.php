<?php
/**
 * Generic contact / inquiry handler
 */
include '../config/db.php';
include '../config/cms.php';
require_once '../config/SkaMailer.php';

$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$phone   = trim($_POST['phone'] ?? '');
$subject = trim($_POST['subject'] ?? 'General Inquiry');
$message = trim($_POST['message'] ?? '');
$redirect = $_POST['redirect'] ?? '../contact.php';

$errors = [];
if (!$name)  $errors[] = 'Name is required.';
if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
if (!$message) $errors[] = 'Message is required.';

if ($errors) {
    header('Location: ' . $redirect . '?error=' . urlencode(implode(' ', $errors)));
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO inquiries (name, email, phone, subject, message, is_read, created_at)
    VALUES (?, ?, ?, ?, ?, 0, NOW())
");

if ($stmt) {
    $stmt->bind_param('sssss', $name, $email, $phone, $subject, $message);
    @$stmt->execute();
    $stmt->close();
}

$to = cms_setting('site_email', 'info@skaboutiquebnb.com');
$mailer = new SkaMailer();
$mailer->sendInquiryReceived([
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'subject' => $subject,
    'message' => $message,
    'site_email' => $to,
]);

header('Location: ' . $redirect . '?sent=1');
exit;
