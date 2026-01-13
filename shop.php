<?php
session_start();
require_once 'db.php';

// 1. Fetch Products that are in stock
try {
    $query = "
        SELECT p.id, p.name, p.price, p.stock, pi.image_path 
        FROM products p 
        LEFT JOIN (
            SELECT product_id, MIN(image_path) as image_path 
            FROM product_images 
            GROUP BY product_id
        ) pi ON p.id = pi.product_id
        WHERE p.stock > 0 
        ORDER BY p.id DESC";
    
    $result = $db->query($query);
} catch (Exception $e) {
    error_log($e->getMessage());
    $error = "Unable to load products.";
}

// Calculate cart count for the header badge
$cart_count = !empty($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Store | Shop</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        :root { --main-bg: #f4f7f6; --nav-bg: #1f242d; --accent: #00eeff; }
        body { font-family: 'Poppins', sans-serif; background: var(--main-bg); padding-top: 90px; }
        
        header { 
            position: fixed; top: 0; width: 100%; z-index: 1000;
            background: var(--nav-bg); color: #fff; padding: 15px 5%;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .product-card { 
            border: none; border-radius: 15px; background: #fff; 
            transition: transform 0.3s, box-shadow 0.3s; height: 100%;
            overflow: hidden;
        }
        .product-card:hover { transform: translateY(-10px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        
        .img-wrapper { position: relative; width: 100%; padding-top: 100%; background: #eee; }
        .img-wrapper img { 
            position: absolute; top: 0; left: 0; width: 100%; height: 100%; 
            object-fit: cover; 
        }

        .price-tag { color: #27ae60; font-weight: 600; font-size: 1.1rem; }
        .btn-add { background: var(--nav-bg); color: #fff; border-radius: 25px; font-weight: 600; transition: 0.3s; border: none; }
        .btn-add:hover { background: var(--accent); color: var(--nav-bg); }
        
        .badge-cart { font-size: 0.7rem; padding: 3px 6px; }
    </style>
</head>
<body>

<header>
    <div class="logo">
        <h2 class="m-0 fs-4 fw-bold">Premium<span style="color:var(--accent)">Store</span></h2>
    </div>
    <nav>
        <a href="cart.php" class="text-white text-decoration-none position-relative">
            <i class='bx bx-shopping-bag fs-3'></i>
            <span id="cart-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger badge-cart">
                <?= $cart_count ?>
            </span>
        </a>
    </nav>
</header>

<div class="container mb-5">
    <div class="row g-4">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($product = $result->fetch_assoc()): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="card product-card shadow-sm">
                        <div class="img-wrapper">
                            <img src="<?= htmlspecialchars($product['image_path'] ?? 'assets/placeholder.jpg') ?>" 
                                 onerror="this.src='https://via.placeholder.com/300x300?text=No+Image'"
                                 alt="<?= htmlspecialchars($product['name']) ?>">
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h6 class="text-truncate mb-1"><?= htmlspecialchars($product['name']) ?></h6>
                            <p class="price-tag mb-3">$<?= number_format($product['price'], 2) ?></p>
                            <button class="btn btn-add w-100 mt-auto py-2" onclick="addToCart(<?= $product['id'] ?>)">
                                <i class='bx bx-plus-circle'></i> Buy Now
                            </button>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class='bx bx-search-alt fs-1 text-muted'></i>
                <p class="mt-3">No products available at the moment.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    async function addToCart(id) {
        try {
            const response = await fetch('ajax/cart_add.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, qty: 1 })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Update badge without refresh
                const badge = document.getElementById('cart-badge');
                badge.innerText = parseInt(badge.innerText) + 1;
                
                // Optional: Provide visual feedback
                alert('Product added to cart!');
            } else {
                alert(data.message || 'Error adding to cart');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Could not connect to server.');
        }
    }
</script>

</body>
</html>