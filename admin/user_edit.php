<?php
/*
==================================================================
 PART 1: PRE-PROCESSING & FORM HANDLING
==================================================================
*/
require_once $_SERVER['DOCUMENT_ROOT'] . '/digital_agent/_partials/db.php';
// ★★★ ADDED: Include the activity logger helper file, needed for admin logging ★★★
require_once __DIR__ . '/_partials/activity_helper.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    exit('Access Denied.');
}
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: users.php?status=error&msg=InvalidRequest");
    exit();
}
$user_id_to_edit = (int)$_GET['id'];
$error_message = "";

// --- FORM SUBMISSION HANDLING ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- Check which form was submitted ---

    // IF 'Save Changes' for user details was clicked
    if (isset($_POST['update_details'])) {
        // This block remains unchanged as it's not related to wallet adjustments
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone_number = trim($_POST['phone_number']);
        $role = trim($_POST['role']);
        $password = trim($_POST['password']);
        $overdraft_limit = trim($_POST['overdraft_limit']);

        if (empty($full_name) || empty($email) || empty($phone_number) || empty($role) || !is_numeric($overdraft_limit) || $overdraft_limit < 0) {
            $error_message = "All user detail fields are required and overdraft must be a valid number.";
        } else {
            if (!empty($password) && strlen($password) < 6) {
                $error_message = "New password must be at least 6 characters long.";
            } else {
                if (!empty($password)) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt_update = $conn->prepare("UPDATE users SET full_name=?, email=?, phone_number=?, role=?, password=?, overdraft_limit=? WHERE id=?");
                    $stmt_update->bind_param("sssssdi", $full_name, $email, $phone_number, $role, $hashed_password, $overdraft_limit, $user_id_to_edit);
                } else {
                    $stmt_update = $conn->prepare("UPDATE users SET full_name=?, email=?, phone_number=?, role=?, overdraft_limit=? WHERE id=?");
                    $stmt_update->bind_param("ssssdi", $full_name, $email, $phone_number, $role, $overdraft_limit, $user_id_to_edit);
                }
                if ($stmt_update->execute()) {
                    header("Location: users.php?status=success&msg=UserUpdated");
                    exit();
                } else {
                    $error_message = "Database Error: " . $stmt_update->error;
                }
            }
        }
    }

   // IF 'Adjust Wallet' was clicked
    if (isset($_POST['adjust_wallet'])) {
        $adjustment_type = $_POST['adjustment_type'];
        $amount = (float)trim($_POST['amount']);
        $reason = trim($_POST['reason']);

        if (empty($amount) || empty($reason) || $amount <= 0) {
            $error_message = "A positive Amount and a Reason are required for wallet adjustments.";
        } else {
            $conn->begin_transaction();
            try {
                // --- YOUR EXISTING WALLET LOGIC (UNCHANGED) ---
                $wallet_check_stmt = $conn->prepare("SELECT id, balance FROM wallet WHERE user_id = ? FOR UPDATE");
                $wallet_check_stmt->bind_param("i", $user_id_to_edit);
                $wallet_check_stmt->execute();
                $wallet_result = $wallet_check_stmt->get_result();
                $wallet_row = $wallet_result->fetch_assoc();

                if ($adjustment_type == 'credit') {
                    if ($wallet_row) {
                        $update_wallet_stmt = $conn->prepare("UPDATE wallet SET balance = balance + ? WHERE user_id = ?");
                        $update_wallet_stmt->bind_param("di", $amount, $user_id_to_edit);
                        $update_wallet_stmt->execute();
                    } else {
                        $create_wallet_stmt = $conn->prepare("INSERT INTO wallet (user_id, balance) VALUES (?, ?)");
                        $create_wallet_stmt->bind_param("id", $user_id_to_edit, $amount);
                        $create_wallet_stmt->execute();
                    }
                } else { // 'debit'
                    if ($wallet_row) {
                        $update_wallet_stmt = $conn->prepare("UPDATE wallet SET balance = balance - ? WHERE user_id = ?");
                        $update_wallet_stmt->bind_param("di", $amount, $user_id_to_edit);
                        $update_wallet_stmt->execute();
                    } else {
                        throw new Exception("Cannot debit funds: User does not have a wallet record.");
                    }
                }

                // --- YOUR EXISTING TRANSACTIONS LOGIC (UNCHANGED) ---
                $trans_stmt = $conn->prepare("INSERT INTO transactions (user_id, type, amount, description, status, transaction_ref) VALUES (?, ?, ?, ?, 'completed', ?)");
                $transaction_ref = "MANUAL-ADJ-" . time();
                $trans_stmt->bind_param("isdss", $user_id_to_edit, $adjustment_type, $amount, $reason, $transaction_ref);
                $trans_stmt->execute();
                
                // ★★★ START: NEW ACTIVITY LOGGING FEATURE ★★★

                // 1. Get the user's name to make the log messages more descriptive.
                $user_info_stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
                $user_info_stmt->bind_param("i", $user_id_to_edit);
                $user_info_stmt->execute();
                $user_info_result = $user_info_stmt->get_result()->fetch_assoc();
                $user_name_to_log = $user_info_result['full_name'] ?? 'Unknown User';

                // 2. Prepare the dynamic parts of the log messages.
                $formatted_amount = number_format($amount, 2);
                $action_text_user = ($adjustment_type == 'credit') ? 'credited with' : 'debited by';
                $action_text_admin = ($adjustment_type == 'credit') ? 'credited' : 'debited';

                // 3. Create the activity log for the AFFECTED USER/AGENT.
                $user_log_message = "Your account was manually {$action_text_user} GHS {$formatted_amount} by an administrator. Reason: " . htmlspecialchars($reason);
                $stmt_user_activity = $conn->prepare("INSERT INTO activity_log (user_id, action_description, created_at) VALUES (?, ?, NOW())");
                $stmt_user_activity->bind_param("is", $user_id_to_edit, $user_log_message);
                $stmt_user_activity->execute();
                
                // 4. Create the activity log for the ADMIN who performed the action.
                $admin_log_message = "You manually {$action_text_admin} user '{$user_name_to_log}' with GHS {$formatted_amount}. Reason: " . htmlspecialchars($reason);
                // This uses your existing logActivity function to log for the currently logged-in admin
                logActivity($conn, $admin_log_message);

                // ★★★ END: NEW ACTIVITY LOGGING FEATURE ★★★

                $conn->commit();
                $_SESSION['message'] = "Wallet adjusted successfully. Both user and admin activities have been logged.";
                $_SESSION['message_type'] = "success";
                
                header("Location: user_edit.php?id=" . $user_id_to_edit . "&t=" . time());
                exit();

            } catch (Exception $e) {
                $conn->rollback();
                $error_message = "Wallet adjustment failed: " . $e->getMessage();
            }
        }
    }
}


/*
==================================================================
 PART 2: PAGE DISPLAY (This entire section is unchanged)
==================================================================
*/
// Fetch user data, now including wallet balance
$stmt_fetch = $conn->prepare("
    SELECT u.full_name, u.email, u.phone_number, u.role, u.overdraft_limit, w.balance 
    FROM users u
    LEFT JOIN wallet w ON u.id = w.user_id
    WHERE u.id = ?
");
$stmt_fetch->bind_param("i", $user_id_to_edit);
$stmt_fetch->execute();
$result = $stmt_fetch->get_result();
if ($result->num_rows === 0) {
    header("Location: users.php?status=error&msg=UserNotFound");
    exit();
}
$user = $result->fetch_assoc();

// Pre-fill form variables
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    $full_name = $user['full_name'];
    $email = $user['email'];
    $phone_number = $user['phone_number'];
    $role = $user['role'];
    $overdraft_limit = $user['overdraft_limit'];
}
$current_balance = $user['balance'] ?? 0.00;

include_once '_partials/header.php';
// This is the code to generate the pop-up script from a session message
$page_scripts = ''; 
if(isset($_SESSION['message'])) {
    $page_scripts = "<script>Swal.fire({toast: true, position: 'top-end', icon: '" . $_SESSION['message_type'] . "', title: '" . addslashes($_SESSION['message']) . "', showConfirmButton: false, timer: 3500, timerProgressBar: true});</script>";
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}
?>

<div class="d-flex justify-content-between align-items-center">
    <h1 class="h2 mb-4">Edit User: <?php echo htmlspecialchars($user['full_name']); ?></h1>
    <h4 class="text-success">Current Balance: GHS <?php echo number_format($current_balance, 2); ?></h4>
</div>

<div class="row">
    <!-- User Details Form -->
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header"><h5 class="mb-0">User Details</h5></div>
            <div class="card-body">
                <form action="user_edit.php?id=<?php echo $user_id_to_edit; ?>" method="POST">
                    <div class="mb-3"><label for="full_name" class="form-label">Full Name</label><input type="text" class="form-control" id="full_name" name="full_name" required value="<?php echo htmlspecialchars($full_name); ?>"></div>
                    <div class="mb-3"><label for="email" class="form-label">Email Address</label><input type="email" class="form-control" id="email" name="email" required value="<?php echo htmlspecialchars($email); ?>"></div>
                    <div class="mb-3"><label for="phone_number" class="form-label">Phone Number</label><input type="tel" class="form-control" id="phone_number" name="phone_number" required value="<?php echo htmlspecialchars($phone_number); ?>"></div>
                    <div class="mb-3"><label for="role" class="form-label">Role</label><select class="form-select" id="role" name="role" required><option value="Customer" <?php echo ($role == 'Customer') ? 'selected' : ''; ?>>Customer</option><option value="Agent" <?php echo ($role == 'Agent') ? 'selected' : ''; ?>>Agent</option><option value="Super Agent" <?php echo ($role == 'Super Agent') ? 'selected' : ''; ?>>Super Agent</option></select></div>
                    <div class="mb-3"><label for="overdraft_limit" class="form-label">Overdraft Limit (GH₵)</label><input type="number" step="0.01" class="form-control" id="overdraft_limit" name="overdraft_limit" value="<?php echo htmlspecialchars($overdraft_limit); ?>" required></div>
                    <hr>
                    <div class="mb-3"><label for="password" class="form-label">New Password</label><input type="password" class="form-control" id="password" name="password"><div class="form-text">Leave blank to not change password.</div></div>
                    <div class="mt-4"><button type="submit" name="update_details" class="btn btn-primary">Save Changes</button><a href="users.php" class="btn btn-secondary">Back to Users</a></div>
                </form>
            </div>
        </div>
    </div>

    <!-- Manual Wallet Adjustment Form -->
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header"><h5 class="mb-0">Manual Wallet Adjustment</h5></div>
            <div class="card-body">
                <?php if(!empty($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>
                <form action="user_edit.php?id=<?php echo $user_id_to_edit; ?>" method="POST">
                    <div class="mb-3">
                        <label for="adjustment_type" class="form-label">Action</label>
                        <select class="form-select" name="adjustment_type" id="adjustment_type">
                            <option value="credit">Credit (Add Funds)</option>
                            <option value="debit">Debit (Remove Funds)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="amount" class="form-label">Amount (GH₵)</label>
                        <input type="number" step="0.01" class="form-control" name="amount" id="amount" required placeholder="e.g., 50.00">
                    </div>
                    <div class="mb-3">
                        <label for="reason" class="form-label">Reason / Description</label>
                        <textarea class="form-control" name="reason" id="reason" rows="2" required placeholder="e.g., Bonus for high sales"></textarea>
                    </div>
                    <button type="submit" name="adjust_wallet" class="btn btn-warning text-dark fw-bold">Adjust Wallet</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// This now includes the script to show the success message popup
echo $page_scripts; 
include_once '_partials/footer.php';
?>