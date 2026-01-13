<?php
session_start();

if (!isset($_SESSION["role"])) {
    header("Location: /COMMERCE/login.php");
    exit();
}

if ($_SESSION["role"] !== "admin") {
    header("Location: /COMMERCE/user-dashboard.php");
    exit();
}

?>