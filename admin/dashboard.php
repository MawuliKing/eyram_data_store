<?php 
// Include our complete, working header
include_once '_partials/header.php'; 

// --- DATA FETCHING FOR INITIAL PAGE LOAD ---
// This entire section fetches the same data as the API.
// Its purpose is to make sure the dashboard is fully populated when the page
// first loads, before the live JavaScript updates begin.

// --- Stat Cards ---
$result_users = $conn->query("SELECT COUNT(id) as total_users FROM users WHERE role != 'Admin'");
$total_users = $result_users->fetch_assoc()['total_users'] ?? 0;

$result_pending = $conn->query("SELECT COUNT(id) as pending_orders FROM orders WHERE status = 'Pending'");
$pending_orders = $result_pending->fetch_assoc()['pending_orders'] ?? 0;

$result_deposits = $conn->query("SELECT SUM(amount) as total_deposits FROM topup_requests WHERE status = 'Approved'");
$total_deposits = $result_deposits->fetch_assoc()['total_deposits'] ?? 0;

$result_overdraft = $conn->query("SELECT SUM(overdraft_limit) as total_overdraft FROM users WHERE role IN ('Agent', 'Super Agent')");
$total_overdraft = $result_overdraft->fetch_assoc()['total_overdraft'] ?? 0;

$query_today = "SELECT SUM(JSON_EXTRACT(order_details, '$.price')) as total_revenue_today FROM orders WHERE status = 'Complete' AND DATE(created_at) = CURDATE()";
$result_daily_revenue = $conn->query($query_today);
$total_revenue_today = $result_daily_revenue->fetch_assoc()['total_revenue_today'] ?? 0;

$query_overall = "SELECT SUM(JSON_EXTRACT(order_details, '$.price')) as total_revenue_overall FROM orders WHERE status = 'Complete'";
$result_overall_revenue = $conn->query($query_overall);
$total_revenue_overall = $result_overall_revenue->fetch_assoc()['total_revenue_overall'] ?? 0;

// --- Chart Data ---
$chart_labels_init = [];
$chart_sales_data_init = [];
$sales_by_date_init = [];

$query_chart_init = "SELECT DATE(created_at) as sale_date, SUM(JSON_EXTRACT(order_details, '$.price')) as daily_total FROM orders WHERE status = 'Complete' AND created_at >= CURDATE() - INTERVAL 6 DAY GROUP BY sale_date ORDER BY sale_date ASC";
$result_chart_init = $conn->query($query_chart_init);
if ($result_chart_init) {
    while ($row = $result_chart_init->fetch_assoc()) {
        $sales_by_date_init[$row['sale_date']] = $row['daily_total'];
    }
}
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_labels_init[] = date('M d', strtotime($date));
    $chart_sales_data_init[] = (float)($sales_by_date_init[$date] ?? 0);
}
// Convert PHP arrays to JSON strings for embedding in JavaScript
$chart_labels_json = json_encode($chart_labels_init);
$chart_data_json = json_encode($chart_sales_data_init);
?>

<!-- Custom Styles (as provided) -->
<style>
    body { background: #f8f9fa; }
    .stat-card { background: linear-gradient(135deg, #6e8efb, #a777e3); color: white; border: none; border-radius: 12px; transition: transform 0.3s ease, box-shadow 0.3s ease; }
    .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.15); }
    .stat-card-icon i { font-size: 2.5rem; color: rgba(255, 255, 255, 0.85); }
    .datetime-box { background: #343a40; color: white; padding: 10px 15px; border-radius: 10px; margin-bottom: 1.5rem; display: inline-block; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .card-revenue-today { background: linear-gradient(135deg, #17ead9, #6078ea); }
    .card-revenue-overall { background: linear-gradient(135deg, #f857a6, #ff5858); }
    .card-overdraft { background: linear-gradient(135deg, #ff9a9e, #fecfef); }
    .card-deposits { background: linear-gradient(135deg, #fbc2eb, #a6c1ee); }
</style>

<h1 class="h2 mb-4">Dashboard Overview</h1>

<div class="datetime-box">
    <i class="fas fa-clock me-2"></i><span id="currentDateTime">Loading date...</span>
</div>

<!-- Main Statistics Row -->
<div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-4">
    <!-- Today's Sales Revenue Card -->
    <div class="col">
        <div class="card stat-card card-revenue-today shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-card-icon me-3"><i class="fas fa-cash-register"></i></div>
                <div>
                    <h6 class="card-subtitle">Today's Sales Revenue</h6>
                    <h4 class="card-title fw-bold" id="today-sales-revenue">GH₵ <?php echo number_format($total_revenue_today, 2); ?></h4>
                </div>
            </div>
        </div>
    </div>
    <!-- Overall Sales Revenue Card -->
    <div class="col">
        <div class="card stat-card card-revenue-overall shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-card-icon me-3"><i class="fas fa-landmark"></i></div>
                <div>
                    <h6 class="card-subtitle">Overall Sales Revenue</h6>
                    <h4 class="card-title fw-bold" id="overall-sales-revenue">GH₵ <?php echo number_format($total_revenue_overall, 2); ?></h4>
                </div>
            </div>
        </div>
    </div>
    <!-- Total Agents Card -->
     <div class="col">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-card-icon me-3"><i class="fas fa-users"></i></div>
                <div>
                    <h6 class="card-subtitle">Total Agents & Customers</h6>
                    <h4 class="card-title fw-bold" id="total-agents"><?php echo number_format($total_users); ?></h4>
                </div>
            </div>
        </div>
    </div>
    <!-- Pending Orders Card -->
    <div class="col">
        <div class="card stat-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-card-icon me-3"><i class="fas fa-hourglass-half"></i></div>
                <div>
                    <h6 class="card-subtitle">Pending Orders</h6>
                    <h4 class="card-title fw-bold" id="pending-orders"><?php echo number_format($pending_orders); ?></h4>
                </div>
            </div>
        </div>
    </div>
    <!-- Total Deposits (Wallet Funding) Card -->
    <div class="col">
        <div class="card stat-card card-deposits shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-card-icon me-3"><i class="fas fa-wallet"></i></div>
                <div>
                    <h6 class="card-subtitle">Total Wallet Deposits</h6>
                    <h4 class="card-title fw-bold" id="total-deposits">GH₵ <?php echo number_format($total_deposits, 2); ?></h4>
                </div>
            </div>
        </div>
    </div>
    <!-- Total Overdraft Card -->
    <div class="col">
        <div class="card stat-card card-overdraft shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-card-icon me-3"><i class="fas fa-credit-card"></i></div>
                <div>
                    <h6 class="card-subtitle">Total Overdraft Limit</h6>
                    <h4 class="card-title fw-bold" id="total-overdraft">GH₵ <?php echo number_format($total_overdraft, 2); ?></h4>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart Section -->
<div class="card shadow-sm mt-4">
    <div class="card-header bg-white border-0 py-3">
        <h5 class="mb-0">Sales Overview (Last 7 Days)</h5>
    </div>
    <div class="card-body">
        <canvas id="salesChart" height="100"></canvas>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- SCRIPT FOR LIVE CLOCK, STATS, AND CHART -->
<script>
    // --- Live Clock ---
    function updateDateTime() {
        const now = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };
        document.getElementById("currentDateTime").textContent = now.toLocaleDateString('en-US', options);
    }
    setInterval(updateDateTime, 1000);
    
    // --- Chart Initialization ---
    const ctx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo $chart_labels_json; ?>,
            datasets: [{
                label: 'Revenue (GH₵)',
                data: <?php echo $chart_data_json; ?>,
                borderColor: 'rgba(75, 192, 192, 1)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderWidth: 2,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: true, position: 'top' }, tooltip: { mode: 'index', intersect: false } },
            scales: { y: { beginAtZero: true } }
        }
    });

    // --- Live Data Update Function ---
    async function updateDashboardStats() {
        try {
            const response = await fetch('get_dashboard_data.php');
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();

            // Helper function to format currency
            const formatCurrency = (value) => `GH₵ ${parseFloat(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            const formatNumber = (value) => parseInt(value).toLocaleString('en-US');
            
            // Update Stat Cards
            document.getElementById('today-sales-revenue').textContent = formatCurrency(data.total_revenue_today);
            document.getElementById('overall-sales-revenue').textContent = formatCurrency(data.total_revenue_overall);
            document.getElementById('total-agents').textContent = formatNumber(data.total_users);
            document.getElementById('pending-orders').textContent = formatNumber(data.pending_orders);
            document.getElementById('total-deposits').textContent = formatCurrency(data.total_deposits);
            document.getElementById('total-overdraft').textContent = formatCurrency(data.total_overdraft);

            // Update Chart
            if (data.chartData) {
                salesChart.data.labels = data.chartData.labels;
                salesChart.data.datasets[0].data = data.chartData.sales;
                salesChart.update(); // Redraw the chart with the new data
            }

        } catch (error) {
            console.error("Could not fetch dashboard data:", error);
        }
    }

    // --- Initial Setup ---
    document.addEventListener('DOMContentLoaded', () => {
        updateDateTime(); // Set clock immediately
        updateDashboardStats(); // Fetch latest data on load
        
        // Set an interval to keep fetching new data every 5 seconds
        setInterval(updateDashboardStats, 5000); 
    });
</script>

<?php 
// Include the admin footer
include_once '_partials/footer.php'; 
?>