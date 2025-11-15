<?php
include_once '_partials/header.php'; // Your standard agent header

// The user_id is already available from your header.php as $user_id
if (!isset($user_id)) {
    // Fallback in case header.php changes
    $user_id = $_SESSION['user_id'] ?? 0;
}

// --- SECURE QUERY: Fetch activities ONLY for the currently logged-in user ---
$log_query = $conn->prepare("SELECT id, action_description, created_at FROM activity_log WHERE user_id = ? ORDER BY created_at DESC");
$log_query->bind_param("i", $user_id);
$log_query->execute();
$activities = $log_query->get_result();
?>

<style>
/* You can reuse or customize styles */
.activity-card-agent {
    border-left: 4px solid #3498db; /* A blue accent for agents */
    background-color: #fff;
    padding: 1rem;
    margin-bottom: 0.75rem;
    border-radius: 8px;
}
</style>

<h1 class="h2 mb-4">My Recent Activity</h1>
<p class="text-muted">A log of your recent actions on the platform.</p>

<div class="card shadow-sm">
    <div class="card-body">
        <?php if ($activities->num_rows > 0): ?>
            <ul class="list-group list-group-flush">
                <?php while ($activity = $activities->fetch_assoc()): ?>
                    <li class="list-group-item">
                        <p class="mb-1"><?php echo htmlspecialchars($activity['action_description']); ?></p>
                        <small class="text-muted">
                            <i class="fas fa-clock me-1"></i>
                            <?php echo date("F j, Y, g:i a", strtotime($activity['created_at'])); ?>
                        </small>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p class="text-center text-muted p-4">You have no recent activity.</p>
        <?php endif; ?>
    </div>
</div>

<?php 
// No delete functionality for agents, so no session messages are needed here.
include_once '_partials/footer.php'; 
?>