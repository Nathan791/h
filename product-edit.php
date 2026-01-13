<?php
session_start();
require_once 'db.php';

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$upload_base_dir = "uploads/products/";
$error = "";
$success = "";

// 1. Validate Product ID
$productId = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
if (!$productId) {
    header("Location: shop_management.php");
    exit;
}

// 2. Fetch Product Data
$stmt = $db->prepare("SELECT name, price, stock FROM products WHERE id = ?");
$stmt->bind_param("i", $productId);
$stmt->execute();
$currentProduct = $stmt->get_result()->fetch_assoc();

if (!$currentProduct) {
    die("Product not found.");
}

// 3. Form Processing
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Security token mismatch.";
    } else {
        $name  = trim($_POST['name'] ?? "");
        $price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
        $stock = filter_var($_POST['stock'], FILTER_VALIDATE_INT);

        if (!$name || $price === false || $stock === false) {
            $error = "Please provide valid product details.";
        } else {
            $db->begin_transaction();
            try {
                // Update Product Info
                $stmt = $db->prepare("UPDATE products SET name = ?, price = ?, stock = ? WHERE id = ?");
                $stmt->bind_param("sdii", $name, $price, $stock, $productId);
                $stmt->execute();

                $dir = $upload_base_dir . "product_$productId/";
                if (!is_dir($dir)) mkdir($dir, 0755, true);

                $imgStmt = $db->prepare("INSERT INTO product_images (product_id, image_path) VALUES (?, ?)");

                // A. Handle Local File Uploads
                if (!empty($_FILES['images']['name'][0])) {
                    foreach ($_FILES['images']['tmp_name'] as $k => $tmp) {
                        if ($_FILES['images']['error'][$k] === UPLOAD_ERR_OK) {
                            // Validate it is actually an image
                            $check = getimagesize($tmp);
                            if ($check !== false) {
                                $ext = strtolower(pathinfo($_FILES['images']['name'][$k], PATHINFO_EXTENSION));
                                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                                
                                if (in_array($ext, $allowed)) {
                                    $newFileName = bin2hex(random_bytes(8)) . "." . $ext;
                                    $destination = $dir . $newFileName;
                                    
                                    if (move_uploaded_file($tmp, $destination)) {
                                        $imgStmt->bind_param("is", $productId, $destination);
                                        $imgStmt->execute();
                                    }
                                }
                            }
                        }
                    }
                }

                // B. Handle Image URLs
                if (!empty(trim($_POST['image_urls']))) {
                    $urls = preg_split('/\r\n|[\r\n]/', $_POST['image_urls']);
                    foreach ($urls as $url) {
                        $url = trim($url);
                        if (filter_var($url, FILTER_VALIDATE_URL)) {
                            // Basic security: ensure it looks like an image URL
                            $imgData = @file_get_contents($url);
                            if ($imgData) {
                                $newFileName = "url_" . bin2hex(random_bytes(8)) . ".jpg";
                                $destination = $dir . $newFileName;
                                file_put_contents($destination, $imgData);
                                
                                $imgStmt->bind_param("is", $productId, $destination);
                                $imgStmt->execute();
                            }
                        }
                    }
                }

                $db->commit();
                $success = "Product updated successfully!";
                header("location: shop_management.php?success=product_updated");
                exit;
                // Refresh local data for display
                $currentProduct = ['name' => $name, 'price' => $price, 'stock' => $stock];

            } catch (Exception $e) {
                $db->rollback();
                $error = "Update failed: " . $e->getMessage();
            }
        }
    }
}

// Fetch current images
$imgQuery = $db->prepare("SELECT id, image_path FROM product_images WHERE product_id = ?");
$imgQuery->bind_param("i", $productId);
$imgQuery->execute();
$images = $imgQuery->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit <?php echo htmlspecialchars($currentProduct['name']); ?> - Edit Product</title>
    <link href="https://fonts.googleapis.com/css2?family=Segoe+UI&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
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
    form label {
        display: block;
        margin-bottom: 8px;
        font-weight: bold;
    }
    form input[type="text"], form input[type="number"], form textarea {
        width: 100%;
        padding: 10px;
        margin-bottom: 20px;
        border: 1px solid #ccc;
        border-radius: 6px;
        box-sizing: border-box;
    }
    form button {
        background-color: var(--accent);
        color: #fff;
        border: none;
        padding: 12px 20px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 16px;
    }
    form button:hover {
        background-color: #2980b9;
    }
    .message {
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 20px;
    }
    .message.success {
        background-color: var(--success);
        color: #fff;
    }
    .message.error {
        background-color: var(--danger);
        color: #fff;
    }
    .image-gallery {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-top: 20px;
    }
    .image-gallery img {
        max-width: 150px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    /* Enhanced Image Grid CSS */
.img-preview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 15px;
    padding: 10px;
    background: #fdfdfd;
    border: 1px dashed #ccc;
    border-radius: 8px;
}

.img-item {
    position: relative;
    overflow: hidden;
    border-radius: 8px;
    transition: transform 0.2s;
}

.img-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.delete-img {
    position: absolute;
    top: 5px;
    right: 5px;
    background: #ff4757;
    color: white;
    border: none;
    border-radius: 50%;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0; /* Hidden until hover */
    transition: opacity 0.2s;
}

.img-item:hover .delete-img {
    opacity: 1;
}
    </style>
    </head>
    <body>
         <div class="container">
            <i id="goBackBtn" class='bx bx-left-arrow-alt back-btn'></i>
        <h1>Edit Product: <?php echo htmlspecialchars($currentProduct['name']); ?></h1>
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
         </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="message success"><?php echo htmlspecialchars($success); ?></div>
         </div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <label for="name">Product Name:</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($currentProduct['name']); ?>" required>

            <label for="price">Price ($):</label>
            <input type="number" step="0.01" id="price" name="price" value="<?php echo htmlspecialchars($currentProduct['price']); ?>" required>

            <label for="stock">Stock Quantity:</label>
            <input type="number" id="stock" name="stock" value="<?php echo htmlspecialchars($currentProduct['stock']); ?>" required>

            <label for="images">Upload Images:</label>
            <input type="file" id="images" name="images[]" multiple accept="image/*">

            <label for="image_urls">Or Enter Image URLs (one per line):</label>
            <textarea id="image_urls" name="image_urls" rows="4" placeholder="http://example.com/image1.jpg&#10;http://example.com/image2.png"></textarea>

            <button type="submit">Update Product</button>
        </form>
        <h2>Current Images</h2>
        <div class="image-gallery">
            <?php if (count($images) > 0): ?>
                <?php foreach ($images as $img): ?>
                    <div class="img-item">
                        <img src="<?php echo htmlspecialchars($img['image_path']); ?>" alt="Product Image" width="150">
                        <form method="POST" action="delete-image.php" onsubmit="return confirm('Are you sure you want to delete this image?');" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="image_id" value="<?php echo $img['id']; ?>">
                            <button type="submit" class="delete-img">&times;</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No images found for this product.</p>
            <?php endif; ?>
        </div>
    <script>
        document.getElementById('goBackBtn').addEventListener('click', function() {
            window.location.href = 'shop_management.php';
        });
        </script>
    </body>
    </html>
   