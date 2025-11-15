<?php
// The logic comes before the HTML
require_once $_SERVER['DOCUMENT_ROOT'] . '/digital_agent/_partials/db.php';

// If user is already logged in, redirect them away from login page
if (isset($_SESSION['user_id'])) {
    // A more flexible redirect based on role
    if (isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'Admin' || $_SESSION['user_role'] === 'platform_admin')) {
         header("Location: " . BASE_URL . "admin/dashboard.php");
    } else {
         header("Location: " . BASE_URL . "agent/dashboard.php");
    }
    exit();
}

$email = "";
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $error_message = "Both email and password are required.";
    } else {
        $sql = "SELECT id, full_name, password, role, status FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                if ($user['status'] == 'blocked') {
                    $error_message = "Your account has been blocked. Please contact support.";
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['user_full_name'] = $user['full_name'];
                    $_SESSION['user_email'] = $email; // Storing email in session can be useful

                    if ($user['role'] == 'Admin' || $user['role'] == 'platform_admin') {
                        header("Location: " . BASE_URL . "admin/dashboard.php");
                    } else {
                        header("Location: " . BASE_URL . "agent/dashboard.php");
                    }
                    exit();
                }
            } else {
                $error_message = "Invalid email or password.";
            }
        } else {
            $error_message = "Invalid email or password.";
        }
        $stmt->close();
    }
}
?>
<?php 
include_once '_partials/header.php'; 
?>
<!-- NEW STYLE FOR THE PASSWORD TOGGLE ICON -->
<style>
.password-wrapper {
    position: relative;
}
.password-toggle-icon {
    position: absolute;
    top: 50%;
    right: 15px;
    transform: translateY(-50%);
    cursor: pointer;
    color: #6c757d;
}
</style>

<div class="login-page-wrapper">
    <div class="login-container">
        <!-- Image Section -->
        <div class="login-image-section">
            <div class="content">
                <h1 class="text-warning" style="font-weight: 700;">SAFEBYTE DIGITAL PLATFORM</h1>
                <p>Your one-stop platform for digital services.</p>
            </div>
        </div>

        <!-- Form Section -->
        <div class="login-form-section">
            <div class="text-center mb-4">
                <h3>Welcome Back!</h3>
                <p class="text-muted">Please sign in to continue</p>
            </div>
            
            <?php if(!empty($error_message)): ?>
                <div class="alert alert-danger p-2 text-center"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <form action="login.php" method="POST">
                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" required value="<?php echo htmlspecialchars($email); ?>">
                    <label for="email">Email Address</label>
                </div>

                <!-- --- UPDATED PASSWORD INPUT --- -->
                <div class="form-floating mb-4 password-wrapper">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                    <label for="password">Password</label>
                    <!-- The eye icon for toggling visibility -->
                    <i class="fas fa-eye password-toggle-icon" id="password-toggle"></i>
                </div>
                <!-- --- END OF UPDATE --- -->

                <div class="d-grid">
                    <button type="submit" class="btn btn-warning btn-lg text-dark fw-bold">Sign In</button>
                </div>
            </form>
            
            <div class="text-center mt-4">
                <a href="forgot_password.php" class="text-decoration-none">Forgot Password?</a>
            </div>
        </div>
    </div>
</div>

<!-- NEW JAVASCRIPT FOR THE TOGGLE FUNCTIONALITY -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const passwordToggle = document.getElementById('password-toggle');

    if (passwordToggle) {
        passwordToggle.addEventListener('click', function() {
            // Toggle the type attribute
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            // Toggle the icon
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    }
});
</script>

<?php 
include_once '_partials/footer.php'; 
?>