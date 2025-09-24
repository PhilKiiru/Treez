<?php 

session_start();
include("db.php");

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $email = $_POST["email"];
    $phone = $_POST["phone"];
    $location = $_POST["location"];
    $password = $_POST["password"];
    $role = $_POST["role"];

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $check_sql = "SELECT * FROM users WHERE EMAIL = '$email'";
    $check_result = mysqli_query($db, $check_sql);
    if(mysqli_num_rows($check_result) > 0 ){
        echo "There is already a user with that email";
    }

    $sql = "INSERT INTO users (USERNAME, EMAIL, PHONE, LOCATION, ROLE, PASSWORD_HASH) VALUES ('$username', '$email', '$phone', '$location', '$role', '$hashed_password')";

    if(mysqli_query($db,$sql)) {
        echo "Registration successful!";
        header("Location: index.html");
        exit();
    } else {
        echo "Error: " . mysqli_error($db);
    }
}

?>