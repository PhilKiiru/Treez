<?php
// M-Pesa Callback Handler Example
// This file receives payment results from Safaricom

echo json_encode(["ResultCode" => 0, "ResultDesc" => "Accepted"]);

include_once('db.php');
$data = file_get_contents('php://input');
file_put_contents('mpesa_callback_log.txt', $data . "\n", FILE_APPEND); // Log for debugging
$callback = json_decode($data, true);

if (isset($callback['Body']['stkCallback'])) {
    $stkCallback = $callback['Body']['stkCallback'];
    $resultCode = $stkCallback['ResultCode'];
    $resultDesc = $stkCallback['ResultDesc'];
    $amount = null;
    $mpesaReceipt = null;
    $phone = null;
    if (isset($stkCallback['CallbackMetadata']['Item'])) {
        foreach ($stkCallback['CallbackMetadata']['Item'] as $item) {
            if ($item['Name'] === 'Amount') $amount = $item['Value'];
            if ($item['Name'] === 'MpesaReceiptNumber') $mpesaReceipt = $item['Value'];
            if ($item['Name'] === 'PhoneNumber') $phone = $item['Value'];
        }
    }
    // Mark order as paid if payment is successful
    if ($resultCode == 0 && $phone && $amount) {
        // Find the most recent pending order for this phone and amount
        $phone = mysqli_real_escape_string($db, $phone);
        $amount = floatval($amount);
        $sql = "UPDATE orders o JOIN users u ON o.BUYER_ID = u.USER_ID SET o.ORDER_STATUS='PROCESSING', o.PAYMENT_REF='" . mysqli_real_escape_string($db, $mpesaReceipt) . "' WHERE u.PHONE='$phone' AND o.TOTAL_PRICE=$amount AND o.ORDER_STATUS='PENDING' ORDER BY o.ORDER_ID DESC LIMIT 1";
        mysqli_query($db, $sql);
        // Optionally, log the payment
        file_put_contents('mpesa_payments.txt', "Accepted: $amount, Receipt: $mpesaReceipt, Phone: $phone\n", FILE_APPEND);
    }
}

header('Content-Type: application/json');
echo json_encode(["ResultCode" => 0, "ResultDesc" => "Accepted"]);
