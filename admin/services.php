<?php
include_once '_partials/header.php';

// --- Determine current level and build breadcrumbs ---
$parent_id = isset($_GET['parent_id']) ? (int)$_GET['parent_id'] : 0;
$breadcrumbs = [];
$page_title = 'Service Management'; // Default title

if ($parent_id > 0) {
    // We are inside a category, so we need to build the breadcrumb trail
    $current_id_for_bc = $parent_id;
    while ($current_id_for_bc > 0) {
        $stmt_bc = $conn->prepare("SELECT id, name, parent_id FROM services WHERE id = ?");
        $stmt_bc->bind_param("i", $current_id_for_bc);
        $stmt_bc->execute();
        $bc_row = $stmt_bc->get_result()->fetch_assoc();
        if ($bc_row) {
            array_unshift($breadcrumbs, $bc_row); // Add to the beginning of the array
            $current_id_for_bc = $bc_row['parent_id'];
        } else {
            break; // Stop if a parent is not found
        }
    }
    // Set the page title to the name of the current category
    if (!empty($breadcrumbs)) {
        $page_title = end($breadcrumbs)['name'];
    }
}

// Fetch items for the current level
$stmt_items = $conn->prepare("SELECT * FROM services WHERE parent_id = ? ORDER BY name");
$stmt_items->bind_param("i", $parent_id);
$stmt_items->execute();
$items = $stmt_items->get_result();
?>

<style>
    /* These styles are fine */
    #content-area { min-height: 400px; position: relative; }
    .loader { position: absolute; top: 40%; left: 50%; transform: translate(-50%, -50%); }
</style>

<div class="d-flex justify-content-between align-items-center mb-2">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
    <a href="service_edit.php?parent_id=<?php echo $parent_id; ?>" class="btn btn-success">
        <i class="fas fa-plus me-2"></i>Add New Item to this Level
    </a>
</div>

<!-- ============================================= -->
<!-- START OF CORRECTED BREADCRUMB LOGIC -->
<!-- ============================================= -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb bg-light rounded-3 p-3">
        <li class="breadcrumb-item">
            <a href="services.php?parent_id=0">Top Level</a>
        </li>
        <?php foreach ($breadcrumbs as $bc): ?>
            <?php if ($bc['id'] != $parent_id): ?>
                <li class="breadcrumb-item">
                    <a href="services.php?parent_id=<?php echo $bc['id']; ?>"><?php echo htmlspecialchars($bc['name']); ?></a>
                </li>
            <?php else: ?>
                <!-- The current page is not a link -->
                <li class="breadcrumb-item active" aria-current="page">
                    <?php echo htmlspecialchars($bc['name']); ?>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ol>
</nav>
<!-- ============================================= -->
<!-- END OF CORRECTED BREADCRUMB LOGIC -->
<!-- ============================================= -->


<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Name</th><th>Category</th><th>Price</th><th class="text-center">Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    <?php if ($items->num_rows > 0): ?>
                        <?php while($item = $items->fetch_assoc()): ?>
                            <tr>
                                <td class="align-middle fw-bold"><?php echo htmlspecialchars($item['name']); ?></td>
                                <td class="align-middle"><?php echo htmlspecialchars($item['category']); ?></td>
                                <td class="align-middle"><?php echo ($item['price'] > 0) ? 'GH₵ '.number_format($item['price'], 2) : '---'; ?></td>
                                <td class="text-center align-middle">
                                    <div class="form-check form-switch d-inline-block">
                                        <input class="form-check-input" type="checkbox" role="switch" <?php echo ($item['status'] == 'enabled' ? 'checked' : ''); ?> onclick="location.href='service_handler.php?action=toggle_status&id=<?php echo $item['id']; ?>&parent_id=<?php echo $parent_id; ?>'">
                                    </div>
                                </td>
                                <td class="text-end">
                                    <?php if ($item['price'] == 0.00): // It's a category ?>
                                        <a href="services.php?parent_id=<?php echo $item['id']; ?>" class="btn btn-sm btn-secondary">Manage Items</a>
                                    <?php endif; ?>
                                    <a href="service_edit.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center p-5 text-muted">No items found here. Use the "Add New Item" button to create one.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once '_partials/footer.php'; ?>