<?php

session_start();
include("db.php");
$error='';

if($SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];
    $sql = "SELECT * FROM users WHERE email = '$email' and password_hash ='$password'";
    $result = mysqli_query($db,$sql);
}

?>