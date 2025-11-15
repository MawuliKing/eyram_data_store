<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/digital_agent/_partials/db.php';
if (!isset($_SESSION['user_id'])) { exit('Access Denied.'); }

function getCartData() { /* ... unchanged ... */ }

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $provider_id = $_POST['provider_id'] ?? 0;
    $provider_name = $_POST['provider_name'] ?? 'Unknown';
    $category = $_POST['category'] ?? 'Uncategorized';
    
    $phones = $_POST['phone'] ?? [];
    $sizes = $_POST['size'] ?? [];

    if (empty($provider_id) || empty($phones) || empty($sizes) || count($phones) !== count($sizes)) {
        $response['message'] = "Incomplete or mismatched data submitted.";
    } else {
        // --- Fetch all available bundles for this provider ---
        $stmt = $conn->prepare("SELECT name, price, id FROM services WHERE parent_id = ? AND status = 'enabled'");
        $stmt->bind_param("i", $provider_id);
        $stmt->execute();
        $available_bundles_result = $stmt->get_result();
        
        $price_map = [];
        while($bundle = $available_bundles_result->fetch_assoc()) {
            $standardized_key = strtolower(str_replace(' ', '', $bundle['name']));
            $price_map[$standardized_key] = ['price' => $bundle['price'], 'id' => $bundle['id'], 'original_name' => $bundle['name']];
        }

        $added_count = 0;
        $failed_count = 0;
        if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = []; }

        // Loop through the submitted rows
        for ($i = 0; $i < count($phones); $i++) {
            $phone = trim($phones[$i]);
            $size_input = trim($sizes[$i]);
            $standardized_size = strtolower(str_replace(' ', '', $size_input));

            if (strlen($phone) == 10 && is_numeric($phone) && isset($price_map[$standardized_size])) {
                $bundle_details = $price_map[$standardized_size];
                $cart_key = uniqid('bulk_');
                $_SESSION['cart'][$cart_key] = [
                    'cart_key' => $cart_key, 'service_id' => $bundle_details['id'],
                    'name' => $bundle_details['original_name'], 'price' => $bundle_details['price'],
                    'recipient_phone' => $phone, 'provider_name' => $provider_name, 'category' => $category
                ];
                $added_count++;
            } else {
                $failed_count++;
            }
        }
        
        $response['status'] = ($added_count > 0) ? 'success' : 'error';
        $response['message'] = "$added_count orders were successfully added to your cart.";
        if ($failed_count > 0) {
            $response['message'] .= " $failed_count rows failed due to invalid data.";
        }
        $response['cart'] = getCartData();
    }
}

echo json_encode($response);
exit();