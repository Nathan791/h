<?php
session_start();

// 1. Centralized Auth Check
if (!isset($_SESSION["email"])) {
    header("Location: /COMMERCE/login.php");
    exit();
}

// 2. Database Connection with Error Handling
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $connection = new mysqli("localhost", "root", "", "commerce");
    $connection->set_charset("utf8mb4");
} catch (Exception $e) {
    die("Database Connection Error");
}

// 3. Optimized Query: Fetch only necessary columns
$query = "SELECT o.id, o.total, o.status, o.created_at, u.name as client_name 
          FROM orders o
          JOIN users u ON u.id = o.user_id
          ORDER BY o.created_at DESC";

$result = $connection->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management | Admin</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .badge-status { width: 100px; display: inline-block; padding: 0.5em; }
        .table-v-align td { vertical-align: middle; }
    </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
        </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="admin-dashboard.php" class="brand-link text-center">
            <span class="brand-text font-weight-light">🛒 COMMERCE</span>
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column">
                    <li class="nav-item">
                        <a href="admin-dashboard.php" class="nav-link">
                            <i class="nav-icon fas fa-tachometer-alt"></i> <p>Dashboard</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="orders_management.php" class="nav-link active">
                            <i class="nav-icon fas fa-shopping-cart"></i> <p>Manage Orders</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="Shop_management.php" class="nav-link">
                            <i class="nav-icon fas fa-store"></i> <p>Inventory</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="users.php">
                            <i class="nav-icon fas fa-user"></i><p>User Management</p>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <div class="content-wrapper p-4">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>📦 Order Management</h1>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h3 class="card-title">Recent Transactions</h3>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-v-align mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th class="ps-3">ID</th>
                                    <th>Client</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while ($o = $result->fetch_assoc()): 
                                        // Status Color Logic
                                        $statusClass = match($o['status']) {
                                            'completed' => 'success',
                                            'cancelled' => 'danger',
                                            'shipped'   => 'info',
                                            default     => 'warning',
                                        };
                                    ?>
                                        <tr>
                                            <td class="ps-3">#<?= (int)$o['id'] ?></td>
                                            <td><strong><?= htmlspecialchars($o['client_name']) ?></strong></td>
                                            <td><?= number_format($o['total'], 2) ?> €</td>
                                            <td>
                                                <span class="badge bg-<?= $statusClass ?>-subtle text-<?= $statusClass ?> border border-<?= $statusClass ?> badge-status">
                                                    <?= ucfirst($o['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <i class="far fa-calendar-alt me-1"></i>
                                                    <?= date("d M Y, H:i", strtotime($o['created_at'])) ?>
                                                </small>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <a href="order-view.php?id=<?= (int)$o['id'] ?>" class="btn btn-sm btn-outline-info" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="order-edit.php?id=<?= (int)$o['id'] ?>" class="btn btn-sm btn-outline-warning" title="Edit Order">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <i class="fas fa-inbox fa-3x text-light mb-3"></i>
                                            <p class="text-muted">No orders found.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <footer class="main-footer">
        <div class="float-right d-none d-sm-block"><b>Version</b> 1.2.0</div>
        <strong>&copy; <?= date('Y') ?> Commerce Admin System.</strong>
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

</body>
</html>