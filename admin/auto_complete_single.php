<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/digital_agent/_partials/db.php';

if (!isset($_POST['order_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing order_id']);
    exit;
}

$order_id = intval($_POST['order_id']);
$stmt = $conn->prepare("UPDATE orders SET status = 'Complete' WHERE id = ? AND status = 'Processing'");
$stmt->bind_param("i", $order_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'failed', 'message' => 'Not updated']);
}
