<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

include '../config/db.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: rooms.php");
    exit;
}

$stmt = $conn->prepare("DELETE FROM rooms WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->close();

header("Location: rooms.php?deleted=1");
exit;
