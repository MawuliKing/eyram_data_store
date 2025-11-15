<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/digital_agent/_partials/db.php';
include_once '_partials/header.php';

// Fetch settings from DB
$settings = [];
$result = $conn->query("SELECT * FROM settings");
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>
<div class="container mt-4">
    <h2>Update Support Settings</h2>

<form id="settingsForm" enctype="multipart/form-data">
    <div class="mb-3">
        <label class="form-label">Support Phone Number</label>
        <input type="text" name="support_number" class="form-control" value="<?= htmlspecialchars($settings['support_number']) ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">WhatsApp Support Number <small class="text-muted">(e.g. 233547001234)</small></label>
        <input type="text" name="support_whatsapp" class="form-control" value="<?= htmlspecialchars($settings['support_whatsapp']) ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Join Us WhatsApp Group Link</label>
        <input type="url" name="whatsapp_community_link" class="form-control" value="<?= htmlspecialchars($settings['whatsapp_community_link']) ?>" required>
    </div>

    <button type="submit" class="btn btn-primary">Save Changes</button>
</form>

</div>


<!-- SweetAlert CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.getElementById('settingsForm').addEventListener('submit', function (e) {
    e.preventDefault(); // Prevent default form submission

    const form = e.target;
    const formData = new FormData(form);

    fetch('settings_ajax.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(response => {
        if (response.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: response.message,
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: response.message
            });
        }
    })
    .catch(() => {
        Swal.fire({
            icon: 'error',
            title: 'Failed!',
            text: 'Something went wrong.'
        });
    });
});
</script>
<?php include_once '_partials/footer.php'; ?>
