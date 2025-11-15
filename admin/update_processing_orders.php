<?php
// admin/update_processing_orders.php
// This is the final, correct code for your server-side cron job.
// This script is the single source of truth for auto-completing orders.

// Set the script to run from the correct directory context
chdir(dirname(__FILE__));

// Use your existing database connection file
// Make sure this path is correct for your server setup.
require_once $_SERVER['DOCUMENT_ROOT'] . '/digital_agent/_partials/db.php';
// Include the activity logger if you want to log this automatic action
require_once __DIR__ . '/_partials/activity_helper.php';

echo "Cron job started at " . date('Y-m-d H:i:s') . "\n";

// This query correctly finds all orders stuck in 'Processing' for more than an hour.
$stmt = $conn->prepare("
    SELECT o.id, o.user_id, o.order_details, u.full_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE 
        o.status = 'Processing' 
        AND (
            o.updated_at <= NOW() - INTERVAL 1 HOUR 
            OR 
            o.created_at <= NOW() - INTERVAL 1 HOUR
        )
");
$stmt->execute();
$orders_to_complete = $stmt->get_result();

if ($orders_to_complete->num_rows === 0) {
    echo "No stuck orders found to update.\n";
    exit;
}

echo "Found " . $orders_to_complete->num_rows . " order(s) to move to 'Complete'.\n";

// This loop correctly processes each stuck order.
while ($order = $orders_to_complete->fetch_assoc()) {
    $order_id = $order['id'];
    $user_id = $order['user_id'];
    $user_full_name = $order['full_name'];
    $details = json_decode($order['order_details'], true);
    $order_price = $details['price'] ?? 0;
    $service_name = $details['name'] ?? 'a service';

    echo "Processing Order ID: $order_id...\n";

    $conn->begin_transaction();
    try {
        // 1. Update the order status to 'Complete'
        $update_order_stmt = $conn->prepare("UPDATE orders SET status = 'Complete' WHERE id = ?");
        $update_order_stmt->bind_param("i", $order_id);
        $update_order_stmt->execute();

        // 2. Create a purchase transaction record if it's a paid service
        if ($order_price > 0) {
            $trans_stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, description, status, transaction_ref) VALUES (?, 'purchase', ?, ?, 'completed', ?)");
            $description = "Purchase of " . $service_name;
            $transaction_ref = "ORD-" . $order_id . "-" . time();
            $trans_stmt->bind_param("idss", $user_id, $order_price, $description, $transaction_ref);
            $trans_stmt->execute();
        }
        
        // 3. Log the automatic completion activity
        $log_description = "[AUTO] Completed " . $service_name . " (Order #" . str_pad($order_id, 5, '0', STR_PAD_LEFT) . ") for user '" . $user_full_name . "'.";
        logActivity($conn, $log_description);

        $conn->commit();
        echo "Successfully completed Order ID: $order_id.\n";

    } catch (Exception $e) {
        $conn->rollback();
        // Log the error for debugging
        error_log("Failed to auto-complete Order ID $order_id: " . $e->getMessage());
        echo "Failed to complete Order ID: $order_id. Check error log.\n";
    }
}

echo "Cron job finished at " . date('Y-m-d H:i:s') . "\n";
?>