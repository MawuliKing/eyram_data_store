<?php
// agent/_partials/activity_helper.php

/**
 * Logs an activity to the database for the currently logged-in user.
 *
 * @param mysqli $conn The database connection object.
 * @param string $description A description of the action.
 * @return bool True on success, false on failure.
 */
function logAgentActivity($conn, $description) {
    // We get user_id and role from the session.
    // Ensure a session has been started before calling this function.
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $user_id = $_SESSION['user_id'] ?? 0;
    $user_role = $_SESSION['user_role'] ?? 'Unknown';

    if (empty($conn) || empty($user_id) || empty($description)) {
        return false;
    }

    $sql = "INSERT INTO activity_log (user_id, user_role, action_description) VALUES (?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("iss", $user_id, $user_role, $description);
        $stmt->execute();
        $stmt->close();
        return true;
    }
    return false;
}
?>