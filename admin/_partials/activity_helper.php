<?php
// admin/_partials/activity_helper.php

/**
 * Logs an activity to the database.
 * This should only be called for the logged-in admin.
 */
function logActivity($conn, $description) {
    // We get user_id and role from the session, assuming it's already started.
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