<?php
include_once '_partials/header.php'; // Your standard admin header

// --- Handle Delete Action ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['log_id'])) {
    $log_id_to_delete = (int)$_GET['log_id'];
    
    // Security: Ensure the log belongs to the current admin before deleting
    // For a platform admin, you might want to allow them to delete any log.
    // If so, you could remove "AND user_id = ?" for the platform_admin role.
    $delete_stmt = $conn->prepare("DELETE FROM activity_log WHERE id = ? AND user_id = ?");
    $delete_stmt->bind_param("ii", $log_id_to_delete, $_SESSION['user_id']);
    
    if ($delete_stmt->execute() && $delete_stmt->affected_rows > 0) {
        // --- THIS PART SETS THE MESSAGE ---
        $_SESSION['message'] = "Activity log entry deleted successfully.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting log entry or permission denied.";
        $_SESSION['message_type'] = "error";
    }
    // Redirect back to the same page to show the message and refresh the list
    header("Location: activity_log.php");
    exit();
}

// Fetch all activities for the currently logged-in admin
$admin_id = $_SESSION['user_id'];
$log_query = $conn->prepare("SELECT id, action_description, created_at FROM activity_log WHERE user_id = ? ORDER BY created_at DESC");
$log_query->bind_param("i", $admin_id);
$log_query->execute();
$activities = $log_query->get_result();
?>

<style>
.activity-card {
    border-left: 4px solid #0d6efd;
}
.delete-btn {
    font-size: 0.8rem;
    padding: 0.2rem 0.5rem;
}
</style>

<h1 class="h2 mb-4">My Recent Activity</h1>
<p class="text-muted">A log of all actions you have performed on the platform.</p>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if ($activities->num_rows > 0): ?>
            <ul class="list-group list-group-flush">
                <?php while ($activity = $activities->fetch_assoc()): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <p class="mb-1"><?php echo htmlspecialchars($activity['action_description']); ?></p>
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                <?php echo date("F j, Y, g:i a", strtotime($activity['created_at'])); ?>
                            </small>
                        </div>
                        <div class="mt-2 mt-md-0">
                            <a href="activity_log.php?action=delete&log_id=<?php echo $activity['id']; ?>" 
                               class="btn btn-outline-danger delete-btn"
                               onclick="return confirm('Are you sure you want to delete this log entry?');">
                                <i class="fas fa-trash-alt"></i> Delete
                            </a>
                        </div>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p class="text-center text-muted p-4">No activity has been logged yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php
// --- THE FIX IS HERE: This block was missing ---
// This code checks for a message in the session and prepares the pop-up script.
$page_scripts = ''; 
if (isset($_SESSION['message'])) {
    $page_scripts = "<script>
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: '" . addslashes($_SESSION['message_type']) . "',
            title: '" . addslashes($_SESSION['message']) . "',
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: true
        });
    </script>";
    // Clear the message from the session so it doesn't show again on refresh
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}

// The footer file will print the $page_scripts variable if it's set.
include_once '_partials/footer.php'; 
?>