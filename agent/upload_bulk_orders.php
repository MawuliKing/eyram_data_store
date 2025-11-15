<?php
// upload_bulk_orders.php

// --- STEP 1: Include the necessary files ---
require_once $_SERVER['DOCUMENT_ROOT'] . '/digital_agent/_partials/config.php';
require_once BASE_PATH . '/_partials/db.php';
require_once __DIR__ . '/_partials/activity_helper.php'; // Include the agent activity logger

header('Content-Type: application/json');

// Security & Setup Checks
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Access Denied.']);
    exit;
}
if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'File upload failed.']);
    exit;
}

// Helper function to get cart data
function getCartData() {
    $cart = $_SESSION['cart'] ?? [];
    $item_count = count($cart);
    $total_price = 0;
    foreach ($cart as $item) { $total_price += (float)($item['price'] ?? 0); }
    return ['items' => array_values($cart), 'item_count' => $item_count, 'total_price' => number_format($total_price, 2)];
}

$user_role = $_SESSION['user_role'] ?? 'Customer'; // Get user role for pricing
$provider_id = (int)($_POST['provider_id'] ?? 0);
$provider_name = $_POST['provider_name'] ?? 'Unknown';
$category = $_POST['category'] ?? 'Uncategorized';

if (empty($provider_id)) {
    echo json_encode(['status' => 'error', 'message' => 'Could not identify the network provider.']);
    exit;
}

// Fetch packages and get the correct role-based price
$price_column_sql = 'price_customer';
if ($user_role === 'Super Agent') $price_column_sql = 'price_super_admin';
elseif ($user_role === 'Agent') $price_column_sql = 'price_agent';

$stmt = $conn->prepare("SELECT id, name, {$price_column_sql} as price FROM services WHERE parent_id = ? AND status = 'enabled'");
$stmt->bind_param("i", $provider_id);
$stmt->execute();
$result = $stmt->get_result();
$valid_packages = [];
while ($bundle = $result->fetch_assoc()) {
    $normalized_key = str_replace(' ', '', strtolower($bundle['name']));
    $valid_packages[$normalized_key] = [
        'id' => $bundle['id'],
        'price' => $bundle['price'],
        'original_name' => $bundle['name']
    ];
}
$stmt->close();

// --- Process the CSV file ---
$file_handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
fgetcsv($file_handle); // Skip header row

$added_count = 0;
$failed_count = 0;
if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = []; }

while (($row = fgetcsv($file_handle)) !== FALSE) {
    if (count($row) < 2 || empty(trim($row[0])) || empty(trim($row[1]))) {
        continue;
    }

    $phone_from_csv = trim($row[0]);
    $size_from_csv = trim($row[1]);

    if (is_numeric($phone_from_csv) && strlen($phone_from_csv) === 9) {
        $phone_from_csv = '0' . $phone_from_csv;
    }
    $is_phone_valid = is_numeric($phone_from_csv) && strlen($phone_from_csv) === 10;
    
    $normalized_input = str_replace(' ', '', strtolower($size_from_csv));
    $is_size_valid = false;
    $found_bundle_details = null;

    if (!empty($normalized_input)) {
        foreach ($valid_packages as $normalized_db_key => $bundle_details) {
            if (strpos($normalized_db_key, $normalized_input) !== false) {
                $is_size_valid = true;
                $found_bundle_details = $bundle_details;
                break;
            }
        }
    }

    if ($is_phone_valid && $is_size_valid) {
        $cart_item_id = uniqid('cart_');
        $_SESSION['cart'][$cart_item_id] = [
            'cart_item_id' => $cart_item_id,
            'service_id' => $found_bundle_details['id'],
            'name' => $found_bundle_details['original_name'],
            'price' => $found_bundle_details['price'], // Use the correct role-based price
            'recipient_phone' => $phone_from_csv,
            'provider_name' => $provider_name,
            'category' => $category
        ];
        $added_count++;
    } else {
        $failed_count++;
    }
}
fclose($file_handle);

// --- STEP 2: Log the summary of the bulk upload action ---
if ($added_count > 0 || $failed_count > 0) {
    $log_description = "Performed a bulk upload for '{$provider_name}'. Result: {$added_count} successful, {$failed_count} failed.";
    logAgentActivity($conn, $log_description);
}
// --- END OF LOGGING ---

// Prepare the final JSON response
$response = [];
$response['status'] = ($added_count > 0) ? 'success' : 'error';
$response['message'] = "$added_count orders were successfully added to your cart.";
if ($failed_count > 0) {
    $response['message'] .= " $failed_count rows failed due to invalid data (e.g., wrong phone number or unrecognized size).";
}
$response['cart'] = getCartData();

echo json_encode($response);
exit();
?>