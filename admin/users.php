<?php
include_once '_partials/header.php';

// --- Session Message Handling (Unchanged) ---
$page_scripts = '';
if (isset($_SESSION['message'])) {
    $page_scripts = "
    <script>
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: '" . $_SESSION['message_type'] . "',
            title: '" . addslashes($_SESSION['message']) . "',
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: true
        });
    </script>";
    unset($_SESSION['message'], $_SESSION['message_type']);
}

// --- SECURE, UPDATED SEARCH AND DATA FETCHING LOGIC ---
$search = trim($_GET['search'] ?? '');

// The base SQL query now includes a LEFT JOIN to the wallet table
$sql = "SELECT u.id, u.full_name, u.email, u.phone_number, u.role, u.status, u.created_at, w.balance 
        FROM users u
        LEFT JOIN wallet w ON u.id = w.user_id";

$params = [];
$types = '';

// If a search term is provided, add the WHERE clause
if (!empty($search)) {
    $sql .= " WHERE u.full_name LIKE ? OR u.email LIKE ? OR u.phone_number LIKE ? OR u.role LIKE ?";
    $search_param = "%" . $search . "%";
    $params = [$search_param, $search_param, $search_param, $search_param];
    $types = 'ssss'; // Four string parameters
}

// Always add the ordering
$sql .= " ORDER BY u.created_at DESC";

// Prepare, bind (if needed), and execute the statement
$stmt = $conn->prepare($sql);
if (!empty($search)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h2">User Management</h1>
    <a href="user_add.php" class="btn btn-success">
        <i class="fas fa-plus me-2"></i>Add New User
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-body">

        <!-- Search Form (Unchanged) -->
        <form method="GET" class="mb-3">
            <div class="input-group">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="form-control" placeholder="Search by Full Name, Email / Phone, or Role">
                <button class="btn btn-dark" type="submit"><i class="fas fa-search"></i> Search</button>
                <?php if (!empty($search)): ?>
                    <a href="users.php" class="btn btn-outline-secondary">Clear</a>
                <?php endif; ?>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email / Phone</th>
                        <th>Role</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th>Registered On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($row['email']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($row['phone_number']); ?></small>
                                </td>
                                <td>
                                    <?php 
                                        $role_color = 'secondary';
                                        if ($row['role'] == 'Admin') $role_color = 'danger';
                                        elseif ($row['role'] == 'Super Agent') $role_color = 'warning';
                                        elseif ($row['role'] == 'Agent') $role_color = 'primary';
                                    ?>
                                    <span class="badge bg-<?php echo $role_color; ?>"><?php echo $row['role']; ?></span>
                                </td>
                                <td>
                                    <?php
                                        // Use the null coalescing operator to default to 0.00 if no wallet exists
                                        $balance = $row['balance'] ?? 0.00;
                                        // Determine text color based on balance
                                        $balance_color = $balance >= 0 ? 'text-success' : 'text-danger';
                                    ?>
                                    <strong class="<?php echo $balance_color; ?>">
                                        GHS <?php echo number_format($balance, 2); ?>
                                    </strong>
                                </td>
                                <td>
                                    <?php if ($row['status'] == 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Blocked</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d M, Y', strtotime($row['created_at'])); ?></td>
                                <td class="d-flex gap-1 flex-wrap">
                                    <a href="user_edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <?php if ($row['id'] != $_SESSION['user_id']): ?>
                                        <?php if ($row['status'] == 'active'): ?>
                                            <a href="user_toggle_status.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="Block User">
                                                <i class="fas fa-ban"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="user_toggle_status.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-success" title="Unblock User">
                                                <i class="fas fa-check-circle"></i>
                                            </a>
                                        <?php endif; ?>

                                        <a href="user_delete.php?id=<?php echo $row['id']; ?>" 
                                           onclick="return confirm('Are you sure you want to delete this user and all their records? This action cannot be undone.');"
                                           class="btn btn-sm btn-danger" title="Delete User">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">No users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
include_once '_partials/footer.php';
// This echoes the SweetAlert script if a session message was set
echo $page_scripts; 
?>