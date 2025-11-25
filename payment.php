<?php
// payment.php - Payment options page after order submission
// Expects order_id, amount, and phone via GET or POST

$order_id = $_GET['order_id'] ?? $_POST['order_id'] ?? '';
$amount = $_GET['amount'] ?? $_POST['amount'] ?? '';
$phone = $_GET['phone'] ?? $_POST['phone'] ?? '';

if (!$order_id || !$amount || !$phone) {
    echo '<div style="color:red;">Missing order details. Please go back and try again.</div>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Choose Payment Method</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container" style="max-width:500px;margin:40px auto;text-align:center;">
        <h2>Choose Payment Method</h2>
        <form action="mpesa.php" method="post" style="margin-bottom:20px;">
            <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order_id); ?>">
            <input type="hidden" name="amount" value="<?php echo htmlspecialchars($amount); ?>">
            <input type="hidden" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
            <button type="submit" class="btn btn-success" style="width:100%;padding:15px;font-size:18px;">Pay with M-Pesa</button>
        </form>
        <form action="cash_payment.php" method="post">
            <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order_id); ?>">
            <input type="hidden" name="amount" value="<?php echo htmlspecialchars($amount); ?>">
            <input type="hidden" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
            <button type="submit" class="btn btn-primary" style="width:100%;padding:15px;font-size:18px;">Pay with Cash</button>
        </form>
        <form action="manual_mpesa.php" method="get" style="margin-top:20px;">
            <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order_id); ?>">
            <input type="hidden" name="amount" value="<?php echo htmlspecialchars($amount); ?>">
            <input type="hidden" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
            <button type="submit" class="btn btn-warning" style="width:100%;padding:15px;font-size:18px;">Manual M-Pesa Payment</button>
        </form>
    </div>
</body>
</html>
