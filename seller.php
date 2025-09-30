<?php 
session_start();
include("db.php");

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "SELLER") {
    header("Location: login.php");
    exit();
}

$seller_id = intval($_SESSION["user_id"]);

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_treeseedling"])) {
    $name = $_POST["name"];
    $price = floatval($_POST["price"]);
    $stock = intval($_POST["stock"]);
    $description = $_POST["description"];

    $stmt = mysqli_prepare($db, "INSERT INTO treespecies (COMMON_NAME, price, stock, description, seller_id) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sdssi", $name, $price, $stock, $description, $seller_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: seller.php");
    exit();
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

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_treeseedling"])) {
    $id = intval($_POST["id"]);
    $name = $_POST["name"];
    $price = floatval($_POST["price"]);
    $stock = intval($_POST["stock"]);
    $description = $_POST["description"];

    $stmt = mysqli_prepare($db, "UPDATE treespecies SET COMMON_NAME=?, price=?, stock=?, description=? WHERE treespecies_id=? AND seller_id=?");
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Tree Seedlings Seller</a>
        <span class="navbar-text text-white ms-auto">
            Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?> (Seller)
        </span>
        <a href="logout.php" class="btn btn-outline-light ms-3">Logout</a>
    </div>
</nav>

<div class="container">
    
    <h3 class="mb-3">Add New Seedling</h3>
    <form method="POST" action="" class="mb-5">
        <div class="row g-3">
            <div class="col-md-3"><input type="text" name="name" placeholder="Tree Name" class="form-control" required></div>
            <div class="col-md-2"><input type="number" step="0.01" name="price" placeholder="Price" class="form-control" required></div>
            <div class="col-md-2"><input type="number" name="stock" placeholder="Stock" class="form-control" required></div>
            <div class="col-md-4"><input type="text" name="description" placeholder="Description" class="form-control"></div>
            <div class="col-md-1"><button type="submit" name="add_treeseedling" class="btn btn-success">Add</button></div>
        </div>
    </form>

    <h3 class="mb-3">Your Tree Seedlings</h3>
    <?php 
    $result = mysqli_query($db,"SELECT * FROM treespecies WHERE seller_id = $seller_id");
    if(mysqli_num_rows($result) > 0) {
        echo "<table class='table table-bordered table-striped'>
                <thead class='table-dark'>
                    <tr>
                        <th>Name</th>
                        <th>Price (KES)</th>
                        <th>Stock</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead><tbody>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>
                    <td>" . htmlspecialchars($row['COMMON_NAME']) . "</td>
                    <td>" . htmlspecialchars($row['price']) . "</td>
                    <td>" . htmlspecialchars($row['stock']) . "</td>
                    <td>" . htmlspecialchars($row['description']) . "</td>
                    <td>
                        <a href='seller.php?delete_id={$row['treespecies_id']}' class='btn btn-sm btn-danger' onclick=\"return confirm('Are you sure you want to delete this tree seedling?');\">Delete</a>
                        
                        <form method='POST' action='' class='d-inline'>
                            <input type='hidden' name='id' value='{$row['treespecies_id']}'>
                            <input type='text' name='name' value='{$row['COMMON_NAME']}' class='form-control form-control-sm mb-1' required>
                            <input type='number' step='0.01' name='price' value='{$row['price']}' class='form-control form-control-sm mb-1' required>
                            <input type='number' name='stock' value='{$row['stock']}' class='form-control form-control-sm mb-1' required>
                            <input type='text' name='description' value='{$row['description']}' class='form-control form-control-sm mb-1'>
                            <button type='submit' name='update_treeseedling' class='btn btn-sm btn-warning'>Update</button>
                        </form>
                    </td>
                </tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<div class='alert alert-info'>You haven't added any tree seedlings yet.</div>";
    }
    ?>

   
    <h3 class="mt-5 mb-3">Orders for Your Tree Seedlings</h3>
    <?php
    $query = "
    SELECT 
        o.ORDER_ID AS order_id, 
        o.ORDER_DATE AS order_date, 
        o.ORDER_STATUS AS order_status, 
        u.USERNAME AS buyer, 
        t.COMMON_NAME AS tree_name, 
        o.QUANTITY AS quantity, 
        o.TOTAL_PRICE AS total_price
    FROM orders o
    JOIN treespecies t ON o.TREESEEDLING_ID = t.TREESPECIES_ID
    JOIN users u ON o.BUYER_ID = u.USER_ID
    WHERE t.seller_id = ?
    ORDER BY o.ORDER_DATE DESC";

    $stmt = mysqli_prepare($db, $query);
    mysqli_stmt_bind_param($stmt, "i", $seller_id);
    mysqli_stmt_execute($stmt);
    $orders = mysqli_stmt_get_result($stmt);

    if(mysqli_num_rows($orders) === 0) {
        echo "<div class='alert alert-warning'>No orders yet for your tree seedlings.</div>";
    } else {
        echo "<table class='table table-hover table-bordered'>
        <thead class='table-dark'>
            <tr>
                <th>Order ID</th>
                <th>Date</th>
                <th>Status</th>
                <th>Buyer</th>
                <th>Tree Species</th>
                <th>Quantity</th>
                <th>Total Price (KES)</th>
                <th>Action</th>
            </tr>
        </thead><tbody>";

        while ($row = mysqli_fetch_assoc($orders)) {
            $order_id = $row['order_id'];
            $status = $row['order_status'];

            echo "<tr>
                <td>" . htmlspecialchars($order_id) . "</td>
                <td>" . htmlspecialchars($row['order_date']) . "</td>
                <td><span class='badge bg-" .
                    ($status == "COMPLETED" ? "success" : ($status == "PENDING" ? "warning text-dark" : "danger")) . "'>". htmlspecialchars($status) . "</span></td>
                <td>" . htmlspecialchars($row['buyer']) . "</td>
                <td>" . htmlspecialchars($row['COMMON_NAME']) . "</td>
                <td>" . htmlspecialchars($row['quantity']) . "</td>
                <td>" . htmlspecialchars(number_format($row['total_price'], 2)) . "</td>
                <td>
                    <form method='POST' action='' class='d-inline'>
                        <input type='hidden' name='order_id' value='" . htmlspecialchars($order_id) . "'>
                        <select name='status' class='form-select form-select-sm d-inline' style='width:150px;'>
                            <option value='PENDING' ".($status =='PENDING'?'selected':'').">Pending</option>
                            <option value='COMPLETED' ".($status =='COMPLETED'?'selected':'').">Completed</option>
                            <option value='CANCELLED' ".($status =='CANCELLED'?'selected':'').">Cancelled</option>
                        </select>
                        <button type='submit' name='update_status' class='btn btn-sm btn-primary'>Update</button>
                    </form>
                </td>
            </tr>";
        }
        echo "</tbody></table>";
    }
    mysqli_stmt_close($stmt);
    ?>
</div>
</body>
</html>
