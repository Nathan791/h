<?php 
session_start();
require_once 'db.php'; 

// 1. Validate Product ID
$productId = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
if (!$productId) {
    header("Location: products.php?error=invalid_id"); // Redirect instead of die() for better UX
    exit;
}

// 2. Fetch Product Data (Consolidated queries if possible, but separate is fine for clarity)
try {
    $stmt = $db->prepare("SELECT name, price, stock FROM products WHERE id = ?");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    if (!$product) {
        die("Product not found.");
    }

    // 3. Fetch Images
    $imgStmt = $db->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
    $imgStmt->bind_param("i", $productId);
    $imgStmt->execute();
    $images = $imgStmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log($e->getMessage());
    die("A system error occurred. Please try again later.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - View Product</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
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
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    /* Layout Grid */
    .product-grid {
        display: flex;
        gap: 40px;
        flex-wrap: wrap;
    }

    .product-info {
        flex: 1;
        min-width: 300px;
    }

    h1 { margin-top: 0; color: var(--primary); }

    .price {
        font-size: 1.5rem;
        font-weight: bold;
        color: var(--accent);
        margin: 10px 0;
    }

    .stock-status {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: bold;
    }

    .in-stock { background: #e8f5e9; color: var(--success); }
    .out-of-stock { background: #ffebee; color: var(--danger); }

    /* Gallery Styling */
    .gallery-title {
        margin-top: 30px;
        border-top: 1px solid #eee;
        padding-top: 20px;
    }

    .image-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 15px;
    }

    .image-grid img {
        width: 100%;
        height: 150px;
        object-fit: cover; /* Keeps aspect ratio without stretching */
        border-radius: 8px;
        border: 1px solid #ddd;
        transition: transform 0.2s ease;
    }

    .image-grid img:hover {
        transform: scale(1.05);
        cursor: zoom-in;
    }

    /* Action Buttons */
    .actions {
        margin-top: 30px;
        display: flex;
        gap: 15px;
    }

    .btn {
        text-decoration: none;
        padding: 10px 20px;
        border-radius: 6px;
        font-weight: 600;
        transition: opacity 0.2s;
    }

    .btn-edit { background: var(--accent); color: white; }
    .btn-back { background: #95a5a6; color: white; }
    .btn:hover { opacity: 0.85; }
</style>
</head>

<body>
    <div class="container">
        <i id="goBackBtn" class='bx bx-left-arrow-alt back-btn'></i>
        <div class="product-grid">
            <div class="product-info">
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <p class="price">$<?php echo number_format($product['price'], 2); ?></p>
                
                <p>
                    <strong>Status:</strong> 
                    <span class="stock-status <?php echo $product['stock'] > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                        <?php echo $product['stock'] > 0 ? (int)$product['stock'] . ' Available' : 'Out of Stock'; ?>
                    </span>
                </p>
            </div>
        </div>

        <div class="gallery-title">
            <h2>Product Images</h2>
        </div>

        <div class="image-grid">
            <?php if (count($images) > 0): ?>
                <?php foreach ($images as $img): ?>
                    <img src="<?php echo htmlspecialchars($img['image_path']); ?>" alt="Product image">
                <?php endforeach; ?>
            <?php else: ?>
                <p>No images found for this product.</p>
            <?php endif; ?>
        </div>

        <div class="actions">
            <a href="product-list.php" class="btn btn-back">Back to Catalog</a>
            <a href="product-edit.php?id=<?php echo $productId; ?>" class="btn btn-edit">Edit Details</a>
        </div>
    </div>
    <script>
        document.getElementById('goBackBtn').addEventListener('click', function() {
            window.history.back();
        });
    </script>
</body>
</html>