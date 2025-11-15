    </div> <!-- This closes the container-fluid from the header.php file -->
</div> <!-- This closes the main-content-agent div from header.php -->

<!-- Main Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- SweetAlert2 Library for Pop-ups -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- ============================================= -->
<!-- LOAD OUR NEW GLOBAL AGENT JAVASCRIPT FILE -->
<!-- This single file now controls all cart functionality and other global scripts. -->
<!-- The ?v=... part is a cache-buster to ensure the latest version is always loaded. -->
<!-- ============================================= -->
<script src="<?php echo BASE_URL; ?>assets/js/agent_global.js?v=<?php echo time(); ?>"></script>


<!-- ============================================================== -->
<!-- GENERAL AGENT POP-UP HANDLER (from URL) -->
<!-- This is kept here as it's a great way to show messages after a page redirect. -->
<!-- ============================================================== -->
<?php
if (isset($_GET['status']) && isset($_GET['msg'])) {
    
    $status_type = $_GET['status'];
    $message_code = $_GET['msg'];

    $messages = [
        'ProfileUpdated' => 'Your profile has been updated successfully!',
        'TopupSuccess' => 'Your wallet has been funded!',
        'OrderPlaced' => 'Your order has been placed successfully.'
    ];

    $icon = ($status_type == 'success') ? 'success' : 'error';
    $title = ($status_type == 'success') ? 'Success!' : 'Error!';
    $text = $messages[$message_code] ?? 'Action completed.';

    echo "
    <script>
        Swal.fire({
            icon: '" . $icon . "',
            title: '" . $title . "',
            text: '" . addslashes($text) . "',
            timer: 3000,
            showConfirmButton: false
        });
    </script>
    ";
}
?>
<!-- ============================================================== -->

</body>
</html>