<?php
/*
==================================================================
 PART 1: PRE-PROCESSING
==================================================================
*/
require_once $_SERVER['DOCUMENT_ROOT'] . '/digital_agent/_partials/db.php';
// --- STEP 1: Include the agent-specific activity logger ---
require_once __DIR__ . '/_partials/activity_helper.php';

// Security check: Make sure a user is logged in.
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
$error_message = "";

// --- FORM SUBMISSION HANDLING ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_full_name = trim($_POST['full_name']);
    $new_phone_number = trim($_POST['phone_number']);
    $new_password = trim($_POST['password']);
    $password_confirm = trim($_POST['password_confirm']);

    if (empty($new_full_name) || empty($new_phone_number)) {
        $error_message = "Your name and phone number cannot be empty.";
    } else {
        $log_message_part = "Updated their profile (Name/Phone)."; // Default log message

        // This empty check ensures the rest of the code only runs if validation passes so far
        if (empty($error_message)) {
            // Check if user is trying to update their password
            if (!empty($new_password)) {
                if (strlen($new_password) < 6) {
                    $error_message = "New password must be at least 6 characters long.";
                } elseif ($new_password !== $password_confirm) {
                    $error_message = "Passwords do not match.";
                } else {
                    // Password is valid and being updated
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt_update = $conn->prepare("UPDATE users SET full_name = ?, phone_number = ?, password = ? WHERE id = ?");
                    $stmt_update->bind_param("sssi", $new_full_name, $new_phone_number, $hashed_password, $user_id);
                    $log_message_part = "Updated their profile and changed their password."; // More specific log message
                }
            } else {
                // User is not updating their password
                $stmt_update = $conn->prepare("UPDATE users SET full_name = ?, phone_number = ? WHERE id = ?");
                $stmt_update->bind_param("ssi", $new_full_name, $new_phone_number, $user_id);
            }

            // If no validation errors occurred and the statement is ready
            if (empty($error_message) && isset($stmt_update)) {
                if ($stmt_update->execute()) {
                    // --- STEP 2: Log the profile update activity ---
                    logAgentActivity($conn, $log_message_part);
                    // --- END OF LOGGING ---

                    // Update the session and save it before redirecting
                    $_SESSION['user_full_name'] = $new_full_name;
                    session_write_close(); 
                    
                    header("Location: dashboard.php?status=success&msg=ProfileUpdated");
                    exit(); 
                } else {
                    $error_message = "Database Error: " . $stmt_update->error;
                }
                $stmt_update->close();
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
$full_name = $_SESSION['user_full_name'] ?? 'User';
$email = $user_data['email'] ?? '';
$phone_number = $user_data['phone_number'] ?? '';
?>

<h1 class="h2 mb-4">Edit Your Profile</h1>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="col-md-8 col-lg-6">
            
            <?php if(!empty($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <form action="profile.php" method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($email); ?>" disabled readonly>
                </div>
                <div class="mb-3">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" required value="<?php echo htmlspecialchars($full_name); ?>">
                </div>
                <div class="mb-3">
                    <label for="phone_number" class="form-label">Phone Number</label>
                    <input type="tel" class="form-control" id="phone_number" name="phone_number" required value="<?php echo htmlspecialchars($phone_number); ?>">
                </div>
                <hr class="my-4">
                <h5 class="mb-3">Change Password</h5>
                <div class="mb-3">
                    <label for="password" class="form-label">New Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Leave blank to keep current password">
                </div>
                <div class="mb-3">
                    <label for="password_confirm" class="form-label">Confirm New Password</label>
                    <input type="password" class="form-control" id="password_confirm" name="password_confirm">
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php
include_once '_partials/footer.php';
?>