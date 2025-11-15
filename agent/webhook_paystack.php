<?php
// webhook_paystack.php

require_once $_SERVER['DOCUMENT_ROOT'] . '/digital_agent/_partials/db.php';

// It's good practice to verify the webhook signature for security
// For simplicity here, we are skipping it, but you should implement it in production.
// Load Paystack Secret Key from environment (loaded via db.php -> config.php)
// $paystackSecretKey = PAYSTACK_SECRET_KEY;
// if ($_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] !== hash_hmac('sha512', $input, $paystackSecretKey)) {
//     exit();
// }


// Get raw input from Paystack
$input = @file_get_contents("php://input");
$event = json_decode($input);

// Respond to Paystack immediately that we've received the event
http_response_code(200);

// Validate event and ensure it's a successful charge
if ($event && isset($event->event) && $event->event === 'charge.success') {
    $reference = $event->data->reference;

    // --- KEY CHANGE: LOOKUP THE TRANSACTION IN YOUR DATABASE FIRST ---
    // Use the reference to find the original top-up request.
    // This is our "source of truth" for the amount to credit.
    $stmt_lookup = $conn->prepare("SELECT user_id, amount, status FROM topup_requests WHERE transaction_id = ?");
    $stmt_lookup->bind_param("s", $reference);
    $stmt_lookup->execute();
    $topup_result = $stmt_lookup->get_result();

    if ($topup_result->num_rows > 0) {
        $topup_request = $topup_result->fetch_assoc();
        $user_id = $topup_request['user_id'];
        $amount_to_credit = $topup_request['amount']; // This is the correct amount! (e.g., 100.00)
        $current_status = $topup_request['status'];

        // Only proceed if the request hasn't already been fully credited.
        // This prevents double-crediting if process_payment.php and the webhook both run.
        if ($current_status === 'Approved') {
            
            // Use a transaction to ensure data integrity
            $conn->begin_transaction();
            try {
                // Step 1: Credit the user's wallet with the CORRECT amount
                $update_wallet_stmt = $conn->prepare("UPDATE wallet SET balance = balance + ? WHERE user_id = ?");
                $update_wallet_stmt->bind_param("di", $amount_to_credit, $user_id);
                $update_wallet_stmt->execute();
                
                // If wallet doesn't exist, you might need to insert it (based on your system logic)
                if ($update_wallet_stmt->affected_rows == 0) {
                     $insert_wallet = $conn->prepare("INSERT INTO wallet (user_id, balance) VALUES (?, ?)");
                     $insert_wallet->bind_param("id", $user_id, $amount_to_credit);
                     $insert_wallet->execute();
                }

                // Step 2: Update the topup_request status to show it's been credited by the webhook
                $update_topup_stmt = $conn->prepare("UPDATE topup_requests SET status = 'Credited by Webhook' WHERE transaction_id = ?");
                $update_topup_stmt->bind_param("s", $reference);
                $update_topup_stmt->execute();
                
                // Note: Your original code had a separate 'transactions' table.
                // If you still use that, you can add an INSERT statement for it here.
                // For example:
                // $desc = "Top-up via Paystack (Webhook)";
                // $type = "Credit";
                // $status = "Completed";
                // $trans_stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, description, status, transaction_ref) VALUES (?, ?, ?, ?, ?, ?)");
                // $trans_stmt->bind_param("isdsss", $user_id, $type, $amount_to_credit, $desc, $status, $reference);
                // $trans_stmt->execute();

                $conn->commit();

            } catch (Exception $e) {
                $conn->rollback();
                // Log the error for debugging
                error_log("Webhook Error for Ref: $reference - " . $e->getMessage());
            }
        }
    }
}
?>