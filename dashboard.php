<?php

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
</head>
<body>
    <h2>Welcome, <?php echo $_SESSION["username"]; ?></h2>
    <p>Your email is: <?php echo $_SESSION["email"]; ?></p>
    <p>Your phone is: <?php echo $_SESSION["phone"]; ?></p>
    <p>Your location is: <?php echo $_SESSION["location"]; ?></p>
    <p>Your role is: <?php echo $_SESSION["role"]; ?></p>
    <a href="logout.php">Logout</a>
</body>
</html>
