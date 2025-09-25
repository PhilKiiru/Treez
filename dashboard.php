<?php

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION["role"] == "SELLER") {
    header("Location: seller.php");
    exit();
} elseif ($_SESSION["role"] == "BUYER") {
    header("Location: buyer.php");
    exit();
}

?>

