<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/digital_agent/_partials/db.php';

// Assuming session start and admin check are handled before this script is included, or are here.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    // A more graceful exit
    $_SESSION['message'] = "Access Denied.";
    $_SESSION['message_type'] = "danger";
    header('Location: orders.php'); // Redirect back to orders page
    exit();
}

$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$category_or_name_to_export = $_GET['category_to_export'] ?? ''; 

if (empty($start_date) || empty($end_date) || empty($category_or_name_to_export)) {
    die("Error: Please provide a valid start date, end date, and service to export.");
}

$start_datetime = $start_date . ' 00:00:00';
$end_datetime = $end_date . ' 23:59:59';

$sql_select = "SELECT o.id, o.order_details, o.status, o.created_at, u.full_name 
               FROM orders AS o
               JOIN users AS u ON o.user_id = u.id
               WHERE o.status = 'Pending'
               AND o.created_at BETWEEN ? AND ?
               AND (
                   JSON_UNQUOTE(JSON_EXTRACT(o.order_details, '$.category')) = ? 
                   OR 
                   JSON_UNQUOTE(JSON_EXTRACT(o.order_details, '$.name')) = ?
               )";

$params_select = [$start_datetime, $end_datetime, $category_or_name_to_export, $category_or_name_to_export];
$types_select = "ssss";

$stmt_select = $conn->prepare($sql_select);
$stmt_select->bind_param($types_select, ...$params_select);
$stmt_select->execute();
$result = $stmt_select->get_result();

$orders_data = [];
$order_ids_to_update = [];
while ($row = $result->fetch_assoc()) {
    $orders_data[] = $row;
    $order_ids_to_update[] = $row['id'];
}

if (empty($orders_data)) {
    echo "<script>alert('No pending orders found for the selected criteria.'); window.history.back();</script>";
    exit();
}

$filename = "Pending-Orders-" . urlencode(str_replace(' ', '-', $category_or_name_to_export)) . "_" . date('Y-m-d') . ".csv";
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
$output = fopen('php://output', 'w');

fputcsv($output, ['Phone Number', 'Package']);

foreach ($orders_data as $order) {
    $details = json_decode($order['order_details'], true);
    $package_name = $details['name'] ?? 'N/A';
    $clean_package = preg_replace('/\s*(GB|MB|gb|mb)\b/', '', $package_name); 
    
    fputcsv($output, [
        $details['recipient_phone'] ?? 'N/A',
        trim($clean_package)
    ]);
}
fclose($output);

// Update only those orders that were exported
if (!empty($order_ids_to_update)) {
    $id_placeholders = implode(',', array_fill(0, count($order_ids_to_update), '?'));
    
    // ✅ MODIFIED: Added `updated_at = NOW()` to set the timestamp when processing begins.
    $sql_update = "UPDATE orders SET status = 'Processing', updated_at = NOW() WHERE id IN ($id_placeholders) AND status = 'Pending'";
    
    $stmt_update = $conn->prepare($sql_update);
    $types_update = str_repeat('i', count($order_ids_to_update));
    $stmt_update->bind_param($types_update, ...$order_ids_to_update);
    $stmt_update->execute();
}

exit;
?>