<?php 

session_start();
include("db.php");

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "SELLER") {
    header("Location: login.php");
    exit();
}

$seller_id = $_SESSION["user_id"];

if($_SERVER["REQUEST_METHOD"] == "POST" && isset( $_POST["add_treeseedling"])) {
    $name = $_POST["name"];
    $price = floatval($_POST["price"]);
    $stock = intval($_POST["stock"]);
    $description = $_POST["description"];
    $seller_id = $_SESSION["user_id"];

    $stmt = mysqli_prepare($db, "INSERT INTO treespecies (name, price, stock, description, seller_id) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sdssi", $name, $price, $stock, $description, $seller_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

if(isset($_GET["delete_id"])) {
    $delete_id = intval($_GET["delete_id"]);
    $stmt = mysqli_prepare($db, "DELETE FROM treespecies WHERE treespecies_id = ? AND seller_id = ?");
    mysqli_stmt_bind_param($stmt, "ii", $delete_id, $seller_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: seller.php");
    exit();
}
if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_status"])) {
    $order_id = intval($_POST["order_id"]);
    $status = $_POST["status"];

    $stmt = mysqli_prepare($db, "UPDATE orders SET ORDER_STATUS=? WHERE ORDER_ID=?");
    mysqli_stmt_bind_param($stmt, "si", $status, $order_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: seller.php");
    exit();
}

if($_SERVER["REQUEST_METHOD"] == "POST" && isset( $_POST["update_treeseedling"])) {
    $id = intval($_POST["id"]);
    $name = $_POST["name"];
    $price = floatval($_POST["price"]);
    $stock = intval($_POST["stock"]);
    $description = $_POST["description"];

    $stmt = mysqli_prepare($db, "UPDATE treespecies SET name =?, price=?, stock=?, description=? WHERE treespecies_id=? AND seller_id=?");
    mysqli_stmt_bind_param($stmt, "sdisii", $name, $price, $stock, $description, $id, $seller_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    header("Location: seller.php");
    exit();
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller's Dashboard</title>
</head>
<body>
    <h2>Welcome Farmer, <?php echo $_SESSION["username"]; ?></h2>
    <h3>Add New Seedling</h3>
    <form method="POST" action="">
        <input type="text" name="name" placeholder="Tree Name" required><br>
        <input type="number" step="0.01" name="price" placeholder="Price" required><br>
        <input type="number" name="stock" placeholder="Stock Quantity" required><br>
        <textarea name="description" placeholder="Description"></textarea><br>
        <button type="submit" name="add_treeseedling">Add Tree seedling</button>
    </form>

    <h3>Your Tree Seedlings</h3>
    <?php 
    $seller_id = $_SESSION["user_id"];
    $result = mysqli_query($db,"SELECT * FROM treespecies WHERE seller_id = $seller_id");
    if(mysqli_num_rows($result) > 0) {
        echo "<table border='1' cellpadding='10' >
                <tr>
                    <th>Name</th>
                    <th>Price (KES)</th>
                    <th>Stock</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>";
    while ($row = mysqli_fetch_array($result)) {
        echo "<tr>
                <td>{$row['name']}</td>
                <td>{$row['price']}</td>
                <td>{$row['stock']}</td>
                <td>{$row['description']}</td>

            <td>
                    <a href='seller.php?delete_id={$row['treespecies_id']}'
                    onclick=\"return confirm('Are you sure you want to delete this tree seedling?');\">
                    Delete
                    </a> |
                    
                    <form method='POST' action='' style='display:inline;'>
                    <input type='hidden' name='id' value='{$row['treespecies_id']}'>
                    <input type='text' name='name' value='{$row['name']}' required>
                    <input type='number' step='0.01' name='price' value='{$row['price']}' required>
                    <input type='number' name='stock' value='{$row['stock']}' required>
                    <input type='text' name='description' value='{$row['description']}'>
                    <button type='submit' name='update_treeseedling'>Update</button>
                    </form>

            </td>
            </tr>";
    }
        echo "</table>";
    }else {
        echo "<p>You haven't added any tree seedlings yet.</p>";
    }

    ?>

    <h3>Orders for your Tree seedlings</h3>
    <?php
    $seller_id = intval($_SESSION["user_id"]);

    $query = "
    SELECT 
    o.ORDER_ID AS order_id, 
    o.ORDER_DATE AS order_date, 
    o.ORDER_STATUS AS order_status, 
    u.USERNAME AS buyer, 
    t.name AS tree_name, 
    o.QUANTITY AS quantity, 
    o.TOTAL_PRICE AS total_price
    FROM orders o
    JOIN treespecies t ON o.TREESEEDLING_ID = t.treespecies_id
    JOIN users u ON o.BUYER_ID = u.user_id
    WHERE t.seller_id = ?
    ORDER BY o.ORDER_DATE DESC
    ";

    $stmt = mysqli_prepare($db, $query);
    if (!$stmt) {
        echo "<p>Error preparing query: " . htmlspecialchars(mysqli_error($db)) . "</p>";
    } else {
        mysqli_stmt_bind_param($stmt, "i", $seller_id);
        mysqli_stmt_execute($stmt);
        $orders = mysqli_stmt_get_result($stmt);

    if(mysqli_num_rows($orders) ===  0) {
        echo "<p>No orders yet for your tree seedlings.</p>";
    } else {
        echo "<table border='1' cellpadding='10'>
        <tr>
            <th>Order ID</th>
            <th>Date</th>
            <th>Status</th>
            <th>Buyer</th>
            <th>Tree species</th>
            <th>Quantity</th>
            <th>Total Price</th>
            <th>Action</th>
            </tr>";

        while ($row = mysqli_fetch_assoc($orders)) {
            $order_id = $row['order_id'];
            $order_date = $row['order_date'];
            $status = $row['order_status'];
            $buyer = $row['buyer'];
            $tree_name = $row['tree_name'];
            $quantity = $row['quantity'];
            $total = $row['total_price'];

            echo "<tr>
            <td>" . htmlspecialchars($order_id) . "</td>
            <td>" . htmlspecialchars($order_date) . "</td>
            <td>" . htmlspecialchars($status) . "</td>
            <td>" . htmlspecialchars($buyer) . "</td>
            <td>" . htmlspecialchars($tree_name) . "</td>
            <td>" . htmlspecialchars($quantity) . "</td>
            <td>" . htmlspecialchars(number_format($total, 2)) . " KES</td>

            <td>
            <form method='POST' action='' style='display:inline;'>
            <input type='hidden' name='order_id' value='" . htmlspecialchars($order_id) . "'>
            <select name ='status'>
                <option value='PENDING' ".($status =='PENDING'?'selected':'').">Pending</option>
                <option value='COMPLETED' ".($status =='COMPLETED'?'selected':'').">Completed</option>
                <option value= 'CANCELLED' ".($status =='CANCELLED'?'selected':'').">Cancelled</option>
            </select>
            <button type='submit' name='update_status'>Update</button>
            </form>
            </td>
            </tr>";
        }
            echo "</table>";
            
        } 
        mysqli_stmt_close($stmt);

        }

    ?>

    <br><a href="logout.php">Logout</a>
    
</body>
</html>