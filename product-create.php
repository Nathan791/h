<?php
ob_start();
session_start();

// 1. Centralized Configuration
$upload_base_dir = "uploads/products/";
$error = "";
$success = "";

// 2. DB Connection
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $db = new mysqli("localhost", "root", "", "commerce");
    $db->set_charset("utf8mb4");
} catch (Exception $e) {
    die("Database connection failed.");
}

// 3. CSRF Guard
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* ==========================
   HELPERS
========================== */
function isValidImage($file_path) {
    if (!file_exists($file_path)) return false;
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file_path);
    finfo_close($finfo);
    return in_array($mime, $allowed);
}

function downloadImage($url, $dest) {
    $options = ['http' => ['timeout' => 5]]; // Prevent hanging
    $context = stream_context_create($options);
    $content = @file_get_contents($url, false, $context);
    if (!$content || strlen($content) > 5000000) return false; // 5MB Limit
    file_put_contents($dest, $content);
    return isValidImage($dest);
}

/* ==========================
   FORM PROCESSING
========================== */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
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
                // Insert Product
                $stmt = $db->prepare("INSERT INTO products (name, price, stock, status) VALUES (?, ?, ?, 'active')");
                $stmt->bind_param("sdi", $name, $price, $stock);
                $stmt->execute();
                $productId = $db->insert_id;

                $dir = $upload_base_dir . "product_$productId/";
                if (!is_dir($dir)) mkdir($dir, 0755, true);

                $imgStmt = $db->prepare("INSERT INTO product_images (product_id, image_path) VALUES (?, ?)");

                // Handle Local Uploads
                if (!empty($_FILES['images']['tmp_name'][0])) {
                    foreach ($_FILES['images']['tmp_name'] as $k => $tmp) {
                        if ($_FILES['images']['error'][$k] === UPLOAD_ERR_OK && isValidImage($tmp)) {
                            $ext = pathinfo($_FILES['images']['name'][$k], PATHINFO_EXTENSION);
                            $path = $dir . bin2hex(random_bytes(8)) . "." . $ext;
                            if (move_uploaded_file($tmp, $path)) {
                                $imgStmt->bind_param("is", $productId, $path);
                                $imgStmt->execute();
                            }
                        }
                    }
                }

                // Handle URL Uploads
                $urls = array_filter(array_map('trim', explode("\n", $_POST['image_urls'] ?? "")));
                foreach ($urls as $url) {
                    if (filter_var($url, FILTER_VALIDATE_URL)) {
                        $path = $dir . bin2hex(random_bytes(8)) . ".jpg";
                        if (downloadImage($url, $path)) {
                            $imgStmt->bind_param("is", $productId, $path);
                            $imgStmt->execute();
                        }
                    }
                }

                $db->commit();
                $success = "Product created successfully!";
                // Refresh token to prevent double submission
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                
            } catch (Exception $e) {
                $db->rollback();
                $error = "Error saving product: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Create Product</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .preview-carousel img { height: 400px; object-fit: cover; border-radius: 8px; }
        .form-container { max-width: 600px; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    </style>
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="form-container mb-5">
                <h2 class="mb-4">📦 Create New Product</h2>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                    <div class="mb-3">
                        <label class="form-label">Product Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Leather Jacket" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Price (€)</label>
                            <input type="number" step="0.01" name="price" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Stock Quantity</label>
                            <input type="number" name="stock" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Upload Local Images</label>
                        <input type="file" name="images[]" multiple class="form-control" accept="image/*">
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Or Import via URL (One per line)</label>
                        <textarea name="image_urls" class="form-control" rows="3" placeholder="https://example.com/image.jpg"></textarea>
                    </div>

                    <button type="submit" class="btn btn-success w-100 py-2 fw-bold">🚀 Create Product</button>
                </form>
            </div>

            <hr>

            <h3 class="my-4">Recently Uploaded Images</h3>
            <?php
            $imgRes = $db->query("SELECT image_path FROM product_images ORDER BY id DESC LIMIT 5");
            if ($imgRes->num_rows > 0): 
            ?>
            <div id="slider" class="carousel slide shadow-sm preview-carousel" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <?php $active = true; while ($img = $imgRes->fetch_assoc()): ?>
                        <div class="carousel-item <?= $active ? 'active' : '' ?>">
                            <img src="<?= htmlspecialchars($img['image_path']) ?>" class="d-block w-100">
                        </div>
                    <?php $active = false; endwhile; ?>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#slider" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#slider" data-bs-slide="next">
                    <span class="carousel-control-next-icon"></span>
                </button>
            </div>
            <?php else: ?>
                <p class="text-muted">No images found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>