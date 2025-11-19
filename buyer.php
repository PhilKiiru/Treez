
<?php
// buyer.php – Fully rewritten & optimized

session_start();
require_once "db.php";
require_once "recommend.php";

// ---------------- AUTH CHECK ----------------
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "BUYER") {
    header("Location: login.php");
    exit();
}

$buyer_id = intval($_SESSION['user_id']);
$success_msg = $success_msg ?? null;
$error_msg   = $error_msg ?? null;

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

// Helper for safe output
function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// ------------------------------------------------
// REMOVE FROM CART
// ------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_from_cart'])) {
    $tree_id = intval($_POST['tree_id'] ?? 0);
    if ($tree_id > 0 && isset($_SESSION['cart'][$tree_id])) {
        unset($_SESSION['cart'][$tree_id]);
    }
    header("Location: buyer.php");
    exit();
}

// ------------------------------------------------
// ADD TO CART
// ------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {

    $tree_id  = intval($_POST['tree_id'] ?? 0);
    $quantity = max(1, intval($_POST['quantity'] ?? 1));

    if ($tree_id > 0) {
        $_SESSION['cart'][$tree_id] = ($_SESSION['cart'][$tree_id] ?? 0) + $quantity;
    }

    header("Location: buyer.php");
    exit();
}

// ------------------------------------------------
// PLACE ORDER
// ------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["place_order"])) {

    if (!empty($_SESSION["cart"])) {

        // Start transaction
        mysqli_begin_transaction($db);

        try {
            $grand_total = 0;

            // Validate stock and compute total
            foreach ($_SESSION["cart"] as $tree_id => $qty) {
                $stmt = mysqli_prepare($db, "SELECT PRICE, STOCK FROM treespecies WHERE TREESPECIES_ID=?");
                mysqli_stmt_bind_param($stmt, "i", $tree_id);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($res);
                mysqli_stmt_close($stmt);

                if (!$row) throw new Exception("Tree not found.");
                if ($row['STOCK'] < $qty) throw new Exception("Not enough stock.");

                $grand_total += floatval($row['PRICE']) * $qty;
            }

            // Insert order
            $stmt = mysqli_prepare($db,
                "INSERT INTO orders (BUYER_ID, TOTAL_PRICE, ORDER_STATUS, ORDER_DATE)
                 VALUES (?, ?, 'PENDING', NOW())"
            );
            mysqli_stmt_bind_param($stmt, "id", $buyer_id, $grand_total);
            mysqli_stmt_execute($stmt);
            $order_id = mysqli_insert_id($db);
            mysqli_stmt_close($stmt);

            if (!$order_id) throw new Exception("Order creation failed.");

            // Insert details & update stock
            foreach ($_SESSION["cart"] as $tree_id => $qty) {

                // Get price
                $stmt = mysqli_prepare($db, "SELECT PRICE FROM treespecies WHERE TREESPECIES_ID=?");
                mysqli_stmt_bind_param($stmt, "i", $tree_id);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $row = mysqli_fetch_assoc($res);
                mysqli_stmt_close($stmt);

                $price = floatval($row['PRICE']);

                // Insert detail
                $stmt = mysqli_prepare($db,
                    "INSERT INTO orderdetails (ORDER_ID, TREESPECIES_ID, QUANTITY, PRICE)
                     VALUES (?, ?, ?, ?)"
                );
                mysqli_stmt_bind_param($stmt, "iiid", $order_id, $tree_id, $qty, $price);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                // Update stock
                $stmt = mysqli_prepare($db,
                    "UPDATE treespecies SET STOCK = STOCK - ? WHERE TREESPECIES_ID=? AND STOCK >= ?"
                );
                mysqli_stmt_bind_param($stmt, "iii", $qty, $tree_id, $qty);
                mysqli_stmt_execute($stmt);
                $affected = mysqli_stmt_affected_rows($stmt);
                mysqli_stmt_close($stmt);

                if ($affected === 0) throw new Exception("Stock update failed.");
            }

            mysqli_commit($db);
            $_SESSION['cart'] = [];
            $success_msg = "Order placed successfully!";
            header("Location: buyer.php");
            exit();

        } catch (Exception $e) {
            mysqli_rollback($db);
            $error_msg = "Order failed: " . e($e->getMessage());
        }

    } else {
        $error_msg = "Cart is empty.";
    }
}


// ------------------------------------------------
// CANCEL ORDER
// ------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["cancel_order"])) {

    $order_id = intval($_POST["order_id"] ?? 0);

    if ($order_id > 0) {
        $stmt = mysqli_prepare(
            $db,
            "UPDATE orders SET ORDER_STATUS='CANCELLED'
             WHERE ORDER_ID=? AND BUYER_ID=? AND ORDER_STATUS='PENDING'"
        );
        mysqli_stmt_bind_param($stmt, "ii", $order_id, $buyer_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    header("Location: buyer.php");
    exit();
}

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Buyer Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

<style>
.recs-wrap {
    display:flex;
    gap:12px;
    overflow-x:auto;
    padding:8px 0;
}
.rec-card {
    min-width:150px;
    background:#fff;
    border-radius:8px;
    padding:8px;
    border: 1px solid #ddd;
    text-align:center;
}
.rec-card img {
    width:100%;
    height:110px;
    object-fit:cover;
    border-radius:6px;
}
</style>
</head>

<body class="bg-light">

<nav class="navbar navbar-dark bg-success mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Treez Buyer</a>

        <div class="ms-auto">
            <span class="text-white me-3">
                Welcome, <?= e($_SESSION["username"]); ?>
            </span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container">

<?php if ($success_msg): ?>
<div class="alert alert-success"><?= e($success_msg); ?></div>
<?php endif; ?>

<?php if ($error_msg): ?>
<div class="alert alert-danger"><?= e($error_msg); ?></div>
<?php endif; ?>


<!-- ------------------------------------------------------------
     AVAILABLE TREES
------------------------------------------------------------ -->
<h3>Available Trees</h3>
<div class="row">

<?php
$stmt = mysqli_prepare(
    $db,
    "SELECT TREESPECIES_ID, COMMON_NAME, SCIENTIFIC_NAME, PRICE, STOCK, DESCRIPTION, IMAGE
     FROM treespecies
     WHERE STOCK > 0"
);
mysqli_stmt_execute($stmt);
$trees = mysqli_stmt_get_result($stmt);

while ($tree = mysqli_fetch_assoc($trees)):
?>
  <div class="col-md-4">
    <div class="card mb-4 shadow-sm">


     <a href="tree_details.php?id=<?= intval($tree['TREESPECIES_ID']); ?>">
       <img src="<?= e($tree['IMAGE']); ?>"
           class="card-img-top"
           style="height:200px; object-fit:cover;">
     </a>


            <div class="card-body">
                <h5 style="text-align:center;">
                    <a href="tree_details.php?id=<?= intval($tree['TREESPECIES_ID']); ?>" style="text-decoration:none; color:inherit;">
                        <?= e($tree['COMMON_NAME']); ?>
                    </a>
                </h5>







      </div>
    </div>
  </div>
<?php endwhile; mysqli_stmt_close($stmt); ?>
</div>


<!-- ------------------------------------------------------------
     CART
------------------------------------------------------------ -->
<h3>Your Cart</h3>

<?php if (!empty($_SESSION['cart'])): ?>
<form method="POST">
<table class="table table-bordered">
<thead>
<tr>
  <th>Tree</th>
  <th>Qty</th>
  <th>Price</th>
  <th>Total</th>
</tr>
</thead>
<tbody>

<?php
$grand_total = 0;
foreach ($_SESSION['cart'] as $tree_id => $qty):

    $stmt = mysqli_prepare($db,
        "SELECT COMMON_NAME, PRICE FROM treespecies WHERE TREESPECIES_ID=?"
    );
    mysqli_stmt_bind_param($stmt, "i", $tree_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $t = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    $line_total = $t['PRICE'] * $qty;
    $grand_total += $line_total;
?>
<tr>
        <td><?= e($t['COMMON_NAME']); ?></td>
        <td><?= intval($qty); ?></td>
        <td><?= number_format($t['PRICE'],2); ?></td>
        <td><?= number_format($line_total,2); ?></td>
        <td>
            <form method="POST" style="display:inline;">
                <input type="hidden" name="tree_id" value="<?= intval($tree_id); ?>">
                <button name="remove_from_cart" class="btn btn-danger btn-sm" onclick="return confirm('Remove this item from cart?');">Remove</button>
            </form>
        </td>
</tr>
<?php endforeach; ?>

<tr>
  <td colspan="3"><strong>Grand Total</strong></td>
  <td><strong><?= number_format($grand_total,2); ?></strong></td>
</tr>
</tbody>
</table>

<button name="place_order" class="btn btn-success">Place Order</button>
</form>

<?php else: ?>
<div class="alert alert-info">Your cart is empty.</div>
<?php endif; ?>


<!-- ------------------------------------------------------------
     PAST ORDERS
------------------------------------------------------------ -->
<h3 class="mt-4">My Orders</h3>

<?php
$stmt = mysqli_prepare(
    $db,
    "SELECT o.ORDER_ID, o.ORDER_DATE, o.ORDER_STATUS,
            t.COMMON_NAME, od.QUANTITY, od.PRICE
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
?>
<table class="table table-bordered">
<thead>
<tr>
  <th>ID</th>
  <th>Date</th>
  <th>Status</th>
<th>Tree</th>
<th>Qty</th>
<th>Price</th>
<th>Total</th>
<th>Action</th>
  <th></th>
</tr>
</thead>
<tbody>

<?php while ($o = mysqli_fetch_assoc($orders)):
    if ($o['ORDER_STATUS'] === 'CANCELLED') continue; // Skip cancelled orders
    $item_total = $o['PRICE'] * $o['QUANTITY'];
?>
<tr>
    <td><?= intval($o['ORDER_ID']); ?></td>
    <td><?= e($o['ORDER_DATE']); ?></td>
    <td>
        <?php
        $badge = match($o['ORDER_STATUS']) {
                'COMPLETED' => 'success',
                'PENDING'   => 'warning text-dark',
                default     => 'danger'
        };
        ?>
        <span class="badge bg-<?= $badge; ?>">
            <?= e($o['ORDER_STATUS']); ?>
        </span>
    </td>

    <td><?= e($o['COMMON_NAME']); ?></td>
    <td><?= intval($o['QUANTITY']); ?></td>
    <td><?= number_format($o['PRICE'],2); ?></td>
    <td><strong><?= number_format($item_total,2); ?></strong></td>

    <td>
        <?php if ($o['ORDER_STATUS'] === "PENDING"): ?>
        <form method="POST">
            <input type="hidden" name="order_id"
                         value="<?= intval($o['ORDER_ID']); ?>">
            <button name="cancel_order"
                            class="btn btn-danger btn-sm"
                            onclick="return confirm('Cancel this order?');">
                Cancel
            </button>
        </form>
        <?php else: ?>
    <span class="text-muted">-</span>
    <?php endif; ?>
  </td>
</tr>
<?php endwhile; ?>

</tbody>
</table>

<?php else: ?>
<div class="alert alert-info">No orders yet.</div>
<?php endif; mysqli_stmt_close($stmt); ?>

</div>
</body>
</html>
