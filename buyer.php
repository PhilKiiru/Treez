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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_to_cart"])) {
    $tree_id = intval($_POST["tree_id"]);
    $quantity = intval($_POST["quantity"]);

    if($quantity > 0){
        if (isset($_SESSION["cart"][$tree_id])) {
            $_SESSION["cart"][$tree_id] += $quantity;
        } else {
            $_SESSION["cart"][$tree_id] = $quantity;
        }
    }
    header("Location: buyer.php");
    exit();
}

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["place_order"])) {
    foreach($_SESSION["cart"] as $tree_id => $qty) {
        $result = mysqli_query($db, "SELECT price FROM treespecies WHERE treespecies_id=$tree_id");
        if($row = mysqli_fetch_assoc($result)) {
            $price = floatval($row['price']);
            $total_price = $price * $qty;

            $sql = "INSERT INTO orders (BUYER_ID, TREESEEDLING_ID, QUANTITY, TOTAL_PRICE, ORDER_STATUS, ORDER_DATE) 
                    VALUES ('$buyer_id', '$tree_id', '$qty', '$total_price', 'PENDING', NOW())";
            mysqli_query($db,$sql);
        }
    }
    $_SESSION["cart"] = [];
    $success_msg = "Order placed successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buyer Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">Treez Buyer</a>
    <span class="navbar-text text-white ms-auto">
        Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?> (Buyer)
    </span>
    <a href="logout.php" class="btn btn-outline-light ms-3">Logout</a>
  </div>
</nav>

<div class="container mt-4">

    <!-- Success Message -->
    <?php if(isset($success_msg)): ?>
        <div class="alert alert-success"><?php echo $success_msg; ?></div>
    <?php endif; ?>

    <!-- Available Seedlings -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">Available Tree Seedlings</div>
        <div class="card-body">
            <?php
            $result = mysqli_query($db, "SELECT * FROM treespecies");
            if (mysqli_num_rows($result) > 0) {
                echo "<table class='table table-bordered table-striped table-hover'>
                <thead class='table-dark'>
                    <tr>
                        <th>Name</th>
                        <th>Price (KES)</th>
                        <th>Stock</th>
                        <th>Description</th>
                        <th>Action</th>
                    </tr>
                </thead><tbody>";

                while($row = mysqli_fetch_assoc($result)) {
                    echo "<tr>
                            <td>" . htmlspecialchars($row['name']) . "</td>
                            <td>" . htmlspecialchars($row['price']) . "</td>
                            <td>" . htmlspecialchars($row['stock']) . "</td>
                            <td>" . htmlspecialchars($row['description']) . "</td>
                            <td>
                                <form method='POST' action='' class='d-flex'>
                                    <input type='hidden' name='tree_id' value='" . $row['treespecies_id'] . "'>
                                    <input type='number' name='quantity' value='1' min='1' max='" . $row['stock'] . "' class='form-control me-2' required>
                                    <button type='submit' name='add_to_cart' class='btn btn-sm btn-primary'>Add to Cart</button>
                                </form>
                            </td>
                        </tr>";
                }
                echo "</tbody></table>";
            } else {
                echo "<p class='text-muted'>No seedlings available</p>";
            }
            ?>
        </div>
    </div>

    <!-- Cart -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">Your Cart</div>
        <div class="card-body">
            <?php
            if(!empty($_SESSION["cart"])) {
                echo "<form method='POST' action=''>
                    <table class='table table-bordered table-striped table-hover'>
                    <thead class='table-dark'>
                        <tr>
                            <th>Tree</th>
                            <th>Quantity</th>
                            <th>Price (KES)</th>
                            <th>Total (KES)</th>
                        </tr>
                    </thead><tbody>";
                $grand_total = 0;
                foreach($_SESSION["cart"] as $tree_id => $qty) {
                    $res = mysqli_query($db, "SELECT name, price FROM treespecies WHERE treespecies_id=$tree_id");
                    if($r = mysqli_fetch_assoc($res)) {
                        $name = $r['name'];
                        $price = $r['price'];
                        $total = $price * $qty;
                        $grand_total += $total;
                        echo "<tr>
                                <td>" . htmlspecialchars($name) . "</td>
                                <td>" . htmlspecialchars($qty) . "</td>
                                <td>" . htmlspecialchars($price) . "</td>
                                <td>" . htmlspecialchars($total) . "</td>
                              </tr>";
                    }
                }
                echo "<tr class='table-secondary'>
                        <td colspan='3'><strong>Grand Total</strong></td>
                        <td><strong>$grand_total</strong></td>
                      </tr>";
                echo "</tbody></table>
                <button type='submit' name='place_order' class='btn btn-success'>Place Order</button>
                </form>";
            } else {
                echo "<p class='text-muted'>Your cart is empty.</p>";
            }
            ?>
        </div>
    </div>

    <!-- Orders -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">My Orders</div>
        <div class="card-body">
            <?php 
            $result = mysqli_query($db, "
            SELECT o.ORDER_ID, o.ORDER_DATE, o.ORDER_STATUS, t.name AS tree_name, o.QUANTITY, o.TOTAL_PRICE
            FROM orders o
            JOIN treespecies t ON o.TREESEEDLING_ID = t.treespecies_id
            WHERE o.BUYER_ID = $buyer_id
            ORDER BY o.ORDER_DATE DESC");

            if(mysqli_num_rows($result) > 0) {
                echo "<table class='table table-bordered table-striped table-hover'>
                        <thead class='table-dark'>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Tree</th>
                                <th>Quantity</th>
                                <th>Total (KES)</th>
                            </tr>
                        </thead><tbody>";

                while ($row = mysqli_fetch_assoc($result)) {
                    echo "<tr>
                            <td>" . htmlspecialchars($row['ORDER_ID']) . "</td>
                            <td>" . htmlspecialchars($row['ORDER_DATE']) . "</td>
                            <td><span class='badge bg-warning text-dark'>" . htmlspecialchars($row['ORDER_STATUS']) . "</span></td>
                            <td>" . htmlspecialchars($row['tree_name']) . "</td>
                            <td>" . htmlspecialchars($row['QUANTITY']) . "</td>
                            <td>" . htmlspecialchars($row['TOTAL_PRICE']) . "</td>
                          </tr>";
                }
                echo "</tbody></table>";
            } else {
                echo "<p class='text-muted'>You have not placed any orders yet.</p>";
            }
            ?>
        </div>
    </div>
</div>

</body>
</html>
