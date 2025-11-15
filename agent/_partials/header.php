<?php
// Use the secure and reliable path to include the config and DB files
require_once $_SERVER['DOCUMENT_ROOT'] . '/digital_agent/_partials/config.php';
require_once BASE_PATH . '/_partials/db.php';

// --- AGENT SECURITY CHECK ---
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "login.php");
    exit();
}
if ($_SESSION['user_role'] === 'Admin' || $_SESSION['user_role'] === 'platform_admin') {
    header("Location: " . BASE_URL . "admin/dashboard.php");
    exit();
}
$settings = [];
$settings_query = $conn->query("SELECT * FROM settings");
while ($row = $settings_query->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// --- FETCH USER-SPECIFIC DATA (NOW INCLUDES ALL CODES) ---
$user_id = $_SESSION['user_id'];
// Fetch all potential codes along with other user data
$sql = "SELECT full_name, email, role, agent_code, super_admin_code, customer_code, 
               (SELECT balance FROM wallet WHERE user_id = ?) as balance 
        FROM users WHERE id = ?";
$user_stmt = $conn->prepare($sql);
$user_stmt->bind_param("ii", $user_id, $user_id);
$user_stmt->execute();
$user_data = $user_stmt->get_result()->fetch_assoc();

$full_name = $user_data['full_name'] ?? 'Guest';
$email = $user_data['email'] ?? '';
$role = $user_data['role'] ?? 'Customer';
$wallet_balance = $user_data['balance'] ?? 0.00;

// --- NEW LOGIC: Determine the correct code and label based on the user's role ---
$user_code_label = 'Your Code';
$user_code_value = 'N/A';

if ($role === 'Super Admin' && !empty($user_data['super_admin_code'])) {
    $user_code_label = 'Super Admin Code';
    $user_code_value = $user_data['super_admin_code'];
} elseif ($role === 'Agent' && !empty($user_data['agent_code'])) {
    $user_code_label = 'Agent Code';
    $user_code_value = $user_data['agent_code'];
} elseif ($role === 'Customer' && !empty($user_data['customer_code'])) {
    $user_code_label = 'Customer ID';
    $user_code_value = $user_data['customer_code'];
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Portal - SafeByte</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin_style.css?v=<?php echo time(); ?>">

    <!-- Styles for animations and gradients (Unchanged) -->
    <style>
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .navbar.sticky-top { animation: fadeInDown 0.6s ease-out forwards; }
        .top-nav-icon { transition: transform 0.2s ease-in-out; }
        .top-nav-icon:hover { transform: scale(1.15); }
        .offcanvas-gradient { background: linear-gradient(180deg, #2c3e50, #16222A); }
        .sidebar-link { transition: background-color 0.2s ease-in-out, padding-left 0.2s ease-in-out; border-radius: 8px; margin: 2px 0; }
        .sidebar-link:hover { background-color: rgba(255, 255, 255, 0.1) !important; padding-left: 1.25rem !important; }
    </style>
</head>
<body>

<!-- Mobile-only Offcanvas Sidebar -->
<div class="offcanvas offcanvas-start offcanvas-gradient" tabindex="-1" id="agentSidebar" style="color: white;">
    <div class="offcanvas-header" style="background-color: rgba(0,0,0,0.2);">
        <div>
            <h5 class="offcanvas-title mb-0"><?php echo htmlspecialchars($full_name); ?></h5>
            <small class="text-white-50"><?php echo htmlspecialchars($email); ?></small>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <!-- Display the correct code label -->
                <small class="text-white-50"><?php echo $user_code_label; ?></small>
                <!-- Display the correct code value -->
                <div class="fw-bold"><?php echo htmlspecialchars($user_code_value); ?></div>
            </div>
            <div>
                <small class="text-white-50">Balance</small>
                <div class="fw-bold">GH₵ <?php echo number_format($wallet_balance, 2); ?></div>
            </div>
        </div>
        
        <!-- Add the new .sidebar-link class to all menu items -->
        <div class="list-group list-group-flush">
            <a href="dashboard.php" class="list-group-item text-white bg-transparent sidebar-link"><i class="fas fa-th-large fa-fw me-3"></i>Dashboard</a>
            <a href="packages.php" class="list-group-item text-white bg-transparent sidebar-link"><i class="fas fa-box-open fa-fw me-3"></i>Packages</a>
            <a href="afa_mashup.php" class="list-group-item text-white bg-transparent sidebar-link"><i class="fas fa-cogs fa-fw me-3"></i>MTN AFA Mashup</a>
            <a href="afa_registration.php" class="list-group-item text-white bg-transparent sidebar-link"><i class="fas fa-user-check fa-fw me-3"></i>MTN AFA Registration</a>
            <div class="text-white-50 small text-uppercase px-3 pt-3 pb-2 fw-bold">Others</div>
            <a href="business_certificate.php" class="list-group-item text-white bg-transparent sidebar-link"><i class="fas fa-briefcase fa-fw me-3"></i>Business Certificate</a>
            <a href="result_checker.php" class="list-group-item text-white bg-transparent sidebar-link"><i class="fas fa-graduation-cap fa-fw me-3"></i>Result & Placement</a>
            <a href="tin_registration.php" class="list-group-item text-white bg-transparent sidebar-link"><i class="fas fa-id-card-alt fa-fw me-3"></i>TIN Registration</a>
            <a href="mtn_sim_registration.php" class="list-group-item text-white bg-transparent sidebar-link"><i class="fas fa-sim-card fa-fw me-3"></i>MTN Agent SIM</a>
            <div class="text-white-50 small text-uppercase px-3 pt-3 pb-2 fw-bold">User Menu</div>
            <a class="list-group-item text-white bg-transparent sidebar-link" data-bs-toggle="collapse" href="#walletCollapse" role="button"><i class="fas fa-wallet fa-fw me-3"></i>Wallet <i class="fas fa-chevron-down float-end small mt-1"></i></a>
            <div class="collapse" id="walletCollapse">
                <a data-bs-toggle="collapse" href="#walletHistoryCollapse" role="button" class="list-group-item text-white bg-transparent ps-5 d-flex justify-content-between align-items-center sidebar-link">Wallet History <i class="fas fa-chevron-down small"></i></a>
                <div class="collapse ps-4" id="walletHistoryCollapse">
                    <a href="topup_history.php" class="list-group-item text-white bg-transparent ps-5 sidebar-link">My Top-Up Requests</a>
                    <a href="wallet_ledger.php" class="list-group-item text-white bg-transparent ps-5 sidebar-link">Full Wallet Ledger</a>
                </div>
                <a href="wallet_topup.php" class="list-group-item text-white bg-transparent ps-5 sidebar-link">Top-Up</a>
            </div>
            <a href="my_orders.php" class="list-group-item text-white bg-transparent sidebar-link"><i class="fas fa-shopping-bag fa-fw me-3"></i>My Orders</a>
              <a href="my_activity.php" class="list-group-item text-white bg-transparent sidebar-link"> <i class="fas fa-history fa-fw me-3"></i>My Activity</a>
            <a href="https://wa.me/<?= htmlspecialchars($settings['support_whatsapp']) ?>" class="list-group-item text-white bg-transparent sidebar-link" target="_blank" rel="noopener noreferrer"><i class="fab fa-whatsapp fa-fw me-3"></i>WhatsApp Support</a>
            <a href="<?= htmlspecialchars($settings['whatsapp_community_link']) ?>" class="list-group-item text-white bg-transparent sidebar-link" target="_blank" rel="noopener noreferrer"><i class="fas fa-users fa-fw me-3"></i>Join Us Now</a>
        </div>
    </div>
</div>

<!-- Main Content Area -->
<div class="main-content-agent">
    <!-- Dark Blue Sticky Top Navigation Bar -->
    <nav class="navbar navbar-dark sticky-top shadow-sm" style="background-color: #2c3e50;">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold top-nav-icon" href="dashboard.php"><i class="fas fa-th-large me-2"></i>Dashboard</a>
            <div class="d-flex align-items-center gap-3">
                <?php $ann_count = $conn->query("SELECT COUNT(*) as total FROM announcements WHERE user_role IN ('All', '{$role}')")->fetch_assoc()['total']; ?>
                <a href="#" class="text-white position-relative top-nav-icon" style="font-size: 1.4rem;" data-bs-toggle="modal" data-bs-target="#announcementModal">
                    <i class="fas fa-bell"></i>
                    <?php if ($ann_count > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning" style="font-size: 0.6rem;"><?= $ann_count ?></span>
                    <?php endif; ?>
                </a>
                <a href="#" class="text-white position-relative top-nav-icon" style="font-size: 1.4rem;" data-bs-toggle="offcanvas" data-bs-target="#cartOffcanvas">
                    <i class="fas fa-shopping-cart"></i>
                    <span id="cart-count-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">0</span>
                </a>
                <a href="profile.php" class="text-white top-nav-icon" style="font-size: 1.4rem;"><i class="fas fa-user"></i></a>
                <a href="<?= BASE_URL ?>logout.php" class="text-white top-nav-icon" style="font-size: 1.4rem;"><i class="fas fa-sign-out-alt"></i></a>
                <button class="navbar-toggler border-0 ms-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#agentSidebar"><span class="navbar-toggler-icon"></span></button>
            </div>
        </div>
    </nav>

    <!-- Slide-in Cart Panel (Unchanged) -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="cartOffcanvas">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title"><i class="fas fa-shopping-cart me-2"></i>Your Cart</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <div id="cart-items-container"><p class="text-center text-muted mt-4">Your cart is empty.</p></div>
        </div>
        <div class="offcanvas-footer border-top p-3 bg-light">
            <div class="d-flex justify-content-between fw-bold mb-3">
                <span>Total:</span>
                <span id="cart-total">GHS 0.00</span>
            </div>
            <div class="d-grid">
                <button class="btn btn-primary" id="process-order-btn" disabled>Process Order</button>
            </div>
        </div>
    </div>
    
    <!-- Announcements Modal (Unchanged) -->
    <div class="modal fade" id="announcementModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
              <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="fas fa-bullhorn me-2"></i>Announcements</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <?php
                $stmt_ann = $conn->prepare("SELECT * FROM announcements WHERE user_role IN ('All', ?) ORDER BY created_at DESC");
                $stmt_ann->bind_param("s", $role);
                $stmt_ann->execute();
                $result_ann = $stmt_ann->get_result();
                if ($result_ann->num_rows === 0): ?>
                    <p class="text-muted text-center">No announcements available at the moment.</p>
                <?php else:
                    while ($row_ann = $result_ann->fetch_assoc()): ?>
                        <div class="mb-4">
                            <h6 class="text-primary mb-1"><?= htmlspecialchars($row_ann['title']) ?></h6>
                            <small class="text-muted"><?= date("F j, Y, g:i a", strtotime($row_ann['created_at'])) ?></small>
                            <p class="mt-2"><?= nl2br(htmlspecialchars($row_ann['message'])) ?></p>
                            <hr>
                        </div>
                <?php endwhile; endif; ?>
              </div>
            </div>
        </div>
    </div>
    
    <!-- Main content container that will hold the page content -->
    <div class="container-fluid p-4">