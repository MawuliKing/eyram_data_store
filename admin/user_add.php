<?php
/*
==================================================================
 PART 1: PRE-PROCESSING & FORM HANDLING
==================================================================
*/
require_once $_SERVER['DOCUMENT_ROOT'] . '/digital_agent/_partials/db.php';
// --- STEP 1: Include our new activity logger ---
require_once __DIR__ . '/_partials/activity_helper.php';

// Security Check
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'Admin' && $_SESSION['user_role'] !== 'platform_admin')) {
    exit('Access Denied.');
}

// Initialize variables
$full_name = $email = $phone_number = $role = $overdraft_limit = "";
$error_message = "";

// --- FORM SUBMISSION HANDLING ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']);
    $overdraft_limit = trim($_POST['overdraft_limit']);

    // --- FORM VALIDATION (Unchanged) ---
    if (empty($full_name) || empty($email) || empty($phone_number) || empty($password) || empty($role)) {
        $error_message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    } elseif (!is_numeric($overdraft_limit) || $overdraft_limit < 0) {
        $error_message = "Overdraft limit must be a valid number (0 or greater).";
    } else {
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ? OR phone_number = ?");
        $stmt_check->bind_param("ss", $email, $phone_number);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $error_message = "An account with this email or phone number already exists.";
        } else {
            // --- ROLE-AWARE CODE GENERATION LOGIC ---
            $code_column = '';
            $generated_code = '';

            if ($role === 'Super Agent') {
                $code_column = 'super_admin_code';
                $generated_code = 'SA-' . rand(1000, 9999);
            } elseif ($role === 'Agent') {
                $code_column = 'agent_code';
                $generated_code = 'IMK' . rand(1000, 9999);
            } elseif ($role === 'Customer') {
                $code_column = 'customer_code';
                $generated_code = 'CUST-' . rand(10000, 99999);
            }

            if (!empty($code_column)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $sql = "INSERT INTO users (full_name, email, phone_number, password, role, overdraft_limit, `$code_column`) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt_insert = $conn->prepare($sql);
                $stmt_insert->bind_param("sssssds", $full_name, $email, $phone_number, $hashed_password, $role, $overdraft_limit, $generated_code);

                if ($stmt_insert->execute()) {
                    $new_user_id = $stmt_insert->insert_id;
                    $stmt_wallet = $conn->prepare("INSERT INTO wallet (user_id, balance) VALUES (?, 0.00)");
                    $stmt_wallet->bind_param("i", $new_user_id);
                    $stmt_wallet->execute();

                    // --- STEP 2: Log the user creation activity ---
                    $log_description = "Created a new user: '" . $full_name . "' with the role '" . $role . "'.";
                    logActivity($conn, $log_description);
                    // --- END OF LOGGING ---

                    header("Location: users.php?status=success&msg=UserCreated");
                    exit();
                } else {
                    $error_message = "Database error. Could not create user.";
                }
            } else {
                $error_message = "An invalid role was selected.";
            }
        }
    }
}

/*
==================================================================
 PART 2: PAGE DISPLAY (Unchanged)
==================================================================
*/
include_once '_partials/header.php';
?>

<h1 class="h2 mb-4">Add New User</h1>
<div class="card shadow-sm">
    <div class="card-body">
        <div class="col-md-8 col-lg-6">
            <?php if(!empty($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            <form action="user_add.php" method="POST">
                <div class="mb-3">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" required value="<?php echo htmlspecialchars($full_name); ?>">
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" required value="<?php echo htmlspecialchars($email); ?>">
                </div>
                <div class="mb-3">
                    <label for="phone_number" class="form-label">Phone Number</label>
                    <input type="tel" class="form-control" id="phone_number" name="phone_number" required value="<?php echo htmlspecialchars($phone_number); ?>">
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Set Initial Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="mb-3">
                    <label for="role" class="form-label">Assign Role</label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="" disabled selected>-- Select a Role --</option>
                        <option value="Customer">Customer</option>
                        <option value="Agent">Agent</option>
                        <option value="Super Agent">Super Agent</option>
                    </select>
                </div>
                <hr class="my-4">
                <div class="mb-3">
                    <label for="overdraft_limit" class="form-label">Overdraft Limit (GH₵)</label>
                    <input type="number" step="0.01" class="form-control" id="overdraft_limit" name="overdraft_limit" value="0.00" required>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Create User Account</button>
                    <a href="users.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
include_once '_partials/footer.php';
?>