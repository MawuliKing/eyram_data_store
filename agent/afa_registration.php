<?php
// We include the header first to get access to session, DB, and the page layout
include_once '_partials/header.php';

// Get the current user's role from the session.
$user_role = $_SESSION['user_role'] ?? 'Customer';

// --- THE FIX: Fetch all price columns for the AFA Registration service ---
$stmt = $conn->prepare("SELECT id, name, category, price_super_admin, price_agent, price_customer 
                        FROM services 
                        WHERE category = 'AFA Registration' AND status = 'enabled' LIMIT 1");
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
$service_name = $service['name'] ?? 'AFA Registration';
$service_category = $service['category'] ?? 'AFA Registration';
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
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
}
.action-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 15px rgba(0,0,0,0.15);
}
.action-card .btn-primary {
    background-color: #fff;
    color: #667eea;
    border: none;
    font-weight: bold;
    transition: background-color 0.2s ease, color 0.2s ease;
}
.action-card .btn-primary:hover {
    background-color: rgba(255,255,255,0.9);
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">MTN AFA Registration & Verification</h1>
</div>

<!-- Updated with new animation and style classes -->
<div class="card shadow-sm action-card">
    <div class="card-body text-center p-5">
        <i class="fas fa-id-card-alt fa-4x mb-4" style="opacity: 0.8;"></i>
        <h4 class="card-title fw-bold">Begin AFA Registration</h4>
        <!-- Use the correct role-based price variable here -->
        <p class="opacity-75">Click the button below to open the registration form. The service fee is <strong>GHS <?php echo number_format($service_price, 2); ?></strong>.</p>
        <button class="btn btn-primary btn-lg mt-2" data-bs-toggle="modal" data-bs-target="#afaFormModal">
            Start Registration Form
        </button>
    </div>
</div>


<!-- ======================================================= -->
<!-- AFA REGISTRATION MODAL (HTML is Unchanged) -->
<!-- ======================================================= -->
<div class="modal fade" id="afaFormModal" tabindex="-1" aria-labelledby="afaFormModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="afaFormModalLabel">AFA Registration Form</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="addAfaToCartForm">
            <!-- Hidden inputs to pass service details -->
            <input type="hidden" name="service_id" value="<?php echo $service_id; ?>">
            <input type="hidden" name="provider_name" value="MTN">
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($service_category); ?>">

            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Full Name</label><input type="text" name="fullName" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">Phone Number</label><input type="tel" name="phoneNumber" class="form-control" required></div>
                <div class="col-md-6">
                    <label class="form-label">ID Type</label>
                    <select name="id_type" class="form-select" required>
                        <option value="" disabled selected>Select ID Type</option>
                        <option value="Ghana Card">Ghana Card</option>
                        <option value="Driver's License">Driver's License</option>
                        <option value="Voter's Card">Voter's Card</option>
                    </select>
                </div>
                <div class="col-md-6"><label class="form-label">ID Number</label><input type="text" name="id_number" class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">Occupation</label><input type="text" name="occupation" class="form-control" required></div>
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
                <div class="col-md-6"><label class="form-label">Date of Birth</label><input type="date" name="dateOfBirth" class="form-control" required></div>
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


<!-- ======================================================= -->
<!-- PAGE-SPECIFIC JAVASCRIPT (Unchanged) -->
<!-- ======================================================= -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const afaForm = document.getElementById('addAfaToCartForm');
    const afaModalElement = document.getElementById('afaFormModal');
    
    if (afaModalElement) {
        const afaModal = new bootstrap.Modal(afaModalElement);

        if (afaForm) {
            afaForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const submitButton = this.querySelector('button[type="submit"]');
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Adding...';

                // We need to package the form data into a sub-array for the cart handler
                const cartFormData = new FormData();
                cartFormData.append('service_id', formData.get('service_id'));
                cartFormData.append('provider_name', formData.get('provider_name'));
                cartFormData.append('category', formData.get('category'));

                // Create the form_data object
                let form_data_obj = {};
                for (let [key, value] of formData.entries()) {
                    if (key !== 'service_id' && key !== 'provider_name' && key !== 'category') {
                        form_data_obj[key] = value;
                    }
                }
                cartFormData.append('form_data', JSON.stringify(form_data_obj));

                fetch(`${BASE_URL_JS}agent/cart_handler.php?action=add_form`, {
                    method: 'POST',
                    body: cartFormData
                })
                .then(response => response.json())
                .then(data => {
                    handleCartResponse(data, 'AFA Registration added to cart!');
                })
                .catch(handleCartError)
                .finally(() => {
                    submitButton.disabled = false;
                    submitButton.innerHTML = 'Submit and Add to Cart';
                    afaModal.hide();
                    afaForm.reset();
                });
            });
        }
    }
});
</script>


<?php include_once '_partials/footer.php'; ?>