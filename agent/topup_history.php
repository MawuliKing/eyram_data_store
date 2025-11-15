<?php
include_once '_partials/header.php';

$user_id = $_SESSION['user_id'];

// All your existing PHP data fetching logic is perfect and remains unchanged.
$wallet_stmt = $conn->prepare("SELECT balance FROM wallet WHERE user_id = ?");
$wallet_stmt->bind_param("i", $user_id);
$wallet_stmt->execute();
$wallet_balance = $wallet_stmt->get_result()->fetch_assoc()['balance'] ?? 0.00;

$sales_stmt = $conn->prepare("SELECT SUM(JSON_EXTRACT(order_details, '$.price')) as total_sales FROM orders WHERE user_id = ? AND status = 'Complete'");
$sales_stmt->bind_param("i", $user_id);
$sales_stmt->execute();
$total_sales = $sales_stmt->get_result()->fetch_assoc()['total_sales'] ?? 0.00;

$deposits_stmt = $conn->prepare("SELECT SUM(amount) as total_deposits FROM topup_requests WHERE user_id = ? AND status = 'Approved'");
$deposits_stmt->bind_param("i", $user_id);
$deposits_stmt->execute();
$total_deposits = $deposits_stmt->get_result()->fetch_assoc()['total_deposits'] ?? 0.00;

$overdraft_stmt = $conn->prepare("SELECT overdraft_limit FROM users WHERE id = ?");
$overdraft_stmt->bind_param("i", $user_id);
$overdraft_stmt->execute();
$overdraft_limit = $overdraft_stmt->get_result()->fetch_assoc()['overdraft_limit'] ?? 0.00;

$requests_query = "SELECT amount, payment_network, payment_number, transaction_id, status, requested_at FROM topup_requests WHERE user_id = ? ORDER BY requested_at DESC";
$requests_stmt = $conn->prepare($requests_query);
$requests_stmt->bind_param("i", $user_id);
$requests_stmt->execute();
$requests = $requests_stmt->get_result();
?>

<style>
.summary-card {
    border-radius: 12px;
    color: #333;
    padding: 1rem;
}
/* --- NEW STYLES FOR WHITE THEME --- */
.history-card-white {
    background-color: #fff;
    color: #343a40; /* Dark text for readability */
    border: 1px solid #dee2e6;
    border-radius: 12px;
    margin-bottom: 1rem;
    padding: 1rem 1.25rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
}
.history-card-white .status-badge {
    font-size: 0.8rem;
    padding: 0.4rem 0.8rem;
    border-radius: 50px;
    font-weight: 600;
    color: #fff; /* Ensure badge text is white */
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Wallet Overview</h1>
    <a href="wallet_topup.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i>New Top-Up Request</a>
</div>

<!-- Summary Cards (Unchanged) -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card summary-card shadow-sm h-100" style="background-color: #e8f5e9;">
            <div><small class="text-muted">Balance</small><h4 class="fw-bold mb-0">GHS <?php echo number_format($wallet_balance, 2); ?></h4></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card summary-card shadow-sm h-100" style="background-color: #e3f2fd;">
            <div><small class="text-muted">Sales</small><h4 class="fw-bold mb-0">GHS <?php echo number_format($total_sales, 2); ?></h4></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card summary-card shadow-sm h-100" style="background-color: #f3e5f5;">
            <div><small class="text-muted">Deposits</small><h4 class="fw-bold mb-0">GHS <?php echo number_format($total_deposits, 2); ?></h4></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card summary-card shadow-sm h-100" style="background-color: #ffebee;">
            <div><small class="text-muted">Overdraft</small><h4 class="fw-bold mb-0">GHS <?php echo number_format($overdraft_limit, 2); ?></h4></div>
        </div>
    </div>
</div>

<!-- TOP-UP REQUESTS HISTORY -->
<div class="mb-4">
    <input type="text" id="topupSearch" onkeyup="filterTopupRequests()" class="form-control" placeholder="Search by Transaction ID, Phone Number or Network...">
</div>

<h4 class="mb-3">My Top-Up Requests</h4>
<?php if ($requests->num_rows > 0): ?>
    <div id="topup-requests-container">
        <?php while($request = $requests->fetch_assoc()): 
            // Use Bootstrap's standard color names
            $status_class = strtolower($request['status']);
            $badge_bg_color = 'secondary'; // Default for Pending
            if ($status_class == 'approved') $badge_bg_color = 'success';
            if ($status_class == 'declined') $badge_bg_color = 'danger';
        ?>
            <!-- --- UPDATED STYLING --- -->
            <div class="history-card-white shadow-sm">
                <div class="row align-items-center g-3">
                    <div class="col-md-3">
                        <small class="text-muted">Requested Amount</small>
                        <h4 class="fw-bold mb-0">GH₵ <?php echo number_format($request['amount'], 2); ?></h4>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-0"><strong>From:</strong> <?php echo htmlspecialchars($request['payment_number']); ?> (<?php echo htmlspecialchars($request['payment_network']); ?>)</p>
                        <p class="mb-0 text-muted"><strong>ID:</strong> <?php echo htmlspecialchars($request['transaction_id']); ?></p>
                    </div>
                    <div class="col-md-3 text-md-center">
                        <p class="mb-0 text-muted"><?php echo date("d M Y, h:i A", strtotime($request['requested_at'])); ?></p>
                    </div>
                    <div class="col-md-2 text-md-end mt-2 mt-md-0">
                        <!-- --- UPDATED BADGE STYLING --- -->
                        <span class="status-badge bg-<?php echo $badge_bg_color; ?>">
                            <?php echo strtoupper($request['status']); ?>
                        </span>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
<?php else: ?>
    <div class="alert alert-light text-center"><p class="mb-0">You have not made any top-up requests.</p></div>
<?php endif; ?>

<!-- JavaScript for Search (Unchanged) -->
<script>
function filterTopupRequests() {
    const input = document.getElementById('topupSearch');
    const filter = input.value.toUpperCase();
    const container = document.getElementById('topup-requests-container');
    const cards = container.querySelectorAll('.history-card-white'); // Updated selector

    cards.forEach(card => {
        const text = card.innerText.toUpperCase();
        if (text.includes(filter)) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>

<?php
include_once '_partials/footer.php';
?>