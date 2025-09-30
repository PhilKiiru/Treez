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