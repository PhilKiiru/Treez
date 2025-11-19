<?php
// cash_payment.php - Mark order as paid by cash and redirect
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "BUYER") {
    header("Location: login.php");
    exit();
}

$order_id = intval($_GET['order_id'] ?? 0);
$buyer_id = intval($_SESSION['user_id']);

if ($order_id > 0) {
    // Only allow buyer to mark their own order as paid
    $stmt = mysqli_prepare($db, "UPDATE orders SET ORDER_STATUS='PAID (CASH)' WHERE ORDER_ID=? AND BUYER_ID=? AND ORDER_STATUS='PENDING'");
    mysqli_stmt_bind_param($stmt, "ii", $order_id, $buyer_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

header("Location: buyer.php?cash_paid=1");
exit();
?>
