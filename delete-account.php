<?php 
session_start();
if (!isset($_SESSION['id'], $_SESSION['email'])) {
    header("Location: /COMMERCE/login.php");
    exit();
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$db = new mysqli("localhost", "root",  "", "commerce");
$db->set_charset("utf8mb4");
try {
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['id']);
    $stmt->execute();
    $stmt->close();
    $db->close();

    // Destroy session and redirect to homepage
    session_unset();
    session_destroy();
    header("Location: /COMMERCE/index.php");
    exit();
} catch (mysqli_sql_exception $e) {
    error_log($e->getMessage());
    die("An error occurred while deleting your account. Please try again later.");
}
?>