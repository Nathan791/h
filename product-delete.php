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
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Delete Product</title>
   <style>
    :root {
        --primary: #2c3e50;
        --accent: #3498db;
        --success: #27ae60;
        --danger: #e74c3c;
        --bg: #f4f7f6;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: var(--bg);
        color: var(--primary);
        line-height: 1.6;
        margin: 0;
        padding: 40px 20px;
    }

    .container {
        max-width: 900px;
        margin: 0 auto;
        background: #fff;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    h1 {
        color: var(--danger);
    }
    </style>
</head>
<body>
   
</body>
</html>