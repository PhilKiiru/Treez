<?php

session_start();
include("db.php");


if($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $stmt = mysqli_prepare($db, "SELECT * FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if($user = mysqli_fetch_assoc($result)) {
        if(password_verify($password, $user["PASSWORD_HASH"])) {

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
        $echo = "Invalid password!";
    }
    }else{
        echo "No account for that email.";
    }
}

?>