<?php 
// Include the agent header, which provides all our user data
include_once '_partials/header.php'; 
$stmt = $conn->prepare("SELECT balance FROM wallet WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$wallet_balance = $stmt->get_result()->fetch_assoc()['balance'] ?? 0.00;

// This block for the success message pop-up remains unchanged.
if(isset($_SESSION['agent_message'])) {
    echo "<script>Swal.fire({ icon: '" . $_SESSION['agent_message_type'] . "', title: 'Success!', text: '" . addslashes($_SESSION['agent_message']) . "', timer: 3000, showConfirmButton: false });</script>";
    unset($_SESSION['agent_message']);
    unset($_SESSION['agent_message_type']);
}
?>

<!-- ============================================= -->
<!-- NEW STYLES FOR ANIMATIONS & MODERN LOOK -->
<!-- ============================================= -->
<style>
@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.welcome-header, .balance-card, .service-card {
    animation: slideInUp 0.6s ease-out forwards;
    opacity: 0; /* Initially hidden */
}

/* Delay animations for a staggered effect */
.balance-card { animation-delay: 0.1s; }
.service-card-1 { animation-delay: 0.2s; }
.service-card-2 { animation-delay: 0.3s; }
.service-card-3 { animation-delay: 0.4s; }
.service-card-4 { animation-delay: 0.5s; }

.service-card {
    border-radius: 16px;
    color: white;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    text-align: center;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    text-shadow: 1px 1px 3px rgba(0,0,0,0.2);
}
.service-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.2);
}
.service-card .graphic {
    height: 80px;
    width: auto;
    margin-bottom: 1.5rem;
    filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));
}
.service-card .btn-service {
    background-color: rgba(255, 255, 255, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.5);
    color: #fff;
    transition: background-color 0.3s ease;
}
.service-card .btn-service:hover {
    background-color: rgba(255, 255, 255, 0.3);
}

/* New Gradient Colors */
.bg-gradient-purple { background: linear-gradient(135deg, #8E2DE2, #4A00E0); }
.bg-gradient-orange { background: linear-gradient(135deg, #ff9966, #ff5e62); }
.bg-gradient-teal { background: linear-gradient(135deg, #00c9ff, #92fe9d); }
.bg-gradient-green { background: linear-gradient(135deg, #2EDC71, #28b485); }
</style>


<!-- ============================================= -->
<!-- NEW ANIMATED WELCOME HEADER -->
<!-- ============================================= -->
<!-- ============================================= -->
<!-- UPDATED GRADIENT-THEMED WELCOME HEADER -->
<!-- ============================================= -->
<div class="card shadow-sm mb-4 welcome-header" style="background: linear-gradient(135deg, #e0f7fa, #b2ebf2); border-radius: 16px; border: none;">
    <div class="card-body p-4 d-flex align-items-center">
        <!-- The icon now has a solid, darker background that complements the gradient -->
        <div class="d-flex align-items-center justify-content-center rounded-circle me-3 text-white" style="width: 50px; height: 50px; background-color: #00796b; font-size: 1.5rem;">
            <?php echo strtoupper(substr($full_name, 0, 1)); ?>
        </div>
        <div>
            <!-- The text color will be dark for high contrast against the light gradient -->
            <h1 class="h4 mb-0 fw-bold text-dark">Hello, <?php echo htmlspecialchars($full_name); ?>!</h1>
            <p class="text-secondary mb-0">Welcome to your <?php echo htmlspecialchars($role); ?> Portal</p>
        </div>
    </div>
</div>


<!-- Main Content Grid -->
<div class="row g-4">

    <!-- Available Balance Card (Unchanged, but with animation class) -->
    <div class="col-12">
        <div class="card text-white shadow-sm balance-card" style="background: linear-gradient(135deg, #3498db, #2980b9); border-radius: 16px;">
            <div class="card-body p-4 d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="card-subtitle text-white-50 mb-1">Available Balance</h6>
                    <h2 class="card-title display-5 fw-bold mb-1">GH₵ <?php echo number_format($wallet_balance, 2); ?></h2>
                </div>
                <a href="wallet_topup.php" class="btn btn-light btn-lg rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; font-size: 1.5rem; color: #2980b9;">
                    <i class="fas fa-plus"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Buy Data Card (Refreshed) -->
    <div class="col-12 col-md-6 col-lg-3">
        <div class="service-card shadow-sm h-100 bg-gradient-purple service-card-1">
    
            <h5 class="card-title fw-bold">Buy Data</h5>
            <p class="card-text opacity-75 small">Purchase data bundles for all networks.</p>
            <a href="packages.php" class="btn btn-service mt-auto fw-bold">Go to Services</a>
        </div>
    </div>
    
    <!-- Result Checker Card (Refreshed) -->
    <div class="col-12 col-md-6 col-lg-3">
        <div class="service-card shadow-sm h-100 bg-gradient-orange service-card-2">

            <h5 class="card-title fw-bold">Exam Results</h5>
            <p class="card-text opacity-75 small">Check WASSCE & BECE results instantly.</p>
            <a href="result_checker.php" class="btn btn-service mt-auto fw-bold">Check Results</a>
        </div>
    </div>
    
    <!-- NEW: AFA Mashup Card -->
    <div class="col-12 col-md-6 col-lg-3">
        <div class="service-card shadow-sm h-100 bg-gradient-green service-card-3">

            <h5 class="card-title fw-bold">AFA Mashup</h5>
            <p class="card-text opacity-75 small">Top-up MTN AFA Mashup bundles.</p>
            <a href="afa_mashup.php" class="btn btn-service mt-auto fw-bold">Buy Mashup</a>
        </div>
    </div>
    
    <!-- Transaction History Card (Refreshed) -->
    <div class="col-12 col-md-6 col-lg-3">
        <div class="service-card shadow-sm h-100 bg-gradient-teal service-card-4">

            <h5 class="card-title fw-bold">Order History</h5>
            <p class="card-text opacity-75 small">View all your past orders and transactions.</p>
            <a href="my_orders.php" class="btn btn-service mt-auto fw-bold">View History</a>
        </div>
    </div>
</div>


<?php 
// Your existing popup code for profile updates remains unchanged.
if (isset($_GET['status']) && $_GET['status'] === 'success' && $_GET['msg'] === 'ProfileUpdated'): 
?>
<style> /* ... your existing popup styles ... */ </style>
<div class="custom-popup">✅ Your profile has been updated successfully!</div>
<?php endif; ?>

<?php 
// Include the agent footer
include_once '_partials/footer.php'; 
?>