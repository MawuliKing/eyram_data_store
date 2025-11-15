<?php
include_once '_partials/header.php';

// --- Get the current user's role from the session. Default to 'Customer' if not set. ---
$user_role = $_SESSION['user_role'] ?? 'Customer';

// --- DATABASE LOGIC ---
$stmt_cat = $conn->prepare("SELECT id FROM services WHERE UPPER(name) = 'MTN AFA MASHUP' AND parent_id = 0 LIMIT 1");
$stmt_cat->execute();
$cat_result = $stmt_cat->get_result();
$category = $cat_result->fetch_assoc();
$category_parent_id = $category['id'] ?? null;

// The array will be populated by the new, role-aware query below.
$mashup_bundles = [];
if ($category_parent_id) {
    // --- THE FIX: SIMPLIFIED SQL QUERY ---
    // We fetch all price columns and let PHP handle the logic.
    $sql = "SELECT id, name, price_super_admin, price_agent, price_customer 
            FROM services 
            WHERE parent_id = ? AND status = 'enabled' 
            ORDER BY price_customer"; // Order by a default price column

    $stmt_bundles = $conn->prepare($sql);
    $stmt_bundles->bind_param("i", $category_parent_id);
    $stmt_bundles->execute();
    $bundles_result = $stmt_bundles->get_result();
    
    while ($row = $bundles_result->fetch_assoc()) {
        $mashup_bundles[] = $row;
    }
}
?>

<!-- STYLES FOR ANIMATIONS & MODERN LOOK (Unchanged) -->
<style>
@keyframes slideInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
.mashup-card {
    background-color: #ffcb05;
    border-radius: 12px;
    padding: 1rem 1.25rem;
    color: #333;
    margin-bottom: 1rem;
    border: none;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    animation: slideInUp 0.5s ease-out forwards;
    opacity: 0;
}
.mashup-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 15px rgba(0,0,0,0.15);
}
.mashup-card .network-label { font-size: 0.8rem; font-weight: bold; color: white; background-color: rgba(0,0,0,0.2); padding: 0.2rem 0.6rem; border-radius: 50px; }
.mashup-card .price-label { font-size: 1.25rem; font-weight: 700; }
.mashup-card .buy-now-btn { background-color: #000; color: #fff; transition: background-color 0.2s ease, color 0.2s ease; }
.mashup-card .buy-now-btn:hover { background-color: #333; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">MTN AFA Mashup</h1>
</div>

<div class="row g-3">
    <?php if (!empty($mashup_bundles)): ?>
        <?php 
            $animation_delay = 0;
            foreach($mashup_bundles as $bundle): 
                // --- THE FIX: PHP LOGIC TO SELECT THE CORRECT PRICE ---
                $price = 0.00; // Default price
                if ($user_role === 'Super Admin') {
                    $price = $bundle['price_super_admin'];
                } elseif ($user_role === 'Agent') {
                    $price = $bundle['price_agent'];
                } else { // This covers 'Customer' and any other roles
                    $price = $bundle['price_customer'];
                }
        ?>
            <div class="col-md-6 col-lg-4">
                <div class="mashup-card shadow-sm h-100" style="animation-delay: <?php echo $animation_delay; ?>s;">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h4 class="fw-bold mb-0"><?php echo htmlspecialchars($bundle['name']); ?></h4>
                            <p class="mb-2" style="color: #555;">Mashup Bundle</p>
                        </div>
                        <div class="network-label">MTN</div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <!-- Use the correct $price variable here -->
                        <div class="price-label">GHS <?php echo number_format($price, 2); ?></div>
                        <a href="#" class="btn btn-dark fw-bold buy-now-btn"
                           data-bs-toggle="modal" 
                           data-bs-target="#buyModal"
                           data-service-id="<?php echo $bundle['id']; ?>"
                           data-service-name="<?php echo htmlspecialchars($bundle['name']); ?>"
                           data-service-price="<?php echo $price; ?>"
                           data-provider-name="MTN"
                           data-category="Mashup">Buy Now</a>
                    </div>
                </div>
            </div>
        <?php 
            $animation_delay += 0.05;
            endforeach; 
        ?>
    <?php else: ?>
        <div class="col-12">
            <div class="alert alert-warning text-center p-4">
                <p class="mb-0">AFA Mashup services are not available at this time.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- BUY NOW MODAL (HTML is Unchanged) -->
<div class="modal fade" id="buyModal" tabindex="-1" aria-labelledby="buyModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="buyModalLabel">Buy Mashup Bundle</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <h6 class="text-muted">Purchase Details</h6>
        <ul class="list-group list-group-flush mb-3">
          <li class="list-group-item d-flex justify-content-between"><span>Minutes/Bundle:</span><strong id="modal-data-amount"></strong></li>
          <li class="list-group-item d-flex justify-content-between"><span>Price:</span><strong id="modal-price" class="text-success"></strong></li>
        </ul>
        <hr>
        <form id="addToCartForm" method="POST" action="cart_handler.php"> <!-- Changed action for clarity -->
            <div class="mb-3">
                <label for="recipient_phone" class="form-label fw-bold">Recipient Phone Number</label>
                <input type="hidden" id="modal-service-id" name="service_id">
                <input type="hidden" id="modal-provider-name" name="provider_name">
                <input type="hidden" id="modal-category" name="category">
                <input type="tel" class="form-control form-control-lg" id="recipient_phone" name="recipient_phone" placeholder="Enter 10-digit number" required pattern="[0-9]{10}" maxlength="10">
            </div>
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Add To Cart</button>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- JAVASCRIPT IS UNCHANGED -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // This script is perfect and doesn't need to be changed.
    // It will continue to populate the modal correctly.
    document.body.addEventListener('click', function(e) {
        const buyBtn = e.target.closest('.buy-now-btn');
        if (buyBtn) {
            const serviceId = buyBtn.dataset.serviceId;
            const serviceName = buyBtn.dataset.serviceName;
            const servicePrice = buyBtn.dataset.servicePrice;
            const providerName = buyBtn.dataset.providerName;
            const category = buyBtn.dataset.category;

            document.getElementById('modal-data-amount').innerText = serviceName;
            document.getElementById('modal-price').innerText = `GHS ${parseFloat(servicePrice).toFixed(2)}`;
            document.getElementById('modal-service-id').value = serviceId;
            document.getElementById('modal-provider-name').value = providerName;
            document.getElementById('modal-category').value = category;
        }
    });

    // Handle form submission to add to cart via AJAX (optional but recommended)
   
});
</script>

<?php include_once '_partials/footer.php'; ?>