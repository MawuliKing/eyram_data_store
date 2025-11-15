<?php
include_once '_partials/header.php';

// --- Get the ID of the parent service we are managing ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: services_main.php?status=error&msg=InvalidID");
    exit();
}
$parent_id = (int)$_GET['id'];

// --- Fetch details of the parent service for the title ---
$stmt_parent = $conn->prepare("SELECT name FROM services WHERE id = ?");
$stmt_parent->bind_param("i", $parent_id);
$stmt_parent->execute();
$parent_service = $stmt_parent->get_result()->fetch_assoc();
if (!$parent_service) {
    header("Location: services_main.php?status=error&msg=NotFound");
    exit();
}
$parent_name = $parent_service['name'];


// --- Handle Form Submission for Adding a New Item ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_item'])) {
    $name = trim($_POST['name']);
    $price = isset($_POST['price']) ? trim($_POST['price']) : 0.00;
    $item_type = trim($_POST['item_type']); // This will be 'category' or 'service'
    $category_name = trim($_POST['category']); // The overall category for grouping

    if (!empty($name) && !empty($category_name)) {
        if ($item_type == 'service' && (!is_numeric($price) || $price <= 0)) {
            // Price is required for a sellable service
            $_SESSION['message'] = "A valid price is required for a service/product.";
            $_SESSION['message_type'] = "error";
        } else {
            $stmt_insert = $conn->prepare("INSERT INTO services (name, category, price, status, parent_id) VALUES (?, ?, ?, 'enabled', ?)");
            $stmt_insert->bind_param("ssdi", $name, $category_name, $price, $parent_id);
            if ($stmt_insert->execute()) {
                $_SESSION['message'] = "New item added successfully.";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Failed to add item.";
                $_SESSION['message_type'] = "error";
            }
        }
    } else {
        $_SESSION['message'] = "Name and Category are required.";
        $_SESSION['message_type'] = "error";
    }
    // Redirect back to the same page to show the new item and message
    header("Location: manage_service.php?id=" . $parent_id . "&t=" . time());
    exit();
}


// --- Fetch all sub-items for this service ---
$stmt_children = $conn->prepare("SELECT id, name, category, price, status FROM services WHERE parent_id = ? ORDER BY name");
$stmt_children->bind_param("i", $parent_id);
$stmt_children->execute();
$sub_items = $stmt_children->get_result();

// This is the code to generate the pop-up script from a session message
$page_scripts = ''; 
if(isset($_SESSION['message'])) {
    $page_scripts = "<script>Swal.fire({toast: true, position: 'top-end', icon: '" . $_SESSION['message_type'] . "', title: '" . addslashes($_SESSION['message']) . "', showConfirmButton: false, timer: 3500, timerProgressBar: true});</script>";
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="services_main.php">Main Categories</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($parent_name); ?></li>
    </ol>
</nav>

<h1 class="h2 mb-4">Manage: <?php echo htmlspecialchars($parent_name); ?></h1>

<div class="row">
    <!-- Form to add new sub-item (e.g., MTN, or 1GB Data) -->
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header"><h5 class="mb-0">Add New Item</h5></div>
            <div class="card-body">
                <form action="manage_service.php?id=<?php echo $parent_id; ?>" method="POST">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required placeholder="e.g., MTN or 1GB Bundle">
                    </div>
                    <div class="mb-3">
                        <label for="category" class="form-label">Grouping Category</label>
                        <input type="text" class="form-control" id="category" name="category" required placeholder="e.g., Data Provider or Data Bundle - MTN">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Item Type</label>
                        <select name="item_type" class="form-select">
                            <option value="category">Category (No Price)</option>
                            <option value="service">Service/Product (Has Price)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="price" class="form-label">Price (GH₵)</label>
                        <input type="number" step="0.01" class="form-control" id="price" name="price" placeholder="Leave 0 for categories">
                    </div>
                    <button type="submit" name="add_item" class="btn btn-primary">Add Item</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Table of existing sub-items -->
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header"><h5 class="mb-0">Existing Items</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Name</th><th>Category</th><th>Price</th><th class="text-center">Status</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                            <?php while($item = $sub_items->fetch_assoc()): ?>
                                <tr>
                                    <td class="align-middle fw-bold"><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td class="align-middle"><?php echo htmlspecialchars($item['category']); ?></td>
                                    <td class="align-middle"><?php echo ($item['price'] > 0) ? 'GH₵ ' . number_format($item['price'], 2) : '---'; ?></td>
                                    <td class="text-center align-middle">
                                        <!-- Toggle Switch -->
                                        <div class="form-check form-switch d-inline-block">
                                            <input class="form-check-input" type="checkbox" role="switch" <?php echo ($item['status'] == 'enabled') ? 'checked' : ''; ?> onclick="window.location.href='service_toggle_status.php?id=<?php echo $item['id']; ?>&parent_id=<?php echo $parent_id; ?>'">
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($item['price'] == 0.00): // It's a category ?>
                                            <a href="manage_service.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-secondary">Manage Sub-items</a>
                                        <?php endif; ?>
                                        <!-- We'll add an edit button here later -->
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '_partials/footer.php'; ?>