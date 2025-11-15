<?php
// We include the header first to get the page layout, session, and DB connection.
include_once '_partials/header.php';

// Get the current user's role from the session.
$user_role = $_SESSION['user_role'] ?? 'Customer';

// --- THE FIX: Fetch all price columns for the Agent SIM service ---
$stmt = $conn->prepare("SELECT id, name, category, price_super_admin, price_agent, price_customer 
                        FROM services 
                        WHERE category = 'Agent SIM' AND status = 'enabled' LIMIT 1");
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
$service_name = $service['name'] ?? 'MTN Agent SIM Registration';
$service_category = $service['category'] ?? 'Agent SIM';
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
    background: linear-gradient(135deg, #ffc107, #f7971e);
    color: #000;
}
.action-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 15px rgba(0,0,0,0.15);
}
.action-card .btn-action {
    background-color: #000;
    color: #fff;
    border: none;
    font-weight: bold;
    transition: background-color 0.2s ease;
}
.action-card .btn-action:hover {
    background-color: #333;
}
</style>

<!-- Main Page View -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2"><?php echo htmlspecialchars($service_name); ?></h1>
</div>

<!-- Updated with new animation and style classes -->
<div class="card shadow-sm action-card">
    <div class="card-body text-center p-5">
        <i class="fas fa-sim-card fa-4x mb-4" style="opacity: 0.8;"></i>
        <h4 class="card-title fw-bold">Begin SIM Registration</h4>
        <p class="opacity-75">Click the button below to open the questionnaire. The service fee is <strong>GHS <?php echo number_format($service_price, 2); ?></strong>.</p>
        <button class="btn btn-action btn-lg mt-2" data-bs-toggle="modal" data-bs-target="#agentSimFormModal">
            Start Questionnaire
        </button>
    </div>
</div>



<!-- AGENT SIM REGISTRATION MODAL (HTML is Unchanged) -->
<div class="modal fade" id="agentSimFormModal" tabindex="-1" aria-labelledby="agentSimFormModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="agentSimFormModalLabel">MTN Agent SIM Registration Questionnaire</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="addAgentSimToCartForm" enctype="multipart/form-data">
            <!-- Hidden inputs -->
            <input type="hidden" name="service_id" value="<?php echo $service_id; ?>">
            <input type="hidden" name="provider_name" value="MTN">
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($service_category); ?>">

            <h5 class="mb-3 text-primary">Personal Information</h5>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">In which locality were you born?</label><input type="text" name="birth_locality" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">Hometown Name</label><input type="text" name="hometown" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">Mother’s first name?</label><input type="text" name="mother_firstname" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">Father’s first name?</label><input type="text" name="father_firstname" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">Day of the week were you born?</label><input type="text" name="birth_day_of_week" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">Month were you born?</label><input type="text" name="birth_month" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">Full Date of Birth</label><input type="date" name="date_of_birth" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">Year of MTN SIM purchase</label><input type="number" name="sim_purchase_year" class="form-control" required placeholder="e.g., 2018"></div>
            </div>
            <hr class="my-4">
            <h5 class="mb-3 text-primary">Educational Background</h5>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Region of secondary school?</label><input type="text" name="school_region" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">Name of secondary school?</label><input type="text" name="school_name" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">Year you started secondary school?</label><input type="number" name="school_start_year" class="form-control" required placeholder="e.g., 2010"></div>
                <div class="col-md-6"><label class="form-label">Year you completed secondary school?</label><input type="number" name="school_end_year" class="form-control" required placeholder="e.g., 2013"></div>
            </div>
            <hr class="my-4">
            <h5 class="mb-3 text-primary">Required Document Uploads</h5>
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">Business Certificate</label><input type="file" name="doc_business_cert" class="form-control" required></div>
                <div class="col-md-4"><label class="form-label">'A' Form</label><input type="file" name="doc_a_form" class="form-control" required></div>
                <div class="col-md-4"><label class="form-label">Ghana Card</label><input type="file" name="doc_ghana_card" class="form-control" required></div>
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
// =======================================================
// PAGE-SPECIFIC JAVASCRIPT (Unchanged)
// =======================================================
$page_scripts = "
<script>
    const agentSimForm = document.getElementById('addAgentSimToCartForm');
    if (agentSimForm) {
        const agentSimModalEl = document.getElementById('agentSimFormModal');
        const agentSimModal = new bootstrap.Modal(agentSimModalEl);

        agentSimForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type=\"submit\"]');
            
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class=\"spinner-border spinner-border-sm\"></span> Adding...';
            
            // This form has file uploads, so we send it to the dedicated handler
            fetch(`${BASE_URL_JS}agent/cart_handler_form.php?action=add_with_files`, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Use the global helper function for a consistent response
                handleCartResponse(data, 'Agent SIM Registration added to cart!');
            })
            .catch(handleCartError)
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = 'Submit and Add to Cart';
                agentSimModal.hide();
                agentSimForm.reset();
            });
        });
    }
</script>
";

// Include the footer, which will print our $page_scripts variable
include_once '_partials/footer.php'; 
?>