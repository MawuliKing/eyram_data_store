<?php
// Start the session to access session variables
session_start();

// Unset all of the session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to the login page
// We define a BASE_URL constant in db.php but this file doesn't include it,
// so we build the URL manually for simplicity here.
header("Location: login.php");
exit;
?>