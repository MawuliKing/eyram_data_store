<?php
// Include the database connection file which also starts the session
include_once '_partials/db.php';

// Check if the user is logged in by looking for a user_id in the session
if (isset($_SESSION['user_id'])) {
    // If logged in, redirect to the agent dashboard
    // We will create this file in the next phase
    header("Location: " . BASE_URL . "admin/dashboard.php");
    exit();
} else {
    // If not logged in, redirect to the login page
    header("Location: " . BASE_URL . "login.php");
    exit();
}
?>