<?php
session_start();
$connection = new mysqli("localhost","root","","commerce");

if (!isset($_SESSION["user_id"])) {
    header("Location: /COMMERCE/login.php");
    exit();
}

function can($permission) {
    global $connection;

    $stmt = $connection->prepare("
        SELECT COUNT(*) FROM permissions p
        JOIN role_permissions rp ON rp.permission_id=p.id
        JOIN users u ON u.role_id=rp.role_id
        WHERE u.id=? AND p.code=?
    ");
    $stmt->bind_param("is", $_SESSION["user_id"], $permission);
    $stmt->execute();
    return $stmt->get_result()->fetch_row()[0] > 0;
}
