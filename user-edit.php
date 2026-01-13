<?php
require "auth.php";
$connection = new mysqli("localhost", "root", "", "commerce");

$id = (int)$_GET["id"];
$user = $connection->query("SELECT * FROM users WHERE id=$id")->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $role = $_POST["role"];
    $stmt = $connection->prepare("UPDATE users SET role=? WHERE id=?");
    $stmt->bind_param("si", $role, $id);
    $stmt->execute();
    header("Location: users.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Edit User</title>
   <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body>
   <form method="post">
<h2>Modify role</h2>
<select name="role">
    <option value="user" <?= $user['role']=="user"?"selected":"" ?>>User</option>
    <option value="admin" <?= $user['role']=="admin"?"selected":"" ?>>Admin</option>
</select>
<button>Enregistrer</button>
</form>
</body>
</html>

