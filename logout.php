<?php

session_start();
session_destroy();

if (isset($_COOKIE["email"]) AND isset ($_COOKIE["password"])){
    setcookie("email", "", time() - (3600));
    setcookie("password", "", time() - (3600));
}
    
header("location:index.html");
exit();
?>