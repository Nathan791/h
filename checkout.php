<?php
session_start();

// 1. Early exit if cart is empty
if (empty($_SESSION['cart'])) {
    die("Your cart is empty.");
}

// 2. Database Connection (Consider moving this to a config file)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $db = new mysqli("localhost", "root", "", "commerce");
    $db->set_charset("utf8mb4");
} catch (Exception $e) {
    die("Connection failed: " . $e->getMessage());
}

// 3. Prepare IDs for a single batch query
$cartIds = array_keys($_SESSION['cart']);
$placeholders = implode(',', array_fill(0, count($cartIds), '?'));
$types = str_repeat('i', count($cartIds));

// 4. Fetch all relevant products at once
$stmt = $db->prepare("SELECT id, price, stock FROM products WHERE id IN ($placeholders)");
$stmt->bind_param($types, ...$cartIds);
$stmt->execute();
$result = $stmt->get_result();

$total = 0;
$itemsFound = 0;

// 5. Calculate total and validate stock
while ($p = $result->fetch_assoc()) {
    $id = $p['id'];
    $qty = $_SESSION['cart'][$id];
    
    if ($p['stock'] < $qty) {
        die("Stock insufficient for one or more items.");
    }
    
    $total += $p['price'] * $qty;
    $itemsFound++;
}

// Ensure all items in session actually existed in DB
if ($itemsFound !== count($_SESSION['cart'])) {
    // Optional: Logic to handle items that no longer exist
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-Out</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8f9fa; padding: 40px; color: #333; }
        .checkout-card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-width: 400px; margin: auto; }
        h2 { margin-top: 0; }
        .total-price { font-size: 1.5rem; font-weight: bold; color: #28a745; margin: 20px 0; }
        button { background-color: #28a745; color: white; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; width: 100%; font-size: 1rem; transition: background 0.3s; }
        button:hover { background-color: #218838; }
        .back-link { display: block; text-align: center; margin-top: 15px; color: #007bff; text-decoration: none; font-size: 0.9rem; }
    </style>
</head>
<body>

<div class="checkout-card">
    <h2>Checkout</h2>
    <p>Please review your total before proceeding to payment.</p>
    
    <div class="total-price">
        Total: $<?= number_format($total, 2) ?>
    </div>

    <form action="pay.php" method="post">
        <button type="submit">Proceed to Payment</button>
    </form>
    
    <a href="cart.php" class="back-link">← Return to Cart</a>
</div>

</body>
</html>