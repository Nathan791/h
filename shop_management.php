<?php
// 1. Database Configuration (Ideally move this to a shared config.php)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'commerce');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $connection->set_charset("utf8mb4");
} catch (Exception $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// 2. Data Retrieval Logic
$status = "active";
$query = "SELECT id, name, price, stock, status, created_at 
          FROM products 
          WHERE status = ? 
          ORDER BY created_at DESC";

$stmt = $connection->prepare($query);
$stmt->bind_param("s", $status);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Management | Admin</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        .table-responsive { min-height: 400px; }
        .badge { font-weight: 500; padding: 0.5em 0.75em; }
        .btn-group-xs > .btn, .btn-xs { padding: .25rem .4rem; font-size: .875rem; }
    </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a></li>
        </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="user-dashboard.php" class="brand-link text-center">
            <span class="brand-text font-weight-light">🛒 COMMERCE</span>
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column">
                    <li class="nav-item">
                        <a href="admin-dashboard.php" class="nav-link">
                            <i class="nav-icon fas fa-chart-line"></i> <p>Dashboard</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="shop_management.php" class="nav-link active">
                            <i class="nav-icon fas fa-store"></i> <p>Shop Management</p>
                        </a>
                    </li>
                    <li class="nav-item">
                      <a href="orders_management.php" class="nav-link">
                          <i class="nav-icon fas fa-shopping-cart"></i> <p>Orders Management</p>
                    </li>
                    <li class="nav-item">
                      <a href="users.php" class="nav-link">
                          <i class="nav-icon fas fa-users"></i> <p>Users Management</p>
                      </a>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <div class="content-wrapper p-3">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6"><h1>Store Catalog</h1></div>
                    <div class="col-sm-6 text-end">
                        <a href="product-create.php" class="btn btn-primary shadow-sm">
                            <i class="fas fa-plus-circle"></i> New Product
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th class="ps-3">ID</th>
                                        <th>Product Details</th>
                                        <th>Price</th>
                                        <th>Inventory</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows === 0): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-5 text-muted">
                                                No products found in the database.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php while ($p = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td class="ps-3 text-muted">#<?= (int)$p['id'] ?></td>
                                                <td><strong><?= htmlspecialchars($p['name']) ?></strong></td>
                                                <td><?= number_format($p['price'], 2) ?> €</td>
                                                <td>
                                                    <?php 
                                                        $stockClass = ($p['stock'] <= 0) ? 'danger' : (($p['stock'] < 10) ? 'warning' : 'success');
                                                        $stockText = ($p['stock'] <= 0) ? 'Out of Stock' : $p['stock'] . ' units';
                                                    ?>
                                                    <span class="badge bg-<?= $stockClass ?>-subtle text-<?= $stockClass ?> border border-<?= $stockClass ?>">
                                                        <?= $stockText ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge rounded-pill bg-<?= $p['status'] === 'active' ? 'success' : 'secondary' ?>">
                                                        <?= ucfirst($p['status']) ?>
                                                    </span>
                                                </td>
                                                <td class="small text-muted"><?= date("M d, Y", strtotime($p['created_at'])) ?></td>
                                                <td class="text-center">
                                                    <div class="btn-group">
                                                        <a href="product-view.php?id=<?= (int)$p['id'] ?>" class="btn btn-outline-info btn-xs" title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="product-edit.php?id=<?= (int)$p['id'] ?>" class="btn btn-outline-warning btn-xs" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="product-delete.php?id=<?= (int)$p['id'] ?>" 
                                                           class="btn btn-outline-danger btn-xs" 
                                                           onclick="return confirm('Archive this product?')" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white">
                        <small class="text-muted">Showing <?= $result->num_rows ?> results</small>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <footer class="main-footer text-center py-3">
        <strong>© <?= date('Y') ?> Commerce Admin Panel</strong>
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>