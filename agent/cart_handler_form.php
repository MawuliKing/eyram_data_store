<?php
// We add our debugging lines to see any potential errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once $_SERVER['DOCUMENT_ROOT'] . '/digital_agent/_partials/db.php';

function getCartData() {
    $cart = $_SESSION['cart'] ?? [];
    $item_count = count($cart);
    $total_price = 0;
    foreach ($cart as $item) { $total_price += (float)($item['price'] ?? 0); }
    return ['items' => array_values($cart), 'item_count' => $item_count, 'total_price' => $total_price];
}

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];
$action = $_GET['action'] ?? '';

if ($action == 'add_with_files') {
    // --- File Upload Logic ---
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/digital_agent/uploads/';
    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); } // Create uploads dir if it doesn't exist

    $uploaded_files = [];
    $allowed_types = ['jpg', 'jpeg', 'png', 'pdf'];
    $error_message = '';

    if (!empty($_FILES)) {
        foreach ($_FILES as $input_name => $file) {
            // Check if it's a valid upload and not empty
            if (isset($file['error']) && $file['error'] == 0) {
                $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (in_array($file_ext, $allowed_types)) {
                    $new_filename = 'doc_' . $input_name . '_' . uniqid() . '.' . $file_ext;
                    if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_filename)) {
                        $uploaded_files[$input_name] = $new_filename;
                    } else { $error_message = "Server error: Failed to move uploaded file '$input_name'."; break; }
                } else { $error_message = "Invalid file type for '$input_name'. Only JPG, PNG, PDF are allowed."; break; }
            }
        }
    }
    
    if (!empty($error_message)) {
        $response['message'] = $error_message;
    } else {
        // --- If uploads are successful (or there were no uploads), add to cart ---
        $service_id = $_POST['service_id'] ?? 0;
        $provider_name = trim($_POST['provider_name'] ?? 'Form Service');
        
        // We get the form data and remove control fields
        $form_data = $_POST;
        unset($form_data['service_id'], $form_data['provider_name'], $form_data['category']);
        $form_data['uploaded_files'] = $uploaded_files; // Add the paths of uploaded files

        // --- THE FIX: Fetch category along with name and price ---
        $stmt = $conn->prepare("SELECT name, price, category FROM services WHERE id = ?");
        $stmt->bind_param("i", $service_id);
        $stmt->execute();
        $service = $stmt->get_result()->fetch_assoc();

        if ($service) {
           if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = []; }
           $cart_key = uniqid('form_');
           
           $_SESSION['cart'][$cart_key] = [
                'cart_key' => $cart_key,
                'service_id' => $service_id,
                'name' => $service['name'],
                'price' => $service['price'],
                'provider_name' => $provider_name,
                'category' => $service['category'], // <-- Now this uses the correct value from the database
                'form_data' => $form_data
            ];
            
            $response['status'] = 'success';
            $response['cart'] = getCartData();
        } else {
            $response['message'] = 'Service not found or is disabled.';
        }
    }
}

echo json_encode($response);
exit();