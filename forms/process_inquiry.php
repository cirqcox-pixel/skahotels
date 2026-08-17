<?php
/**
 * Generic contact / inquiry handler
 */
include '../config/db.php';
include '../config/cms.php';

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
$body = "New inquiry from SKA website\n\nName: $name\nEmail: $email\nPhone: $phone\nSubject: $subject\n\n$message";
$headers = "From: noreply@skaboutiquebnb.com\r\nReply-To: $email";
@mail($to, "SKA Website: $subject", $body, $headers);

header('Location: ' . $redirect . '?sent=1');
exit;
