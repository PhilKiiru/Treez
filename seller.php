<?php 


session_start();
include("db.php");

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "SELLER") {
    header("Location: login.php");
    exit();
}

if($_SERVER["REQUEST_METHOD"] == "POST" && isset( $_POST["add_treeseedling"])) {
    $name = $_POST["name"];
    $price = $_POST["price"];
    $stock = $_POST["stock"];
    $description = $_POST["description"];
    $seller_id = $_SESSION["user_id"];

    $sql = "INSERT INTO treespecies (name, price, stock, description, seller_id) VALUES ('$name', '$price', '$stock', '$description', '$seller_id')";
    mysqli_query($db, $sql);
}

if(isset($_GET["delete_id"])) {
    $delete_id = $_Get["delete_id"];
    mysqli_query($db, "DELETE FROM treespecies WHERE treespecies_id = $delete_id AND seller_id = $seller_id");
    header("Location: seller.php");
    exit();
}

if($_SERVER["REQUEST_METHOD"] == "POST" && isset( $_POST["update_treeseedling"])) {
    $id = $_POST["id"];
    $name = $_POST["name"];
    $price = $_POST["price"];
    $stock = $_POST["stock"];
    $description = $_POST["description"];

    $sql = "UPDATE treespecies SET name ='$name', price='$price', stock='$stock', description='$description' WHERE treespecies_id=$id AND seller_id=$seller_id";
    mysqli_query($db, $sql);

    mysqli_query($db, $sql);
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
                    <input type='text' name='description' value='{$row['descriprion']}'>
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

    $orders = mysqli_query($db, "
    SELECT o.order_id, o.order_date, o.status, u.username AS buyer, t.name, d.quantity, d.price
    FROM orders o
    JOIN order_details d ON o.order_id = d.order_id
    JOIN treespecies t ON d.treespecies_id = t.treespecies_id
    JOIN users u ON o.buyer_id = u.user_id
    WHERE t.seller_id = $seller_id
    ORDER BY o.order_date DESC
    ");

    if(mysqli_num_rows($orders) > 0) {
        echo "<table border='1' cellpadding='10'>
        <tr>
            <th>Order ID</th>
            <th>Date</th>
            <th>Order ID</th>
            <th>Status</th>
            <th>Buyer</th>
            <th>Tree species</th>
            <th>Total Price</th>
            <th>Action</th>
            </tr>";

        while ($row = mysqli_fetch_assoc($orders)) {
            $total = $row['quantity'] * $row['price'];
            echo "<tr>
            <td>{$row['Order_id']}</td>
            <td>{$row['Order_date']}</td>
            <td>{$row['status']}</td>
            <td>{$row['buyer']}</td>
            <td>{$row['name']}</td>
            <td>{$row['quantity']}</td>
            <td>{$row['total']}</td>

            <td>
            <form method='POST' action='' style='display:inline;'>
            <input type='hidden' name='order_id' value='{$row['order_id']}'>
            <select name ='status'>
                <option value='Pending' ".($row['status']=='Pending'?'selected':'').">Pending</option>
                <option value='Confirmed' ".($row['status']=='Confirmed'?'selected':'').">Pending</option>
                <option value= 'Delivered' ".($row['status']=='Delivered'?'selected':'').">Pending</option>
            </select>
            <button type='submit' name='update_status'>Update</button>
            </form>
            </td>
            </tr>";
        }
            echo "</table>";
            
        } else {
            echo "<p>No orders yet for your seedlings.</p>";
        }

    ?>

    <br><a href="logout.php">Logout</a>
    
</body>
</html>