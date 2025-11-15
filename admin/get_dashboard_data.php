<?php
/**
 * API Endpoint to Fetch Live Dashboard Data
 *
 * This script connects to the database, queries all necessary statistics,
 * and outputs them in a structured JSON format for the frontend to consume.
 */

// Include your database connection file. 
// IMPORTANT: Ensure this file ONLY handles the connection ($conn) and does NOT output any HTML.
// If your '_partials/header.php' outputs HTML, create a dedicated 'db_connect.php' and include that instead.
include_once '_partials/db_connect.php'; // Recommended approach

// --- DATA FETCHING FOR STATISTIC CARDS ---

// 1. Fetch Today's Sales Revenue
$query_today = "SELECT SUM(JSON_EXTRACT(order_details, '$.price')) as total_revenue_today 
                FROM orders 
                WHERE status = 'Complete' AND DATE(created_at) = CURDATE()";
$result_daily_revenue = $conn->query($query_today);
$total_revenue_today = $result_daily_revenue->fetch_assoc()['total_revenue_today'] ?? 0;

// 2. Fetch Overall Sales Revenue
$query_overall = "SELECT SUM(JSON_EXTRACT(order_details, '$.price')) as total_revenue_overall 
                  FROM orders 
                  WHERE status = 'Complete'";
$result_overall_revenue = $conn->query($query_overall);
$total_revenue_overall = $result_overall_revenue->fetch_assoc()['total_revenue_overall'] ?? 0;

// 3. Fetch Total Agents & Customers
$result_users = $conn->query("SELECT COUNT(id) as total_users FROM users WHERE role != 'Admin'");
$total_users = $result_users->fetch_assoc()['total_users'] ?? 0;

// 4. Fetch Pending Orders
$result_pending = $conn->query("SELECT COUNT(id) as pending_orders FROM orders WHERE status = 'Pending'");
$pending_orders = $result_pending->fetch_assoc()['pending_orders'] ?? 0;

// 5. Fetch Total Wallet Deposits
$result_deposits = $conn->query("SELECT SUM(amount) as total_deposits FROM topup_requests WHERE status = 'Approved'");
$total_deposits = $result_deposits->fetch_assoc()['total_deposits'] ?? 0;

// 6. Fetch Total Overdraft Limit
$result_overdraft = $conn->query("SELECT SUM(overdraft_limit) as total_overdraft FROM users WHERE role IN ('Agent', 'Super Agent')");
$total_overdraft = $result_overdraft->fetch_assoc()['total_overdraft'] ?? 0;


// --- DATA FETCHING FOR SALES CHART (Last 7 Days) ---

$chart_labels = [];
$chart_sales_data = [];
$sales_by_date = [];

// Query the database for sales totals grouped by day for the last week
$query_chart = "SELECT 
                    DATE(created_at) as sale_date, 
                    SUM(JSON_EXTRACT(order_details, '$.price')) as daily_total
                FROM orders 
                WHERE 
                    status = 'Complete' AND created_at >= CURDATE() - INTERVAL 6 DAY
                GROUP BY sale_date
                ORDER BY sale_date ASC";

$result_chart = $conn->query($query_chart);
if ($result_chart) {
    while ($row = $result_chart->fetch_assoc()) {
        $sales_by_date[$row['sale_date']] = $row['daily_total'];
    }
}

// Populate the final arrays, filling in 0 for any days that had no sales
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('M d', strtotime($date)); // Format: "Aug 16"
    $chart_sales_data[] = (float)($sales_by_date[$date] ?? 0); // Ensure value is a number
}


// --- COMPILE ALL DATA INTO A SINGLE ARRAY ---

$data = [
    // Stat card data
    'total_revenue_today'   => (float)$total_revenue_today,
    'total_revenue_overall' => (float)$total_revenue_overall,
    'total_users'           => (int)$total_users,
    'pending_orders'        => (int)$pending_orders,
    'total_deposits'        => (float)$total_deposits,
    'total_overdraft'       => (float)$total_overdraft,
    
    // Chart data nested in its own object
    'chartData' => [
        'labels' => $chart_labels,
        'sales'  => $chart_sales_data
    ]
];

// --- SET HEADER AND OUTPUT AS JSON ---
// This is the final step. It tells the browser it's receiving JSON data.
header('Content-Type: application/json');
echo json_encode($data);

// Ensure no other output (HTML, warnings, etc.) is sent after this line.
exit();
?>