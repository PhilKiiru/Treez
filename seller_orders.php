<?php
session_start();
include("db.php");

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "SELLER") {
    header("Location: login.php");
    exit();
}

$seller_id = intval($_SESSION["user_id"]);

$filter_status = $_GET['filter_status'] ?? '';
$where_status = '';
if ($filter_status === 'accepted') {
    $where_status = " AND o.ORDER_STATUS = 'PROCESSING' ";
}
$orders = mysqli_query($db, "
    SELECT o.ORDER_ID, o.ORDER_DATE, o.ORDER_STATUS, o.TOTAL_PRICE,
           u.USERNAME AS buyer, u.PHONE AS buyer_phone,
           t.COMMON_NAME, t.SCIENTIFIC_NAME, t.DESCRIPTION, od.QUANTITY, od.PRICE
    FROM orders o
    JOIN users u ON o.BUYER_ID = u.USER_ID
    JOIN orderdetails od ON o.ORDER_ID = od.ORDER_ID
    JOIN treespecies t ON od.TREESPECIES_ID = t.TREESPECIES_ID
    WHERE t.SELLER_ID = $seller_id $where_status
    ORDER BY DATE(o.ORDER_DATE) DESC, o.ORDER_DATE DESC
");


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accept_order_id'])) {
        $oid = intval($_POST['accept_order_id']);
        $details = mysqli_query($db, "SELECT TREESPECIES_ID, QUANTITY FROM orderdetails WHERE ORDER_ID = $oid");
        while ($d = mysqli_fetch_assoc($details)) {
            $tree_id = intval($d['TREESPECIES_ID']);
            $qty = intval($d['QUANTITY']);
            $check = mysqli_query($db, "SELECT STOCK FROM treespecies WHERE TREESPECIES_ID = $tree_id AND SELLER_ID = $seller_id");
            if ($row = mysqli_fetch_assoc($check)) {
                $current_stock = intval($row['STOCK']);
                if ($current_stock >= $qty) {
                    mysqli_query($db, "UPDATE treespecies SET STOCK = STOCK - $qty WHERE TREESPECIES_ID = $tree_id AND SELLER_ID = $seller_id");
                }
            }
        }
       
        $update = mysqli_prepare($db, "UPDATE orders SET ORDER_STATUS='PROCESSING' WHERE ORDER_ID=? AND ORDER_STATUS='PENDING'");
        mysqli_stmt_bind_param($update, "i", $oid);
        mysqli_stmt_execute($update);
        mysqli_stmt_close($update);
        header("Location: seller_orders.php");
        exit();
    } elseif (isset($_POST['delete_order_id'])) {
        $oid = intval($_POST['delete_order_id']);
        $update = mysqli_prepare($db, "UPDATE orders SET ORDER_STATUS='CANCELLED' WHERE ORDER_ID=? AND (ORDER_STATUS='PENDING' OR ORDER_STATUS='PROCESSING')");
        mysqli_stmt_bind_param($update, "i", $oid);
        mysqli_stmt_execute($update);
        mysqli_stmt_close($update);
        header("Location: seller_orders.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Seller Orders</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container-fluid">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <li class="nav-item">
                <a class="nav-link fw-bold text-white" href="seller.php">&larr; Back to Seller Dashboard</a>
            </li>
        </ul>
        <span class="navbar-text text-white ms-auto">
            Welcome, <?= htmlspecialchars($_SESSION["username"]); ?> (Seller)
        </span>
        <a href="logout.php" class="btn btn-outline-light ms-3">Logout</a>
    </div>
</nav>
<div class="container">
    <h3 class="mb-3">Orders for Your Seedlings</h3>
    <form method="GET" class="mb-3">
        <div class="input-group" style="max-width:350px;">
            <label class="input-group-text" for="filter_status">Show</label>
            <select name="filter_status" id="filter_status" class="form-select" onchange="this.form.submit()">
                <option value="">All Orders</option>
                <option value="accepted"<?= ($filter_status==='accepted'?' selected':'') ?>>Accepted Orders</option>
            </select>
        </div>
    </form>
    <?php if (mysqli_num_rows($orders) > 0): ?>
        <?php
        $last_date = null;
        while ($row = mysqli_fetch_assoc($orders)):
            $order_date = date('Y-m-d', strtotime($row['ORDER_DATE']));
            if ($order_date !== $last_date) {
                if ($last_date !== null) echo '</tbody></table>';
                echo '<h5 class="mt-4 mb-2">' . date('l, F j, Y', strtotime($order_date)) . '</h5>';
                echo '<table class="table table-hover table-bordered"><thead class="table-dark"><tr>';
                echo '<th>Order ID</th><th>Time</th><th>Status</th><th>Buyer</th><th>Phone</th><th>Tree</th><th>Qty</th><th>Price</th><th>Total</th><th>Description</th><th>Action</th>';
                echo '</tr></thead><tbody>';
                $last_date = $order_date;
            }
            $item_total = $row['PRICE'] * $row['QUANTITY'];
            if ($row['ORDER_STATUS'] == 'PROCESSING') {
                $badge = 'info';
                $status_text = 'Accepted';
            } elseif ($row['ORDER_STATUS'] == 'PENDING') {
                $badge = 'warning text-dark';
                $status_text = 'Pending';
            } elseif ($row['ORDER_STATUS'] == 'DELIVERED') {
                $badge = 'success';
                $status_text = 'Delivered';
            } elseif ($row['ORDER_STATUS'] == 'CANCELLED') {
                $badge = 'danger';
                $status_text = 'Cancelled';
            } else {
                $badge = 'secondary';
                $status_text = htmlspecialchars($row['ORDER_STATUS']);
            }
        ?>
        <tr>
            <td><?= $row['ORDER_ID'] ?></td>
            <td><?= date('H:i', strtotime($row['ORDER_DATE'])) ?></td>
            <td><span class="badge bg-<?= $badge ?>"><?= $status_text ?></span></td>
            <td><?= htmlspecialchars($row['buyer']) ?></td>
            <td><?= htmlspecialchars($row['buyer_phone']) ?></td>
            <td><?= htmlspecialchars($row['COMMON_NAME']) ?><br><em><?= htmlspecialchars($row['SCIENTIFIC_NAME']) ?></em></td>
            <td><?= $row['QUANTITY'] ?></td>
            <td><?= $row['PRICE'] ?></td>
            <td><strong><?= $item_total ?></strong></td>
            <td><?= htmlspecialchars($row['DESCRIPTION']) ?></td>
            <td>
                <?php if ($row['ORDER_STATUS'] == 'PENDING'): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="accept_order_id" value="<?= $row['ORDER_ID'] ?>">
                        <button type="submit" class="btn btn-sm btn-success mb-1">Accept</button>
                    </form>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="delete_order_id" value="<?= $row['ORDER_ID'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger mb-1" onclick="return confirm('Are you sure you want to cancel this order?');">Delete</button>
                    </form>
                <?php elseif ($row['ORDER_STATUS'] == 'PROCESSING'): ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="delete_order_id" value="<?= $row['ORDER_ID'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger mb-1" onclick="return confirm('Are you sure you want to cancel this order?');">Delete</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; if ($last_date !== null) echo '</tbody></table>'; ?>
    <?php else: ?>
        <div class="alert alert-info">No orders for your seedlings yet.</div>
    <?php endif; ?>
</div>
</body>
</html>
