<?php
// Set the content type to JSON
header('Content-Type: application/json');

// Include the header which contains the database connection and session start
include_once '_partials/header.php';

// Security: Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    // If not logged in, return an error and stop
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

// Get the JSON data sent from the agent's page
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Check if we received a valid list of order IDs
if (empty($data['order_ids']) || !is_array($data['order_ids'])) {
    // If not, return an empty object
    echo json_encode([]);
    exit();
}

$user_id = $_SESSION['user_id'];
$order_ids = $data['order_ids'];

// Prepare placeholders for the SQL query (e.g., ?,?,?)
$placeholders = implode(',', array_fill(0, count($order_ids), '?'));
// Prepare the data types for bind_param (e.g., 'iii' for three integers)
$types = str_repeat('i', count($order_ids));

// The SQL query will find the current status of the requested orders, but ONLY if:
// 1. The order belongs to the currently logged-in user (for security).
// 2. The order's status is NO LONGER 'Processing'. This is efficient.
$sql = "SELECT id, status 
        FROM orders 
        WHERE user_id = ? AND id IN ($placeholders) AND status != 'Processing'";

$stmt = $conn->prepare($sql);

// Bind the user_id first, then all the order IDs
$stmt->bind_param('i' . $types, $user_id, ...$order_ids);
$stmt->execute();
$result = $stmt->get_result();

$updated_statuses = [];
while ($row = $result->fetch_assoc()) {
    // Create an associative array like [ "21569" => "Complete" ]
    $updated_statuses[$row['id']] = $row['status'];
}

// Return the list of updated statuses as JSON
echo json_encode($updated_statuses);
?>