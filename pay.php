<?php
session_start();
require_once 'db.php';
require __DIR__ . '/vendor/autoload.php';

// Load .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$stripeKey = $_ENV['STRIPE_SECRET'] ?? null;
if (!$stripeKey) {
    http_response_code(500);
    error_log('Missing STRIPE_SECRET env var');
    echo 'Server configuration error';
    exit;  
}

\Stripe\Stripe::setApiKey($stripeKey);

if (empty($_SESSION['cart'])) {
    header("Location: shop.php");
    exit();
}

$line_items = [];
$total_cents = 0;

// 1. Prepare items for Stripe
foreach ($_SESSION['cart'] as $productId => $quantity) {
    $stmt = $db->prepare("SELECT name, price FROM products WHERE id = ?");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    if ($product) {
        $line_items[] = [
            'price_data' => [
                'currency' => 'usd',
                'product_data' => ['name' => $product['name']],
                'unit_amount' => $product['price'] * 100, // Stripe uses cents
            ],
            'quantity' => $quantity,
        ];
    }
}

// 2. Create Stripe Checkout Session
try {
    $checkout_session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => $line_items,
        'mode' => 'payment',
        'success_url' => 'http://localhost/payment_success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => 'http://localhost/cart.php',
    ]);

    // Redirect to Stripe's secure hosted page
    header("HTTP/1.1 303 See Other");
    header("Location: " . $checkout_session->url);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}