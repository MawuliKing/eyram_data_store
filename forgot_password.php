<?php
require_once '_partials/db.php';

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $unique_code = trim($_POST['unique_code']);

    if (empty($unique_code)) {
        $error_message = "Please enter your unique code.";
    } else {
        // Check if a user with this code exists (across all code columns)
        $stmt = $conn->prepare("SELECT id FROM users WHERE agent_code = ? OR super_admin_code = ? OR customer_code = ? LIMIT 1");
        $stmt->bind_param("sss", $unique_code, $unique_code, $unique_code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            // User found, redirect to the verification step with their ID
            header("Location: verify_identity.php?id=" . $user['id']);
            exit();
        } else {
            $error_message = "No account found with that unique code.";
        }
        $stmt->close();
    }
}

include_once '_partials/header.php';
?>

<div class="login-page-wrapper">
    <div class="login-container">
        <div class="login-image-section">
            <div class="content">
                <h1 class="text-warning" style="font-weight: 700;">PASSWORD RESET</h1>
                <p>Step 1: Identify Your Account</p>
            </div>
        </div>
        <div class="login-form-section">
            <div class="text-center mb-4">
                <h3>Forgot Your Password?</h3>
                <p class="text-muted">Please enter your unique code to begin.</p>
            </div>

            <?php if(!empty($error_message)): ?>
                <div class="alert alert-danger p-2 text-center"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <form action="forgot_password.php" method="POST">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="unique_code" name="unique_code" placeholder="Your Unique Code" required>
                    <label for="unique_code">Enter Your Agent/Customer Code</label>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-warning btn-lg text-dark fw-bold">Continue</button>
                </div>
            </form>
            <div class="text-center mt-4">
                <a href="login.php" class="text-decoration-none"><i class="fas fa-arrow-left me-1"></i> Back to Login</a>
            </div>
        </div>
    </div>
</div>

<?php
include_once '_partials/footer.php';
?>