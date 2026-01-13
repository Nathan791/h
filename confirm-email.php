<?php
$db = new mysqli("localhost", "root", "", "commerce");

$token = $_GET['token'] ?? '';

$stmt = $db->prepare("
    SELECT id, email_new FROM users WHERE email_token=?
");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if ($user) {
    $stmt = $db->prepare("
        UPDATE users 
        SET email=email_new, email_new=NULL, email_token=NULL
        WHERE id=?
    ");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    echo "Email confirmed successfully!";
} else {
    echo "Invalid or expired token.";
}
?>