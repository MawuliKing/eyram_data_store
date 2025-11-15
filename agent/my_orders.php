<?php
// ✅ --- NEW, MORE ROBUST CACHE CONTROL ---
// This code MUST be at the very top of the file.

// Starting the session is often a way to signal that a page is dynamic.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// These headers instruct browsers AND proxy caches (like LiteSpeed) not to cache.
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
// This is a specific instruction for LiteSpeed Cache to NOT cache the page.
header("X-LiteSpeed-Cache-Control: no-cache"); 
header("Pragma: no-cache");
header("Expires: 0");

// The rest of your file remains exactly the same.
include_once '_partials/header.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page or show an error
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// PHP logic to fetch initial orders remains the same
$sql = "SELECT id, order_details, status, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id); 
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
function getCategory($name) {
    $name = strtolower($name);
    if (strpos($name, 'wassce') !== false) return 'WASSCE Result';
    if (strpos($name, 'bece') !== false) return 'BECE Result';
    if (strpos($name, 'placement voucher') !== false) return 'School Placement';
    if (strpos($name, 'tin') !== false) return 'TIN Registration';
    if (strpos($name, 'business certificate') !== false) return 'Business Certificate';
    if (strpos($name, 'afa registration') !== false) return 'AFA Registration';
    if (strpos($name, 'agent sim') !== false) return 'Agent SIM';
    if (strpos($name, 'mtn') !== false) return 'MTN Service';
    if (strpos($name, 'telecel') !== false) return 'Telecel Service';
    if (strpos($name, 'airteltigo') !== false) return 'AirtelTigo Service';
    return ucfirst(str_replace('_', ' ', $name));
}

while ($order = $result->fetch_assoc()) {
    $details = json_decode($order['order_details'], true);
    $category = getCategory($details['name'] ?? '');
    $orders[] = array_merge($order, [
        'details' => $details,
        'provider' => $details['provider_name'] ?? 'N/A',
        'category' => $category,
        'phone' => $details['recipient_phone'] ?? ''
    ]);
}
?>

<!-- --- STYLES (Unchanged) --- -->
<style>
/* Your existing styles are perfect and remain here */
.order-card-white { background-color: #fff; color: #343a40; border: 1px solid #dee2e6; border-radius: 12px; padding: 20px; margin-bottom: 1rem; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
.order-card-white p { margin-bottom: 5px; }
.order-card-white .badge { color: #fff; }
.order-card-white .view-details-btn { background-color: #f8f9fa; border-color: #dee2e6; color: #343a40; }
.order-card-white .view-details-btn:hover { background-color: #e9ecef; }
#searchResultModal .order-card-white { margin-top: 15px; }
.modal-header .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }
</style>

<h1 class="h2 mb-4">📋 My Order History</h1>
<p class="text-muted">All your orders are listed below. Use the search bar to instantly find an order by the recipient's phone number.</p>

<!-- SEARCH FORM (Unchanged) -->
<form id="searchForm" class="mb-3">
    <div class="input-group">
        <input type="text" id="searchInput" name="search" class="form-control form-control-lg" placeholder="🔍 Search by Phone Number...">
        <button class="btn btn-dark" type="submit"><i class="fas fa-search"></i> Search</button>
        <button id="clearSearchBtn" class="btn btn-outline-secondary" type="button" style="display: none;">Clear</button>
    </div>
</form>


<div id="ordersContainer">
    <?php if (!empty($orders)): ?>
        <?php foreach ($orders as $order): 
            $details = $order['details'];
            
            $status_color = 'secondary'; // Default
            if ($order['status'] == 'Processing') $status_color = 'primary';
            elseif ($order['status'] == 'Complete') $status_color = 'success';
            elseif ($order['status'] == 'Failed') $status_color = 'danger';
        ?>
        <!-- ✅ MODIFIED: Added data-status and data-created-at for the new timer logic -->
        <div class="order-card-white mb-3" 
             id="order-card-<?= $order['id']; ?>"
             data-phone="<?= htmlspecialchars($order['phone']); ?>"
             data-order-id="<?= $order['id']; ?>"
             data-status="<?= $order['status'] ?>"
             data-created-at="<?= strtotime($order['created_at']) ?>"
             data-order-details='<?= htmlspecialchars(json_encode($order), ENT_QUOTES, 'UTF-8') ?>'>
            
            <div class="d-flex justify-content-between align-items-center">
                <strong>Order ID: #<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></strong>
                <span class="badge bg-<?= $status_color; ?> order-status-badge"><?= $order['status']; ?></span>
            </div>
            <hr class="my-2">
            <p><strong>Package:</strong> <?= htmlspecialchars($details['name']); ?></p>
            <p><strong>Phone:</strong> <?= htmlspecialchars($order['phone'] ?: 'N/A'); ?></p>
            <p><strong>Network:</strong> <?= htmlspecialchars($order['provider']); ?></p>
            <p><strong>Category:</strong> <?= htmlspecialchars($order['category']); ?></p>
            <p class="mb-3"><small class="text-muted"><strong>Date:</strong> <?= date("d M Y, h:i A", strtotime($order['created_at'])); ?></small></p>
            
            <button class="btn btn-sm view-details-btn">
                <i class="fas fa-eye me-1"></i> View Details
            </button>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-info text-center p-5">You have not placed any orders yet.</div>
    <?php endif; ?>
</div>

<!-- MODALS (Unchanged) -->
<div class="modal fade" id="searchResultModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white"><h5 class="modal-title">Search Results</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="searchResultBody"></div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>
<div class="modal fade" id="orderDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="orderDetailsModalLabel">Order Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body" id="orderDetailsModalBody"></div>
        </div>
    </div>
</div>


<!-- ========================================================= -->
<!-- JAVASCRIPT: COMBINED OLD AND NEW LOGIC FOR FULL ROBUSTNESS -->
<!-- ========================================================= -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // --- Existing variables and functions for modals and search (Unchanged) ---
    const ordersContainer = document.getElementById('ordersContainer');
    const allOrderCards = document.querySelectorAll('#ordersContainer .order-card-white');
    const searchForm = document.getElementById('searchForm');
    const searchInput = document.getElementById('searchInput');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    const searchResultModal = new bootstrap.Modal(document.getElementById('searchResultModal'));
    const searchResultBody = document.getElementById('searchResultBody');
    const detailsModal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
    const detailsModalTitle = document.getElementById('orderDetailsModalLabel');
    const detailsModalBody = document.getElementById('orderDetailsModalBody');
    function showOrderDetails(orderCardElement) { /* This function is unchanged */
        const orderData = JSON.parse(orderCardElement.dataset.orderDetails);
        detailsModalTitle.textContent = `Details for Order #${String(orderData.id).padStart(5, '0')}`;
        let bodyHtml = '<div class="table-responsive"><table class="table table-sm table-bordered table-striped"><tbody>';
        const formatKey = (key) => key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        for (const key in orderData.details) {
            if (key !== 'form_data' && typeof orderData.details[key] !== 'object') {
                bodyHtml += `<tr><th style="width: 40%;">${formatKey(key)}</th><td>${orderData.details[key]}</td></tr>`;
            }
        }
        if (orderData.details.form_data && typeof orderData.details.form_data === 'object') {
            bodyHtml += '<tr><td colspan="2" class="bg-light fw-bold text-center">Submitted Form Information</td></tr>';
            for (const key in orderData.details.form_data) {
                if (key !== 'uploaded_files') {
                   bodyHtml += `<tr><th>${formatKey(key)}</th><td>${orderData.details.form_data[key]}</td></tr>`;
                }
            }
        }
        bodyHtml += '</tbody></table></div>';
        detailsModalBody.innerHTML = bodyHtml;
        detailsModal.show();
    }
    ordersContainer.addEventListener('click', function(event) { if (event.target.closest('.view-details-btn')) { showOrderDetails(event.target.closest('.order-card-white')); } });
    searchResultBody.addEventListener('click', function(event) { if (event.target.closest('.view-details-btn')) { searchResultModal.hide(); showOrderDetails(event.target.closest('.order-card-white')); } });
    searchForm.addEventListener('submit', function(event) { /* Search logic is unchanged */
        event.preventDefault(); const searchTerm = searchInput.value.trim(); if (searchTerm === '') return; let foundCardsHTML = '';
        allOrderCards.forEach(card => { if (card.dataset.phone && card.dataset.phone.includes(searchTerm)) { foundCardsHTML += card.outerHTML; } });
        if (foundCardsHTML) { searchResultBody.innerHTML = foundCardsHTML; } else { searchResultBody.innerHTML = `<div class="alert alert-warning text-center">No orders found for "<strong>${searchTerm}</strong>".</div>`; }
        searchResultModal.show(); clearSearchBtn.style.display = 'inline-block';
    });
    clearSearchBtn.addEventListener('click', function() { searchInput.value = ''; this.style.display = 'none'; searchInput.focus(); });
    // --- End of existing logic ---


    // --- LOGIC 1: LIVE STATUS POLLING (For fast, real-time updates) ---
    let statusCheckerInterval;
    let processingOrderIds = [];

    function startStatusPolling() {
        const processingBadges = document.querySelectorAll('.order-status-badge.bg-primary');
        processingOrderIds = Array.from(processingBadges).map(badge => {
            return badge.closest('.order-card-white').dataset.orderId;
        });

        if (processingOrderIds.length > 0) {
            statusCheckerInterval = setInterval(fetchStatusUpdates, 15000); 
            fetchStatusUpdates();
        }
    }

    async function fetchStatusUpdates() {
        if (processingOrderIds.length === 0) {
            clearInterval(statusCheckerInterval);
            return;
        }

        try {
            const response = await fetch('check_order_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_ids: processingOrderIds })
            });
            const updatedStatuses = await response.json();

            if (Object.keys(updatedStatuses).length > 0) {
                for (const orderId in updatedStatuses) {
                    const newStatus = updatedStatuses[orderId];
                    updateOrderStatusBadge(orderId, newStatus);
                }
            }
        } catch (error) {
            console.error('Error fetching status updates:', error);
            clearInterval(statusCheckerInterval);
        }
    }

    function updateOrderStatusBadge(orderId, newStatus) {
        const orderCard = document.getElementById(`order-card-${orderId}`);
        if (!orderCard) return;

        const badge = orderCard.querySelector('.order-status-badge');
        if (!badge) return;

        let newColorClass = 'bg-secondary';
        if (newStatus === 'Complete') newColorClass = 'bg-success';
        if (newStatus === 'Failed') newColorClass = 'bg-danger';

        badge.textContent = newStatus;
        badge.classList.remove('bg-primary', 'bg-success', 'bg-danger', 'bg-secondary');
        badge.classList.add(newColorClass);
        
        processingOrderIds = processingOrderIds.filter(id => id !== orderId);
    }
    
    // --- LOGIC 2: AUTO-COMPLETION FAILSAFE (The one-hour backup timer) ---

    function initializeOrderCardScripts() {
        const nowInSeconds = Math.floor(Date.now() / 1000);
        
        document.querySelectorAll('.order-card-white[data-status="Processing"]').forEach(card => {
            const createdAt = parseInt(card.dataset.createdAt, 10);
            const orderId = card.dataset.orderId;
            const timePassed = nowInSeconds - createdAt;
            const timeLeft = 3600 - timePassed; // 3600 seconds = 1 hour

            if (timeLeft <= 0) {
                autoCompleteOrder(orderId, card);
            } else {
                setTimeout(() => autoCompleteOrder(orderId, card), timeLeft * 1000);
            }
        });
    }

    function autoCompleteOrder(orderId, cardElement) {
        const currentStatus = cardElement.querySelector('.order-status-badge').textContent.trim();
        if (currentStatus !== 'Processing') {
            return; // Do nothing, polling or another timer already handled it.
        }

        fetch('auto_complete_single.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'order_id=' + encodeURIComponent(orderId)
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                updateOrderStatusBadge(orderId, 'Complete');
            }
        });
    }

    // --- INITIALIZE BOTH SYSTEMS ON PAGE LOAD ---
    startStatusPolling();       // Starts the 15-second checker for fast updates.
    initializeOrderCardScripts(); // Starts the 1-hour failsafe timer for reliability.
});
</script>

<?php include_once '_partials/footer.php'; ?>