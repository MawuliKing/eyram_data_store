<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/digital_agent/_partials/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

$_SESSION['alert_message'] = "Announcement deleted.";
$_SESSION['alert_type'] = "danger";
header("Location: announcements.php");
exit();
