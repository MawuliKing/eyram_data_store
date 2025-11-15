<?php
// remove_from_cart.php

// Use the robust config file for pathing and sessions
require_once $_SERVER['DOCUMENT_ROOT'] . '/digital_agent/_partials/config.php';
// No need to include db.php if we are only manipulating the session

header('Content-Type: application/json');

// Helper function to get the latest cart data
function getCartData() {
    $cart = $_SESSION['cart'] ?? [];
    $item_count = count($cart);
    $total_price = 0;
    foreach ($cart as $item) {
        $total_price += (float)($item['price'] ?? 0);
    }
    // We use array_values to re-index the array for consistent JSON output
    return ['items' => array_values($cart), 'item_count' => $item_count, 'total_price' => number_format($total_price, 2)];
}

// 1. Security Checks
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Access Denied. Please log in again.']);
    exit;
}

// 2. Get the item ID to remove from the POST data
$input = json_decode(file_get_contents("php://input"), true);
$cart_item_id = $input['cart_item_id'] ?? null;

if (empty($cart_item_id)) {
    echo json_encode(['status' => 'error', 'message' => 'No item specified for removal.']);
    exit;
}

// 3. Validate and Remove the Item
if (isset($_SESSION['cart']) && isset($_SESSION['cart'][$cart_item_id])) {
    // The key exists, so we can safely remove it. This is the correct way.
    unset($_SESSION['cart'][$cart_item_id]);

    // 4. Send back a success response with the updated cart
    echo json_encode([
        'status' => 'success',
        'message' => 'Item removed from cart.',
        'cart' => getCartData() // Send the new cart state back to the frontend
    ]);
} else {
    // This is where the "Invalid action" error comes from.
    // The key was not found in the session cart array.
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid action. Item not found in cart.',
        'cart' => getCartData() // Still send back the current cart state
    ]);
}

exit();
?>