<?php
session_start();
// 1. Auth Guard
if (!isset($_SESSION['id'])) {
    header("Location: /COMMERCE/Login.php");
    exit();
}
 // 2. Database Connection
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $db = new mysqli("localhost", "root", "", "commerce");
    $db->set_charset("utf8mb4");
} catch (Exception $e) {
    die("Connection failed. Please try again later.");
}
// 3. CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$errorMessage = "";
$successMessage = "";
// 4. Processing Logic
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token   = $_POST['csrf_token'] ?? '';
    if ($token !== $_SESSION['csrf_token']) {
        $errorMessage = "Security token mismatch.";
    } else {
        $product_id = intval($_POST['product_id'] ?? 0);
        if ($product_id <= 0) {
            $errorMessage = "Invalid product selection.";
        } else {
            // Check if product already in wishlist
            $check = $db->prepare("SELECT id FROM wishlists WHERE user_id = ? AND product_id = ?");
            $check->bind_param("ii", $_SESSION['id'], $product_id);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $errorMessage = "Product is already in your wishlist.";
            } else {
                // Add to wishlist
                $insert = $db->prepare("INSERT INTO wishlists (user_id, product_id) VALUES (?, ?)");
                $insert->bind_param("ii", $_SESSION['id'], $product_id);
                if ($insert->execute()) {
                    $successMessage = "Product added to your wishlist!";
                } else {
                    $errorMessage = "Failed to add product to wishlist. Please try again.";
                }
            }
            $check->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Wishlist</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body { background-color: #f8f9fa; padding-top: 50px; }
        .container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .table thead { background-color: #f1f3f5; }
    </style>
</head>
<body>
   <header class="mb-4">
       <div class="container">
        <i id="goBackBtn" class='bx bx-left-arrow-alt back-btn'></i>
           <h1 class="h3">❤️ My Wishlist</h1>
       </div>
    </header>
    <div class="container">
         <?php if ($errorMessage): ?>
              <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
         <?php endif; ?>
         <?php if ($successMessage): ?>
              <div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
         <?php endif; ?>
    
         <form method="POST" class="mb-4">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
              <div class="mb-3">
                <label for="product_id" class="form-label">Product ID to Add:</label>
                <input type="number" class="form-control" id="product_id" name="product_id" required>
              </div>
                <button type="submit" class="btn btn-primary">Add to Wishlist</button>
                </form>
    </div>
    <script>
        // Navigation
    document.getElementById('goBackBtn').onclick = () => window.history.back();
    </script>
</body>
</html>