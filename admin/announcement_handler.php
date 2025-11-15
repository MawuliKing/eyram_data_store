<?php
// Let's assume this file is named 'add_announcement_handler.php' or similar

require_once $_SERVER['DOCUMENT_ROOT'] . '/digital_agent/_partials/db.php';
// --- STEP 1: Include our new activity logger ---
require_once __DIR__ . '/_partials/activity_helper.php';

// It's good practice to start the session if it's not in your db.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security Check: Ensure an Admin is performing this action
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_role']) !== 'admin') {
    exit('Access Denied.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);
    $user_role = $_POST['user_role'];

    if (!empty($title) && !empty($message)) {
        $stmt = $conn->prepare("INSERT INTO announcements (title, message, user_role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $title, $message, $user_role);
        
        // Check if the insert was successful before logging
        if ($stmt->execute()) {
            // --- STEP 2: Log the announcement creation activity ---
            $log_description = "Created a new announcement titled '" . $title . "' for role: " . $user_role . ".";
            logActivity($conn, $log_description);
            // --- END OF LOGGING ---
        }
    }

    // Redirect back to the announcements page with a success message
    $_SESSION['message'] = "Announcement posted successfully.";
    $_SESSION['message_type'] = "success";
    header("Location: announcements.php");
    exit();
}

// Redirect back if the request method is not POST
header("Location: announcements.php");
exit();
?>