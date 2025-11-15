<?php
include_once '_partials/header.php';

header('Content-Type: application/json');

$sql = "SELECT id, order_details, created_at FROM orders WHERE status = 'Pending' ORDER BY created_at DESC LIMIT 5";
$result = $conn->query($sql);

$orders = [];
while ($row = $result->fetch_assoc()) {
    $details = json_decode($row['order_details'], true);
    $orders[] = [
        'id' => $row['id'],
        'name' => $details['name'] ?? 'Unknown',
        'time' => date("d M, h:i A", strtotime($row['created_at']))
    ];
}

echo json_encode([
    'count' => count($orders),
    'orders' => $orders
]);