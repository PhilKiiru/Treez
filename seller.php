<?php 
session_start();
include("db.php");

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "SELLER") {
    header("Location: login.php");
    exit();
}

$seller_id = intval($_SESSION["user_id"]);


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["accept_order"])) {
    $order_id = intval($_POST["order_id"]);
    $status = 'PROCESSING';
    $stmt = mysqli_prepare($db, "UPDATE orders SET ORDER_STATUS=? WHERE ORDER_ID=? AND ORDER_STATUS='PENDING'");
    mysqli_stmt_bind_param($stmt, "si", $status, $order_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("Location: seller.php");
    exit();
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["cancel_order"])) {
    $order_id = intval($_POST["order_id"]);
    $stmt = mysqli_prepare($db, "
        UPDATE orders o
        JOIN orderdetails od ON o.ORDER_ID = od.ORDER_ID
        JOIN treespecies t ON od.TREESPECIES_ID = t.TREESPECIES_ID
        SET o.ORDER_STATUS='CANCELLED'
        WHERE o.ORDER_ID=? AND t.SELLER_ID=? AND o.ORDER_STATUS='PENDING'
    ");
    mysqli_stmt_bind_param($stmt, "ii", $order_id, $seller_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header("Location: seller.php");
    exit();
}



if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_treeseedling"])) {
    if (
        isset($_POST["name"], $_POST["scientific_name"], $_POST["price"], $_POST["stock"]) &&
        trim($_POST["name"]) !== '' && trim($_POST["scientific_name"]) !== '' && $_POST["price"] !== '' && $_POST["stock"] !== ''
    ) {
        $name = trim($_POST["name"]);
        $scientific = trim($_POST["scientific_name"]);
        $price = floatval($_POST["price"]);
        $stock = intval($_POST["stock"]);
        $description = isset($_POST["description"]) ? trim($_POST["description"]) : '';

      
        $dup_check = mysqli_prepare($db, "SELECT 1 FROM treespecies WHERE COMMON_NAME = ? AND SELLER_ID = ?");
        mysqli_stmt_bind_param($dup_check, "si", $name, $seller_id);
        mysqli_stmt_execute($dup_check);
        mysqli_stmt_store_result($dup_check);
        if (mysqli_stmt_num_rows($dup_check) > 0) {
            echo '<div class="alert alert-danger">A seedling with this COMMON NAME already exists for you.</div>';
            mysqli_stmt_close($dup_check);
        } else {
            mysqli_stmt_close($dup_check);
            $target_dir = "uploads/";
            if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
            $image_name = time() . "_" . basename($_FILES["image"]["name"]);
            $target_file = $target_dir . $image_name;

            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $stmt = mysqli_prepare($db, "INSERT INTO treespecies 
                    (COMMON_NAME, SCIENTIFIC_NAME, PRICE, STOCK, DESCRIPTION, SELLER_ID, IMAGE) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "ssdsiss", $name, $scientific, $price, $stock, $description, $seller_id, $target_file);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
            header("Location: seller.php");
            exit();
        }
    } else {
        echo '<div class="alert alert-danger">All fields are required.</div>';
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["edit_treeseedling"])) {
    $tree_id = intval($_POST["tree_id"]);
    $name = trim($_POST["name"]);
    $scientific = trim($_POST["scientific_name"]);
    $price = floatval($_POST["price"]);
    $stock = intval($_POST["stock"]);
    $description = trim($_POST["description"] ?? '');

   
    $dup_check = mysqli_prepare($db, "SELECT 1 FROM treespecies WHERE COMMON_NAME = ? AND SCIENTIFIC_NAME = ? AND SELLER_ID = ? AND TREESPECIES_ID != ?");
    mysqli_stmt_bind_param($dup_check, "ssii", $name, $scientific, $seller_id, $tree_id);
    mysqli_stmt_execute($dup_check);
    mysqli_stmt_store_result($dup_check);
    if (mysqli_stmt_num_rows($dup_check) > 0) {
        echo '<div class="alert alert-danger">A seedling with this COMMON NAME and SCIENTIFIC NAME already exists for you.</div>';
        mysqli_stmt_close($dup_check);
    } else {
        mysqli_stmt_close($dup_check);
        $stmt = mysqli_prepare($db, "UPDATE treespecies 
            SET COMMON_NAME=?, SCIENTIFIC_NAME=?, PRICE=?, STOCK=?, DESCRIPTION=? 
            WHERE TREESPECIES_ID=? AND SELLER_ID=?");
        mysqli_stmt_bind_param($stmt, "ssdisii", $name, $scientific, $price, $stock, $description, $tree_id, $seller_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header("Location: seller.php");
        exit();
    }
}


if (isset($_GET["delete_id"])) {
    $delete_id = intval($_GET["delete_id"]);
    $stmt = mysqli_prepare($db, "DELETE FROM treespecies WHERE TREESPECIES_ID=? AND SELLER_ID=?");
    mysqli_stmt_bind_param($stmt, "ii", $delete_id, $seller_id);
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
    <title>Seller Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Treez Seller</a>
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <li class="nav-item">
                <a class="nav-link fw-bold text-white" href="seller_insights.php">Seller Insights</a>
            </li>
            <li class="nav-item">
                <a class="nav-link fw-bold text-white" href="seller_orders.php">Seller Orders</a>
            </li>
        </ul>
        <span class="navbar-text text-white ms-auto">
            Welcome, <?= htmlspecialchars($_SESSION["username"]); ?> (Seller)
        </span>
        <a href="logout.php" class="btn btn-outline-light ms-3">Logout</a>
    </div>
</nav>

<div class="container">
       


    <h3 class="mb-3">Add New Seedling</h3>
    <form method="POST" action="" enctype="multipart/form-data" class="mb-5">
        <div class="row g-3">
            <div class="col-md-3"><input type="text" name="name" placeholder="Common Name" class="form-control" required></div>
            <div class="col-md-3"><input type="text" name="scientific_name" placeholder="Scientific Name" class="form-control" required></div>
            <div class="col-md-2"><input type="number" step="0.01" name="price" placeholder="Price" class="form-control" required></div>
            <div class="col-md-2"><input type="number" name="stock" placeholder="Stock" class="form-control" required></div>
            <div class="col-md-12">
                <textarea name="description" placeholder="Description" class="form-control" rows="3"></textarea>
            </div>
            <div class="col-md-3"><input type="file" name="image" accept="image/*" class="form-control" required></div>
            <div class="col-md-12"><button type="submit" name="add_treeseedling" class="btn btn-success">Add</button></div>
        </div>
    </form>
    <?php
    $search = trim($_GET['search'] ?? '');
    if ($search !== '') {
        $like = '%' . mysqli_real_escape_string($db, $search) . '%';
        $result = mysqli_query($db, "SELECT * FROM treespecies WHERE SELLER_ID=$seller_id AND (COMMON_NAME LIKE '$like' OR SCIENTIFIC_NAME LIKE '$like')");
    } else {
        $result = mysqli_query($db,"SELECT * FROM treespecies WHERE SELLER_ID=$seller_id");
    }
    if(mysqli_num_rows($result) > 0) {
        echo "<div class='row'>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo '<div class="col-md-4">
                <div class="card mb-4 shadow-sm">
                    <img src="' . htmlspecialchars($row['IMAGE']) . '" class="card-img-top" alt="Tree Image" style="height:200px; object-fit:cover;">
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="tree_id" value="' . $row['TREESPECIES_ID'] . '">
                            <div class="mb-2">
                                <label class="form-label mb-0"><strong>Common Name:</strong></label>
                                <input type="text" name="name" value="' . htmlspecialchars($row['COMMON_NAME']) . '" class="form-control form-control-sm" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label mb-0"><strong>Scientific Name:</strong></label>
                                <input type="text" name="scientific_name" value="' . htmlspecialchars($row['SCIENTIFIC_NAME']) . '" class="form-control form-control-sm" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label mb-0"><strong>Price (KES):</strong></label>
                                <input type="number" step="0.01" name="price" value="' . htmlspecialchars($row['PRICE']) . '" class="form-control form-control-sm" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label mb-0"><strong>Stock:</strong></label>
                                <input type="number" name="stock" value="' . htmlspecialchars($row['STOCK']) . '" class="form-control form-control-sm" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label mb-0"><strong>Description:</strong></label>
                                <textarea name="description" class="form-control form-control-sm" rows="2">' . htmlspecialchars($row['DESCRIPTION']) . '</textarea>
                            </div>
                            <div class="d-flex justify-content-between">
                                <button type="submit" name="edit_treeseedling" class="btn btn-sm btn-primary">Update</button>
                                <a href="?delete_id=' . $row['TREESPECIES_ID'] . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Are you sure you want to delete this seedling?\');">Delete</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>';
        }
        echo "</div>";
    } else {
        echo "<div class='alert alert-info'>You haven't added any seedlings yet.</div>";
    }
    ?>

</div>
</body>
</html>
