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

    $sql = "INSERT INTO treespecies (name, price, stock, description, seler_id) VALUES ('$name', '$price', '$stock', '$description', '$seller_id')";
    mysqli_query($db, $sql);
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
        <button type="submit" name="add-treeseedling">Add Seedling</button>
    </form>

    <h3>Your Tree Seedlings</h3>
    <?php 
    $seller_id = $_SESSION["user_id"];
    $result = mysqli_query($db,"SELECT * FROM treespecies WHERE seller_id = $seller_id");
    while ($row = mysqli_fetch_array($result)) {
        echo "<p>{$row['name']} - {$row['price']} KES ({$row['stock']} in stock)</p>";
    }

    ?>
    <a href="logout.php">Logout</a>
    
</body>
</html>