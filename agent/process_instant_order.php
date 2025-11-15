<?php
// process_instant_order.php

require_once $_SERVER['DOCUMENT_ROOT'] . '/digital_agent/_partials/db.php';
// It's crucial to start the session to access $_SESSION variables
session_start(); 

header('Content-Type: application/json');

// Security check
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

// 1. GET INPUT AND CHECK SESSION
$data = json_decode(file_get_contents('php://input'), true);
$reference = $data['reference'] ?? null;
$service_id = $data['service_id'] ?? null;
$recipient_phone = $data['recipient_phone'] ?? null;
$provider_name = $data['provider_name'] ?? null;
$category = $data['category'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

if (!$reference || !$user_id || !$service_id || !$recipient_phone) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required data. Please try again.']);
    exit;
}

// 2. GET AUTHORITATIVE SERVICE DETAILS FROM YOUR DATABASE
// This is the source of truth for the price. NEVER trust the amount from the frontend.
$stmt_service = $conn->prepare("SELECT name, price FROM services WHERE id = ?");
$stmt_service->bind_param("i", $service_id);
$stmt_service->execute();
$service_result = $stmt_service->get_result();

if ($service_result->num_rows == 0) {
    echo json_encode(['status' => 'error', 'message' => 'The selected service does not exist.']);
    exit;
}
$service = $service_result->fetch_assoc();
$service_name = $service['name'];
$service_price = $service['price'];
// Convert the authoritative price to pesewas for comparison with Paystack
$expected_amount_in_pesewas = round($service_price * 100);
$stmt_service->close();

// 3. VERIFY PAYMENT WITH PAYSTACK
// Load Paystack Secret Key from environment (loaded via db.php -> config.php)
$paystackSecretKey = PAYSTACK_SECRET_KEY;
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.paystack.co/transaction/verify/" . rawurlencode($reference));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $paystackSecretKey",
    "Content-Type: application/json"
]);
$response = curl_exec($ch);
curl_close($ch);
$result = json_decode($response, true);

// 4. CHECK PAYSTACK RESPONSE ***WITH AMOUNT VERIFICATION***
// This is the critical security fix.
if ($result && $result['status'] && $result['data']['status'] === 'success' && $result['data']['amount'] == $expected_amount_in_pesewas) {
    // Payment is valid AND the amount paid matches the product price.
    
    // Prepare Order Details
    $order_details = json_encode([
        'name' => $service_name,
        'price' => $service_price,
        'recipient_phone' => $recipient_phone,
        'provider_name' => $provider_name,
        'category' => $category,
        'payment_method' => 'Instant Payment',
        'payment_ref' => $reference
    ]);

    // Use a database transaction to ensure all queries succeed or none do.
    $conn->begin_transaction();
    try {
        // Insert into Orders Table with status: Pending
        $stmt_order = $conn->prepare("INSERT INTO orders (user_id, service_id, order_details, status, created_at) VALUES (?, ?, ?, 'Pending', NOW())");
        $stmt_order->bind_param("iis", $user_id, $service_id, $order_details);
        $stmt_order->execute();

        // Log the transaction in your transaction history
        $tx_stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, description, status, created_at) VALUES (?, 'purchase', ?, ?, 'completed', NOW())");
        $description = "Purchase: " . $service_name . " via Instant Payment";
        // We log a negative amount for purchases to reflect a debit from a logical standpoint
        $tx_stmt->bind_param("ids", $user_id, $service_price, $description);
        $tx_stmt->execute();
        
        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Payment verified and order placed successfully!']);

    } catch (Exception $e) {
        $conn->rollback();
        // Log the internal error for your records: error_log("Instant Order DB Error: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Payment was successful, but there was an error saving your order. Please contact support.']);
    }

} else {
    // This block executes if verification fails OR if the amounts do not match.
    // Log the failure for investigation: 
    // error_log("Instant order verification failed for ref: $reference. Expected: $expected_amount_in_pesewas, Got: " . ($result['data']['amount'] ?? 'N/A'));
    echo json_encode(['status' => 'error', 'message' => 'Payment verification failed. Your order was not placed.']);
}
?>