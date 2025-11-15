<?php
// get_orders.php (FINAL ROBUST VERSION 4)

require_once $_SERVER['DOCUMENT_ROOT'] . '/digital_agent/_partials/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit();
}

// ===================================================================
// STEP 1: DEFINE CATEGORIES AND FETCH RECENT ORDERS
// ===================================================================

$display_groups = [
    'MTN Data Packages'        => ['Data Bundle - MTN'],
    'AirtelTigo Data Packages' => ['Data Bundle'],
    'Telecel Data Packages'    => ['Data Bundle-Telecel'],
    'MTN AFA Mashup'           => ['Mashup'],
    'Exam Results'             => ['Exam Results'],
    'Exam Vouchers'            => ['Exam Vouchers']
];

// This is a simple query to get the last 200 orders. It is fast and reliable.
$base_sql = "SELECT o.id, o.order_details, o.status, o.created_at, u.full_name, u.role
             FROM orders AS o 
             JOIN users AS u ON o.user_id = u.id
             ORDER BY o.created_at DESC LIMIT 200";

// Handle search separately as it's a simple, reliable filter
if (!empty($_GET['search'])) {
    $search_term = '%' . trim($_GET['search']) . '%';
    // Add a WHERE clause specifically for search
    $base_sql = "SELECT o.id, o.order_details, o.status, o.created_at, u.full_name, u.role
                 FROM orders AS o 
                 JOIN users AS u ON o.user_id = u.id
                 WHERE JSON_UNQUOTE(JSON_EXTRACT(order_details, '$.recipient_phone')) LIKE ?
                 ORDER BY o.created_at DESC LIMIT 200";
    
    $stmt = $conn->prepare($base_sql);
    $stmt->bind_param('s', $search_term);
} else {
    $stmt = $conn->prepare($base_sql);
}

$stmt->execute();
$result = $stmt->get_result();

$orders_to_display = $result->fetch_all(MYSQLI_ASSOC);
$found_orders = false;

// ===================================================================
// STEP 2: FILTER THE RESULTS IN PHP (THE RELIABLE PART)
// ===================================================================

// Get the group key if one was clicked
$group_key_filter = $_GET['group_key'] ?? null;
$target_categories = [];

// If a category was clicked, find the exact database categories we need to show.
if ($group_key_filter) {
    foreach ($display_groups as $name => $cats) {
        if (str_replace(' ', '-', strtolower($name)) === $group_key_filter) {
            // Clean the target categories using your exact logic
            $target_categories = array_map(function($c) { 
                return str_replace(' ', '', strtolower(trim($c))); 
            }, $cats);
            break;
        }
    }
}

// Loop through every order we fetched
foreach ($orders_to_display as $order) {
    $details = json_decode($order['order_details'], true);
    $order_category_raw = trim($details['category'] ?? '');
    
    // If a category filter is active, check if this order should be shown.
    if ($group_key_filter) {
        // Clean the current order's category using your exact logic
        $current_order_category_cleaned = str_replace(' ', '', strtolower($order_category_raw));
        
        // If the order's category is NOT in our list of targets, skip it and go to the next order.
        if (!in_array($current_order_category_cleaned, $target_categories)) {
            continue;
        }
    }
    
    // If we reach here, it means the order should be displayed.
    $found_orders = true;
    
    // --- The rest of this is your original, working HTML generation ---
    $status_color = 'secondary';
    if ($order['status'] == 'Processing') $status_color = 'primary';
    if ($order['status'] == 'Complete') $status_color = 'success';
    if ($order['status'] == 'Failed') $status_color = 'danger';

    $provider_name = $details['provider_name'] ?? 'N/A';
    $provider = strtolower($provider_name);
    $bg_class = 'bg-default';
    if (strpos($provider, 'mtn') !== false) $bg_class = 'bg-mtn';
    elseif (strpos($provider, 'telecel') !== false) $bg_class = 'bg-telecel';
    elseif (strpos($provider, 'airteltigo') !== false) $bg_class = 'bg-airteltigo';
    
    $group_key_for_order = 'other';
    $order_category_normalized = str_replace(' ', '', strtolower($order_category_raw));
    foreach ($display_groups as $group_name => $db_categories) {
        foreach ($db_categories as $db_cat) {
            if (!empty($order_category_normalized) && $order_category_normalized === str_replace(' ', '', strtolower($db_cat))) {
                $group_key_for_order = str_replace(' ', '-', strtolower($group_name));
                break 2;
            }
        }
    }
    ?>
    <div class="order-card <?= $bg_class; ?>" 
         data-group-key="<?= htmlspecialchars($group_key_for_order); ?>"
         data-db-category="<?= htmlspecialchars($order_category_raw) ?>"
         data-id="<?= $order['id']; ?>" 
         data-status="<?= $order['status']; ?>"
         data-created-at="<?= strtotime($order['created_at']); ?>">
        
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div><strong>Order ID:</strong> #<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></div>
            <span class="badge bg-<?= $status_color; ?>"><?= $order['status']; ?></span>
        </div>

        <p><strong>Product:</strong> <?= htmlspecialchars($details['name'] ?? 'Unknown'); ?></p>
        <p><strong>Phone:</strong> <?= htmlspecialchars($details['recipient_phone'] ?? 'N/A'); ?></p>
        <p><strong>Price:</strong> GH₵ <?= number_format($details['price'] ?? 0, 2); ?></p>
        <p><strong>By:</strong> <?= htmlspecialchars($order['full_name']); ?>
            <span class="badge bg-dark ms-2"><?= htmlspecialchars($order['role']); ?></span>
        </p>
        <p><strong>Date:</strong> <?= date("d M Y, h:i A", strtotime($order['created_at'])); ?></p>

        <?php if (in_array($order['status'], ['Pending', 'Processing'])): ?>
            <div class="mt-2 action-buttons">
                <a href="order_handler.php?action=approve&id=<?= $order['id']; ?>" class="btn btn-success btn-sm">✅ Approve</a>
                <a href="order_handler.php?action=decline&id=<?= $order['id']; ?>" class="btn btn-danger btn-sm">❌ Decline</a>
            </div>
        <?php else: ?>
            <div class="mt-2 text-light fst-italic action-buttons-placeholder">No action required</div>
        <?php endif; ?>
    </div>
    <?php
} // End of the main foreach loop

// If after looping through everything, no orders were shown, display the message.
if (!$found_orders) {
    echo '<div class="alert alert-info text-center">No orders found matching your criteria.</div>';
}
?>