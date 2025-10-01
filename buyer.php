<?php  
session_start();
include("db.php");

if(!isset($_SESSION["user_id"]) || $_SESSION["role"] != "BUYER") {
    header("Location: login.php");
    exit();
}

$buyer_id = intval($_SESSION["user_id"]);

if(!isset($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}

// ----------------- ADD TO CART ------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_to_cart"])) {
    $tree_id = intval($_POST["tree_id"]);
    $quantity = intval($_POST["quantity"]);

    if($quantity > 0){
        $_SESSION["cart"][$tree_id] = ($_SESSION["cart"][$tree_id] ?? 0) + $quantity;
    }
    header("Location: buyer.php");
    exit();
}

// ----------------- PLACE ORDER ------------------
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["place_order"])) {
    if (!empty($_SESSION["cart"])) {
        mysqli_begin_transaction($db);
        try {
            $grand_total = 0;
            foreach($_SESSION["cart"] as $tree_id => $qty) {
                $stmt = mysqli_prepare($db, "SELECT PRICE, STOCK FROM treespecies WHERE TREESPECIES_ID=?");
                mysqli_stmt_bind_param($stmt, "i", $tree_id);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                if($row = mysqli_fetch_assoc($res)) {
                    if ($row['STOCK'] < $qty) throw new Exception("Not enough stock");
                    $grand_total += $row['PRICE'] * $qty;
                }
                mysqli_stmt_close($stmt);
            }

            $stmt = mysqli_prepare($db, "INSERT INTO orders (BUYER_ID, TOTAL_PRICE, ORDER_STATUS, ORDER_DATE) VALUES (?, ?, 'PENDING', NOW())");
            mysqli_stmt_bind_param($stmt, "id", $buyer_id, $grand_total);
            mysqli_stmt_execute($stmt);
            $order_id = mysqli_insert_id($db);
            mysqli_stmt_close($stmt);

            foreach($_SESSION["cart"] as $tree_id => $qty) {
                $stmt = mysqli_prepare($db, "SELECT PRICE FROM treespecies WHERE TREESPECIES_ID=?");
                mysqli_stmt_bind_param($stmt, "i", $tree_id);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                if($r = mysqli_fetch_assoc($res)) {
                    $price = $r['PRICE'];
                    $stmt2 = mysqli_prepare($db, "INSERT INTO orderdetails (ORDER_ID, TREESPECIES_ID, QUANTITY, PRICE) VALUES (?, ?, ?, ?)");
                    mysqli_stmt_bind_param($stmt2, "iiid", $order_id, $tree_id, $qty, $price);
                    mysqli_stmt_execute($stmt2);
                    mysqli_stmt_close($stmt2);

                    $stmt3 = mysqli_prepare($db, "UPDATE treespecies SET STOCK=STOCK-? WHERE TREESPECIES_ID=? AND STOCK>=?");
                    mysqli_stmt_bind_param($stmt3, "iii", $qty, $tree_id, $qty);
                    mysqli_stmt_execute($stmt3);
                    mysqli_stmt_close($stmt3);
                }
            }

            mysqli_commit($db);
            $_SESSION["cart"] = [];
            $success_msg = "Order placed successfully!";
        } catch (Exception $e) {
            mysqli_rollback($db);
            $error_msg = "Order failed: ".$e->getMessage();
        }
    }
}

// ----------------- CANCEL ORDER ------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["cancel_order"])) {
    $order_id = intval($_POST["order_id"]);
    $stmt = mysqli_prepare($db, "UPDATE orders SET ORDER_STATUS='CANCELLED' WHERE ORDER_ID=? AND BUYER_ID=? AND ORDER_STATUS='PENDING'");
    mysqli_stmt_bind_param($stmt, "ii", $order_id, $buyer_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("Location: buyer.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Buyer Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-success mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Treez Buyer</a>
        <span class="navbar-text text-white ms-auto">
            Welcome, <?= htmlspecialchars($_SESSION["username"]); ?> (Buyer)
        </span>
        <a href="logout.php" class="btn btn-outline-light ms-3">Logout</a>
    </div>
</nav>

<div class="container">
    <?php if(isset($success_msg)) echo "<div class='alert alert-success'>$success_msg</div>"; ?>
    <?php if(isset($error_msg)) echo "<div class='alert alert-danger'>$error_msg</div>"; ?>

    <!-- Available Trees -->
    <h3>Available Trees</h3>
    <div class="row">
    <?php
    $result = mysqli_query($db, "SELECT * FROM treespecies WHERE STOCK > 0");
    while($row = mysqli_fetch_assoc($result)) {
        echo "
        <div class='col-md-4'>
            <div class='card mb-4 shadow-sm'>
                <img src='".htmlspecialchars($row['IMAGE'])."' class='card-img-top' style='height:200px; object-fit:cover;'>
                <div class='card-body'>
                    <h5 class='card-title'>".htmlspecialchars($row['COMMON_NAME'])."</h5>
                    <p><em>".htmlspecialchars($row['SCIENTIFIC_NAME'])."</em></p>
                    <p><strong>KES ".htmlspecialchars($row['PRICE'])."</strong></p>
                    <p>".nl2br(htmlspecialchars($row['DESCRIPTION']))."</p>
                    <form method='POST'>
                        <input type='hidden' name='tree_id' value='{$row['TREESPECIES_ID']}'>
                        <input type='number' name='quantity' value='1' min='1' max='{$row['STOCK']}' class='form-control mb-2'>
                        <button type='submit' name='add_to_cart' class='btn btn-primary w-100'>Add to Cart</button>
                    </form>
                </div>
            </div>
        </div>";
    }
    ?>
    </div>

    <!-- Cart -->
    <h3>Your Cart</h3>
    <?php
    if(!empty($_SESSION["cart"])) {
        echo "<form method='POST'><table class='table table-bordered'><thead><tr><th>Tree</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead><tbody>";
        $grand_total = 0;
        foreach($_SESSION["cart"] as $tree_id => $qty) {
            $res = mysqli_query($db, "SELECT COMMON_NAME, PRICE FROM treespecies WHERE TREESPECIES_ID=$tree_id");
            if($r = mysqli_fetch_assoc($res)) {
                $total = $r['PRICE'] * $qty;
                $grand_total += $total;
                echo "<tr><td>{$r['COMMON_NAME']}</td><td>$qty</td><td>{$r['PRICE']}</td><td>$total</td></tr>";
            }
        }
        echo "<tr><td colspan='3'><strong>Grand Total</strong></td><td><strong>$grand_total</strong></td></tr>";
        echo "</tbody></table><button type='submit' name='place_order' class='btn btn-success'>Place Order</button></form>";
    } else echo "<div class='alert alert-info'>Your cart is empty.</div>";
    ?>

    <!-- My Orders -->
    <h3>My Orders</h3>
    <?php 
    $result = mysqli_query($db, "
    SELECT o.ORDER_ID, o.ORDER_DATE, o.ORDER_STATUS, t.COMMON_NAME, od.QUANTITY, od.PRICE
    FROM orders o
    JOIN orderdetails od ON o.ORDER_ID = od.ORDER_ID
    JOIN treespecies t ON od.TREESPECIES_ID = t.TREESPECIES_ID
    WHERE o.BUYER_ID=$buyer_id ORDER BY o.ORDER_DATE DESC");
    if(mysqli_num_rows($result) > 0) {
        echo "<table class='table table-bordered'><thead><tr><th>Order ID</th><th>Date</th><th>Status</th><th>Tree</th><th>Qty</th><th>Price</th><th>Total</th><th>Action</th></tr></thead><tbody>";
        while($row = mysqli_fetch_assoc($result)) {
            $item_total = $row['PRICE'] * $row['QUANTITY'];
            $badge = ($row['ORDER_STATUS']=="COMPLETED")?"success":(($row['ORDER_STATUS']=="PENDING")?"warning text-dark":"danger");
            echo "<tr>
                <td>{$row['ORDER_ID']}</td>
                <td>{$row['ORDER_DATE']}</td>
                <td><span class='badge bg-$badge'>{$row['ORDER_STATUS']}</span></td>
                <td>{$row['COMMON_NAME']}</td>
                <td>{$row['QUANTITY']}</td>
                <td>{$row['PRICE']}</td>
                <td><strong>$item_total</strong></td>
                <td>";
            if ($row['ORDER_STATUS']=="PENDING") {
                echo "<form method='POST'><input type='hidden' name='order_id' value='{$row['ORDER_ID']}'><button type='submit' name='cancel_order' class='btn btn-danger btn-sm'>Cancel</button></form>";
            } else {
                echo "<span class='text-muted'>-</span>";
            }
            echo "</td></tr>";
        }
        echo "</tbody></table>";
    } else echo "<div class='alert alert-info'>You have no orders yet.</div>";
    ?>
</div>
</body>
</html>
