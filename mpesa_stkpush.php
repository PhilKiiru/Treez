<?php

$consumerKey = "UqZ9EmAJvuPjjPjtCET1piEkvFZn6vQDy7YdqMLotpBhbiuO"; 
$consumerSecret = "BOc6MRTXQXCCGZddtA9r5FifOEBSxdrfKFAchutG6rXAWuIdDbDVwO3N1CgAS4MD"; 
$shortCode = '174379';
$passkey = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919'; 
$callbackUrl = 'https://yourdomain.com/mpesa_callback.php'; 



$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 1;


$phone = preg_replace('/\D/', '', $phone);
if (strpos($phone, '0') === 0) {
    $phone = '254' . substr($phone, 1);
}
if (strlen($phone) !== 12 || strpos($phone, '2547') !== 0) {
    header('Content-Type: application/json');
    echo json_encode([
        'errorCode' => '400.002.02',
        'errorMessage' => 'Bad Request - Invalid PhoneNumber',
        'phone' => $phone
    ]);
    exit;
}

$url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
$credentials = base64_encode($consumerKey . ':' . $consumerSecret);


$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Basic ' . $credentials
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$token_data = json_decode($response, true);
if ($httpcode !== 200 || !isset($token_data['access_token'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'errorCode' => '401.001.01',
        'errorMessage' => 'Failed to obtain access token',
        'httpCode' => $httpcode,
        'tokenResponse' => $token_data,
        'rawResponse' => $response
    ]);
    exit;
}
$access_token = $token_data['access_token'];


$timestamp = date('YmdHis');
$password = base64_encode($shortCode . $passkey . $timestamp);

$stkpush_url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
$request = [
    'BusinessShortCode' => $shortCode,
    'Password' => $password,
    'Timestamp' => $timestamp,
    'TransactionType' => 'CustomerPayBillOnline',
    'Amount' => $amount,
    'PartyA' => $phone,
    'PartyB' => $shortCode,
    'PhoneNumber' => $phone,
    'CallBackURL' => $callbackUrl,
    'AccountReference' => 'TreezOrder',
    'TransactionDesc' => 'Treez Payment'
];

$ch = curl_init($stkpush_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $access_token
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request));
$result = curl_exec($ch);
curl_close($ch);


header('Content-Type: text/html');
$response = json_decode($result, true);
if (isset($response['ResponseCode']) && $response['ResponseCode'] == '0') {
    echo '<div style="padding:2em;text-align:center;font-family:sans-serif;">'
        . '<h2>Payment Request Sent</h2>'
        . '<p>Please check your phone and enter your M-Pesa PIN to complete the payment.</p>'
        . '<a href="my_orders.php" class="btn btn-success mt-3">Back to My Orders</a>'
        . '</div>';
} else {
    echo '<div style="padding:2em;text-align:center;font-family:sans-serif;">'
        . '<h2>Payment Error</h2>'
        . '<p>There was a problem initiating the payment. Please try again or contact support.</p>';
    if (isset($response['errorMessage'])) echo '<div class="alert alert-danger">' . htmlspecialchars($response['errorMessage']) . '</div>';
    echo '<a href="my_orders.php" class="btn btn-secondary mt-3">Back to My Orders</a>'
        . '</div>';
}
?>
