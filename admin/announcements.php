<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/digital_agent/_partials/db.php';
include_once '_partials/header.php';

// Handle flash message (session-based)
if (isset($_SESSION['alert_message'])) {
    echo '<div class="alert alert-' . $_SESSION['alert_type'] . '">' . $_SESSION['alert_message'] . '</div>';
    unset($_SESSION['alert_message'], $_SESSION['alert_type']);
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Post Announcement</h2>
</div>

<form action="announcement_handler.php" method="POST">
    <div class="mb-3">
        <label for="title" class="form-label">Announcement Title</label>
        <input type="text" name="title" class="form-control" id="title" required>
    </div>

    <div class="mb-3">
        <label for="message" class="form-label">Message</label>
        <textarea name="message" id="message" rows="5" class="form-control" required></textarea>
    </div>

    <div class="mb-3">
        <label for="role" class="form-label">Target Audience</label>
        <select name="user_role" id="role" class="form-select" required>
            <option value="All">All Users</option>
            <option value="Agent">Agents Only</option>
            <option value="SuperAgent">Super Agents Only</option>
            <option value="Customer">Customers Only</option>
        </select>
    </div>

    <button type="submit" class="btn" style="background-color: blue; color: white;">
        <i class="fas fa-bullhorn"></i> Post Announcement
    </button>
</form>

<hr class="my-4">

<h4>Recent Announcements</h4>

<?php
$result = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 10");
while ($row = $result->fetch_assoc()):
?>
    <div class="card mb-3">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <div>
                <strong><?= htmlspecialchars($row['title']) ?></strong>
                <span class="badge bg-primary ms-2"><?= htmlspecialchars($row['user_role']) ?></span>
            </div>
            <div>
                <a href="announcement_edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">
                    <i class="fas fa-edit"></i>
                </a>
                <a href="announcement_delete.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this announcement?');">
                    <i class="fas fa-trash"></i>
                </a>
            </div>
        </div>
        <div class="card-body">
            <p><?= nl2br(htmlspecialchars($row['message'])) ?></p>
            <small class="text-muted"><?= date("F j, Y, g:i a", strtotime($row['created_at'])) ?></small>
        </div>
    </div>
<?php endwhile; ?>

<?php include_once '_partials/footer.php'; ?>
