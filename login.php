<?php
session_start();
include("db.php");

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
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

            // Redirect based on role
            if($row["ROLE"] == "SELLER") {
                header("Location: seller.php");
            } elseif ($row["ROLE"] == "BUYER") {
                header("Location: buyer.php");
            } elseif ($row["ROLE"] == "ADMIN") {
                header("Location: admin.php");
            }
            exit();
        } else {
            echo "<div style='color:red; text-align:center;'>Invalid password.</div>";
        }
    } else {
        echo "<div style='color:red; text-align:center;'>Invalid email or password.</div>";
    }
    mysqli_stmt_close($stmt);
}
?>
