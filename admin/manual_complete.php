<?php
// admin/manual_complete.php (FINAL, CORRECTED VERSION)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ --- THIS IS THE FINAL, CORRECTED SECURITY CHECK BASED ON YOUR DEBUG INFO --- ✅
// It now correctly checks if the session variable 'user_role' is exactly 'Admin'.
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    die("Access Denied. You must be an administrator to perform this action.");
}
// --- END OF FIX ---


require_once $_SERVER['DOCUMENT_ROOT'] . '/digital_agent/_partials/db.php';

// --- Input Validation ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['category_to_complete'])) {
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'Invalid request or category not selected.'];
    header('Location: orders.php');
    exit();
}

$category = $_POST['category_to_complete'];
$start_date = $_POST['start_date'] ?? null;
$end_date = $_POST['end_date'] ?? null;

// --- Build the SQL Query Dynamically ---
$sql = "UPDATE orders SET status = 'Complete' WHERE status = 'Processing'";
$params = [];
$types = "";

// 1. Add the mandatory category filter
$sql .= " AND JSON_UNQUOTE(JSON_EXTRACT(order_details, '$.category')) = ?";
$params[] = $category;
$types .= "s";

// 2. Add date filters if they were provided
if (!empty($start_date)) {
    $sql .= " AND created_at >= ?";
    $params[] = $start_date . " 00:00:00";
    $types .= "s";
}
if (!empty($end_date)) {
    $sql .= " AND created_at <= ?";
    $params[] = $end_date . " 23:59:59";
    $types .= "s";
}

// --- Execute the Query ---
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    $_SESSION['message'] = ['type' => 'danger', 'text' => 'Error preparing the database query.'];
    header('Location: orders.php');
    exit();
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$affected_rows = $stmt->affected_rows;
$stmt->close();
$conn->close();

// --- Provide Feedback to the Admin ---
if ($affected_rows > 0) {
    $_SESSION['message'] = ['type' => 'success', 'text' => "Success! Manually completed " . $affected_rows . " order(s)."];
} else {
    $_SESSION['message'] = ['type' => 'info', 'text' => "No 'Processing' orders were found matching your criteria."];
}

header('Location: orders.php');
exit();
?>