<?php 

$db_server = "localhost";
$db_user   = "root";
$db_pass   = "";
$db_name   = "treez";

$db = mysqli_connect($db_server, $db_user, $db_pass, $db_name);

if(!$db){
    die("Database connection failed: " . mysqli_connect_error());
}

?>
