<?php 
require 'db.php';
session_start();
// Fetch all products
try {
    $result = $db->query("SELECT id, name, price, stock FROM products");
    $products = $result->fetch_all(MYSQLI_ASSOC);
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
    <title>Product List</title>
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
        max-width: 1200px;
        margin: auto;
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    th, td {
        padding: 12px;
        border-bottom: 1px solid #ddd;
        text-align: left;
    }
    th {
        background-color: var(--accent);
        color: #fff;
    }
    tr:hover {
        background-color: #f1f1f1;
    }
    a.button {
        display: inline-block;
        padding: 10px 15px;
        background-color: var(--accent);
        color: #fff;
        text-decoration: none;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    a.button:hover {
        background-color: #2980b9;
    }
    </style>
    </head>
     <body>
        <div class="container">
            <i id="goBackBtn" class='bx bx-left-arrow-alt back-btn'></i>
            <h1>📦 Product List</h1>
            <a href="product-create.php" class="button"><i class="fas fa-plus"></i> Add New Product</a>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Price (€)</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?= htmlspecialchars($product['id']) ?></td>
                        <td><?= htmlspecialchars($product['name']) ?></td>
                        <td><?= number_format($product['price'], 2) ?></td>
                        <td><?= htmlspecialchars($product['stock']) ?></td>
                        <td>
                            <a href="product-view.php?id=<?= urlencode($product['id']) ?>" class="button"><i class="fas fa-eye"></i> View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <script>
            document.getElementById('goBackBtn').onclick = function() {
        window.history.back();
    };
        </script>
    </body>
</html>