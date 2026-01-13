<?php
session_start();
require_once 'db.php';

$cart_items = [];
$grand_total = 0;

if (!empty($_SESSION['cart'])) {
    // Convert array keys (IDs) into a comma-separated string for the query
    $ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    // Fetch product details for items currently in the cart
    $query = "
        SELECT p.id, p.name, p.price, p.stock, pi.image_path 
        FROM products p 
        LEFT JOIN (
            SELECT product_id, MIN(image_path) as image_path 
            FROM product_images 
            GROUP BY product_id
        ) pi ON p.id = pi.product_id
        WHERE p.id IN ($placeholders)";

    $stmt = $db->prepare($query);
    $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $qty = $_SESSION['cart'][$row['id']];
        $subtotal = $row['price'] * $qty;
        $grand_total += $subtotal;
        
        $row['qty'] = $qty;
        $row['subtotal'] = $subtotal;
        $cart_items[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart | Premium Store</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f4f7f6; padding-top: 50px; }
        .cart-card { border: none; border-radius: 15px; background: #fff; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .product-img { width: 80px; height: 80px; object-fit: cover; border-radius: 10px; }
        .qty-input { width: 60px; text-align: center; border-radius: 5px; border: 1px solid #ddd; }
        .btn-checkout { background: #1f242d; color: #fff; border-radius: 30px; padding: 12px 30px; font-weight: 600; }
        .btn-checkout:hover { background: #00eeff; color: #1f242d; }
    </style>
</head>
<body>

<div class="container my-5">
    <div class="d-flex align-items-center mb-4">
        <a href="shop.php" class="text-dark me-3"><i class='bx bx-left-arrow-alt fs-2'></i></a>
        <h2 class="fw-bold m-0">Shopping Cart</h2>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card cart-card p-4">
                <?php if (empty($cart_items)): ?>
                    <div class="text-center py-5">
                        <i class='bx bx-cart-alt fs-1 text-muted'></i>
                        <p class="mt-3">Your cart is empty.</p>
                        <a href="shop.php" class="btn btn-outline-primary rounded-pill">Start Shopping</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart_items as $item): ?>
                                <tr id="row-<?= $item['id'] ?>">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?= htmlspecialchars($item['image_path'] ?? 'assets/placeholder.jpg') ?>" class="product-img me-3">
                                            <span class="fw-bold text-truncate" style="max-width: 150px;"><?= htmlspecialchars($item['name']) ?></span>
                                        </div>
                                    </td>
                                    <td>$<?= number_format($item['price'], 2) ?></td>
                                    <td>
                                        <input type="number" class="qty-input" value="<?= $item['qty'] ?>" min="1" max="<?= $item['stock'] ?>" 
                                               onchange="updateQty(<?= $item['id'] ?>, this.value)">
                                    </td>
                                    <td class="fw-bold">$<?= number_format($item['subtotal'], 2) ?></td>
                                    <td>
                                        <button class="btn text-danger" onclick="removeItem(<?= $item['id'] ?>)">
                                            <i class='bx bx-trash fs-5'></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card cart-card p-4">
                <h4 class="fw-bold mb-4">Order Summary</h4>
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal</span>
                    <span>$<?= number_format($grand_total, 2) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-4">
                    <span>Shipping</span>
                    <span class="text-success">Free</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-4">
                    <span class="fw-bold fs-5">Total</span>
                    <span class="fw-bold fs-5 text-primary">$<?= number_format($grand_total, 2) ?></span>
                </div>
                <button class="btn btn-checkout w-100" <?= empty($cart_items) ? 'disabled' : '' ?> onclick="window.location.href='checkout.php'">
                    Proceed to Checkout
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Update Quantity via AJAX
    async function updateQty(id, qty) {
        const response = await fetch('ajax/cart_update.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ id, qty })
        });
        const data = await response.json();
        if (data.success) location.reload();
        else alert(data.message);
    }

    // Remove Item via AJAX
    async function removeItem(id) {
        if (!confirm("Remove this item?")) return;
        const response = await fetch('ajax/cart_remove.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ id })
        });
        const data = await response.json();
        if (data.success) location.reload();
    }
</script>

</body>
</html>