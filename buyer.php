<?php  

session_start();
include("db.php");

if(!isset($_SESSION["user_id"]) || $_SESSION["role"] != "BUYER") {
    header("Location: login.php");
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

            $sql = "INSERT INTO orders (BUYER_ID, TREESEEDLING_ID, QUANTITY, TOTAL_PRICE, ORDER_STATUS, ORDER_DATE) VALUES ('$buyer_id', '$tree_id', '$qty', '$total_price', 'PENDING', NOW())";
            mysqli_query($db,$sql);
        }
    }
    $_SESSION["cart"] = [];
    echo "<p style='color:green;'>Order placed successfully!</p>";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buyer Dashboard</title>
</head>
<body>
    <h2>Welcome <?php echo htmlspecialchars($_SESSION["username"]); ?> (Buyer)</h2>

    <h3>Available Tree Seedlings</h3>

    <?php
    $result = mysqli_query($db, "SELECT *FROM treespecies");
    if (mysqli_num_rows($result) > 0) {
        echo "<table border='1' cellpadding='10'>
        <tr>
            <th>Name</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Description</th>
            <th>Action</th>
        </tr>";

        while($row = mysqli_fetch_assoc($result)) {
            echo "<tr>
                    <td>" . htmlspecialchars($row['name']) . "</td>
                    <td>" . htmlspecialchars($row['price']) . "</td>
                    <td>" . htmlspecialchars($row['stock']) . "</td>
                    <td>" . htmlspecialchars($row['description']) . "</td>

                    <td>
                        <form method='POST' action=''>
                            <input type='hidden' name='tree_id' value='" . $row['treespecies_id'] . "'>
                            <input type='number' name='quantity' value='1' min='1' max='" . $row['stock'] . "' required>
                            <button type='submit' name='add_to_cart'>Add to Cart</button>
                        </form>
                    </td>
                </tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No seedlings available</p>";
    }

    ?>

    <h3>Your Cart</h3>
    <?php

    if(!empty($_SESSION["cart"])) {
        echo "<form method='POST' action=''>
            <table border='1' cellpadding='10'>
            <tr>
                <th>Tree</th>
                <th>Quantity</th>
                <th>Price (KES)</th>
                <th>Total (KES)</th>
            </tr>";
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
        echo "<tr>
                <td colspan='3'><strong>Grand Total</strong></td>
                <td><strong>$grand_total</strong></td>
            </tr>";
        echo "</table>
        <button type='submit' name='place_order'>Place Order</button>
        </form>";
    } else {
        echo "<p>Your cart is empty.</p>";
    }

    ?>

    <h3>My Orders</h3>
    <?php 

    $result = mysqli_query($db, "
    SELECT o.ORDER_ID, o.ORDER_DATE, o.ORDER_STATUS, t.COMMON_NAME AS tree_name, o.QUANTITY, o.TOTAL_PRICE
    FROM orders o
    JOIN treespecies t ON o.TREESEEDLING_ID = t.treespecies_id
    WHERE o.BUYER_ID = $buyer_id
    ORDER BY o.ORDER_DATE DESC");

    if(mysqli_num_rows($result) > 0) {
        echo "<table border='1' cellpadding='10'>
                <tr>
                    <th>Order ID</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Tree</th>
                    <th>Quantity</th>
                    <th>Total (KES)</th>
                </tr>";

        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>
                    <td>" . htmlspecialchars($row['ORDER_ID']) . "</td>
                    <td>" . htmlspecialchars($row['ORDER_DATE']) . "</td>
                    <td>" . htmlspecialchars($row['ORDER_STATUS']) . "</td>
                    <td>" . htmlspecialchars($row['COMMON_NAME']) . "</td>
                    <td>" . htmlspecialchars($row['QUANTITY']) . "</td>
                    <td>" . htmlspecialchars($row['TOTAL_PRICE']) . "</td>
                </tr>";
        }
        echo "</table>";
    } else {
        echo "<p>You have not placed any orders yet.</p>";
    }

    ?>

    <br><a href="logout.php"></a>
    
</body>
</html>