<?php
/*
==================================================================
 PART 1: PRE-PROCESSING & FORM HANDLING
==================================================================
*/
require_once $_SERVER['DOCUMENT_ROOT'] . '/digital_agent/_partials/db.php';

// Security Check: Ensure an Admin is performing this action
if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] !== 'Admin' && $_SESSION['user_role'] !== 'platform_admin')) {
    exit('Access Denied.');
}

// Determine if we are editing or adding
$is_editing = isset($_GET['id']) && is_numeric($_GET['id']);
$parent_id = isset($_GET['parent_id']) ? (int)$_GET['parent_id'] : 0;

// --- Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $category = trim($_POST['category']);
    $price = trim($_POST['price']); // This is now the Admin/Base Price
    $status = trim($_POST['status']);
    
    // --- NEW: Get the role-based prices from the form ---
    $price_super_admin = trim($_POST['price_super_admin']);
    $price_agent = trim($_POST['price_agent']);
    $price_customer = trim($_POST['price_customer']);

    $service_id_to_edit = $_POST['service_id'] ?? null;

    if ($service_id_to_edit) { // UPDATING an existing item
        $stmt = $conn->prepare("UPDATE services SET name=?, category=?, price=?, price_super_admin=?, price_agent=?, price_customer=?, status=? WHERE id=?");
        $stmt->bind_param("ssdddsdi", $name, $category, $price, $price_super_admin, $price_agent, $price_customer, $status, $service_id_to_edit);
    } else { // INSERTING a new item
        $stmt = $conn->prepare("INSERT INTO services (name, category, price, price_super_admin, price_agent, price_customer, status, parent_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdddsdi", $name, $category, $price, $price_super_admin, $price_agent, $price_customer, $status, $parent_id);
    }

    if ($stmt->execute()) {
        header("Location: services.php?parent_id=" . $parent_id . "&status=success&msg=ServiceSaved");
        exit();
    } else {
        $error_message = "Database Error: " . $stmt->error;
    }
}

/*
==================================================================
 PART 2: PAGE DISPLAY (Fetch data for pre-filling the form)
==================================================================
*/
// Initialize variables for the form
$name = $category = $price = $status = '';
$price_super_admin = $price_agent = $price_customer = '0.00'; // Default values
$page_title = 'Add New Item';
$service_id = null;

if ($is_editing) {
    $service_id = (int)$_GET['id'];
    $page_title = 'Edit Item';
    
    // Fetch existing data for the form, including new price columns
    $stmt_fetch = $conn->prepare("SELECT * FROM services WHERE id = ?");
    $stmt_fetch->bind_param("i", $service_id);
    $stmt_fetch->execute();
    $service = $stmt_fetch->get_result()->fetch_assoc();
    if (!$service) { exit("Service not found."); }

    $name = $service['name'];
    $category = $service['category'];
    $price = $service['price'];
    $status = $service['status'];
    $parent_id = $service['parent_id'];
    
    // --- NEW: Get existing role-based prices ---
    $price_super_admin = $service['price_super_admin'];
    $price_agent = $service['price_agent'];
    $price_customer = $service['price_customer'];
}

include_once '_partials/header.php';
?>

<h1 class="h2 mb-4"><?php echo $page_title; ?></h1>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="col-md-8 col-lg-6">
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <form action="service_edit.php?parent_id=<?php echo $parent_id; ?>" method="POST">
                <?php if ($is_editing): ?>
                    <input type="hidden" name="service_id" value="<?php echo $service_id; ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                </div>
                 <div class="mb-3">
                    <label for="category" class="form-label">Grouping Category</label>
                    <input type="text" class="form-control" id="category" name="category" value="<?php echo htmlspecialchars($category); ?>" required placeholder="e.g., Data Provider or Data Bundle - MTN">
                </div>
                
                <hr class="my-4">
                <h5 class="mb-3">Pricing Structure</h5>

                <div class="mb-3">
                    <label for="price" class="form-label">Admin Cost / Base Price (GH₵)</label>
                    <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?php echo htmlspecialchars($price); ?>" required>
                    <div class="form-text">Your cost for the item. Enter 0 for non-sellable categories.</div>
                </div>

                <!-- --- NEW FORM FIELDS FOR ROLE-BASED PRICING --- -->
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="price_super_admin" class="form-label fw-bold">Super Agent Price</label>
                        <input type="number" step="0.01" class="form-control" id="price_super_admin" name="price_super_admin" value="<?php echo htmlspecialchars($price_super_admin); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="price_agent" class="form-label fw-bold">Agent Price</label>
                        <input type="number" step="0.01" class="form-control" id="price_agent" name="price_agent" value="<?php echo htmlspecialchars($price_agent); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="price_customer" class="form-label fw-bold">Customer Price</label>
                        <input type="number" step="0.01" class="form-control" id="price_customer" name="price_customer" value="<?php echo htmlspecialchars($price_customer); ?>" required>
                    </div>
                </div>
                <!-- --- END OF NEW FIELDS --- -->
                
                <hr class="my-4">

                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="enabled" <?php if($status == 'enabled') echo 'selected'; ?>>Enabled</option>
                        <option value="disabled" <?php if($status == 'disabled') echo 'selected'; ?>>Disabled</option>
                    </select>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="services.php?parent_id=<?php echo $parent_id; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once '_partials/footer.php'; ?>