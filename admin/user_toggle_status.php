<?php
// This is a backend handler. It should NOT print any HTML.
// We only require the db.php file for the database connection and session.
require_once $_SERVER['DOCUMENT_ROOT'] . '/digital_agent/_partials/db.php';

// Security Check: Ensure an Admin is performing this action
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    exit('Access Denied.');
}

// Get the user ID from the URL
$user_id_to_toggle = $_GET['id'] ?? 0;
$redirect_page = 'users.php';

// Validation
if (!is_numeric($user_id_to_toggle) || $user_id_to_toggle <= 0) {
    header("Location: " . $redirect_page . "?status=error&msg=InvalidRequest");
    exit();
}
if ($user_id_to_toggle == $_SESSION['user_id']) {
    header("Location: " . $redirect_page . "?status=error&msg=SelfBlock");
    exit();
}

// Fetch the current status
$stmt = $conn->prepare("SELECT status FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id_to_toggle);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: " . $redirect_page . "?status=error&msg=UserNotFound");
    exit();
}

$user = $result->fetch_assoc();
$new_status = ($user['status'] == 'active') ? 'blocked' : 'active';

// Update the database
$update_stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
$update_stmt->bind_param("si", $new_status, $user_id_to_toggle);

// Redirect with the appropriate message code
if ($update_stmt->execute()) {
    header("Location: " . $redirect_page . "?status=success&msg=UserStatusUpdated");
} else {
    header("Location: " . $redirect_page . "?status=error&msg=UpdateFailed");
}
exit();
?>