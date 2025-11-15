<?php
// admin/profile.php (FINAL CORRECTED VERSION - Solves "Headers Already Sent" Error)

// Start the session at the very top, before anything else.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ===================================================================
//  LOGIC LAYER: Handle the form submission BEFORE any HTML is output.
// ===================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // We only need the DB connection when processing the form.
    require_once $_SERVER['DOCUMENT_ROOT'] . '/digital_agent/_partials/db.php';

    // Security check to ensure the user is still a logged-in admin.
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
        die("Security check failed. Please log in again.");
    }
    $admin_id = $_SESSION['user_id'];

    // 1. Sanitize and retrieve form data
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // 2. Validation
    if (empty($full_name) || empty($email)) {
        $_SESSION['alert_type'] = 'error';
        $_SESSION['alert_message'] = 'Full Name and Email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['alert_type'] = 'error';
        $_SESSION['alert_message'] = 'Please enter a valid email address.';
    } elseif (!empty($password) && $password !== $confirm_password) {
        $_SESSION['alert_type'] = 'error';
        $_SESSION['alert_message'] = 'Passwords do not match.';
    } else {
        // 3. Build the SQL update query
        $sql = "UPDATE users SET full_name = ?, email = ?";
        $types = "ss";
        $params = [$full_name, $email];
        
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql .= ", password = ?";
            $types .= "s";
            $params[] = $hashed_password;
        }
        
        $sql .= " WHERE id = ?";
        $types .= "i";
        $params[] = $admin_id;

        // 4. Execute the query
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            $_SESSION['alert_type'] = 'success';
            $_SESSION['alert_message'] = 'Profile updated successfully!';
            $_SESSION['user_full_name'] = $full_name; // Update session name
        } else {
            $_SESSION['alert_type'] = 'error';
            $_SESSION['alert_message'] = 'An error occurred. Could not update profile.';
        }
        $stmt->close();
    }
    
    // After processing, redirect and then stop the script immediately.
    header("Location: profile.php");
    exit();
}

// =====================================================================
//  PRESENTATION LAYER: This code only runs if the form was NOT submitted.
// =====================================================================

// Now that all logic is done, we can safely include the header and start displaying the page.
include_once '_partials/header.php';

// Get the current admin's ID from the session for displaying data.
$admin_id = $_SESSION['user_id'];

// Fetch the current admin data to pre-fill the form fields.
$stmt = $conn->prepare("SELECT full_name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin_data = $result->fetch_assoc();
$stmt->close();

?>

<!-- HTML Form for the Profile Page -->
<h1 class="h2 mb-4">Admin Profile</h1>

<div class="row">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header">
                <h5 class="mb-0">Edit Your Details</h5>
            </div>
            <div class="card-body">
                <form action="profile.php" method="POST">
                    <!-- Full Name -->
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?= htmlspecialchars($admin_data['full_name']) ?>" required>
                    </div>

                    <!-- Email Address -->
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($admin_data['email']) ?>" required>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h6 class="mb-3 text-muted">Update Password</h6>
                    
                    <!-- New Password -->
                    <div class="mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="password" name="password">
                        <div class="form-text">Leave blank to keep your current password.</div>
                    </div>

                    <!-- Confirm New Password -->
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// If you have a file like '_partials/footer.php', include it here.
// include_once '_partials/footer.php'; 
?>