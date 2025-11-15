<?php
include_once '_partials/header.php';

// --- MODIFIED PHP LOGIC FOR SERVER-SIDE SEARCH ---

// 1. Get the status filter, default to 'Pending'
$filter_status = $_GET['filter'] ?? 'Pending';

// 2. Initialize search variables
$search = '';
$search_param = '';
$where_clauses = ["tr.status = ?"]; // Start with the mandatory status filter
$bind_types = 's'; // The type for the status parameter is a string
$bind_params = [$filter_status]; // The value for the status parameter

// 3. Check for and process a search term from the URL
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search = trim($_GET['search']);
    // Prepare the search term for a LIKE query
    $search_param = "%" . $search . "%"; 
    
    // Add the search conditions to the WHERE clause
    $where_clauses[] = "(u.full_name LIKE ? OR u.role LIKE ? OR tr.transaction_id LIKE ?)";
    
    // Add the types and values for the search parameters
    $bind_types .= 'sss'; // Three more string parameters
    $bind_params[] = $search_param; // for full_name
    $bind_params[] = $search_param; // for role
    $bind_params[] = $search_param; // for transaction_id
}

// 4. Construct the final SQL query
$sql = "SELECT tr.id, tr.amount, tr.transaction_id, tr.status, tr.requested_at, u.full_name, u.role 
        FROM topup_requests as tr 
        JOIN users as u ON tr.user_id = u.id 
        WHERE " . implode(' AND ', $where_clauses) . "
        ORDER BY tr.requested_at ASC";

// 5. Prepare, bind, and execute the statement
$stmt = $conn->prepare($sql);
// Use the splat operator (...) to pass the array of parameters to bind_param
$stmt->bind_param($bind_types, ...$bind_params); 
$stmt->execute();
$requests = $stmt->get_result();

// Session message handler (Unchanged)
$page_scripts = ''; 
if (isset($_SESSION['message'])) {
    $page_scripts = "<script>Swal.fire({toast: true, position: 'top-end', icon: '" . $_SESSION['message_type'] . "', title: '" . addslashes($_SESSION['message']) . "', showConfirmButton: false, timer: 3500, timerProgressBar: true});</script>";
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}
?>

<h1 class="h2 mb-4">Manage Top-Up Requests</h1>

<!-- Filter Buttons (Unchanged) -->
<div class="btn-group mb-3">
    <a href="topup_requests.php?filter=Pending" class="btn <?php echo ($filter_status == 'Pending') ? 'btn-primary' : 'btn-outline-primary'; ?>">Pending</a>
    <a href="topup_requests.php?filter=Approved" class="btn <?php echo ($filter_status == 'Approved') ? 'btn-primary' : 'btn-outline-primary'; ?>">Approved</a>
    <a href="topup_requests.php?filter=Declined" class="btn <?php echo ($filter_status == 'Declined') ? 'btn-primary' : 'btn-outline-primary'; ?>">Declined</a>
</div>

<!-- ============================================= -->
<!-- REPLACED WITH PHP-BASED SEARCH FORM           -->
<!-- ============================================= -->
<form method="GET" class="mb-3">
    <!-- Hidden input to preserve the current status filter when searching -->
    <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter_status); ?>">
    
    <div class="input-group">
        <input type="text" name="search" class="form-control" 
               value="<?php echo htmlspecialchars($search); ?>" 
               placeholder="Search by Name, Role, Transaction ID...">
        <button class="btn btn-dark" type="submit"><i class="fas fa-search"></i> Search</button>
        <?php if (!empty($search)): ?>
            <!-- The clear button now correctly removes the search query but keeps the filter -->
            <a href="topup_requests.php?filter=<?php echo htmlspecialchars($filter_status); ?>" class="btn btn-outline-secondary">Clear</a>
        <?php endif; ?>
    </div>
</form>


<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>User Name & Role</th>
                        <th>Amount (GH₵)</th>
                        <th>Transaction ID</th>
                        <th>Date Requested</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <!-- Table body is now populated by the filtered PHP results -->
                <tbody>
                    <?php if ($requests->num_rows > 0): ?>
                        <?php while($req = $requests->fetch_assoc()): ?>
                            <tr>
                                <td class="align-middle">
                                    <div class="fw-bold"><?php echo htmlspecialchars($req['full_name']); ?></div>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($req['role']); ?></span>
                                </td>
                                <td class="align-middle fs-5"><?php echo number_format($req['amount'], 2); ?></td>
                                <td class="align-middle">
                                    <?php echo htmlspecialchars($req['transaction_id']); ?>
                                </td>
                                <td class="align-middle">
                                    <?php echo date("d M Y, h:i A", strtotime($req['requested_at'])); ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($filter_status == 'Pending'): ?>
                                        <a href="topup_handler.php?action=approve&id=<?php echo $req['id']; ?>" class="btn btn-sm btn-success">Approve</a>
                                        <a href="topup_handler.php?action=decline&id=<?php echo $req['id']; ?>" class="btn btn-sm btn-danger">Decline</a>
                                    <?php else: ?>
                                        <span class="text-muted">No actions</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <!-- Dynamic message if no results are found -->
                        <tr><td colspan="5" class="text-center p-5 text-muted">No requests found for this criteria.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- JAVASCRIPT FOR LIVE SEARCH HAS BEEN REMOVED   -->
<!-- ============================================= -->

<?php include_once '_partials/footer.php'; ?>