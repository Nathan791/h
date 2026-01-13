<?php
session_start();

// 1. Access Control: Ensure user is logged in
if (!isset($_SESSION["id"])) {
    header("Location: /COMMERCE/login.php");
    exit();
}

$user_id = $_SESSION["id"];

/* ===============================
   DB CONNECTION & QUERY
================================ */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // In a real project, move connection details to a config.php file
    $db = new mysqli("localhost", "root", "", "commerce");
    $db->set_charset("utf8mb4");

    $stmt = $db->prepare("
        SELECT id, created_at, total, status 
        FROM orders 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

} catch (mysqli_sql_exception $e) {
    // Log error internally, show user a clean message
    error_log($e->getMessage());
    die("Unable to fetch order history. Please try again later.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders | Commerce</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f8f9fa; padding-top: 50px; }
        .container { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .table thead { background-color: #f1f3f5; }
    </style>
</head>
<body>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">📦 My Order History</h1>
        <a href="/COMMERCE/user-dashboard.php" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
    </div>

    <?php if ($result->num_rows === 0): ?>
        <div class="alert alert-light border text-center py-5">
            <p class="text-muted mb-0">You haven't placed any orders yet.</p>
            <a href="/COMMERCE/shop.php" class="btn btn-primary mt-3">Start Shopping</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th class="ps-3">Order ID</th>
                        <th>Date Purchased</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th class="text-end pe-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="ps-3 fw-bold">#<?= htmlspecialchars($order['id']) ?></td>
                            <td><?= date("M d, Y • H:i", strtotime($order['created_at'])) ?></td>
                            <td class="fw-semibold text-dark">
                                <?= number_format($order['total'], 2) ?> €
                            </td>
                            <td>
                                <?php 
                                    $statusClass = match($order['status']) {
                                        'completed' => 'success',
                                        'cancelled' => 'danger',
                                        'shipped'   => 'info',
                                        default     => 'warning'
                                    };
                                ?>
                                <span class="badge rounded-pill bg-<?= $statusClass ?>">
                                    <?= ucfirst(htmlspecialchars($order['status'])) ?>
                                </span>
                            </td>
                            <td class="text-end pe-3">
                                <a href="order-details.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-light border">View Details</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php 
// Clean up
$stmt->close();
$db->close(); 
?>
</body>
</html>