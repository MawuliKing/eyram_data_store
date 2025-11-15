<?php
// agent/process_payment.php

require_once $_SERVER['DOCUMENT_ROOT'] . '/digital_agent/_partials/db.php';
// --- STEP 1: Include the agent-specific activity logger ---
require_once __DIR__ . '/_partials/activity_helper.php';

// It's good practice to start the session if it's not in your db.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
// Load Paystack Secret Key from environment (loaded via db.php -> config.php)
$paystackSecretKey = PAYSTACK_SECRET_KEY;

// Get data from the fetch request
$input = json_decode(file_get_contents("php://input"), true);
$reference = $input['reference'] ?? '';
$amount_to_credit = $input['amount'] ?? 0;

if (empty($reference) || !is_numeric($amount_to_credit) || $amount_to_credit <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid payment details received']);
    exit();
}

// Verification logic (Unchanged)
$service_charge = $amount_to_credit * 0.02;
$expected_total_paid = $amount_to_credit + $service_charge;
$expected_total_in_pesewas = round($expected_total_paid * 100);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.paystack.co/transaction/verify/" . rawurlencode($reference));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $paystackSecretKey", "Cache-Control: no-cache"]);
$response = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    echo json_encode(['status' => 'error', 'message' => 'API connection error: ' . $err]);
    exit();
}
$data = json_decode($response, true);

if (!$data || !$data['status']) {
    echo json_encode(['status' => 'error', 'message' => 'Transaction verification failed with gateway.']);
    exit();
}

if ($data['data']['status'] == 'success' && $data['data']['amount'] == $expected_total_in_pesewas) {
    // Verification successful!
    $payment_number = $data['data']['customer']['phone'] ?? 'Paystack';
    $transaction_id = $data['data']['reference'];
    $payment_network = 'Paystack';
    $status = 'Approved';

    $conn->begin_transaction();
    try {
        // Step 1: Insert into topup_requests
        $stmt_topup = $conn->prepare("INSERT INTO topup_requests (user_id, amount, payment_network, payment_number, transaction_id, status) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_topup->bind_param("idssss", $user_id, $amount_to_credit, $payment_network, $payment_number, $transaction_id, $status);
        $stmt_topup->execute();

        // Step 2: Update wallet (your existing logic is fine)
        $check = $conn->prepare("SELECT balance FROM wallet WHERE user_id = ?");
        $check->bind_param("i", $user_id);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $update = $conn->prepare("UPDATE wallet SET balance = balance + ? WHERE user_id = ?");
            $update->bind_param("di", $amount_to_credit, $user_id);
            $update->execute();
        } else {
            $insert_wallet = $conn->prepare("INSERT INTO wallet (user_id, balance) VALUES (?, ?)");
            $insert_wallet->bind_param("id", $user_id, $amount_to_credit);
            $insert_wallet->execute();
        }

        // If all queries were successful, commit the transaction
        $conn->commit();

        // --- STEP 2: Log the successful instant top-up ---
        $log_description = "Made an instant wallet top-up of GHS " . number_format($amount_to_credit, 2) . " via Paystack.";
        logAgentActivity($conn, $log_description);
        // --- END OF LOGGING ---

        echo json_encode(['status' => 'success', 'message' => 'Top-up successful and wallet credited.']);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Database error. Could not credit wallet.']);
    }

} else {
    echo json_encode(['status' => 'error', 'message' => 'Payment verification failed. Amount mismatch or transaction was not successful.']);
}
?>