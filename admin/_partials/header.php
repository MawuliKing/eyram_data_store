<?php

// DB connection and security checks
require_once $_SERVER['DOCUMENT_ROOT'] . '/digital_agent/_partials/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: https://safbyte.com//digital_agent/login.php");
    exit();
}

// Get the current page name to set the 'active' class on the correct menu item
$current_page = basename($_SERVER['PHP_SELF']);
$site_settings = [];
$result = $conn->query("SELECT setting_key, setting_value FROM settings");
while ($row = $result->fetch_assoc()) {
    $site_settings[$row['setting_key']] = $row['setting_value'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - SafeByte</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/admin_style.css?v=<?php echo time(); ?>">
</head>
<body>

<!-- Mobile-only Offcanvas Sidebar -->
<div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="mobileSidebar" style="background-color: #024781; color: white;">
    <div class="offcanvas-header border-bottom border-light">
        <h5 class="offcanvas-title">SAFEBYTE</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        <div class="list-group list-group-flush">
              <a href="profile.php" class="list-group-item <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>"><i class="fas fa-user-circle fa-fw me-2"></i>Admin Profile</a>
            <a href="dashboard.php" class="list-group-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt fa-fw me-2"></i>Dashboard</a>
            <a href="users.php" class="list-group-item <?php echo ($current_page == 'users.php') ? 'active' : ''; ?>"><i class="fas fa-users fa-fw me-2"></i>User Management</a>
            <a href="services.php" class="list-group-item <?php echo ($current_page == 'services.php') ? 'active' : ''; ?>"><i class="fas fa-box-open fa-fw me-2"></i>Service Management</a>
            <a href="orders.php" class="list-group-item <?php echo ($current_page == 'orders.php') ? 'active' : ''; ?>"><i class="fas fa-shopping-cart fa-fw me-2"></i>Order Management</a>
            <a href="topup_requests.php" class="list-group-item <?php echo ($current_page == 'topup_requests.php') ? 'active' : ''; ?>"><i class="fas fa-wallet fa-fw me-2"></i>Transactions</a>
            <a href="form_submissions.php" class="list-group-item <?php echo ($current_page == 'form_submissions.php') ? 'active' : ''; ?>"><i class="fas fa-file-alt fa-fw me-2"></i>Forms Submission</a>
            <a href="announcements.php" class="list-group-item <?php echo ($current_page == 'announcements.php') ? 'active' : ''; ?>"><i class="fas fa-bullhorn fa-fw me-2"></i>Announcements</a>
            <a href="settings.php" class="list-group-item <?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>"><i class="fas fa-cog fa-fw me-2"></i>System Settings</a>
            <!-- ✅ NEW PROFILE LINK FOR MOBILE -->
        
            <a href="activity_log.php" class="list-group-item <?php echo ($current_page == 'activity_log.php') ? 'active' : ''; ?>"><i class="fas fa-history fa-fw me-2"></i>Recent Activity</a>
        </div>
    </div>
</div>

<div class="admin-layout">
    <!-- Desktop-only Sidebar -->
    <div class="admin-sidebar d-none d-lg-flex flex-column">
        <div class="sidebar-heading text-center">
            <h5 class="text-warning my-2">SAFEBYTE</h5>
        </div>
        <div class="list-group list-group-flush">
              <a href="profile.php" class="list-group-item <?php echo ($current_page == 'profile.php') ? 'active' : ''; ?>"><i class="fas fa-user-circle fa-fw me-2"></i>Admin Profile</a>
            <a href="dashboard.php" class="list-group-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt fa-fw me-2"></i>Dashboard</a>
            <a href="users.php" class="list-group-item <?php echo ($current_page == 'users.php') ? 'active' : ''; ?>"><i class="fas fa-users fa-fw me-2"></i>User Management</a>
            <a href="services.php" class="list-group-item <?php echo ($current_page == 'services.php') ? 'active' : ''; ?>"><i class="fas fa-box-open fa-fw me-2"></i>Service Management</a>
            <a href="orders.php" class="list-group-item <?php echo ($current_page == 'orders.php') ? 'active' : ''; ?>"><i class="fas fa-shopping-cart fa-fw me-2"></i>Order Management</a>
            <a href="topup_requests.php" class="list-group-item <?php echo ($current_page == 'topup_requests.php') ? 'active' : ''; ?>"><i class="fas fa-wallet fa-fw me-2"></i>Transactions</a>
            <a href="form_submissions.php" class="list-group-item <?php echo ($current_page == 'form_submissions.php') ? 'active' : ''; ?>"><i class="fas fa-file-alt fa-fw me-2"></i>Forms Submission</a>
            <a href="announcements.php" class="list-group-item <?php echo ($current_page == 'announcements.php') ? 'active' : ''; ?>"><i class="fas fa-bullhorn fa-fw me-2"></i>Announcements</a>
            <a href="settings.php" class="list-group-item <?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>"><i class="fas fa-cog fa-fw me-2"></i>System Settings</a>
             <!-- ✅ NEW PROFILE LINK FOR DESKTOP -->
          
            <a href="activity_log.php" class="list-group-item <?php echo ($current_page == 'activity_log.php') ? 'active' : ''; ?>"><i class="fas fa-history fa-fw me-2"></i>Recent Activity</a>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="admin-content">
        <!-- Top bar with welcome message and logout -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm">
            <div class="container-fluid">
                <!-- Hamburger button, only visible on mobile -->
                <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="ms-auto d-flex align-items-center">
                    <div class="me-3 d-none d-md-block">
                        <!-- ✅ MADE THE WELCOME NAME A CLICKABLE LINK -->
                        <a href="profile.php" class="text-decoration-none text-dark">
                            Welcome, <strong><?php echo htmlspecialchars($_SESSION['user_full_name']); ?></strong>
                        </a>
                    </div>
                    <a href="<?php echo BASE_URL; ?>logout.php" class="btn btn-danger">
                        <i class="fas fa-power-off d-md-none"></i>
                        <span class="d-none d-md-inline">Logout</span>
                    </a>
                </div>
            </div>
        </nav>
        
        <!-- This container is where the page-specific content will live -->
        <div class="container-fluid p-4">
            <?php if (isset($_SESSION['alert_message'])): ?>
            <script>
                Swal.fire({
                    icon: '<?= $_SESSION['alert_type'] ?? 'info' ?>',
                    title: '<?= ucfirst($_SESSION['alert_type']) ?>',
                    text: '<?= $_SESSION['alert_message'] ?>',
                    timer: 2500,
                    showConfirmButton: false
                });
            </script>
            <?php
                unset($_SESSION['alert_message'], $_SESSION['alert_type']);
            endif;
            ?>