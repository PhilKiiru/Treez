<?php
// mpesa_callback.php - Handles M-Pesa payment confirmation from Safaricom
// This file should be set as your callback URL in the Daraja portal and mpesa.php

// Get the raw POST data
$data = file_get_contents('php://input');
$logFile = 'mpesa_callback_log.txt';
file_put_contents($logFile, date('Y-m-d H:i:s') . "\n" . $data . "\n\n", FILE_APPEND);

// Decode the JSON data
$callback = json_decode($data, true);

// Example: Extract result code and transaction details
$resultCode = $callback['Body']['stkCallback']['ResultCode'] ?? null;
$resultDesc = $callback['Body']['stkCallback']['ResultDesc'] ?? '';
$amount = $callback['Body']['stkCallback']['CallbackMetadata']['Item'][0]['Value'] ?? 0;
$mpesaReceipt = $callback['Body']['stkCallback']['CallbackMetadata']['Item'][1]['Value'] ?? '';
$phone = $callback['Body']['stkCallback']['CallbackMetadata']['Item'][4]['Value'] ?? '';

// Get order_id from callback URL (sent as GET param)
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : null;


// Connect to database
require_once __DIR__ . '/db.php';

// If payment is successful, record it
if ($resultCode === 0 && $order_id) {
	$stmt = $db->prepare("INSERT INTO mpesa_payments (mpesa_receipt, phone, amount, result_desc, created_at, order_id) VALUES (?, ?, ?, ?, NOW(), ?)");
	$stmt->bind_param('ssdsi', $mpesaReceipt, $phone, $amount, $resultDesc, $order_id);
	$stmt->execute();
	$stmt->close();
}

// Respond to Safaricom (MUST return a 200 OK)
header('Content-Type: application/json');
echo json_encode(["ResultCode" => 0, "ResultDesc" => "Accepted"]);
?>
