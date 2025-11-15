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

$history_query = "SELECT type, amount, description, created_at FROM transactions WHERE user_id = ? AND status = 'completed' ORDER BY created_at DESC";
$history_stmt = $conn->prepare($history_query);
$history_stmt->bind_param("i", $user_id);
$history_stmt->execute();
$transactions = $history_stmt->get_result();
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
.text-credit { color: #198754 !important; } /* Bootstrap's standard green */
.text-debit { color: #dc3545 !important; } /* Bootstrap's standard red */
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Ledger Overview</h1>
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

<!-- FULL WALLET HISTORY (LEDGER) -->
<div class="mb-4">
    <input type="text" id="ledgerSearch" onkeyup="filterLedger()" class="form-control" placeholder="Search your transaction history...">
</div>

<h4 class="mb-3 mt-5">Full Wallet Ledger</h4>
<div id="ledger-container">
    <?php if ($transactions->num_rows > 0): ?>
        <?php while($tx = $transactions->fetch_assoc()): 
            $tx_type_class = strtolower($tx['type']);
            $icon = 'fa-question-circle text-muted'; 
            $amount_prefix = ''; 
            $amount_class = 'text-muted';

            if ($tx_type_class == 'credit') { 
                $icon = 'fa-arrow-down text-credit'; 
                $amount_prefix = '+ '; 
                $amount_class = 'text-credit'; 
            } elseif ($tx_type_class == 'debit' || $tx_type_class == 'purchase') { 
                $icon = 'fa-arrow-up text-debit'; 
                $amount_prefix = '- '; 
                $amount_class = 'text-debit'; 
            }
        ?>
            <!-- Applying the new white background class -->
            <div class="history-card-white shadow-sm">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <i class="fas <?php echo $icon; ?> fa-2x"></i>
                    </div>
                    <div class="col">
                        <p class="fw-bold mb-0"><?php echo htmlspecialchars($tx['description']); ?></p>
                        <small class="text-muted"><?php echo date("d M Y, h:i A", strtotime($tx['created_at'])); ?></small>
                    </div>
                    <div class="col-auto text-end">
                        <h5 class="fw-bold mb-0 <?php echo $amount_class; ?>">
                            <?php echo $amount_prefix; ?>GH₵ <?php echo number_format($tx['amount'], 2); ?>
                        </h5>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="alert alert-light text-center"><p class="mb-0">You have no wallet transactions yet.</p></div>
    <?php endif; ?>
</div>

<!-- JavaScript for Search -->
<script>
function filterLedger() {
    const input = document.getElementById('ledgerSearch');
    const filter = input.value.toUpperCase();
    const container = document.getElementById('ledger-container');
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