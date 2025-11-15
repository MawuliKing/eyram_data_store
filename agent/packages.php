<?php
include_once '_partials/header.php';

// --- DATABASE LOGIC ---
$stmt_pkg_cat = $conn->prepare("SELECT id FROM services WHERE UPPER(name) = 'PACKAGES' AND parent_id = 0 LIMIT 1");
$stmt_pkg_cat->execute();
$packages_cat_result = $stmt_pkg_cat->get_result();
$packages_cat = $packages_cat_result->fetch_assoc();
$packages_parent_id = $packages_cat['id'] ?? null;
$providers = [];
if ($packages_parent_id) {
    // --- THE FIX: Custom ORDER BY to prioritize MTN ---
    $sql = "SELECT id, name 
            FROM services 
            WHERE parent_id = ? AND status = 'enabled' 
            ORDER BY 
                CASE 
                    WHEN UPPER(name) = 'MTN' THEN 1 
                    WHEN UPPER(name) = 'AIRTELTIGO' THEN 2 
                    WHEN UPPER(name) = 'TELECEL' THEN 3 
                    ELSE 4 
                END";
    $stmt_providers = $conn->prepare($sql);
    $stmt_providers->bind_param("i", $packages_parent_id);
    $stmt_providers->execute();
    $providers_result = $stmt_providers->get_result();
    while ($row = $providers_result->fetch_assoc()) {
        $providers[] = $row;
    }
}

// Get the current user's role from the session.
$user_role = $_SESSION['user_role'] ?? 'Customer';
?>

<!-- STYLES FOR ANIMATIONS & MODERN LOOK (Unchanged) -->
<style>
@keyframes slideInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
.package-card {
    border-radius: 12px; padding: 1rem 1.25rem; color: #fff; margin-bottom: 1rem; border: none;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    animation: slideInUp 0.5s ease-out forwards; opacity: 0;
}
.package-card:hover { transform: translateY(-5px); box-shadow: 0 8px 15px rgba(0,0,0,0.15); }
.package-card .network-label { font-size: 0.8rem; font-weight: bold; padding: 0.2rem 0.6rem; border-radius: 50px; background-color: rgba(0,0,0,0.2); }
.price-label { font-size: 1.25rem; font-weight: 700; }
.buy-now-btn { transition: background-color 0.2s ease, color 0.2s ease; }
.buy-now-btn:hover { background-color: #fff !important; color: #333 !important; }
.bg-mtn { background-color: #ffcb05; color: #000; }
.bg-mtn .buy-now-btn { background-color: #000; color: #fff; }
.bg-telecel { background-color: #d90429; }
.bg-airteltigo { background-color: #007bff; }
.action-card { padding: 1rem 1.5rem; border-radius: 12px; color: white; text-align: center; cursor: pointer; transition: all 0.3s ease; display: flex; flex-direction: column; align-items: center; justify-content: center; }
.action-card:hover { transform: translateY(-5px); box-shadow: 0 8px 15px rgba(0,0,0,0.2); }
.action-card i { font-size: 1.5rem; margin-bottom: 0.5rem; }
.bg-gradient-download { background: linear-gradient(135deg, #007bff, #00c6ff); }
.bg-gradient-upload { background: linear-gradient(135deg, #6f42c1, #A052E1); }
</style>

<!-- Page Header and Bulk Action cards remain unchanged -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
    <h1 class="h2 mb-2 me-3">Buy Data Packages</h1>
    <div class="d-flex gap-3">
        <div class="action-card bg-gradient-download shadow-sm" data-bs-toggle="modal" data-bs-target="#downloadTemplateModal">
            <i class="fas fa-download"></i>
            <span class="fw-bold small">Download Template</span>
        </div>
        <div class="action-card bg-gradient-upload shadow-sm" data-bs-toggle="modal" data-bs-target="#uploadTemplateModal">
            <i class="fas fa-upload"></i>
            <span class="fw-bold small">Bulk Upload</span>
        </div>
    </div>
</div>

<!-- Provider Pills & Package Display -->
<?php if (!empty($providers)): ?>
    <ul class="nav nav-pills nav-fill mb-3" id="pills-tab" role="tablist">
        <?php foreach ($providers as $index => $provider): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo ($index == 0) ? 'active' : ''; ?>" id="pills-<?php echo $provider['id']; ?>-tab" data-bs-toggle="pill" data-bs-target="#pills-<?php echo $provider['id']; ?>" type="button" role="tab"><?php echo htmlspecialchars($provider['name']); ?></button>
            </li>
        <?php endforeach; ?>
    </ul>
    <div class="tab-content" id="pills-tabContent">
        <?php foreach ($providers as $index => $provider): ?>
            <div class="tab-pane fade <?php echo ($index == 0) ? 'show active' : ''; ?>" id="pills-<?php echo $provider['id']; ?>" role="tabpanel">
                <div class="row">
                <?php
                // --- SIMPLIFIED SQL QUERY (Unchanged) ---
                $sql_bundles = "SELECT id, name, category, price_super_admin, price_agent, price_customer 
                                FROM services 
                                WHERE parent_id = ? AND status = 'enabled' 
                                ORDER BY price_customer";
                
                $stmt_bundles = $conn->prepare($sql_bundles);
                $stmt_bundles->bind_param("i", $provider['id']);
                $stmt_bundles->execute();
                $bundles = $stmt_bundles->get_result();

                if ($bundles->num_rows > 0):
                    $animation_delay = 0;
                    while($bundle = $bundles->fetch_assoc()):
                        
                        // --- PHP LOGIC TO SELECT THE CORRECT PRICE (Unchanged) ---
                        $price = 0.00;
                        if ($user_role === 'Super Admin') {
                            $price = $bundle['price_super_admin'];
                        } elseif ($user_role === 'Agent') {
                            $price = $bundle['price_agent'];
                        } else {
                            $price = $bundle['price_customer'];
                        }
                        
                        $provider_class = 'bg-mtn';
                        $network = strtolower($provider['name']);
                        if ($network === 'telecel') { $provider_class = 'bg-telecel'; } 
                        elseif ($network === 'airteltigo' || $network === 'airtel tigo') { $provider_class = 'bg-airteltigo'; }
                ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="package-card shadow-sm <?php echo $provider_class; ?>" style="animation-delay: <?php echo $animation_delay; ?>s;">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h4 class="fw-bold mb-0"><?php echo htmlspecialchars($bundle['name']); ?></h4>
                                        <p class="mb-2 opacity-75 small">Data Bundle</p>
                                        <span class="badge bg-light text-dark">Non Expiry</span>
                                    </div>
                                    <div class="network-label"><?php echo htmlspecialchars($provider['name']); ?></div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div class="price-label">GHS <?php echo number_format($price, 2); ?></div>
                                    <a href="#" class="btn btn-dark fw-bold buy-now-btn"
                                       data-bs-toggle="modal" data-bs-target="#buyModal"
                                       data-service-id="<?php echo $bundle['id']; ?>"
                                       data-service-name="<?php echo htmlspecialchars($bundle['name']); ?>"
                                       data-service-price="<?php echo $price; ?>"
                                       data-provider-name="<?php echo htmlspecialchars($provider['name']); ?>"
                                       data-category="<?php echo htmlspecialchars($bundle['category']); ?>">
                                       Buy Now
                                    </a>
                                </div>
                            </div>
                        </div>
                <?php $animation_delay += 0.05; endwhile; else: echo '<div class="alert alert-warning">No data packages are available for ' . htmlspecialchars($provider['name']) . ' at the moment.</div>'; endif; $stmt_bundles->close(); ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="alert alert-info text-center p-4"><p class="mb-0">Data package services are not available at this time. Please check back later.</p></div>
<?php endif; ?>

<!-- MODALS and SCRIPTS (Unchanged) -->

<!-- BUY MODAL -->
<div class="modal fade" id="buyModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Buy Bundle</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <ul class="list-group list-group-flush mb-3">
          <li class="list-group-item d-flex justify-content-between"><span>Network:</span><strong id="modal-network-name"></strong></li>
          <li class="list-group-item d-flex justify-content-between"><span>Data Amount:</span><strong id="modal-data-amount"></strong></li>
          <li class="list-group-item d-flex justify-content-between"><span>Price:</span><strong id="modal-price" class="text-success"></strong></li>
        </ul><hr>
        <form id="addToCartForm">
            <input type="hidden" id="modal-service-id" name="service_id">
            <input type="hidden" id="modal-provider-name" name="provider_name">
            <input type="hidden" id="modal-category" name="category">
            <div class="mb-3">
                <label for="recipient_phone" class="form-label fw-bold">Recipient Phone Number</label>
                <input type="tel" class="form-control form-control-lg" id="recipient_phone" name="recipient_phone" placeholder="Enter 10-digit number" required pattern="[0-9]{10}" maxlength="10">
            </div>
  

<div class="d-grid gap-2 d-md-flex justify-content-md-end">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
    <button type="submit" class="btn btn-primary">Continue</button>
</div>

        </form>
      </div>
    </div>
  </div>
</div>

<!-- DOWNLOAD TEMPLATE MODAL -->
<div class="modal fade" id="downloadTemplateModal" tabindex="-1" aria-labelledby="downloadModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="downloadModalLabel">Generate Excel Template</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <h6>How to use the template:</h6>
        <ol>
            <li>Fill in the <strong>Phone Number</strong> column with recipient numbers.</li>
            <li>Fill in the <strong>Size</strong> column with the exact data bundle name.</li>
            <li><strong>Note:</strong> Network is set automatically based on your tab selection.</li>
        </ol>
        <div class="alert alert-warning text-center">
            Current Selection: <strong id="current-selection-label">MTN</strong>
        </div>
        <form id="downloadTemplateForm" action="download_template.php" method="POST">
            <input type="hidden" name="provider_id" id="download-provider-id">
            <input type="hidden" name="provider_name" id="download-provider-name">
            <input type="hidden" name="category" id="download-category">
            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg">Generate Template</button>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- UPLOAD TEMPLATE MODAL -->
<div class="modal fade" id="uploadTemplateModal" tabindex="-1" aria-labelledby="uploadModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="uploadModalLabel">Bulk Upload Orders</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
      <form id="bulkUploadForm" action="upload_bulk_orders.php" method="POST" enctype="multipart/form-data">

            <p class="text-muted">Upload completed CSV. Network will be: <strong id="upload-network-label">MTN</strong>.</p>
            <input type="hidden" id="upload-provider-id" name="provider_id">
            <input type="hidden" id="upload-provider-name" name="provider_name">
            <input type="hidden" id="upload-category" name="category">
            <div class="mb-3">
                <label for="csv_file" class="form-label">Select CSV File</label>
                <input class="form-control" type="file" id="csv_file" name="csv_file" accept=".csv" required>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Upload and Add to Cart</button>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>
<script>
    const pillsTab = document.getElementById('pills-tab');
    if (pillsTab) {
        const selectionLabel = document.getElementById('current-selection-label');
        const uploadLabel = document.getElementById('upload-network-label');

        const uploadProviderId = document.getElementById('upload-provider-id');
        const uploadProviderName = document.getElementById('upload-provider-name');
        const uploadCategory = document.getElementById('upload-category');

        const downloadProviderId = document.getElementById('download-provider-id');
        const downloadProviderName = document.getElementById('download-provider-name');
        const downloadCategory = document.getElementById('download-category');

        function updateModalLabels(activeTab) {
            if (!activeTab) return;
            const providerName = activeTab.textContent.trim();
            const providerId = activeTab.id.replace('-tab', '').replace('pills-', '');
            const category = 'Data Bundle - ' + providerName;

            selectionLabel.textContent = providerName;
            uploadLabel.textContent = providerName;

            uploadProviderId.value = providerId;
            uploadProviderName.value = providerName;
            uploadCategory.value = category;

            downloadProviderId.value = providerId;
            downloadProviderName.value = providerName;
            downloadCategory.value = category;
        }

        const initialActiveTab = pillsTab.querySelector('.nav-link.active');
        updateModalLabels(initialActiveTab);

        pillsTab.addEventListener('shown.bs.tab', function (event) {
            updateModalLabels(event.target);
        });
    }
</script>

<script>
document.getElementById('bulkUploadForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);
    const uploadBtn = form.querySelector('button[type=\"submit\"]');
    uploadBtn.disabled = true;
    uploadBtn.innerHTML = 'Uploading...';

    fetch('upload_bulk_orders.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        uploadBtn.disabled = false;
        uploadBtn.innerHTML = 'Upload and Add to Cart';

        const alertBox = document.createElement('div');
        alertBox.className = `alert alert-${data.status === 'success' ? 'success' : 'danger'} mt-3`;
        alertBox.textContent = data.message;

        const existingAlert = form.querySelector('.alert');
        if (existingAlert) existingAlert.remove();

        form.appendChild(alertBox);

        setTimeout(() => alertBox.remove(), 4000);

        if (data.status === 'success') {
            setTimeout(() => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('uploadTemplateModal'));
                modal.hide();
            }, 2000);
        }

        if (data.cart) {
            refreshCartUI(data.cart);
        }
    })
    .catch(error => {
        uploadBtn.disabled = false;
        uploadBtn.innerHTML = 'Upload and Add to Cart';
        alert('Upload failed. Please try again.');
    });
});

function refreshCartUI(cart) {
    const cartCount = document.querySelector('#cart-count');
    const cartTotal = document.querySelector('#cart-total');

    if (cartCount) cartCount.textContent = cart.item_count;
    if (cartTotal) cartTotal.textContent = 'GHS ' + parseFloat(cart.total_price).toFixed(2);

    const cartList = document.querySelector('#cart-items');
    if (cartList) {
        cartList.innerHTML = '';
        cart.items.forEach(item => {
            const li = document.createElement('li');
            li.innerHTML = `
                <strong>${item.name}</strong> to ${item.recipient_phone} - GHS ${parseFloat(item.price).toFixed(2)}
            `;
            cartList.appendChild(li);
        });
    }
}
</script>

<script src="https://js.paystack.co/v1/inline.js"></script>
<script>
document.getElementById('addToCartForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const serviceId = document.getElementById('modal-service-id').value;
    const provider = document.getElementById('modal-provider-name').value;
    const category = document.getElementById('modal-category').value;
    const phone = document.getElementById('recipient_phone').value;
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
    const amount = document.getElementById('modal-price').textContent.replace('GHS', '').trim();

    if (paymentMethod === 'manual') {
        fetch('add_to_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ service_id: serviceId, recipient_phone: phone, provider_name: provider, category: category })
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            const modal = bootstrap.Modal.getInstance(document.getElementById('buyModal'));
            modal.hide();
            refreshCartUI(data.cart);
        });

    } else {
        let handler = PaystackPop.setup({
            key: 'pk_live_fd15e01e5e221af2c23e751eedfaa3a56b5a553b',
            email: '<?php echo $_SESSION["user_email"] ?? "tengehvivian09@gmail.com"; ?>',
            amount: parseFloat(amount) * 100,
            currency: 'GHS',
            callback: function (response) {
                fetch('process_instant_order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        reference: response.reference,
                        service_id: serviceId,
                        recipient_phone: phone,
                        provider_name: provider,
                        category: category,
                        amount: amount
                    })
                })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    const modal = bootstrap.Modal.getInstance(document.getElementById('buyModal'));
                    modal.hide();
                });
            },
            onClose: function () {
                alert('Payment cancelled.');
            }
        });
        handler.openIframe();
    }
});
</script>

<?php 
include_once '_partials/footer.php'; 
?>