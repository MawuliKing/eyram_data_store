<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/digital_agent/admin/_partials/tcpdf/tcpdf.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/digital_agent/_partials/db.php';

// No whitespace or HTML should appear before this point

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid request.");
}

$order_id = intval($_GET['id']);

// Fetch order data
$stmt = $conn->prepare("SELECT o.order_details, u.full_name, o.created_at 
                        FROM orders o 
                        JOIN users u ON o.user_id = u.id 
                        WHERE o.id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Order not found.");
}

$order = $result->fetch_assoc();
$details = json_decode($order['order_details'], true);

// Handle nested form_data
$form_data = [];
if (isset($details['form_data'])) {
    $form_data = is_string($details['form_data']) ? json_decode($details['form_data'], true) : $details['form_data'];
}
$uploaded_files = $form_data['uploaded_files'] ?? [];
unset($form_data['uploaded_files']);

// Start PDF
$pdf = new TCPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Digital Agent');
$pdf->SetTitle('Form Submission Order #' . $order_id);
$pdf->SetMargins(15, 15, 15);
$pdf->AddPage();
$pdf->SetFont('dejavusans', '', 10);

// Header
$html = '<h2 style="text-align:center;">Form Submission Report</h2>';
$html .= '<p><strong>Order ID:</strong> ' . $order_id . '</p>';
$html .= '<p><strong>Service:</strong> ' . htmlspecialchars($details['name'] ?? 'N/A') . '</p>';
$html .= '<p><strong>Submitted By:</strong> ' . htmlspecialchars($order['full_name']) . '</p>';
$html .= '<p><strong>Date:</strong> ' . htmlspecialchars($order['created_at']) . '</p>';

// Table of Form Data
$html .= '<h4>Submitted Form Fields:</h4>';
$html .= '<table border="1" cellpadding="4">';
foreach ($form_data as $key => $value) {
    $label = ucwords(str_replace('_', ' ', $key));
    $html .= '<tr><td style="width: 30%;"><strong>' . htmlspecialchars($label) . '</strong></td><td>' . htmlspecialchars($value) . '</td></tr>';
}
$html .= '</table>';
$pdf->writeHTML($html, true, false, true, false, '');

// Show Uploaded Images (if any)
if (!empty($uploaded_files)) {
    foreach ($uploaded_files as $label => $filename) {
        $file_path = $_SERVER['DOCUMENT_ROOT'] . '/digital_agent/uploads/' . $filename;

        if (file_exists($file_path)) {
            $pdf->AddPage();
            $pdf->SetFont('dejavusans', 'B', 12);
            $pdf->Cell(0, 10, ucwords(str_replace('_', ' ', $label)), 0, 1, 'C');
            $pdf->Ln(4);
            $pdf->Image($file_path, 30, 40, 150, '', '', '', '', false, 300, '', false, false, 1, false, false, false);
        }
    }
}

// Output PDF for download
$pdf->Output("form_submission_order_{$order_id}.pdf", 'D');
exit;
