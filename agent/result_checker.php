<?php
// We must include the header first to get access to session, DB, and the page layout
include_once '_partials/header.php';

// --- Get the current user's role from the session. ---
$user_role = $_SESSION['user_role'] ?? 'Customer';

// --- Fetch all available result checker services from the database ---
$categories_to_fetch = ['Exam Results', 'Exam Vouchers'];
$placeholders = implode(',', array_fill(0, count($categories_to_fetch), '?'));

// --- THE FIX: SIMPLIFIED SQL QUERY ---
// Fetch all price columns and let PHP handle the logic.
$sql = "SELECT id, name, category, price_super_admin, price_agent, price_customer 
        FROM services 
        WHERE category IN ($placeholders) AND status = 'enabled' 
        ORDER BY name";

$stmt = $conn->prepare($sql);
$stmt->bind_param(str_repeat('s', count($categories_to_fetch)), ...$categories_to_fetch);
$stmt->execute();
$checkers = $stmt->get_result();
?>

<!-- STYLES FOR ANIMATIONS & MODERN LOOK (Unchanged) -->
<style>
@keyframes slideInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
.service-option-card {
    border-radius: 16px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    animation: slideInUp 0.6s ease-out forwards;
    opacity: 0;
    background: linear-gradient(135deg, #fd7e14, #ff5f6d);
    color: white;
    border: none;
}
.service-option-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 15px rgba(0,0,0,0.15);
}
.service-option-card .card-icon { opacity: 0.8; }
.service-option-card .price-text { color: #fff !important; text-shadow: 1px 1px 3px rgba(0,0,0,0.2); }
.service-option-card .btn-buy {
    background-color: #fff;
    color: #fd7e14;
    border: none;
    font-weight: bold;
    transition: background-color 0.2s ease, color 0.2s ease;
}
.service-option-card .btn-buy:hover { background-color: rgba(255,255,255,0.9); }
</style>

<h1 class="h2 mb-4">Result & Placement Checkers</h1>
<p class="text-muted">Select the voucher you wish to purchase.</p>

<div class="row g-4">
    <?php 
        $animation_delay = 0;
        while($checker = $checkers->fetch_assoc()):
            // --- THE FIX: PHP LOGIC TO SELECT THE CORRECT PRICE ---
            $price = 0.00; // Default price
            if ($user_role === 'Super Admin') {
                $price = $checker['price_super_admin'];
            } elseif ($user_role === 'Agent') {
                $price = $checker['price_agent'];
            } else { // This covers 'Customer' and any other roles
                $price = $checker['price_customer'];
            }
    ?>
    <div class="col-md-6 col-lg-4">
        <div class="card service-option-card text-center h-100" style="animation-delay: <?php echo $animation_delay; ?>s;">
            <div class="card-body d-flex flex-column p-4">
                <div class="mb-3"><i class="fas fa-graduation-cap fa-3x card-icon"></i></div>
                <h5 class="card-title fw-bold"><?php echo htmlspecialchars($checker['name']); ?></h5>
                <!-- Use the correct $price variable here -->
                <p class="card-text fs-4 fw-bold price-text">GH₵ <?php echo number_format($price, 2); ?></p>
                <button class="btn btn-buy mt-auto" 
                        data-bs-toggle="modal" 
                        data-bs-target="#buyCheckerModal"
                        data-service-id="<?php echo $checker['id']; ?>"
                        data-service-name="<?php echo htmlspecialchars($checker['name']); ?>"
                        data-service-price="<?php echo $price; ?>"
                        data-category="<?php echo htmlspecialchars($checker['category']); ?>">
                    Buy Voucher
                </button>
            </div>
        </div>
    </div>
    <?php 
        $animation_delay += 0.1;
        endwhile; 
    ?>
</div>


<!-- BUY CHECKER MODAL (HTML is Unchanged) -->
<div class="modal fade" id="buyCheckerModal" tabindex="-1" aria-labelledby="buyCheckerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="buyCheckerModalLabel">Purchase Voucher</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>You are about to purchase:</p>
        <h3 id="modal-checker-name" class="text-center text-primary fw-bold"></h3>
        <p class="text-center fs-4">Price: <strong id="modal-checker-price" class="text-success"></strong></p>
        <hr>
        <form id="addCheckerToCartForm" action="add_to_cart.php" method="POST">
            <p class="text-muted text-center mb-2">Kindly provide your WhatsApp number below to receive your voucher.</p>
            <input type="hidden" id="modal-checker-service-id" name="service_id">
            <input type="hidden" id="modal-checker-category" name="category">
            <input type="hidden" id="modal-checker-provider-name" name="provider_name" value="WAEC"> <!-- Add provider name -->

            <div class="mb-3">
                <label for="whatsapp_number" class="form-label visually-hidden">WhatsApp Number</label>
                <input type="tel" class="form-control form-control-lg" id="whatsapp_number" name="recipient_phone" placeholder="Enter WhatsApp Number" required pattern="[0-9]{10}" maxlength="10" title="Enter a valid 10-digit phone number.">
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg">Add to Cart & Continue</button>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- JAVASCRIPT IS UNCHANGED -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalName = document.getElementById('modal-checker-name');
    const modalPrice = document.getElementById('modal-checker-price');
    const modalServiceId = document.getElementById('modal-checker-service-id');
    const modalCategory = document.getElementById('modal-checker-category');
    // Note: It's good practice to add the provider name to the form as well.
    const modalProviderName = document.getElementById('modal-checker-provider-name');

    document.querySelectorAll('.btn-buy').forEach(button => {
        button.addEventListener('click', function () {
            const serviceName = this.getAttribute('data-service-name');
            const servicePrice = this.getAttribute('data-service-price');
            const serviceId = this.getAttribute('data-service-id');
            const category = this.getAttribute('data-category');
            
            modalName.textContent = serviceName;
            modalPrice.textContent = `GH₵ ${parseFloat(servicePrice).toFixed(2)}`;
            modalServiceId.value = serviceId;
            modalCategory.value = category;
        });
    });

    // Handle form submission via AJAX (optional but recommended for better UX)

});
</script>

<?php include_once '_partials/footer.php'; ?>