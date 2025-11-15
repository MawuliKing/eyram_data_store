// This is the full, clean code for your admin/orders.php file.
// The PHP and HTML parts are unchanged.

<?php
include_once '_partials/header.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$display_groups = [
    'MTN Data Packages'        => ['Data Bundle - MTN'],
    'AirtelTigo Data Packages' => ['Data Bundle'],
    'Telecel Data Packages'    => ['Data Bundle-Telecel'],
    'MTN AFA Mashup'           => ['Mashup'],
    'Exam Results'             => ['Exam Results'],
    'Exam Vouchers'            => ['Exam Vouchers']
];

$sql_counts = "SELECT JSON_UNQUOTE(JSON_EXTRACT(order_details, '$.category')) as category, COUNT(id) as pending_count 
               FROM orders 
               WHERE status = 'Pending' 
               GROUP BY category";
$result_counts = $conn->query($sql_counts);
$pending_counts_by_cat = [];
while ($row = $result_counts->fetch_assoc()) {
    $pending_counts_by_cat[$row['category']] = $row['pending_count'];
}

$grouped_data = [];
foreach ($display_groups as $group_name => $db_categories) {
    $key = str_replace(' ', '-', strtolower($group_name));
    $total_pending = 0;
    foreach ($db_categories as $db_cat) {
        $total_pending += ($pending_counts_by_cat[$db_cat] ?? 0);
    }
    $grouped_data[$group_name] = [
        'pending_count' => $total_pending,
        'key' => $key
    ];
}
?>

<style>
/* All your existing CSS styles remain unchanged. */
.container-box { background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
.search-box { margin-bottom: 25px; max-width: 500px; margin-left: auto; margin-right: auto; }
.order-card { padding: 15px 20px; margin-bottom: 20px; border-radius: 10px; color: #fff; transition: display 0.3s; }
.bg-mtn { background-color: #ffcb05; color: #000 !important; }
.bg-telecel { background-color: #d90429; }
.bg-airteltigo { background-color: #007bff; }
.bg-default { background-color: #6c757d; }
.order-card .btn { margin-right: 5px; }
.order-card p { margin-bottom: 5px; }
.category-card { border-radius: 12px; color: white; padding: 20px; position: relative; cursor: pointer; transition: transform 0.2s ease-in-out, box-shadow 0.2s; min-height: 120px; display: flex; align-items: center; justify-content: center; text-align: center; font-weight: 600; }
.category-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.2); }
.category-card .card-title { font-size: 1.2rem; }
.pending-count-badge { position: absolute; top: 10px; right: 15px; background-color: rgba(255, 255, 255, 0.95); color: #333; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1rem; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
.category-card[data-group-key="mtn-afa-mashup"] { background: linear-gradient(135deg, #8E2DE2, #4A00E0); }
.category-card[data-group-key="mtn-data-packages"] { background: linear-gradient(135deg, #f7971e, #ffd200); }
.category-card[data-group-key="airteltigo-data-packages"] { background: linear-gradient(135deg, #d32f2f, #c2185b); }
.category-card[data-group-key="telecel-data-packages"] { background: linear-gradient(135deg, #e53935, #c62828); }
.category-card[data-group-key="exam-results"] { background: linear-gradient(135deg, #02AAB0, #00CDAC); }
.category-card[data-group-key="exam-vouchers"] { background: linear-gradient(135deg, #f953c6, #b91d73); }
#ordersList.loading::after { content: 'Loading orders...'; display: block; text-align: center; font-size: 1.5rem; padding: 40px; color: #666; }
</style>

<!-- Main Body - The HTML structure is the same as before -->
<form id="searchForm" class="mb-3">
    <div class="input-group">
        <input type="text" id="searchInput" name="search" class="form-control form-control-lg" placeholder="🔍 Search by Phone Number...">
        <button class="btn btn-dark" type="submit"><i class="fas fa-search"></i> Search</button>
        <button id="clearSearchBtn" class="btn btn-outline-secondary" type="button" style="display: none;">Clear</button>
    </div>
</form>

<div class="container-fluid">
    <!-- Excel Download Form -->
    <h1 class="h2 mb-3 text-center">Export Orders</h1>
    <form method="GET" action="download_csv.php" class="row g-3 mb-5 align-items-end p-3 bg-light rounded border">
      <div class="col-md-3"><label for="start_date" class="form-label">Start Date</label><input type="date" class="form-control" id="start_date" name="start_date"></div>
      <div class="col-md-3"><label for="end_date" class="form-label">End Date</label><input type="date" class="form-control" id="end_date" name="end_date"></div>
      <div class="col-md-3"><label for="network" class="form-label">Select Service to Export</label><select class="form-select" id="network" name="category_to_export" required><option value="" disabled selected>-- Choose a Service --</option><option value="Data Bundle - MTN">MTN Data</option><option value="Data Bundle">AirtelTigo Data</option><option value="Data Bundle-Telecel">Telecel Data</option><option value="Mashup">MTN Mashup</option><option value="Exam Results">BECE/WASSCE Results</option><option value="Exam Vouchers">School Placement</option></select></div>
      <div class="col-md-3"><button type="submit" class="btn btn-success w-100" id="downloadBtn">📥 Download Excel</button></div>
    </form>

    <!-- Category Containers Section -->
    <h1 class="h2 mb-4 text-center">📦 Order Categories</h1>
    <div class="row g-4 justify-content-center">
        <?php foreach ($grouped_data as $group_name => $data): ?>
            <div class="col-xl-3 col-lg-4 col-md-6">
                <div class="category-card" data-group-key="<?= $data['key'] ?>" data-group-name="<?= htmlspecialchars($group_name) ?>">
                    <div class="card-body">
                        <div class="card-title"><?= htmlspecialchars($group_name) ?></div>
                        <div class="pending-count-badge"><?= $data['pending_count'] ?></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <div class="col-12 text-center mt-4">
            <button id="showAllBtn" class="btn btn-primary px-5 py-2">View All Orders</button>
        </div>
    </div>
</div>

<!-- Orders Display Section -->
<div class="container-fluid mt-5" id="ordersSection" style="display: none;">
    <h1 id="ordersListTitle" class="h2 mb-4 text-center"></h1>
    <div class="container-box">
        <div id="ordersList"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');

    if (startDateInput && endDateInput) {
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        const todayString = `${yyyy}-${mm}-${dd}`;
        startDateInput.value = todayString;
        endDateInput.value = todayString;
    }

    const ordersSection = document.getElementById('ordersSection');
    const ordersListTitle = document.getElementById('ordersListTitle');
    const ordersList = document.getElementById('ordersList');
    
    function loadOrders(params, title) {
        ordersSection.style.display = 'block';
        ordersListTitle.textContent = title;
        ordersList.innerHTML = '';
        ordersList.classList.add('loading');

        fetch(`get_orders.php?${new URLSearchParams(params)}`)
            .then(response => response.text())
            .then(html => {
                ordersList.classList.remove('loading');
                ordersList.innerHTML = html;
                // We no longer need to call initializeOrderCardScripts() here.
                window.scrollTo({ top: ordersSection.offsetTop - 70, behavior: 'smooth' });
            })
            .catch(error => {
                ordersList.classList.remove('loading');
                ordersList.innerHTML = '<div class="alert alert-danger">Failed to load orders. Please try again.</div>';
                console.error('Error fetching orders:', error);
            });
    }
    
    document.querySelectorAll('.category-card').forEach(card => {
        card.addEventListener('click', function() {
            const groupKey = this.dataset.groupKey;
            const groupName = this.dataset.groupName;
            loadOrders({ group_key: groupKey }, `Orders for ${groupName}`);
        });
    });

    document.getElementById('showAllBtn').addEventListener('click', function() {
        loadOrders({}, 'All Recent Orders');
    });

    const searchForm = document.getElementById('searchForm');
    const searchInput = document.getElementById('searchInput');
    const clearSearchBtn = document.getElementById('clearSearchBtn');

    searchForm.addEventListener('submit', function(event) {
        event.preventDefault();
        const searchTerm = searchInput.value.trim();
        if (searchTerm === '') return;
        
        loadOrders({ search: searchTerm }, `Search Results for "${searchTerm}"`);
        clearSearchBtn.style.display = 'inline-block';
    });
    
    clearSearchBtn.addEventListener('click', function() {
        searchInput.value = '';
        this.style.display = 'none';
        ordersSection.style.display = 'none';
        searchInput.focus();
    });

    function decrementPendingCount(groupKey) {
        if (groupKey && groupKey !== 'other') {
            const countBadge = document.querySelector(`.category-card[data-group-key="${groupKey}"] .pending-count-badge`);
            if (countBadge) {
                let currentCount = parseInt(countBadge.textContent, 10);
                if (currentCount > 0) {
                    countBadge.textContent = currentCount - 1;
                }
            }
        }
    }

    const downloadForm = document.querySelector('form[action="download_csv.php"]');
    if (downloadForm) {
        downloadForm.addEventListener('submit', function (event) {
            const categorySelect = document.getElementById('network');
            if (!categorySelect.value) {
                alert("Please select a service to export before downloading.");
                event.preventDefault();
                return;
            }
            setTimeout(() => {
                const categoryToProcess = categorySelect.value;
                const selector = `.order-card[data-status="Pending"][data-db-category="${categoryToProcess}"]`;
                document.querySelectorAll(selector).forEach(card => {
                    decrementPendingCount(card.dataset.groupKey);
                    const badge = card.querySelector('.badge');
                    badge.classList.remove('bg-secondary');
                    badge.classList.add('bg-primary');
                    badge.textContent = 'Processing';
                    card.dataset.status = 'Processing';
                });
            }, 1000);
        });
    }
});
</script>

<?php include_once '_partials/footer.php'; ?>