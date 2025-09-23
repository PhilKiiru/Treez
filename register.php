<?php 

session_start();
include("db.php");

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $email = $_POST["email"];
    $password = $_POST["password"];

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (username, email, password_hash) VALUES ('$username', '$email', '$hashed_password')";

    if(mysqli_query($db,$sql)) {
        echo "Registration successful!";
        header("Location: index.html");
        exit();
    } else {
        echo "Error: " . mysqli_error($db);
    }
}

?>