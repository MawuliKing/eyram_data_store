<?php
// download_template.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/digital_agent/_partials/config.php';
require_once BASE_PATH . '/_partials/db.php';

// Security check
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['provider_name'])) {
    die("Invalid access.");
}

$provider_name = htmlspecialchars($_POST['provider_name'] ?? 'Data');
$filename = "Bulk_Upload_Template_(" . preg_replace('/[^a-zA-Z0-9-]/', '', $provider_name) . ").csv";

// Set headers for file download
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel compatibility

$output = fopen('php://output', 'w');

// --- Write the simple, clean header row ---
fputcsv($output, ['Phone Number', 'Size']);

// --- No example data, no instructions, just a blank slate as requested ---

fclose($output);
exit();
?>