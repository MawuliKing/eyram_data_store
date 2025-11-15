<?php
require_once '_partials/db.php';

$user_id = (int)($_GET['id'] ?? 0);
$error_message = '';
$step = 1; // Start at step 1: Email verification

if ($user_id <= 0) {
    header("Location: forgot_password.php");
    exit();
}

// Fetch the user's actual email from the database to compare against
$stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows == 0) {
    header("Location: forgot_password.php");
    exit();
}
$user = $result->fetch_assoc();
$correct_email = $user['email'];

// Handle the form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handling Step 1: Email Verification
    if (isset($_POST['email_to_verify'])) {
        $submitted_email = trim($_POST['email_to_verify']);
        if (strtolower($submitted_email) === strtolower($correct_email)) {
            // Email matches! Proceed to step 2.
            $step = 2;
        } else {
            $error_message = "The email address does not match our records for this account.";
            $step = 1; // Stay on step 1
        }
    }

    // Handling Step 2: New Password Submission
    elseif (isset($_POST['new_password'])) {
        $password = $_POST['new_password'];
        $password_confirm = $_POST['password_confirm'];

        if (strlen($password) < 8) {
            $error_message = "Password must be at least 8 characters long.";
            $step = 2; // Stay on step 2
        } elseif ($password !== $password_confirm) {
            $error_message = "Passwords do not match.";
            $step = 2; // Stay on step 2
        } else {
            // All good! Update the password.
            $new_password_hash = password_hash($password, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $new_password_hash, $user_id);
            $update_stmt->execute();
            
            $_SESSION['login_message'] = "Your password has been reset successfully. Please log in.";
            header("Location: login.php");
            exit();
        }
    }
}

include_once '_partials/header.php';
?>
<div class="login-page-wrapper">
    <div class="login-container">
        <div class="login-image-section">
            <div class="content">
                <h1 class="text-warning" style="font-weight: 700;">PASSWORD RESET</h1>
            </div>
        </div>
        <div class="login-form-section">
            
            <?php if ($step === 1): ?>
                <!-- Step 1: Verify Email Form -->
                <div class="text-center mb-4">
                    <h3>Step 2: Verify Your Identity</h3>
                    <p class="text-muted">Please enter the email address associated with this account.</p>
                </div>
                <?php if(!empty($error_message)): ?>
                    <div class="alert alert-danger p-2 text-center"><?php echo $error_message; ?></div>
                <?php endif; ?>
                <form action="verify_identity.php?id=<?php echo $user_id; ?>" method="POST">
                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="email_to_verify" name="email_to_verify" placeholder="name@example.com" required>
                        <label for="email_to_verify">Enter Your Email Address</label>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-warning btn-lg text-dark fw-bold">Verify Email</button>
                    </div>
                </form>

            <?php elseif ($step === 2): ?>
                <!-- Step 2: Set New Password Form -->
                <div class="text-center mb-4">
                    <h3>Step 3: Set Your New Password</h3>
                    <p class="text-success"><i class="fas fa-check-circle me-1"></i> Identity verified successfully!</p>
                </div>
                <?php if(!empty($error_message)): ?>
                    <div class="alert alert-danger p-2 text-center"><?php echo $error_message; ?></div>
                <?php endif; ?>
                <form action="verify_identity.php?id=<?php echo $user_id; ?>" method="POST">
                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="new_password" name="new_password" placeholder="New Password" required>
                        <label for="new_password">New Password</label>
                    </div>
                    <div class="form-floating mb-4">
                        <input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="Confirm New Password" required>
                        <label for="password_confirm">Confirm New Password</label>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-warning btn-lg text-dark fw-bold">Reset Password</button>
                    </div>
                </form>
            <?php endif; ?>

            <div class="text-center mt-4">
                <a href="login.php" class="text-decoration-none"><i class="fas fa-arrow-left me-1"></i> Back to Login</a>
            </div>
        </div>
    </div>
</div>
<?php
include_once '_partials/footer.php';
?>