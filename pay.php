<?php
session_start();
require_once 'db.php'; // Assuming this provides a $db (mysqli) connection

if (!isset($_SESSION["email"]) || empty($_SESSION['cart'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. CSRF Validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Security violation: Invalid CSRF token.");
    }

    // 2. Start Database Transaction
    // This ensures if the stock update fails, the order isn't created.
    $db->begin_transaction();

    try {
        $userId = $_SESSION['user_id']; // Ensure this is set during login
        $totalOrderAmount = 0;
        $orderItems = [];

        // 3. Re-verify stock and price (never trust the client-side total)
        foreach ($_SESSION['cart'] as $id => $qty) {
            $stmt = $db->prepare("SELECT price, stock FROM products WHERE id = ? FOR UPDATE");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $product = $stmt->get_result()->fetch_assoc();

            if (!$product || $product['stock'] < $qty) {
                throw new Exception("Product ID $id is out of stock.");
            }

            $subtotal = $product['price'] * $qty;
            $totalOrderAmount += $subtotal;
            $orderItems[] = ['id' => $id, 'qty' => $qty, 'price' => $product['price']];
        }

        // 4. Create the Order Record
        $stmt = $db->prepare("INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'paid')");
        $stmt->bind_param("id", $userId, $totalOrderAmount);
        $stmt->execute();
        $orderId = $db->insert_id;

        // 5. Update Stock and Insert Order Items
        $updateStock = $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $insertItem = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");

        foreach ($orderItems as $item) {
            $updateStock->bind_param("ii", $item['qty'], $item['id']);
            $updateStock->execute();

            $insertItem->bind_param("iiid", $orderId, $item['id'], $item['qty'], $item['price']);
            $insertItem->execute();
        }

        // 6. Everything worked! Commit changes.
        $db->commit();
        unset($_SESSION['cart']);
        header("Location: payment_success.php?order_id=" . $orderId);
        exit();

    } catch (Exception $e) {
        // Something went wrong, undo everything
        $db->rollback();
        die("Transaction failed: " . $e->getMessage());
    }
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
    </style>
</head>
<body>
    <div class="checkout-card">
        <h2>Confirm Your Order</h2>
        <p>Total Amount: 
            <?php 
                $total = 0; 
                foreach ($_SESSION['cart'] as $id => $qty) {
                    $stmt = $db->prepare("SELECT price FROM products WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $product = $stmt->get_result()->fetch_assoc();
                    $total += $product['price'] * $qty;
                }
                echo '$' . number_format($total, 2); 
            ?>
        </p>
        <form method="POST" action="pay.php">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <button type="submit">Pay Now</button>
        </form>
    </div>
    <script>
        // Add this to your <script>
document.querySelector('form').onsubmit = function() {
    const btn = document.querySelector('.btn-pay');
    btn.disabled = true;
    btn.innerText = "Processing...";
};
    </script>
</body>
</html>