<?php
session_start();
require_once 'db.php';
// 1. Validate Product ID
$productId = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
if (!$productId) {
    header("Location: products.php?error=invalid_id"); // Redirect instead of die() for better UX
    exit;
}
// 2. Delete Product
try {
    $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $productId);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        header("Location: products.php?error=not_found");
        exit;
    }

    // Optionally, delete associated images
    $imgStmt = $db->prepare("DELETE FROM product_images WHERE product_id = ?");
    $imgStmt->bind_param("i", $productId);
    $imgStmt->execute();

    header("Location: products.php?success=deleted");
    exit;
} catch (Exception $e) {
    error_log($e->getMessage());
    header("Location: products.php?error=system_error");
    exit;
}
        
?>
