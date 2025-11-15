<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/digital_agent/_partials/db.php';
include_once '_partials/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

$id = intval($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM announcements WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    $_SESSION['alert_message'] = "Announcement not found.";
    $_SESSION['alert_type'] = "error";
    header("Location: announcements.php");
    exit();
}

$show_alert = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);
    $audience = trim($_POST['audience']);

    $stmt = $conn->prepare("UPDATE announcements SET title = ?, message = ?, user_role = ? WHERE id = ?");
    $stmt->bind_param("sssi", $title, $message, $audience, $id);
    $stmt->execute();

    $show_alert = true;
}
?>

<div class="container mt-4">
    <h2 class="mb-4">Edit Announcement</h2>

    <form method="POST">
        <div class="mb-3">
            <label for="title" class="form-label">Title</label>
            <input type="text" name="title" id="title" class="form-control" value="<?= htmlspecialchars($data['title']) ?>" required>
        </div>

        <div class="mb-3">
            <label for="message" class="form-label">Message</label>
            <textarea name="message" id="message" class="form-control" rows="5" required><?= htmlspecialchars($data['message']) ?></textarea>
        </div>

        <div class="mb-3">
            <label for="audience" class="form-label">Target Audience</label>
            <select name="audience" id="audience" class="form-select" required>
                <option value="Agent" <?= $data['user_role'] === 'Agent' ? 'selected' : '' ?>>Agents Only</option>
                <option value="SuperAgent" <?= $data['user_role'] === 'SuperAgent' ? 'selected' : '' ?>>Super Agents Only</option>
                <option value="Customer" <?= $data['user_role'] === 'Customer' ? 'selected' : '' ?>>Customers Only</option>
                <option value="All" <?= $data['user_role'] === 'All' ? 'selected' : '' ?>>All Users</option>
            </select>
        </div>

        <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i> Update</button>
        <a href="announcements.php" class="btn btn-secondary ms-2">Cancel</a>
    </form>
</div>

<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if ($show_alert): ?>
<script>
    Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: 'Announcement updated successfully.',
        showConfirmButton: false,
        timer: 2000
    });
</script>
<?php endif; ?>

<?php include_once '_partials/footer.php'; ?>
