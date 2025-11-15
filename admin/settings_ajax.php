<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/digital_agent/_partials/db.php';
header('Content-Type: application/json');

// Get values
$support_number = trim($_POST['support_number'] ?? '');
$whatsapp_support_number = trim($_POST['support_whatsapp'] ?? ''); // now this is a number
$whatsapp_community_link = trim($_POST['whatsapp_community_link'] ?? ''); // this is a link

// Validation
if (!$support_number || !$whatsapp_support_number || !$whatsapp_community_link) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

// Optional: Validate phone number format (10-15 digits)
if (!preg_match('/^[0-9]{10,15}$/', $whatsapp_support_number)) {
    echo json_encode(['success' => false, 'message' => 'Enter a valid WhatsApp number (digits only).']);
    exit;
}

// Optional: Validate link format for community
if (!filter_var($whatsapp_community_link, FILTER_VALIDATE_URL)) {
    echo json_encode(['success' => false, 'message' => 'Enter a valid Join Us WhatsApp link.']);
    exit;
}

try {
    // Update settings
    $conn->query("UPDATE settings SET setting_value = '" . $conn->real_escape_string($support_number) . "' WHERE setting_key = 'support_number'");
    $conn->query("UPDATE settings SET setting_value = '" . $conn->real_escape_string($whatsapp_support_number) . "' WHERE setting_key = 'support_whatsapp'");
    $conn->query("UPDATE settings SET setting_value = '" . $conn->real_escape_string($whatsapp_community_link) . "' WHERE setting_key = 'whatsapp_community_link'");

    echo json_encode(['success' => true, 'message' => 'Settings updated successfully.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
