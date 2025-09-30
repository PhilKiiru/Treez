<?php

session_start();
include("db.php");

if(!isset($_SESSION["user_id"]) || $_SESSION["role"] != "ADMIN") {
    header("Location: login.php");
    exit();
}

if(isset($_GET['delete_user'])) {
    $delete_id = intval($_GET['delete_user']);
    if($delete_id != $_SESSION["user_id"]) {
        $stmt = mysqli_prepare($db, "DELETE FROM users WHERE USER_ID = ?");
        mysqli_stmt_bind_param($stmt, "i", $delete_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    header("Location: admin.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Tree Seedling Admin</a>
            <span class="navbar-text text-white ms-auto">
                Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?> (Admin)
            </span>
            <a href="logout.php" class="btn btn-outline-light ms-3">Logout</a>
        </div>
    </nav>

    <div>
        <h3 class="mb-3">Manage Users</h3>
        <?php
        $users = mysqli_query($db, "SELECT USER_ID, USERNAME, EMAIL, PHONE, ROLE, LOCATION FROM users");
        if (mysqli_num_rows($users) > 0) {
            echo "<table class='table table-bordered table-striped'>
                    <thead class='table-dark'>
                        <tr>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Location</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <body>";

        while ($row = mysqli_fetch_assoc($users)) {
            echo "<tr>
                    <td>" . htmlspecialchars($row['USER_ID']) . "</td>            
                    <td>" . htmlspecialchars($row['USERNAME']) . "</td>            
                    <td>" . htmlspecialchars($row['EMAIL']) . "</td>            
                    <td>" . htmlspecialchars($row['PHONE']) . "</td>   
                    <td><span class='badgebg-info tetxt-dark'>" . htmlspecialchars($row['ROLE']) . "</span></td>
                    <td>" . htmlspecialchars($row['LOCATION']) . "</td>
                    <td>";
            if ($row['USER_ID'] != $_SESSION["user_id"]) {
                echo "<a class='btn btn-sm btn-danger' href='admin.php?delete_user=" . $row['USER_ID'] . "' onclick=\"return confirm('Delete this user?');\">Delete<a>";

            } else {
                echo "<span class='text-muted'>Self</span>";
            }
            echo "</td></tr>";
        } 
            echo "</tbody></table>";
        } else {
            echo "<div class='alert alert-warning'>No users foundd.</div>";
        }

        ?>

        <h3 class="mt-5 mb-3">View Orders</h3>
        <?php
        $orders = mysqli_query($db, "
        SELECT o.ORDER_ID, o.ORDER_DATE, o.ORDER_STATUS, u.USERNAME AS buyer, t.COMMON_NAME AS tree_name, o.QUANTITY, o.TOTAL_PRICE
        FROM orders o
        JOIN users u ON o.BUYER_ID = u .USER_ID
        JOIN treespecies t ON o.TREESEEDLINGS_ID = t.TREESPECIES_ID
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
                            <th>Tree Species</th>
                            <th>TOTAL (KES)</th>
                        </tr>
                    </thead>
                    <tbody>";
            while ($row = mysqli_fetch_assoc($orders)) {
                echo "<tr>
                        <td>" . htmlspecialchars($row['ORDER_ID']) . "</td>
                        <td>" . htmlspecialchars($row['ORDER_DATE']) . "</td>
                        <td><span class='badge bg-" .
                            ($row['ORDER_STATUS'] == "COMPLETED" ? "success" : ($row['ORDER_STATUS'] == "PENDING" ? "warning text-dark" : "danger")) . "'>". htmlspecialchars($row['ORDER_STATUS']) . "</span></td>
                        <td>" . htmlspecialchars($row['buyer']) . "</td>   
                        <td>" . htmlspecialchars($row['tree_name']) . "</td>   
                        <td>" . htmlspecialchars($row['QUANTTIY']) . "</td>   
                        <td>" . htmlspecialchars($row['QUANTTIY']) . "</td>   
                        <td><strong>" . htmlspecialchars($row['TOTAL_PRICE']) . "</strong></td>   
                    </tr>";
            }
            echo "</tbody></table>";
        } else {
            echo "<div class='alert alert-info'>No orders found.</div>";
        }

        ?>

    </div>

 
    
</body>
</html>