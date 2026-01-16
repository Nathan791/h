<?php
session_start();
require_once 'db.php';

// 1. Security Check: If there's no order ID in session, redirect to shop
if (!isset($_SESSION['last_order_id'])) {
    header("Location: shop.php");
    exit();
}

$orderId = $_SESSION['last_order_id'];
$userId = $_SESSION['user_id'];

// 2. Fetch Order Details (Join with items and products)
$stmt = $db->prepare("
    SELECT o.total_price, o.created_at, oi.quantity, oi.price, p.name 
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->bind_param("ii", $orderId, $userId);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
$orderTotal = 0;
$orderDate = '';

while ($row = $result->fetch_assoc()) {
    $items[] = $row;
    $orderTotal = $row['total_price'];
    $orderDate = $row['created_at'];
}

// Optional: Clear the success session so refresh doesn't keep showing this specific receipt
// unset($_SESSION['last_order_id']); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Successful | Commerce</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f7f6; color: #333; line-height: 1.6; }
        .container { max-width: 600px; margin: 50px auto; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .success-icon { color: #28a745; font-size: 4rem; text-align: center; display: block; margin-bottom: 20px; }
        h1 { text-align: center; color: #1a1a1a; margin-bottom: 10px; }
        .order-meta { text-align: center; color: #777; margin-bottom: 30px; font-size: 0.9rem; }
        .receipt-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .receipt-table th { text-align: left; border-bottom: 2px solid #eee; padding: 10px; }
        .receipt-table td { padding: 10px; border-bottom: 1px solid #eee; }
        .total-row { font-weight: bold; font-size: 1.2rem; }
        .btn-home { display: block; text-align: center; background: #007bff; color: white; padding: 12px; border-radius: 6px; text-decoration: none; margin-top: 20px; }
        .btn-home:hover { background: #0056b3; }
    </style>
</head>
<body>

<div class="container">
    <span class="success-icon">✔</span>
    <h1>Payment Received!</h1>
    <p class="order-meta">Order #<?= $orderId ?> • Confirmed on <?= date('M d, Y H:i', strtotime($orderDate)) ?></p>

    <table class="receipt-table">
        <thead>
            <tr>
                <th>Item</th>
                <th>Qty</th>
                <th>Price</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['name']) ?></td>
                <td><?= $item['quantity'] ?></td>
                <td>$<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td colspan="2">Total Paid</td>
                <td>$<?= number_format($orderTotal, 2) ?></td>
            </tr>
        </tbody>
    </table>

    <p style="text-align: center;">A confirmation email has been sent to your inbox.</p>
    <a href="shop.php" class="btn-home">Continue Shopping</a>
</div>

</body>
</html>