        </div> <!-- Closes the container-fluid from the header -->
    </div> <!-- Closes the admin-content div -->
</div> <!-- Closes the admin-layout div -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- ============================================================== -->
<!-- GENERAL ADMIN POP-UP HANDLER (The "popup.php" logic) -->
<!-- ============================================================== -->
<!-- GENERAL ADMIN POP-UP HANDLER (from URL) -->
<?php
// This script checks the URL for a status message and displays a pop-up toast.
if (isset($_GET['status']) && isset($_GET['msg'])) {
    
    $status_type = $_GET['status'];
    $message_code = $_GET['msg'];

    $messages = [
        // User messages
        'UserCreated' => 'New user has been created successfully.',
        'UserUpdated' => 'User details have been updated.',
        'UserStatusUpdated' => 'User status has been changed.',
        'SelfBlock' => 'You cannot block your own account.',
        'UserNotFound' => 'The requested user was not found.',
        // Service messages
        'ServiceSaved' => 'Service details have been saved.',
        // Order messages
        'OrderApproved' => 'Order has been approved successfully.',
        'OrderDeclined' => 'Order declined and funds have been refunded.',
        'OrderAlreadyProcessed' => 'This order has already been processed.',
        'RefundFailed' => 'A critical error occurred during the refund process.',
        'OrderNotFound' => 'The specified order could not be found.',
        // Generic messages
        'InvalidRequest' => 'The request was invalid.',
        'UpdateFailed' => 'The action could not be completed.'
    ];

    $icon = ($status_type == 'success') ? 'success' : 'error';
    $text = $messages[$message_code] ?? 'Action completed successfully.';

    echo "
    <script>
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: '" . $icon . "',
            title: '" . addslashes($text) . "',
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: true
        });
    </script>
    ";
}
?>
<!-- This is where page-specific scripts would be printed if needed -->
<?php if (isset($page_scripts)) { echo $page_scripts; } ?>
<script>
function fetchNotifications() {
    fetch("fetch_notifications.php")
        .then(res => res.json())
        .then(data => {
            document.getElementById('notificationCount').textContent = data.count;

            const list = document.getElementById('notificationList');
            list.innerHTML = '<li class="dropdown-header">Recent Orders</li><li><hr class="dropdown-divider"></li>';

            if (data.orders.length === 0) {
                list.innerHTML += '<li class="text-muted px-2">No new orders.</li>';
            } else {
                data.orders.forEach(order => {
                    list.innerHTML += `
                        <li class="dropdown-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${order.name}</strong><br>
                                <small class="text-muted">#${order.id} - ${order.time}</small>
                            </div>
                            <a href="orders.php?id=${order.id}" class="btn btn-sm btn-outline-primary">View</a>
                        </li>`;
                });
            }
        });
}

// Initial load and repeat every 20 seconds
fetchNotifications();
setInterval(fetchNotifications, 20000);
</script>

<!-- ======================================================= -->
<!-- END OF NEW SCRIPT SECTION -->
<!-- ======================================================= -->

</body>
</html>