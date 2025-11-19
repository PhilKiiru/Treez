// ----------------- MARK AS DELIVERED -----------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["mark_delivered"])) {
    $order_id = intval($_POST["order_id"]);
    $stmt = mysqli_prepare($db, "UPDATE orders SET ORDER_STATUS='DELIVERED' WHERE ORDER_ID=? AND (ORDER_STATUS='PAID (CASH)' OR ORDER_STATUS='PAID (MPESA)' OR ORDER_STATUS='PROCESSING')");
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("Location: admin.php");
    exit();
}
<?php
session_start();
include("db.php");

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "ADMIN") {
    header("Location: login.php");
    exit();
}

// ----------------- DELETE USER -----------------
if (isset($_GET['delete_user'])) {
    $delete_id = intval($_GET['delete_user']);
    if ($delete_id != $_SESSION["user_id"]) {
        $stmt = mysqli_prepare($db, "DELETE FROM users WHERE USER_ID=?");
        mysqli_stmt_bind_param($stmt, "i", $delete_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    header("Location: admin.php");
    exit();
}

// ----------------- CANCEL ORDER -----------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["cancel_order"])) {
    $order_id = intval($_POST["order_id"]);
    $stmt = mysqli_prepare($db, "UPDATE orders SET ORDER_STATUS='CANCELLED' WHERE ORDER_ID=? AND ORDER_STATUS='PENDING'");
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("Location: admin.php");
    exit();
}

// ----------------- DELETE ORDER -----------------
if (isset($_GET["delete_order"])) {
    $order_id = intval($_GET["delete_order"]);
    $stmt = mysqli_prepare($db, "DELETE FROM orderdetails WHERE ORDER_ID=?");
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $stmt2 = mysqli_prepare($db, "DELETE FROM orders WHERE ORDER_ID=?");
    mysqli_stmt_bind_param($stmt2, "i", $order_id);
    mysqli_stmt_execute($stmt2);
    mysqli_stmt_close($stmt2);

    header("Location: admin.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Treez Admin</a>
            <span class="navbar-text text-white ms-auto">
                Welcome, <?= htmlspecialchars($_SESSION["username"]); ?> (Admin)
            </span>
            <a href="logout.php" class="btn btn-outline-light ms-3">Logout</a>
        </div>
    </nav>

    <div class="container">
        <!-- Manage Users -->
        <h3 class="mb-3">Manage Users</h3>
        <?php
        $users = mysqli_query($db, "SELECT USER_ID, USERNAME, EMAIL, PHONE, ROLE, LOCATION FROM users");
        if (mysqli_num_rows($users) > 0) {
            echo "<table class='table table-bordered table-striped'>
                    <thead class='table-dark'>
                        <tr>
                            <th>ID</th><th>Username</th><th>Email</th><th>Phone</th><th>Role</th><th>Location</th><th>Action</th>
                        </tr>
                    </thead><tbody>";
            while ($row = mysqli_fetch_assoc($users)) {
                echo "<tr>
                        <td>{$row['USER_ID']}</td>
                        <td>{$row['USERNAME']}</td>
                        <td>{$row['EMAIL']}</td>
                        <td>{$row['PHONE']}</td>
                        <td><span class='badge bg-info text-dark'>{$row['ROLE']}</span></td>
                        <td>{$row['LOCATION']}</td>
                        <td>";
                if ($row['USER_ID'] != $_SESSION["user_id"]) {
                    echo "<a class='btn btn-sm btn-danger' href='admin.php?delete_user={$row['USER_ID']}' onclick=\"return confirm('Delete this user?');\">Delete</a>";
                } else {
                    echo "<span class='text-muted'>Self</span>";
                }
                echo "</td></tr>";
            }
            echo "</tbody></table>";
        } else {
            echo "<div class='alert alert-warning'>No users found.</div>";
        }
        ?>

        <!-- Manage Orders -->
        <h3 class="mt-5 mb-3">Manage Orders</h3>
        <?php
        $orders = mysqli_query($db, "
            SELECT o.ORDER_ID, o.ORDER_DATE, o.ORDER_STATUS, o.TOTAL_PRICE,
                   u.USERNAME AS buyer,
                   t.COMMON_NAME, t.SCIENTIFIC_NAME, t.DESCRIPTION, od.QUANTITY, od.PRICE
            FROM orders o
            JOIN users u ON o.BUYER_ID = u.USER_ID
            JOIN orderdetails od ON o.ORDER_ID = od.ORDER_ID
            JOIN treespecies t ON od.TREESPECIES_ID = t.TREESPECIES_ID
            ORDER BY o.ORDER_DATE DESC
        ");

        if (mysqli_num_rows($orders) > 0) {
            echo "<table class='table table-hover table-bordered'>
                    <thead class='table-dark'>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Buyer</th>
                            <th>Tree</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Total</th>
                            <th>Description</th>
                            <th>Action</th>
                        </tr>
                    </thead><tbody>";
            while ($row = mysqli_fetch_assoc($orders)) {
                $item_total = $row['PRICE'] * $row['QUANTITY'];
                $badge = ($row['ORDER_STATUS']=="COMPLETED")?"success":(($row['ORDER_STATUS']=="PENDING")?"warning text-dark":"danger");
                echo "<tr>
                    <td>{$row['ORDER_ID']}</td>
                    <td>{$row['ORDER_DATE']}</td>
                    <td><span class='badge bg-$badge'>{$row['ORDER_STATUS']}</span></td>
                    <td>{$row['buyer']}</td>
                    <td>{$row['COMMON_NAME']}<br><em>{$row['SCIENTIFIC_NAME']}</em></td>
                    <td>{$row['QUANTITY']}</td>
                    <td>{$row['PRICE']}</td>
                    <td><strong>$item_total</strong></td>
                    <td>".nl2br(htmlspecialchars($row['DESCRIPTION']))."</td>
                    <td>";
                if ($row['ORDER_STATUS']=="PENDING") {
                    echo "<form method='POST' class='d-inline'>
                              <input type='hidden' name='order_id' value='{$row['ORDER_ID']}'>
                              <button type='submit' name='cancel_order' class='btn btn-sm btn-warning'>Cancel</button>
                          </form> ";
                }
                // Show 'Mark as Delivered' for paid but not completed/cancelled orders
                if (in_array($row['ORDER_STATUS'], ["PAID (CASH)", "PAID (MPESA)", "PROCESSING"])) {
                    echo "<form method='POST' class='d-inline ms-1'>
                              <input type='hidden' name='order_id' value='{$row['ORDER_ID']}'>
                              <button type='submit' name='mark_delivered' class='btn btn-sm btn-success'>Mark as Delivered</button>
                          </form> ";
                }
                echo "<a class='btn btn-sm btn-danger' href='admin.php?delete_order={$row['ORDER_ID']}' onclick=\"return confirm('Delete order?');\">Delete</a>";
                echo "</td></tr>";
            }
            echo "</tbody></table>";
        } else {
            echo "<div class='alert alert-info'>No orders found.</div>";
        }
        ?>
    </div>
</body>
</html>
