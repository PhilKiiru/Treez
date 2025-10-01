<?php 
session_start();
include("db.php");

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "SELLER") {
    header("Location: login.php");
    exit();
}

$seller_id = intval($_SESSION["user_id"]);

// ----------------- ADD NEW SEEDLING -----------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_treeseedling"])) {
    $name = $_POST["name"];
    $scientific = $_POST["scientific_name"];
    $price = floatval($_POST["price"]);
    $stock = intval($_POST["stock"]);
    $description = $_POST["description"];

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

// ----------------- EDIT SEEDLING -----------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["edit_treeseedling"])) {
    $tree_id = intval($_POST["tree_id"]);
    $name = $_POST["name"];
    $scientific = $_POST["scientific_name"];
    $price = floatval($_POST["price"]);
    $stock = intval($_POST["stock"]);
    $description = $_POST["description"];

    $stmt = mysqli_prepare($db, "UPDATE treespecies 
        SET COMMON_NAME=?, SCIENTIFIC_NAME=?, PRICE=?, STOCK=?, DESCRIPTION=? 
        WHERE TREESPECIES_ID=? AND SELLER_ID=?");
    mysqli_stmt_bind_param($stmt, "ssdisii", $name, $scientific, $price, $stock, $description, $tree_id, $seller_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: seller.php");
    exit();
}

// ----------------- DELETE SEEDLING -----------------
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
        <span class="navbar-text text-white ms-auto">
            Welcome, <?= htmlspecialchars($_SESSION["username"]); ?> (Seller)
        </span>
        <a href="logout.php" class="btn btn-outline-light ms-3">Logout</a>
    </div>
</nav>

<div class="container">
    <!-- Add New Seedling -->
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

    <!-- Seedlings -->
    <h3 class="mb-3">Your Tree Seedlings</h3>
    <?php 
    $result = mysqli_query($db,"SELECT * FROM treespecies WHERE SELLER_ID=$seller_id");
    if(mysqli_num_rows($result) > 0) {
        echo "<div class='row'>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "
            <div class='col-md-4'>
                <div class='card mb-4 shadow-sm'>
                    <img src='{$row['IMAGE']}' class='card-img-top' alt='Tree Image' style='height:200px; object-fit:cover;'>
                    <div class='card-body'>
                        <form method='POST'>
                            <input type='hidden' name='tree_id' value='{$row['TREESPECIES_ID']}'>
                            <input type='text' name='name' value='".htmlspecialchars($row['COMMON_NAME'])."' class='form-control mb-2'>
                            <input type='text' name='scientific_name' value='".htmlspecialchars($row['SCIENTIFIC_NAME'])."' class='form-control mb-2'>
                            <input type='number' step='0.01' name='price' value='{$row['PRICE']}' class='form-control mb-2'>
                            <input type='number' name='stock' value='{$row['STOCK']}' class='form-control mb-2'>
                            <textarea name='description' class='form-control mb-2' rows='3'>".htmlspecialchars($row['DESCRIPTION'])."</textarea>
                            <button type='submit' name='edit_treeseedling' class='btn btn-warning btn-sm'>Update</button>
                            <a href='seller.php?delete_id={$row['TREESPECIES_ID']}' onclick=\"return confirm('Delete this tree?');\" class='btn btn-danger btn-sm'>Delete</a>
                        </form>
                    </div>
                </div>
            </div>";
        }
        echo "</div>";
    } else {
        echo "<div class='alert alert-info'>You haven't added any seedlings yet.</div>";
    }
    ?>
</div>
</body>
</html>
