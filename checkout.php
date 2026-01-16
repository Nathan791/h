<?php
session_start();
require_once 'db.php';
require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

\Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET']);

if (empty($_SESSION['cart'])) {
    header("Location: shop.php");
    exit();
}

$line_items = [];
$order_total = 0;
$items_to_save = [];

// 1. Fetch data and calculate total server-side
foreach ($_SESSION['cart'] as $productId => $quantity) {
    $stmt = $db->prepare("SELECT id, name, price FROM products WHERE id = ?");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    if ($product) {
        $unit_amount = (int)($product['price'] * 100); // Convert to cents
        $line_items[] = [
            'price_data' => [
                'currency' => 'usd',
                'product_data' => ['name' => $product['name']],
                'unit_amount' => $unit_amount,
            ],
            'quantity' => $quantity,
        ];
        $order_total += ($unit_amount * $quantity);
        $items_to_save[] = $product;
    }
}

// 2. Create a "Pending" order in your database
// This ensures you have a record even if the user closes the tab mid-payment
$user_id = $_SESSION['user_id'] ?? 0; 
$stmt = $db->prepare("INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'pending')");
$stmt->bind_param("ii", $user_id, $order_total);
$stmt->execute();
$internal_order_id = $db->insert_id;

// 3. Create Stripe Session with Metadata
try {
    $checkout_session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => $line_items,
        'mode' => 'payment',
        // Use environment variable for the base URL
        'success_url' => $_ENV['BASE_URL'] . '/payment_success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => $_ENV['BASE_URL'] . '/cart.php',
        'metadata' => [
            'order_id' => $internal_order_id // CRITICAL: Links Stripe to your DB
        ],
    ]);

    header("HTTP/1.1 303 See Other");
    header("Location: " . $checkout_session->url);
} catch (Exception $e) {
    error_log("Stripe Error: " . $e->getMessage());
    echo "Temporary payment error. Please try again later.";
}
// Ensure all items in session actually existed in DB
if ($itemsFound !== count($_SESSION['cart'])) {
    // Optional: Logic to handle items that no longer exist
}

// Check if token is older than 1 hour (3600 seconds)
$max_time = 3600; 
if (time() - $_SESSION['csrf_token_time'] > $max_time) {
    unset($_SESSION['csrf_token']); // Clear expired token
    die("Session expired. Please refresh the page.");
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

  <form action="pay.php" method="POST">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    <button type="submit">Confirm and Pay</button>
</form>
    
    <a href="cart.php" class="back-link">← Return to Cart</a>
</div>

</body>
</html>