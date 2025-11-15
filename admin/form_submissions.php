<?php
// --- Fully Updated PHP Logic ---
include_once '_partials/header.php';

// This is the master list of categories this page handles
$form_service_categories = [
    'AFA Registration',
    'TIN  Registration',
    'Agent SIM',
    'Business Certificate',
];

// --- 1. Get Filters and Search Term from the URL ---
$filter_category = $_GET['filter'] ?? 'all'; // Default to showing 'all' categories
$search_term = trim($_GET['search'] ?? '');

// --- 2. Dynamically Build the Main SQL Query for displaying orders ---
$where_clauses = [];
$bind_types = '';
$bind_params = [];

$placeholders = implode(',', array_fill(0, count($form_service_categories), '?'));
$where_clauses[] = "JSON_UNQUOTE(JSON_EXTRACT(o.order_details, '$.category')) IN ($placeholders)";
$bind_types .= str_repeat('s', count($form_service_categories));
$bind_params = $form_service_categories;

if ($filter_category !== 'all' && in_array($filter_category, $form_service_categories)) {
    $where_clauses[] = "JSON_UNQUOTE(JSON_EXTRACT(o.order_details, '$.category')) = ?";
    $bind_types .= 's';
    $bind_params[] = $filter_category;
}

if (!empty($search_term)) {
    $search_like = "%" . $search_term . "%";
    $where_clauses[] = "(o.id LIKE ? OR JSON_UNQUOTE(JSON_EXTRACT(o.order_details, '$.recipient_phone')) LIKE ? OR u.full_name LIKE ?)";
    $bind_types .= 'sss';
    $bind_params[] = $search_like;
    $bind_params[] = $search_like;
    $bind_params[] = $search_like;
}

$sql_orders = "SELECT o.id, o.order_details, o.status, o.created_at, u.full_name 
               FROM orders as o 
               JOIN users as u ON o.user_id = u.id 
               WHERE " . implode(' AND ', $where_clauses) . "
               ORDER BY o.created_at DESC";
$stmt_orders = $conn->prepare($sql_orders);
$stmt_orders->bind_param($bind_types, ...$bind_params);
$stmt_orders->execute();
$orders_result = $stmt_orders->get_result();

// --- 3. Get PENDING Counts for the Category Boxes ---
$counts_placeholders = implode(',', array_fill(0, count($form_service_categories), '?'));
$sql_counts = "SELECT JSON_UNQUOTE(JSON_EXTRACT(order_details, '$.category')) as category, COUNT(id) as pending_count 
               FROM orders 
               WHERE status = 'Pending' 
               AND JSON_UNQUOTE(JSON_EXTRACT(order_details, '$.category')) IN ($counts_placeholders)
               GROUP BY category";
$stmt_counts = $conn->prepare($sql_counts);
$stmt_counts->bind_param(str_repeat('s', count($form_service_categories)), ...$form_service_categories);
$stmt_counts->execute();
$counts_result = $stmt_counts->get_result();

$category_counts = array_fill_keys($form_service_categories, 0);
while ($row = $counts_result->fetch_assoc()) {
    $category_counts[$row['category']] = $row['pending_count'];
}

// ✅ --- 4. NEW: Get TOTAL SUBMITTED (Actioned) Count for the 'View All' box ---
$sql_total_actioned = "SELECT COUNT(id) as total_count 
                       FROM orders 
                       WHERE status IN ('Complete', 'Processing')
                       AND JSON_UNQUOTE(JSON_EXTRACT(order_details, '$.category')) IN ($counts_placeholders)";
$stmt_total_actioned = $conn->prepare($sql_total_actioned);
$stmt_total_actioned->bind_param(str_repeat('s', count($form_service_categories)), ...$form_service_categories);
$stmt_total_actioned->execute();
$total_actioned_count = $stmt_total_actioned->get_result()->fetch_assoc()['total_count'] ?? 0;
?>

<!-- Your CSS styles are perfect and remain unchanged -->
<style>
.category-box { border-radius: 8px; padding: 25px; color: #fff; font-weight: bold; text-align: center; position: relative; cursor: pointer; transition: transform 0.3s ease, box-shadow 0.3s ease; height: 100px; display: flex; align-items: center; justify-content: center; }
.category-box:hover { transform: scale(1.05); box-shadow: 0 10px 25px rgba(0,0,0,0.15); }
.category-box.active { transform: scale(1.05); box-shadow: 0 10px 25px rgba(0,0,0,0.2); border: 3px solid #fff; }
.category-badge { position: absolute; top: -8px; right: -8px; background-color: #fff; color: #000; font-size: 12px; font-weight: bold; padding: 4px 8px; border-radius: 50%; box-shadow: 0 0 5px rgba(0,0,0,0.2); }
.bg-afa { background: linear-gradient(to right, #667eea, #764ba2); }
.bg-govt { background: linear-gradient(to right, #ff6a00, #ee0979); }
.bg-agent { background: linear-gradient(to right, #00c9ff, #92fe9d); color: #000; }
.bg-business { background: linear-gradient(to right, #f7971e, #ffd200); color: #000; }
.bg-all-forms { background: linear-gradient(to right, #434343, #000000); }
.badge-actioned { background-color: #28a745 !important; color: #fff !important; }
</style>

<h1 class="h2 mb-4">Form Service Submissions</h1>
<p class="text-muted">Select a category to view its form submissions or use the search bar.</p>

<!-- ✅ --- MOVED: Server-Side Search Form is now at the top --- -->
<form method="GET" class="mb-4">
    <input type="hidden" name="filter" value="<?= htmlspecialchars($filter_category); ?>">
    <div class="input-group">
        <input type="text" name="search" class="form-control form-control-lg" 
               value="<?= htmlspecialchars($search_term); ?>" 
               placeholder="Search by Order ID, Phone Number, or Agent Name...">
        <button class="btn btn-dark" type="submit"><i class="fas fa-search"></i> Search</button>
        <?php if (!empty($search_term)): ?>
            <a href="?filter=<?= htmlspecialchars($filter_category); ?>" class="btn btn-outline-secondary">Clear Search</a>
        <?php endif; ?>
    </div>
</form>

<!-- --- Category Boxes are now simple links --- -->
<div class="row mb-4">
    <?php
    $category_colors = [ 'AFA Registration' => 'bg-afa', 'TIN  Registration' => 'bg-govt', 'Agent SIM' => 'bg-agent', 'Business Certificate' => 'bg-business' ];
    foreach ($form_service_categories as $category): ?>
        <div class="col-sm-6 col-md-4 col-lg-3 mb-3">
            <a href="?filter=<?= htmlspecialchars($category); ?>" class="text-decoration-none">
                <div class="category-box <?= $category_colors[$category]; ?> <?= ($filter_category == $category) ? 'active' : '' ?>">
                    <div class="category-badge"><?= $category_counts[$category]; ?></div>
                    <?= htmlspecialchars($category); ?>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
    <div class="col-sm-6 col-md-4 col-lg-3 mb-3">
        <a href="?filter=all" class="text-decoration-none">
             <div class="category-box bg-all-forms <?= ($filter_category == 'all') ? 'active' : '' ?>">
                <!-- ✅ RESTORED: The total submitted count is back -->
                <div class="category-badge badge-actioned"><?= $total_actioned_count; ?></div>
                View All Forms
            </div>
        </a>
    </div>
</div>

<!-- --- Orders are now rendered directly by PHP --- -->
<div id="orders-container">
    <h2 class="mb-3">
        <?php
        if (!empty($search_term)) {
            echo "Search Results for \"" . htmlspecialchars($search_term) . "\"";
        } elseif ($filter_category === 'all') {
            echo "All Form Submissions";
        } else {
            echo htmlspecialchars($filter_category) . " Submissions";
        }
        ?>
    </h2>

    <?php if ($orders_result->num_rows > 0): ?>
        <?php while($order = $orders_result->fetch_assoc()):
            $details = json_decode($order['order_details'], true);
            $formData = $details['form_data'] ?? [];
            $uploadedFiles = $formData['uploaded_files'] ?? [];
            $name = $details['name'] ?? 'N/A';
            $agent = $order['full_name'] ?? 'N/A';
            $status = $order['status'];
            $orderId = $order['id'];
            $statusColor = 'secondary';
            if ($status === 'Processing') $statusColor = 'primary';
            elseif ($status === 'Complete') $statusColor = 'success';
            elseif ($status === 'Failed') $statusColor = 'danger';

            unset($formData['service_id'], $formData['provider_name'], $formData['category'], $formData['uploaded_files']);
        ?>
            <div class="card shadow-sm mb-3">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-3"><small class="text-muted">Order #<?= $orderId ?></small><p class="fw-bold mb-0"><?= htmlspecialchars($name) ?></p></div>
                        <div class="col-md-4"><small class="text-muted">Agent</small><p class="fw-bold mb-0"><?= htmlspecialchars($agent) ?></p></div>
                        <div class="col-md-3"><small class="text-muted">Status</small><p class="fw-bold mb-0"><span class="badge bg-<?= $statusColor ?>"><?= $status ?></span></p></div>
                        <div class="col-md-2 text-end"><button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#details-<?= $orderId ?>">View Details</button></div>
                    </div>
                    <div class="collapse mt-3" id="details-<?= $orderId ?>">
                        <hr>
                        <div class="row">
                            <div class="col-lg-7">
                                <h6><strong>Submitted Information</strong></h6>
                                <div class="table-responsive"><table class="table table-sm table-bordered table-striped"><tbody>
                                <?php foreach($formData as $key => $value): ?>
                                    <tr>
                                        <th style="width: 40%;"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $key))) ?></th>
                                        <td><?= htmlspecialchars($value) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody></table></div>
                            </div>
                            <div class="col-lg-5">
                                <h6><strong>Uploaded Documents</strong></h6>
                                <?php if (!empty($uploadedFiles)): ?>
                                    <ul class="list-group mb-3">
                                    <?php foreach($uploadedFiles as $label => $filename):
                                        $filePath = "/digital_agent/uploads/" . $filename;
                                    ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <?= htmlspecialchars(ucwords(str_replace('_', ' ', $label))) ?>
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-sm btn-outline-info view-image-btn" data-bs-toggle="modal" data-bs-target="#imageModal" data-img-src="<?= $filePath ?>" data-img-title="<?= htmlspecialchars(ucwords(str_replace('_', ' ', $label))) ?>"><i class="fas fa-eye me-1"></i> View</button>
                                                <a class="btn btn-sm btn-outline-secondary" href="<?= $filePath ?>" download="<?= htmlspecialchars($filename) ?>"><i class="fas fa-download me-1"></i> Download</a>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <p class="text-muted">No documents were uploaded for this request.</p>
                                <?php endif; ?>
                                <hr>
                                <h6><strong>Actions</strong></h6>
                                <a href="generate_pdf.php?id=<?= $orderId ?>" target="_blank" class="btn btn-warning mb-2"><i class="fas fa-file-pdf"></i> Download PDF</a><br>
                                <?php if ($status === 'Pending' || $status === 'Processing'): ?>
                                    <a href="order_handler.php?action=approve&id=<?= $orderId ?>&redirect_to=form_submissions.php" class="btn btn-success"><i class="fas fa-check"></i> Approve</a>
                                    <a href="order_handler.php?action=decline&id=<?= $orderId ?>&redirect_to=form_submissions.php" class="btn btn-danger"><i class="fas fa-times"></i> Decline</a>
                                <?php else: ?>
                                    <span class="text-muted fst-italic">This order was actioned (Status: <?= $status ?>).</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="alert alert-info">No submissions found for the selected criteria.</div>
    <?php endif; ?>
</div>

<!-- The JavaScript remains simple and effective -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.addEventListener('click', function(e) { 
        if (e.target.closest('.view-image-btn')) { 
            const btn = e.target.closest('.view-image-btn'); 
            const imgSrc = btn.getAttribute('data-img-src'); 
            const imgTitle = btn.getAttribute('data-img-title') || 'Document Preview';
            
            const modalImageElement = document.getElementById('modalImage');
            const modalTitleElement = document.getElementById('imageModalLabel');

            if (modalImageElement && modalTitleElement) {
                modalImageElement.src = imgSrc;
                modalTitleElement.textContent = imgTitle;
            } else {
                console.error("Modal image or title element not found. Ensure the modal HTML is correct.");
            }
        } 
    });
});
</script>

<!-- The Bootstrap Image Modal remains the same -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalLabel">Document Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img src="" id="modalImage" class="img-fluid" alt="Document Preview">
            </div>
        </div>
    </div>
</div>

<?php include_once '_partials/footer.php'; ?>