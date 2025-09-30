<?php 

session_start();
include("db.php");

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $email    = trim($_POST["email"]);
    $phone    = trim($_POST["phone"]);
    $location = trim($_POST["location"]);
    $password = $_POST["password"];
    $role     = $_POST["role"];

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $check_sql = "SELECT USER_ID FROM users WHERE EMAIL = ?";
    $stmt = mysqli_prepare($db, $check_sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if(mysqli_num_rows($result) > 0){
        echo "<div style='color:red; text-align:center;'>There is already a user with that email.</div>";
        exit();
    }
    mysqli_stmt_close($stmt);

    $sql = "INSERT INTO users (USERNAME, EMAIL, PHONE, LOCATION, ROLE, PASSWORD_HASH) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, "ssssss", $username, $email, $phone, $location, $role, $hashed_password);

    if(mysqli_stmt_execute($stmt)) {
        echo "<div style='color:green; text-align:center;'>Registration successful! Redirecting...</div>";
        header("refresh:2;url=index.html");
        exit();
    } else {
        echo "<div style='color:red; text-align:center;'>Error: " . htmlspecialchars(mysqli_error($db)) . "</div>";
    }
    mysqli_stmt_close($stmt);
}

?>
