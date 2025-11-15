<?php
// We include the header first to get access to session, DB, and the page layout
include_once '_partials/header.php';

// Get the current user's role from the session.
$user_role = $_SESSION['user_role'] ?? 'Customer';

// --- THE FIX: Fetch all price columns for the Business Certificate service ---
$stmt = $conn->prepare("SELECT id, name, category, price_super_admin, price_agent, price_customer 
                        FROM services 
                        WHERE category = 'Business Certificate' AND status = 'enabled' LIMIT 1");
$stmt->execute();
$service = $stmt->get_result()->fetch_assoc();

// --- THE FIX: Use PHP logic to determine the correct price ---
$service_price = 0.00;
if ($service) {
    if ($user_role === 'Super Admin') {
        $service_price = $service['price_super_admin'];
    } elseif ($user_role === 'Agent') {
        $service_price = $service['price_agent'];
    } else { // 'Customer' or any other role
        $service_price = $service['price_customer'];
    }
}

// Set other variables to use on the page and in the modal
$service_id = $service['id'] ?? null;
$service_name = $service['name'] ?? 'Business Certificate';
$service_category = $service['category'] ?? 'Business Certificate';
?>

<!-- STYLES FOR ANIMATIONS & MODERN LOOK (Unchanged) -->
<style>
@keyframes slideInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
.action-card {
    border-radius: 16px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    animation: slideInUp 0.6s ease-out forwards;
    opacity: 0;
    background: linear-gradient(135deg, #007bff, #00c6ff);
    color: white;
}
.action-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 15px rgba(0,0,0,0.15);
}
.action-card .btn-action {
    background-color: #fff;
    color: #007bff;
    border: none;
    font-weight: bold;
    transition: background-color 0.2s ease, color 0.2s ease;
}
.action-card .btn-action:hover {
    background-color: rgba(255,255,255,0.9);
}
</style>

<!-- Main Page View -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2"><?php echo htmlspecialchars($service_name); ?> Application</h1>
</div>

<!-- Updated with new animation and style classes -->
<div class="card shadow-sm action-card">
    <div class="card-body text-center p-5">
        <i class="fas fa-briefcase fa-4x mb-4" style="opacity: 0.8;"></i>
        <h4 class="card-title fw-bold">Begin Business Certificate Application</h4>
        <!-- Use the correct role-based price variable here -->
        <p class="opacity-75">Click the button below to open the application form. The service fee is <strong>GHS <?php echo number_format($service_price, 2); ?></strong>.</p>
        <button class="btn btn-action btn-lg mt-2" data-bs-toggle="modal" data-bs-target="#bizCertFormModal">
            Start Application Form
        </button>
    </div>
</div>

<!-- ======================================================= -->
<!-- PART 2: THE REGISTRATION FORM MODAL (HTML is Unchanged) -->
<!-- ======================================================= -->
<div class="modal fade" id="bizCertFormModal" tabindex="-1" aria-labelledby="bizCertFormModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="bizCertFormModalLabel">Business Certificate Application Form</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="addBizCertToCartForm" enctype="multipart/form-data">
            <!-- Hidden inputs to pass service details -->
            <input type="hidden" name="service_id" value="<?php echo $service_id; ?>">
            <input type="hidden" name="provider_name" value="Registrar General">
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($service_category); ?>">

            <h5 class="mb-3">Business Details</h5>
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">Business Name Option 1</label><input type="text" name="businessName1" class="form-control" required></div>
                <div class="col-md-4"><label class="form-label">Business Name Option 2</label><input type="text" name="businessName2" class="form-control" required></div>
                <div class="col-md-4"><label class="form-label">Business Name Option 3</label><input type="text" name="businessName3" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">Nature of Business</label><input type="text" name="natureOfBusiness" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">Number of Workers</label><input type="number" name="numWorkers" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">TIN (if available)</label><input type="text" name="tin" class="form-control"></div>
            </div>

            <hr class="my-4">
            <h5 class="mb-3">Proprietor's Details</h5>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Full Name</label><input type="text" name="ownerName" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">Date of Birth</label><input type="date" name="ownerDob" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">Place of Birth</label><input type="text" name="ownerPob" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">Occupation</label><input type="text" name="ownerOccupation" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">Contact</label><input type="tel" name="ownerContact" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">Email Address</label><input type="email" name="ownerEmail" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">Digital Address</label><input type="text" name="ownerDigitalAddress" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">House Number / Landmark</label><input type="text" name="ownerLandmark" class="form-control" required></div>
               <div class="col-md-6">
                    <label class="form-label">Region</label>
                    <select name="region" class="form-control" required>
                        <option value="" disabled selected>Select Region</option>
                        <option value="Ahafo">Ahafo</option>
                        <option value="Ashanti">Ashanti</option>
                        <option value="Bono">Bono</option>
                        <option value="Bono East">Bono East</option>
                        <option value="Central">Central</option>
                        <option value="Eastern">Eastern</option>
                        <option value="Greater Accra">Greater Accra</option>
                        <option value="North East">North East</option>
                        <option value="Northern">Northern</option>
                        <option value="Oti">Oti</option>
                        <option value="Savannah">Savannah</option>
                        <option value="Upper East">Upper East</option>
                        <option value="Upper West">Upper West</option>
                        <option value="Volta">Volta</option>
                        <option value="Western">Western</option>
                        <option value="Western North">Western North</option>
                    </select>
                </div>
                <div class="col-md-6"><label class="form-label">District</label><input type="text" name="ownerDistrict" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">Is where you stay rented?</label><select name="ownerIsRented" class="form-select"><option>Yes</option><option>No</option></select></div>
            </div>

            <hr class="my-4">
            <h5 class="mb-3">Required Documents</h5>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Upload Ghana Card (Front)</label><input type="file" name="ghanaCardFront" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">Upload Ghana Card (Back)</label><input type="file" name="ghanaCardBack" class="form-control" required></div>
            </div>

            <div class="modal-footer mt-4">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit and Add to Cart</button>
            </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php
// =============================================
// PAGE-SPECIFIC JAVASCRIPT (Unchanged)
// =============================================
$page_scripts = "
<script>
    const bizCertForm = document.getElementById('addBizCertToCartForm');
    if (bizCertForm) {
        const bizCertModalEl = document.getElementById('bizCertFormModal');
        const bizCertModal = new bootstrap.Modal(bizCertModalEl);

        bizCertForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type=\"submit\"]');
            
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class=\"spinner-border spinner-border-sm\"></span> Adding...';
            
            // Point to the dedicated handler for forms with files
            fetch(`${BASE_URL_JS}agent/cart_handler_form.php?action=add_with_files`, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                handleCartResponse(data, 'Business Certificate Application added to cart!');
            })
            .catch(handleCartError)
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = 'Submit and Add to Cart';
                bizCertModal.hide();
                bizCertForm.reset();
            });
        });
    }
</script>
";

// Include the footer, which will print the $page_scripts variable
include_once '_partials/footer.php'; 
?>