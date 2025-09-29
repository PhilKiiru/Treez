<?php

session_start();
include("db.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);


if($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];

    $stmt = mysqli_prepare($db, "SELECT USER_ID, USERNAME, ROLE, PASSWORD_HASH FROM users WHERE EMAIL = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if($row = mysqli_fetch_assoc($result)) {
        
        if(password_verify($password, $row["PASSWORD_HASH"])) {

        $_SESSION["user_id"] = $row["USER_ID"];
        $_SESSION["username"] = $row["USERNAME"];
        $_SESSION["role"] = $row["ROLE"];

        if($row["ROLE"] == "SELLER") {
            header("Location: seller.php");
            exit();
        } elseif ($row["ROLE"] == "BUYER") {
            header("Location: buyer.php");
            exit();
        } elseif ($row["ROLE"] == "ADMIN") {
            header("Location: admin.php");
            exit();
        }
        } else {
            echo "<p style='color:red;'>Invalid password.</p>";
        }
    
    }else{
        $echo = "Invalid password!";
    }
    mysqli_stmt_close($stmt);
}

?>
