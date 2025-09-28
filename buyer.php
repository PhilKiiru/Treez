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

            $sql = "INSERT INTO orders (BUYER_ID, TREESEEDLING_ID, QUANTITY, TOTAL_PRICE, ORDER_STATUS, ORDER_DATE) VALUES ('$buyer_id', '$tree_id',)";
        }
    }
}


?>