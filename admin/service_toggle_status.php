<?php
include_once '_partials/header.php';

$service_id = $_GET['id'] ?? null;
// We now get the parent_id to redirect back correctly
$parent_id = $_GET['parent_id'] ?? 0;
$redirect_page = ($parent_id > 0) ? 'manage_service.php?id=' . $parent_id : 'services_main.php';

// ... (rest of the toggle logic from before) ...

$stmt = $conn->prepare("SELECT status FROM services WHERE id = ?");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$result = $stmt->get_result();
$service = $result->fetch_assoc();
$new_status = ($service['status'] == 'enabled') ? 'disabled' : 'enabled';

$update_stmt = $conn->prepare("UPDATE services SET status = ? WHERE id = ?");
$update_stmt->bind_param("si", $new_status, $service_id);
$update_stmt->execute();

$_SESSION['message'] = "Status updated.";
$_SESSION['message_type'] = "success";

header("Location: " . $redirect_page . "&t=" . time());
exit();
?>