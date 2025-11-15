<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/digital_agent/_partials/db.php';
// --- STEP 1: Include the agent-specific activity logger ---
require_once __DIR__ . '/_partials/activity_helper.php';

// It's good practice to start the session if your db.php doesn't already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


function getCartData() {
    $cart = $_SESSION['cart'] ?? [];
    $item_count = count($cart);
    $total_price = 0;
    foreach ($cart as $item) {
        $total_price += (float)($item['price'] ?? 0);
    }
    return [
        'items' => array_values($cart),
        'item_count' => $item_count,
        'total_price' => $total_price
    ];
}

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Invalid action.'];
$action = $_GET['action'] ?? '';

// --- ACTION: GET CURRENT CART ---
if ($action == 'get') {
    $response['status'] = 'success';
    $response['message'] = 'Cart data fetched.';
    $response['cart'] = getCartData();
}

// --- ACTION: ADD ITEM TO CART ---
if ($action == 'add' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $service_id = $_POST['service_id'] ?? 0;
    $recipient_phone = trim($_POST['recipient_phone'] ?? '');
    $provider_name = trim($_POST['provider_name'] ?? 'Unknown');
    $category = trim($_POST['category'] ?? 'Uncategorized');

    if (empty($service_id)) {
        $response['message'] = 'Invalid service selected.';
    } elseif (empty($recipient_phone)) {
        $response['message'] = 'A phone number is required.';
    } elseif (!is_numeric($recipient_phone) || strlen($recipient_phone) != 10) {
        $response['message'] = 'Phone number must be exactly 10 digits.';
    } else {
        // Use role to get correct price
        $user_role = $_SESSION['user_role'] ?? 'Customer';
        $price_column_sql = 'price_customer';
        if ($user_role === 'Super Agent') $price_column_sql = 'price_super_admin';
        elseif ($user_role === 'Agent') $price_column_sql = 'price_agent';

        $stmt = $conn->prepare("SELECT name, {$price_column_sql} as price FROM services WHERE id = ? AND status = 'enabled'");
        $stmt->bind_param("i", $service_id);
        $stmt->execute();
        $service = $stmt->get_result()->fetch_assoc();

        if ($service) {
            if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = []; }

            foreach ($_SESSION['cart'] as $item) {
                if (($item['recipient_phone'] ?? '') == $recipient_phone && ($item['service_id'] == $service_id)) {
                    $response['message'] = 'This number and service is already in your cart.';
                    echo json_encode($response);
                    exit();
                }
            }

            $cart_key = uniqid('item_');
            $_SESSION['cart'][$cart_key] = [
                'cart_key' => $cart_key,
                'service_id' => $service_id,
                'name' => $service['name'],
                'price' => $service['price'],
                'recipient_phone' => $recipient_phone,
                'provider_name' => $provider_name,
                'category' => $category
            ];
            $response['status'] = 'success';
            $response['message'] = 'Item added to cart.';
            $response['cart'] = getCartData();
        } else {
            $response['message'] = 'This service is currently unavailable.';
        }
    }
}

// --- ACTION: REMOVE & CLEAR CART ---
if ($action == 'remove') {
    $cart_key = $_GET['key'] ?? '';
    if (isset($_SESSION['cart'][$cart_key])) {
        unset($_SESSION['cart'][$cart_key]);
        $response['status'] = 'success';
        $response['message'] = 'Item removed from cart.';
        $response['cart'] = getCartData();
    }
}

if ($action == 'clear') {
    $_SESSION['cart'] = [];
    $response['status'] = 'success';
    $response['message'] = 'Cart cleared.';
    $response['cart'] = getCartData();
}

// --- ACTION: PROCESS ORDER WITH OVERDRAFT ---
if ($action == 'process') {
    $cart_data = getCartData();
    $user_id = $_SESSION['user_id'];

    if ($cart_data['item_count'] > 0) {
        $stmt_user = $conn->prepare("
            SELECT w.balance, u.overdraft_limit 
            FROM users u 
            LEFT JOIN wallet w ON u.id = w.user_id 
            WHERE u.id = ?
        ");
        $stmt_user->bind_param("i", $user_id);
        $stmt_user->execute();
        $user_funds = $stmt_user->get_result()->fetch_assoc();

        $wallet_balance = $user_funds['balance'] ?? 0;
        $overdraft_limit = $user_funds['overdraft_limit'] ?? 0;
        $available_credit = $wallet_balance + $overdraft_limit;

        if ($available_credit < $cart_data['total_price']) {
            $response['status'] = 'error';
            $response['message'] = 'Insufficient funds. Your available balance and overdraft are not enough to complete this purchase.';
        } else {
            $conn->begin_transaction();
            try {
                $new_balance = $wallet_balance - $cart_data['total_price'];
                $update_wallet_stmt = $conn->prepare("UPDATE wallet SET balance = ? WHERE user_id = ?");
                $update_wallet_stmt->bind_param("di", $new_balance, $user_id);
                $update_wallet_stmt->execute();

                $insert_order_stmt = $conn->prepare("INSERT INTO orders (user_id, service_id, order_details, status) VALUES (?, ?, ?, 'Pending')");

                foreach ($cart_data['items'] as $item) {
                    $order_details = json_encode([
                        'name' => $item['name'],
                        'price' => $item['price'],
                        'recipient_phone' => $item['recipient_phone'] ?? '',
                        'provider_name' => $item['provider_name'],
                        'category' => $item['category'],
                        'form_data' => $item['form_data'] ?? []
                    ]);
                    $insert_order_stmt->bind_param("iis", $user_id, $item['service_id'], $order_details);
                    $insert_order_stmt->execute();

                    if (!empty($item['form_data'])) {
                        $form_data_json = json_encode($item['form_data']);
                        $insert_form_stmt = $conn->prepare("INSERT INTO form_submissions (user_id, service_id, form_data) VALUES (?, ?, ?)");
                        $insert_form_stmt->bind_param("iis", $user_id, $item['service_id'], $form_data_json);
                        $insert_form_stmt->execute();
                    }
                }

                $conn->commit();

                // --- LOG THE ACTIVITY HERE ---
                $log_description = "Placed an order for " . $cart_data['item_count'] . " item(s) totaling GHS " . number_format($cart_data['total_price'], 2) . ".";
                logAgentActivity($conn, $log_description);
                // --- END OF LOGGING ---

                $_SESSION['cart'] = [];
                $response['status'] = 'success';
                $response['message'] = 'Orders placed successfully!';
                $response['redirect_url'] = 'my_orders.php';
            } catch (Exception $e) {
                $conn->rollback();
                $response['status'] = 'error';
                $response['message'] = 'An error occurred while processing your order. Please try again.';
            }
        }
    } else {
        $response['message'] = 'Your cart is empty.';
    }
}

// --- ACTION: ADD FORM-BASED SERVICE TO CART (WITH FILES) ---
if ($action == 'add_form' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $service_id = $_POST['service_id'] ?? 0;
    $provider_name = trim($_POST['provider_name'] ?? 'Form Service');
    $category = trim($_POST['category'] ?? 'Uncategorized');
    $form_data = json_decode($_POST['form_data'] ?? '{}', true);

    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/digital_agent/uploads/';
    $uploaded_files = [];
    $allowed_types = ['jpg', 'jpeg', 'png', 'pdf'];

    foreach ($_FILES as $key => $file) {
        if ($file['error'] === 0) {
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($file_ext, $allowed_types)) {
                $new_filename = 'doc_' . $key . '_' . uniqid() . '.' . $file_ext;
                if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_filename)) {
                    $uploaded_files[$key] = $new_filename;
                }
            }
        }
    }

    if (!empty($uploaded_files)) {
        $form_data['uploaded_files'] = $uploaded_files;
    }

    if ($service_id && !empty($form_data)) {
        $user_role = $_SESSION['user_role'] ?? 'Customer';
        $price_column_sql = 'price_customer';
        if ($user_role === 'Super Agent') $price_column_sql = 'price_super_admin';
        elseif ($user_role === 'Agent') $price_column_sql = 'price_agent';

        $stmt = $conn->prepare("SELECT name, {$price_column_sql} as price, category FROM services WHERE id = ? AND status = 'enabled'");
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
                'category' => $service['category'],
                'form_data' => $form_data
            ];

            $response['status'] = 'success';
            $response['message'] = 'Form added to cart successfully!';
            $response['cart'] = getCartData();
        } else {
            $response['message'] = 'This service is currently unavailable.';
        }
    } else {
        $response['message'] = 'Invalid service or form data.';
    }
}

echo json_encode($response);
exit();
?>