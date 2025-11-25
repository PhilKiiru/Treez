<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "BUYER") {
    header("Location: login.php");
    exit();
}
// Fetch buyer's phone number from DB
$buyer_id = intval($_SESSION['user_id']);
function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$buyer_phone = '';
$stmt_phone = mysqli_prepare($db, "SELECT PHONE FROM users WHERE USER_ID = ?");
mysqli_stmt_bind_param($stmt_phone, "i", $buyer_id);
mysqli_stmt_execute($stmt_phone);
$res_phone = mysqli_stmt_get_result($stmt_phone);
if ($row_phone = mysqli_fetch_assoc($res_phone)) {
    $buyer_phone = preg_replace('/\D/', '', $row_phone['PHONE']); // Remove non-digits
    if (strpos($buyer_phone, '0') === 0) {
        $buyer_phone = '254' . substr($buyer_phone, 1); // Convert 07... to 2547...
    }
}
mysqli_stmt_close($stmt_phone);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>My Orders</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-success mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="buyer.php">Treez Buyer</a>
        <div class="ms-auto">
            <span class="text-white me-3">Welcome, <?= e($_SESSION["username"] ?? "") ?></span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>
<div class="container">
<div style="margin: 20px 0; text-align: center;">
    <a href="buyer.php" class="btn btn-success">&larr; Return to Dashboard</a>
</div>
<h3 class="mt-4">My Orders</h3>
<?php
$stmt = mysqli_prepare(
    $db,
    "SELECT o.ORDER_ID, o.ORDER_DATE, o.ORDER_STATUS, t.COMMON_NAME, od.QUANTITY, od.PRICE
     FROM orders o
     JOIN orderdetails od ON o.ORDER_ID = od.ORDER_ID
     JOIN treespecies t ON od.TREESPECIES_ID = t.TREESPECIES_ID
     WHERE o.BUYER_ID = ?
     ORDER BY o.ORDER_DATE DESC"
);
mysqli_stmt_bind_param($stmt, "i", $buyer_id);
mysqli_stmt_execute($stmt);
$orders = mysqli_stmt_get_result($stmt);
if (mysqli_num_rows($orders) > 0):
// Group orders by day
$orders_by_day = [];
while ($row = mysqli_fetch_assoc($orders)) {
    $date = date('Y-m-d', strtotime($row['ORDER_DATE']));
    $orders_by_day[$date][] = $row;
}
?>
<?php foreach ($orders_by_day as $day => $orders_list): ?>
    <h5 class="mt-4 mb-2 text-primary">Orders for <?= e(date('l, F j, Y', strtotime($day))); ?></h5>
    <table class="table table-bordered">
    <thead>
    <tr>
      <th>Date</th>
      <th>Status</th>
      <th>Tree</th>
      <th>Qty</th>
      <th>Price</th>
      <th>Total</th>
      <th>Action</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($orders_list as $o):
        $item_total = $o['PRICE'] * $o['QUANTITY'];
    ?>
    <tr>
        <td><?= e($o['ORDER_DATE']); ?></td>
        <td>
            <?php
            if ($o['ORDER_STATUS'] === 'PROCESSING') {
                $badge = 'info';
                $status_text = 'Accepted';
            } elseif ($o['ORDER_STATUS'] === 'COMPLETED') {
                $badge = 'success';
                $status_text = 'Completed';
            } elseif ($o['ORDER_STATUS'] === 'PENDING') {
                $badge = 'warning text-dark';
                $status_text = 'Pending';
            } elseif ($o['ORDER_STATUS'] === 'CANCELLED') {
                $badge = 'danger';
                $status_text = 'Cancelled';
            } else {
                $badge = 'secondary';
                $status_text = e($o['ORDER_STATUS']);
            }
            ?>
            <span class="badge bg-<?= $badge; ?>">
                <?= $status_text; ?>
            </span>
        </td>
        <td><?= e($o['COMMON_NAME']); ?></td>
        <td><?= intval($o['QUANTITY']); ?></td>
        <td><?= number_format($o['PRICE'],2); ?></td>
        <td><strong><?= number_format($item_total,2); ?></strong></td>
        <td>
            <?php if ($o['ORDER_STATUS'] === "PENDING"): ?>
            <form method="POST" action="buyer.php" style="display:inline;">
                <input type="hidden" name="order_id" value="<?= intval($o['ORDER_ID']); ?>">
                <button name="cancel_order" class="btn btn-danger btn-sm" onclick="return confirm('Cancel this order?');">Cancel</button>
            </form>
            <form method="POST" action="buyer.php" style="display:inline; margin-left:5px;">
                <input type="hidden" name="order_id" value="<?= intval($o['ORDER_ID']); ?>">
                <input type="hidden" name="pay_with_cash" value="1">
                <button type="submit" class="btn btn-warning btn-sm">Pay with Cash</button>
            </form>
            <form method="POST" action="mpesa_stkpush.php" style="display:inline; margin-left:5px;">
                <input type="hidden" name="order_id" value="<?= intval($o['ORDER_ID']); ?>">
                <input type="hidden" name="amount" value="<?= number_format($item_total,2,'.',''); ?>">
                <input type="hidden" name="phone" value="<?= e($buyer_phone) ?>">
                <button type="submit" class="btn btn-success btn-sm">Pay with M-Pesa</button>
            </form>
            <?php else: ?>
            <span class="text-muted">-</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
<?php endforeach; ?>

<?php else: ?>
<div class="alert alert-info">No orders yet.</div>
<?php endif; mysqli_stmt_close($stmt); ?>
</div>
</body>
</html>
