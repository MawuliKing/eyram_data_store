<?php
// admin/order_handler.php

require_once $_SERVER['DOCUMENT_ROOT'] . '/digital_agent/_partials/db.php';
require_once __DIR__ . '/_partials/activity_helper.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security check for admin
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_role']) !== 'admin' && strtolower($_SESSION['user_role']) !== 'platform_admin') {
    exit('Access Denied.');
}

$action = $_GET['action'] ?? '';
$order_id = (int)($_GET['id'] ?? 0);

$redirect_page = $_GET['redirect_to'] ?? 'orders.php';
$safe_pages = ['orders.php', 'form_submissions.php'];
if (!in_array($redirect_page, $safe_pages)) {
    $redirect_page = 'orders.php';
}

if (($action !== 'approve' && $action !== 'decline') || $order_id <= 0) {
    header("Location: " . $redirect_page . "?status=error&msg=InvalidRequest");
    exit();
}

// Enhance the query to get user's name and service name for logging
$stmt_order = $conn->prepare("
    SELECT o.user_id, o.order_details, o.status, u.full_name 
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
");
$stmt_order->bind_param("i", $order_id);
$stmt_order->execute();
$order = $stmt_order->get_result()->fetch_assoc();

if (!$order) {
    header("Location: " . $redirect_page . "?status=error&msg=OrderNotFound");
    exit();
}

if ($order['status'] !== 'Pending' && $order['status'] !== 'Processing') {
    header("Location: " . $redirect_page . "?status=error&msg=OrderAlreadyProcessed");
    exit();
}

$details = json_decode($order['order_details'], true);
$order_price = $details['price'] ?? 0;
$user_id = $order['user_id'];
$user_full_name = $order['full_name'];
$service_name = $details['name'] ?? 'a service'; // Get service name for log

// --- Main Logic ---

if ($action == 'approve') {
    $conn->begin_transaction();
    try {
        $update_order_stmt = $conn->prepare("UPDATE orders SET status = 'Complete' WHERE id = ?");
        $update_order_stmt->bind_param("i", $order_id);
        $update_order_stmt->execute();

        // Only create a purchase transaction if it's a paid service
        if ($order_price > 0) {
            $trans_stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, description, status, transaction_ref) VALUES (?, 'purchase', ?, ?, 'completed', ?)");
            $description = "Purchase of " . $service_name;
            $transaction_ref = "ORD-" . $order_id . "-" . time();
            $trans_stmt->bind_param("idss", $user_id, $order_price, $description, $transaction_ref);
            $trans_stmt->execute();
        }
        
        // Log the approval activity
        $log_description = "Approved " . $service_name . " (Order #" . str_pad($order_id, 5, '0', STR_PAD_LEFT) . ") for user '" . $user_full_name . "'.";
        logActivity($conn, $log_description);

        $conn->commit();
        $_SESSION['message'] = "Order approved successfully.";
        $_SESSION['message_type'] = "success";
        header("Location: " . $redirect_page);
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: " . $redirect_page . "?status=error&msg=UpdateFailed");
    }
    exit();
}

elseif ($action == 'decline') {
    $conn->begin_transaction();
    try {
        // Refund the agent only if it was a paid service
        if ($order_price > 0) {
            $refund_stmt = $conn->prepare("UPDATE wallet SET balance = balance + ? WHERE user_id = ?");
            $refund_stmt->bind_param("di", $order_price, $user_id);
            $refund_stmt->execute();
        }
        
        $decline_stmt = $conn->prepare("UPDATE orders SET status = 'Failed' WHERE id = ?");
        $decline_stmt->bind_param("i", $order_id);
        $decline_stmt->execute();

        // Log the decline activity
        $log_description = "Declined " . $service_name . " (Order #" . str_pad($order_id, 5, '0', STR_PAD_LEFT) . ") for user '" . $user_full_name . "'.";
        logActivity($conn, $log_description);
        
        $conn->commit();
        $_SESSION['message'] = "Order declined successfully.";
        $_SESSION['message_type'] = "success";
        header("Location: " . $redirect_page);
        
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: " . $redirect_page . "?status=error&msg=RefundFailed");
    }
    exit();
}
?>