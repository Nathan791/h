<?php
require "auth.php";
$connection = new mysqli("localhost", "root", "", "commerce");

$id = (int)$_GET["id"];
$connection->query("DELETE FROM users WHERE id=$id");

header("Location: users.php");
exit();
