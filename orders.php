<?php
session_start();



$connection = new mysqli("localhost", "root", "", "commerce");

$result = $connection->query("
    SELECT o.*, u.name 
    FROM orders o 
    JOIN users u ON u.id=o.user_id
    ORDER BY o.id DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Gestion des commandes</title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
<style>

</style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

<!-- NAVBAR -->
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
  <ul class="navbar-nav">
    <li class="nav-item">
      <a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
    </li>
  </ul>
</nav>

<!-- SIDEBAR -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
  <a href="dashboard.php" class="brand-link text-center">
    <span class="brand-text font-weight-light">🛒 COMMERCE</span>
  </a>
  <div class="sidebar">
    <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column">
        <li class="nav-item">
          <a href="user-dashboard.php" class="nav-link">
            <i class="nav-icon fas fa-chart-line"></i>
            <p>Dashboard</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="orders.php" class="nav-link active">
            <i class="nav-icon fas fa-box"></i>
            <p>Commandes</p>
          </a>
        </li>
        <li class="nav-item">
          <a href="shop_management.php" class="nav-link">
            <i class="nav-icon fas fa-store"></i>
            <p>Shop Management</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="orders_management.php" class="nav-link">
            <i class="nav-icon fas fa-box"></i>
            <p>Orders</p>
          </a>
        </li>

      </ul>
    </nav>
  </div>
</aside>

<!-- CONTENT -->
<div class="content-wrapper p-4">
      <i id="goBackBtn" class='bx bx-arrow-back text-3xl cursor-pointer hover:text-blue-600 transition'></i>
  <h1 class="h3 mb-4">📦 Gestion des commandes</h1>

  <div class="card">
    <div class="card-body table-responsive">
      <table class="table table-bordered table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Client</th>
            <th>Total</th>
            <th>Status</th>
            <th>Date</th>
            <th width="120">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php while($o = $result->fetch_assoc()): ?>
          <tr>
            <td><?= $o['id'] ?></td>
            <td><?= htmlspecialchars($o['name']) ?></td>
            <td><?= number_format($o['total'],2) ?> €</td>
            <td>
              <span class="badge bg-<?= $o['status']=='completed' ? 'success' : ($o['status']=='cancelled' ? 'danger' : 'warning') ?>">
                <?= ucfirst($o['status']) ?>
              </span>
            </td>
            <td><?= $o['created_at'] ?></td>
            <td>
              <a href="order-view.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-info">
                <i class="fas fa-eye"></i>
              </a>
              <?php if (can('manage_orders')): ?>
              <a href="order-edit.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-warning">
                <i class="fas fa-edit"></i>
              </a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<footer class="main-footer text-center">
  <strong>© <?= date("Y") ?> COMMERCE</strong>
</footer>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>
