<?php
// This is a backend-only file.

// --- STEP 1: Include the necessary files ---
require_once $_SERVER['DOCUMENT_ROOT'] . '/digital_agent/_partials/db.php';
require_once __DIR__ . '/_partials/activity_helper.php'; // Include our new activity logger

// It's good practice to call session_start() if it's not in your db.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Basic security - ensure an admin is logged in
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_role']) !== 'admin') {
    exit('Access Denied');
}

$action = $_GET['action'] ?? '';

// This part of the file generates HTML and does not need logging. It remains unchanged.
if ($action == 'get_sub_items') {
    $parent_id = isset($_GET['parent_id']) ? (int)$_GET['parent_id'] : 0;
    
    $stmt = $conn->prepare("SELECT id, name, category, price, status FROM services WHERE parent_id = ? ORDER BY name");
    $stmt->bind_param("i", $parent_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $html = '<div class="table-responsive"><table class="table table-hover mb-0">';
        $html .= '<thead><tr><th>Name</th><th>Price</th><th class="text-center">Status</th><th class="text-end">Actions</th></tr></thead><tbody>';
        while ($row = $result->fetch_assoc()) {
            $html .= '<tr>';
            $html .= '<td class="align-middle">' . htmlspecialchars($row['name']) . '</td>';
            $html .= '<td class="align-middle">' . ($row['price'] > 0 ? 'GH₵ ' . number_format($row['price'], 2) : '---') . '</td>';
            $html .= '<td class="text-center align-middle">';
            // The redirect URL for the toggle now includes the parent_id to return to the correct view
            $html .= '<div class="form-check form-switch d-inline-block"><input class="form-check-input" type="checkbox" ' . ($row['status'] == 'enabled' ? 'checked' : '') . ' onclick="location.href=\'service_handler.php?action=toggle_status&id=' . $row['id'] . '&parent_id=' . $parent_id . '\'"></div>';
            $html .= '</td>';
            $html .= '<td class="text-end">';
            if ($row['price'] == 0.00) {
                $html .= '<a href="services.php?parent_id=' . $row['id'] . '" class="btn btn-sm btn-secondary">Manage Items</a> ';
            }
            $html .= '<a href="service_edit.php?id=' . $row['id'] . '" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>';
            $html .= '</td></tr>';
        }
        $html .= '</tbody></table></div>';
    } else {
        $html = '<div class="text-center p-5 text-muted">No items found in this category.</div>';
    }
    echo $html;
    exit();
}


// --- This is the part we will modify for logging ---
if ($action == 'toggle_status') {
    $id = (int)$_GET['id'];
    // --- STEP 2: Enhance the query to get service name for logging ---
    $stmt_current = $conn->prepare("SELECT name, status, parent_id FROM services WHERE id = ?");
    $stmt_current->bind_param("i", $id);
    $stmt_current->execute();
    $service = $stmt_current->get_result()->fetch_assoc();
    
    if ($service) {
        $new_status = ($service['status'] == 'enabled') ? 'disabled' : 'enabled';
        $stmt_update = $conn->prepare("UPDATE services SET status = ? WHERE id = ?");
        $stmt_update->bind_param("si", $new_status, $id);
        
        if ($stmt_update->execute()) {
            // --- STEP 3: Log the status toggle activity ---
            $log_description = "Toggled status of service '" . $service['name'] . "' to '" . $new_status . "'.";
            logActivity($conn, $log_description);
            // --- END OF LOGGING ---

            $_SESSION['message'] = "Status updated successfully.";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Failed to update status.";
            $_SESSION['message_type'] = "error";
        }
    } else {
        $_SESSION['message'] = "Service not found.";
        $_SESSION['message_type'] = "error";
    }

    // Redirect back to the main services page, preserving the parent view
    $parent_id_redirect = (int)($_GET['parent_id'] ?? 0);
    header("Location: services.php?parent_id=" . $parent_id_redirect);
    exit();
}
?>