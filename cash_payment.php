<?php
// cash_payment.php - Mark order as accepted and processing, then show message
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "BUYER") {
    header("Location: login.php");
    exit();
}

$order_id = intval($_POST['order_id'] ?? $_GET['order_id'] ?? 0);
$buyer_id = intval($_SESSION['user_id']);
$success = false;
if ($order_id > 0) {
    $stmt = mysqli_prepare($db, "UPDATE orders SET ORDER_STATUS='PROCESSING' WHERE ORDER_ID=? AND BUYER_ID=? AND ORDER_STATUS IN ('PENDING','PAID (CASH)')");
    mysqli_stmt_bind_param($stmt, "ii", $order_id, $buyer_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    $success = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Accepted</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container" style="max-width:500px;margin:40px auto;text-align:center;">
    <?php if ($success): ?>
        <h2 style="color:green;">Order Accepted!</h2>
        <p>Your order has been accepted and your package is on the way.</p>
    <?php else: ?>
        <h2 style="color:red;">Order Error</h2>
        <p>There was a problem accepting your order. Please try again.</p>
    <?php endif; ?>
    <a href="buyer.php">Back to Dashboard</a>
</div>
</body>
</html>
