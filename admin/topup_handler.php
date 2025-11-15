<?php
// topup_handler.php

require_once $_SERVER['DOCUMENT_ROOT'] . '/digital_agent/_partials/db.php';
// --- STEP 1: Include our new activity logger ---
require_once __DIR__ . '/_partials/activity_helper.php';


// It's good practice to call session_start() if it's not in your db.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Security Check: Ensure an Admin is performing this action ---
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_role']) !== 'admin') {
    exit('Access Denied.');
}

$action = $_GET['action'] ?? '';
$request_id = (int)($_GET['id'] ?? 0);

$redirect_url = 'topup_requests.php';
$query_params = [];
if (isset($_GET['filter'])) { $query_params['filter'] = $_GET['filter']; }
if (isset($_GET['search'])) { $query_params['search'] = $_GET['search']; }
$redirect_location = $redirect_url . "?" . http_build_query($query_params);


if (($action !== 'approve' && $action !== 'decline') || $request_id <= 0) {
    $_SESSION['message'] = "Invalid action or request ID.";
    $_SESSION['message_type'] = "error";
    header("Location: " . $redirect_location);
    exit();
}

// Fetch the request details AND the user's name for a better log message
$stmt_req = $conn->prepare("
    SELECT tr.user_id, tr.amount, tr.status, u.full_name 
    FROM topup_requests tr
    JOIN users u ON tr.user_id = u.id
    WHERE tr.id = ?
");
$stmt_req->bind_param("i", $request_id);
$stmt_req->execute();
$request = $stmt_req->get_result()->fetch_assoc();

if (!$request) {
    $_SESSION['message'] = "Top-up request not found.";
    $_SESSION['message_type'] = "error";
    header("Location: " . $redirect_location);
    exit();
}

if ($request['status'] !== 'Pending') {
    $_SESSION['message'] = "This request has already been processed.";
    $_SESSION['message_type'] = "error";
    header("Location: " . $redirect_location);
    exit();
}

// --- Main Logic ---

if ($action == 'approve') {
    $conn->begin_transaction();
    try {
        // --- THIS IS YOUR ORIGINAL, WORKING WALLET LOGIC ---
        $check_wallet = $conn->prepare("SELECT id FROM wallet WHERE user_id = ?");
        $check_wallet->bind_param("i", $request['user_id']);
        $check_wallet->execute();
        $result = $check_wallet->get_result();

        if ($result->num_rows == 0) {
            $create_wallet = $conn->prepare("INSERT INTO wallet (user_id, balance) VALUES (?, ?)");
            $create_wallet->bind_param("id", $request['user_id'], $request['amount']);
            $create_wallet->execute();
        } else {
            $update_wallet_stmt = $conn->prepare("UPDATE wallet SET balance = balance + ? WHERE user_id = ?");
            $update_wallet_stmt->bind_param("di", $request['amount'], $request['user_id']);
            $update_wallet_stmt->execute();
        }
        // --- END OF YOUR ORIGINAL WALLET LOGIC ---

        $update_request_stmt = $conn->prepare("UPDATE topup_requests SET status = 'Approved', reviewed_at = NOW() WHERE id = ?");
        $update_request_stmt->bind_param("i", $request_id);
        $update_request_stmt->execute();
        
        // --- Log the approval activity for the ADMIN ---
        $log_description = "Approved a top-up of GHS " . number_format($request['amount'], 2) . " for user '" . $request['full_name'] . "'.";
        logActivity($conn, $log_description);
        
        // ★★★ START: NEW CODE TO NOTIFY THE AGENT OF APPROVAL ★★★
        $agent_message_approve = "Your manual top-up of GHS " . number_format($request['amount'], 2) . " has been approved and your wallet credited.";
        $stmt_agent_activity = $conn->prepare("INSERT INTO activity_log (user_id, action_description, created_at) VALUES (?, ?, NOW())");
        $stmt_agent_activity->bind_param("is", $request['user_id'], $agent_message_approve);
        $stmt_agent_activity->execute();
        // ★★★ END: NEW CODE ★★★

        $conn->commit();
        $_SESSION['message'] = "Request approved and wallet credited successfully.";
        $_SESSION['message_type'] = "success";

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['message'] = "An error occurred: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
    }
} elseif ($action == 'decline') {
    $update_request_stmt = $conn->prepare("UPDATE topup_requests SET status = 'Declined', reviewed_at = NOW() WHERE id = ?");
    $update_request_stmt->bind_param("i", $request_id);
    
    if ($update_request_stmt->execute()) {
        // --- Log the decline activity for the ADMIN ---
        $log_description = "Declined a top-up request of GHS " . number_format($request['amount'], 2) . " for user '" . $request['full_name'] . "'.";
        logActivity($conn, $log_description);
        
        // ★★★ START: NEW CODE TO NOTIFY THE AGENT OF DECLINE ★★★
        $agent_message_decline = "Your manual top-up request of GHS " . number_format($request['amount'], 2) . " has been declined.";
        $stmt_agent_activity = $conn->prepare("INSERT INTO activity_log (user_id, action_description, created_at) VALUES (?, ?, NOW())");
        $stmt_agent_activity->bind_param("is", $request['user_id'], $agent_message_decline);
        $stmt_agent_activity->execute();
        // ★★★ END: NEW CODE ★★★

        $_SESSION['message'] = "Request has been declined.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Failed to decline the request.";
        $_SESSION['message_type'] = "error";
    }
}

// Final Redirect
header("Location: " . $redirect_location);
exit();
?>