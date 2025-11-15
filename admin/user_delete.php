<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/digital_agent/_partials/db.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header('Location: users.php');
    exit;
}

$userIdToDelete = intval($_GET['id']);
$currentUserId = $_SESSION['user_id'];

if ($userIdToDelete === $currentUserId) {
    $_SESSION['message'] = "You cannot delete your own account.";
    $_SESSION['message_type'] = "error";
    header("Location: users.php");
    exit;
}

$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $userIdToDelete);

if ($stmt->execute()) {
    $_SESSION['message'] = "User deleted successfully.";
    $_SESSION['message_type'] = "success";
} else {
    $_SESSION['message'] = "Failed to delete user.";
    $_SESSION['message_type'] = "error";
}

$stmt->close();
$conn->close();
header("Location: users.php");
exit;
