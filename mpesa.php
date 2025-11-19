<?php
// mpesa.php - Example M-Pesa STK Push integration (Sandbox)
// You must replace the credentials and URLs with your own from Safaricom Daraja Portal

// 1. Set your credentials and endpoint
$consumerKey = 'Cc6UztExSLtGBHap6xj0ADaejdNy2bK6wdqtnQBHviAOS839';
$consumerSecret = 'URFRq2Gw2KuJsHEK8RtPPgnPqxMTPPYhuWYbALyAZerLNknQVuWol7NtTUOSlnYf';
$BusinessShortCode = '174379'; // Test paybill
$Passkey = 'bfb279f9aa9bdbcf158e97dd71a467cd2c2c8fa7b6b890c9e8fbbd3c02c6a0f9';

// Use local callback for testing
$callbackUrl = 'http://localhost/Treez/mpesa_callback.php';

// 2. Get access token
define('API_URL', 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');
$credentials = base64_encode($consumerKey . ':' . $consumerSecret);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, API_URL);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$access_token = json_decode($response)->access_token;
curl_close($ch);

// 3. Prepare STK Push request
$phone = $_POST['phone'] ?? '';
$amount = $_POST['amount'] ?? ($_GET['amount'] ?? '');
$order_id = $_POST['order_id'] ?? ($_GET['order_id'] ?? '');
if (!$phone || !$amount || !$order_id) {
    // Show a simple form for testing
    echo '<form method="post">';
    echo '<label>Order ID: <input name="order_id" value="' . htmlspecialchars($order_id) . '" required></label><br>';
    echo '<label>Phone (format: 2547XXXXXXXX): <input name="phone" required></label><br>';
    echo '<label>Amount: <input name="amount" type="number" min="1" value="' . htmlspecialchars($amount) . '" required></label><br>';
    echo '<button type="submit">Pay with M-Pesa</button>';
    echo '</form>';
    echo '<p>Enter your order ID, phone number, and amount to test M-Pesa STK Push (sandbox).</p>';
    exit;
}
$timestamp = date('YmdHis');
$password = base64_encode($BusinessShortCode . $Passkey . $timestamp);

$stkPushUrl = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

$stkPushData = [
    'BusinessShortCode' => $BusinessShortCode,
    'Password' => $password,
    'Timestamp' => $timestamp,
    'TransactionType' => 'CustomerPayBillOnline',
    'Amount' => $amount,
    'PartyA' => $phone,
    'PartyB' => $BusinessShortCode,
    'PhoneNumber' => $phone,
    'CallBackURL' => $callbackUrl . '?order_id=' . urlencode($order_id),
    'AccountReference' => 'TreezOrder',
    'TransactionDesc' => 'Treez Payment'
];

$ch = curl_init($stkPushUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $access_token
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($stkPushData));
$response = curl_exec($ch);
curl_close($ch);

// 4. Show response (for demo)
$respArr = json_decode($response, true);
echo '<pre>';
print_r($respArr);
echo '</pre>';
if (isset($respArr['errorCode'])) {
    echo '<p style="color:red;">Error: ' . htmlspecialchars($respArr['errorMessage']) . '</p>';
} else {
    echo '<p style="color:green;">STK Push sent. Check your phone to complete the payment.</p>';
}

// In production, redirect or show a user-friendly message
?>
