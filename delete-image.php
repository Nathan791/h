<?php 
session_start();
require_once 'db.php';
// 1. Auth Check
if (!isset($_SESSION["email"])) {
    header("Location: /COMMERCE/login.php");
    exit();
}
// 2. CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$userName = htmlspecialchars($_SESSION["name"] ?? 'User');

// 3. Image Deletion Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['image_id'], $_POST['csrf_token'])) {
    if (hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $imageId = intval($_POST['image_id']);
        // Fetch image path from DB
        $stmt = $db->prepare("SELECT image_path FROM product_images WHERE id = ?");
        $stmt->bind_param("i", $imageId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $image = $result->fetch_assoc();
            $imagePath = $image['image_path'];
            // Delete file from server
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
            // Delete record from DB
            $delStmt = $db->prepare("DELETE FROM product_images WHERE id = ?");
            $delStmt->bind_param("i", $imageId);
            $delStmt->execute();
        }
        $stmt->close();
    } else {
        die("CSRF token validation failed.");
    }
}header("Location: shop_management.php");
?>