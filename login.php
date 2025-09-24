<?php

session_start();
include("db.php");


if($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];

    $sql = "SELECT * FROM users WHERE email = '$email' and password_hash ='$password'";
    $result = mysqli_query($db,$sql);
    $count = mysqli_num_rows($result);

    if($count == 1) {
        $user = mysqli_fetch_assoc($result);

        $_SESSION["user_id"] = $user["user_id"];
        $_SESSION["username"] = $user["username"];
        $_SESSION["email"] = $user["email"];
        $_SESSION["phone"] = $user["phone"];
        $_SESSION["location"] = $user["location"];
        $_SESSION["role"] = $user["role"];


        if (isset($_POST["remember"])){
            setcookie("email", $email, time() + (86400 * 30), "/");
            setcookie("password", $password, time() + (86400 * 30), "/");
        } else {
                setcookie("email", "", time() - (3600), "/");      

                setcookie("password", "", time() - (3600), "/");
            }
    

        header("location: dashboard.php"); 
        exit();       
    }else{
        $echo = "Invalid email or password!";
    }
}

?>