<?php
/*
==================================================================
 PART 1: PRE-PROCESSING & FORM HANDLING
==================================================================
*/
require_once $_SERVER['DOCUMENT_ROOT'] . '/digital_agent/_partials/db.php';
require_once __DIR__ . '/_partials/activity_helper.php';

// --- STEP 1: Check for a valid session and get the user ID FIRST ---
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "login.php");
    exit();
}
$user_id = $_SESSION['user_id']; // Now $user_id is available for the rest of the script.
$error_message = "";


// --- STEP 2: Now that we have the user ID, fetch user data ---
$stmt_user = $conn->prepare("SELECT email FROM users WHERE id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_result = $stmt_user->get_result();
$user_data = $user_result->fetch_assoc();
$user_email = $user_data['email'] ?? ''; // The user's email will now be correctly fetched.

// Load Paystack Public Key from environment (loaded via db.php -> config.php)
$paystack_public_key = PAYSTACK_PUBLIC_KEY;


// --- STEP 3: Handle the form submission for MANUAL top-up ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['transaction_id'])) {
    $amount = trim($_POST['amount']);
    $network = trim($_POST['payment_network']);
    $transaction_id = trim($_POST['transaction_id']);

    // --- Validate all inputs (Phone number validation has been removed) ---
    if (empty($amount) || empty($network) || empty($transaction_id)) {
        $error_message = "All fields are required.";
    } elseif (!is_numeric($amount) || $amount < 100) {
        $error_message = "The minimum top-up amount is GHS 100.00.";
    } else {
        // --- Check for duplicate transaction ID on the SAME network ---
        $stmt_check = $conn->prepare("SELECT id FROM topup_requests WHERE transaction_id = ? AND payment_network = ?");
        $stmt_check->bind_param("ss", $transaction_id, $network);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            // A duplicate was found for this specific network. Set an error message.
            $error_message = "This Transaction ID has already been submitted for the " . htmlspecialchars($network) . " network. Each transaction must be unique.";
        } else {
            // --- No duplicates found, proceed with inserting the new request (Phone number has been removed from query) ---
            $stmt = $conn->prepare("INSERT INTO topup_requests (user_id, amount, payment_network, transaction_id, status) VALUES (?, ?, ?, ?, 'Pending')");
            $stmt->bind_param("isss", $user_id, $amount, $network, $transaction_id);

            if ($stmt->execute()) {
                // Log the successful submission
                $log_description = "Submitted a manual top-up request of GHS " . number_format($amount, 2) . ".";
                logAgentActivity($conn, $log_description);

                // Redirect to prevent form resubmission
                header("Location: topup_history.php?status=success&msg=RequestSubmitted");
                exit();
            } else {
                $error_message = "There was an error submitting your request. Please try again.";
            }
        }
    }
}

/*
==================================================================
 PART 2: PAGE DISPLAY
==================================================================
*/
include_once '_partials/header.php';
?>

<!-- All your existing HTML, CSS, and JavaScript is perfect and remains unchanged -->
<style>
@keyframes slideInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
.animated-card { animation: slideInUp 0.6s ease-out forwards; }
.btn-gradient-history { background: linear-gradient(135deg, #6c757d, #343a40); color: white; font-weight: bold; border: none; transition: transform 0.2s ease, box-shadow 0.2s ease; }
.btn-gradient-history:hover { color: white; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.15); }
.form-control, .form-select { border: 1px solid #dee2e6; border-radius: 8px; padding: 0.75rem 1rem; transition: border-color 0.3s ease, box-shadow 0.3s ease; }
.form-control:focus, .form-select:focus { border-color: #86b7fe; box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25); }
.form-control::placeholder { color: #b0b0b0; opacity: 1; }
.nav-tabs .nav-link { border-width: 0 0 2px 0; border-color: transparent; color: #6c757d; transition: color 0.2s ease, border-color 0.2s ease; }
.nav-tabs .nav-link.active, .nav-tabs .nav-item.show .nav-link { color: #0d6efd; border-color: #0d6efd; background-color: transparent; font-weight: 600; }
.tab-content > .tab-pane.active { animation: fadeIn 0.5s ease-in-out; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">Top Up Your Wallet</h1>
    <a href="topup_history.php" class="btn btn-gradient-history"><i class="fas fa-history me-2"></i>View History</a>
</div>

<div class="card shadow-sm animated-card">
    <div class="card-body p-lg-4">
        <div class="col-md-10 col-lg-8 mx-auto">
            <ul class="nav nav-tabs mb-4" id="topupTabs" role="tablist">
                <li class="nav-item" role="presentation"><button class="nav-link active" id="manual-tab" data-bs-toggle="tab" data-bs-target="#manual" type="button" role="tab">Manual Top-Up</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" id="online-tab" data-bs-toggle="tab" data-bs-target="#online" type="button" role="tab">Instant Top-Up</button></li>
            </ul>

            <div class="tab-content" id="topupTabsContent">
                <div class="tab-pane fade show active" id="manual" role="tabpanel">
                    <div class="alert alert-info">
                        <h5 class="alert-heading">How to Top Up (Manually):</h5>
                        <ol class="mb-0 ps-3">
                            <li>Dial <strong>*170#</strong> on your MTN Mobile Money registered number.</li>
                            <li>Enter the official company number: <strong>0593351594
(Madam Baby Ventures/Vivian Tenge)</strong></li>
                            <li>Use <strong>1</strong> as the reference.</li>
                            <li><strong>Copy the Transaction ID</strong> from the MTN confirmation message and paste it into the form below.</li>
                        </ol>
                    </div>
                    <?php if(!empty($error_message)): ?>
                        <div class="alert alert-danger mt-3"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    <form action="wallet_topup.php" method="POST" class="mt-4">
                        <div class="mb-3"><label for="amount" class="form-label fw-bold">Top Up Amount (GH₵)</label><input type="number" step="0.01" class="form-control form-control-lg" id="amount" name="amount" required min="100" placeholder="Minimum GHS 100.00"></div>
                        <div class="mb-3"><label for="payment_network" class="form-label fw-bold">Payment Network You Paid With</label><select class="form-select form-select-lg" id="payment_network" name="payment_network" required><option value="" disabled selected>-- Choose a Network --</option><option value="MTN">MTN</option><option value="Telecel">Telecel</option><option value="AirtelTigo">AirtelTigo</option></select></div>
                   
                        <div class="mb-3">
                          <label for="transaction_id" class="form-label fw-bold">Transaction ID</label>
                          <input 
                            type="text" 
                            class="form-control form-control-lg" 
                            id="transaction_id" 
                            name="transaction_id" 
                            required 
                            placeholder="Paste the Transaction ID from the SMS"
                            maxlength="11" 
                            pattern="\d{11}" 
                            style="width: 100%; border: 1px solid #ced4da; padding: 10px; font-size: 1.25rem;" 
                            title="Transaction ID must be exactly 11 digits"
                          >
                        </div>

                        <div class="d-grid mt-4"><button type="submit" class="btn btn-primary btn-lg fw-bold">Submit Top-Up Request</button></div>
                    </form>
                </div>
                <div class="tab-pane fade" id="online" role="tabpanel">
                    <form id="paystack-form" class="mt-4">
                        <div class="mb-3"><label for="online_amount" class="form-label fw-bold">Amount to Deposit (Min GH₵ 10)</label><input type="number" class="form-control form-control-lg" id="online_amount" name="online_amount" min="10" required placeholder="e.g. 10" onkeyup="updateChargeSummary()"></div>
                        <div id="summary-section" class="card bg-light p-3 mb-4" style="display: none;"><div class="d-flex justify-content-between"><span>Amount to Deposit:</span><span id="summary_deposit">GH₵ 0.00</span></div><div class="d-flex justify-content-between"><span>Service Charge (2%):</span><span id="summary_charge">GH₵ 0.00</span></div><hr class="my-2"><div class="d-flex justify-content-between fw-bold fs-5"><span>Total To Pay:</span><span id="summary_total">GH₵ 0.00</span></div></div>
                        <div class="d-grid"><button type="button" class="btn btn-success btn-lg fw-bold" onclick="payWithPaystack()">Pay Now</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- This script for the Paystack library should already exist -->
<script src="https://js.paystack.co/v1/inline.js"></script>

<!-- Your existing <script> block remains unchanged -->
<script>
// These constants are passed from your PHP code
const PAYSTACK_PUBLIC_KEY = "<?php echo $paystack_public_key; ?>";
const USER_EMAIL = "<?php echo $user_email; ?>";
const USER_ID = <?php echo $user_id; ?>;

// --- Point this to your existing verification file ---
const VERIFY_URL = "<?php echo rtrim(BASE_URL, '/'); ?>" + "/agent/process_payment.php";

/**
 * Calculates and displays the service charge and total amount. (Unchanged)
 */
function updateChargeSummary() {
    const amountInput = document.getElementById('online_amount');
    const summarySection = document.getElementById('summary-section');
    let amount = parseFloat(amountInput.value) || 0;

    if (amount >= 10) {
        let charge = amount * 0.02; // 2% service charge
        let total = amount + charge;
        document.getElementById('summary_deposit').innerText = 'GHS ' + amount.toFixed(2);
        document.getElementById('summary_charge').innerText = 'GHS ' + charge.toFixed(2);
        document.getElementById('summary_total').innerText = 'GHS ' + total.toFixed(2);
        summarySection.style.display = 'block';
    } else {
        summarySection.style.display = 'none';
    }
}

/**
 * Initializes and opens the Paystack payment popup.
 */
function payWithPaystack() {
    const amountInput = document.getElementById('online_amount');
    let depositAmount = parseFloat(amountInput.value) || 0;

    if (!PAYSTACK_PUBLIC_KEY || !USER_EMAIL) {
         alert('Payment system is not configured correctly. Please check user email and API keys.');
         return;
    }
    
    if (depositAmount < 10) {
        alert('The minimum deposit amount is GHS 10.00.');
        return;
    }

    let charge = depositAmount * 0.02;
    let totalToPay = depositAmount + charge;
    let amountInPesewas = Math.round(totalToPay * 100);

    let handler = PaystackPop.setup({
        key: PAYSTACK_PUBLIC_KEY,
        email: USER_EMAIL,
        amount: amountInPesewas,
        currency: 'GHS',
        ref: 'wallet-topup-' + Math.floor((Math.random() * 1000000000) + 1),
        metadata: {
            user_id: USER_ID,
            deposit_amount: depositAmount,
            purpose: 'Wallet Top-up'
        },
        callback: function(response) {
            // Show an immediate "processing" state to the user
            alert('Payment received! Verifying and crediting your wallet, please wait...');

            // Use the fetch API to send the reference to YOUR server-side script
            fetch(VERIFY_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    reference: response.reference,
                    amount: depositAmount // Sending the original deposit amount
                })
            })
            .then(res => res.json()) // Get the JSON response from your PHP script
            .then(data => {
                if (data.status === 'success') {
                    // Success! Redirect to the history page with a success message.
                    alert(data.message);
                    window.location.href = 'topup_history.php?status=success&msg=PaystackTopupSuccess';
                } else {
                    // The server returned an error, show it to the user.
                    alert('Verification Failed: ' + data.message);
                }
            })
            .catch(error => {
                // Handle network errors or other issues with the fetch call
                console.error('Error verifying payment:', error);
                alert('A connection error occurred. Please contact support and provide this reference: ' + response.reference);
            });
        },
        onClose: function() {
            console.log('Payment popup closed by user.');
        }
    });
    handler.openIframe();
}
</script>

<?php
include_once '_partials/footer.php';
?>