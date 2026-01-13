<?php
require "auth.php";

// 1. Centralized Database Connection with Error Handling
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "commerce";

$connection = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

// 2. OPTIMIZED: Combined User Stats Query (Single trip to DB)
$statsResult = $connection->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN r.name = 'admin' THEN 1 ELSE 0 END) as admins,
        SUM(CASE WHEN r.name = 'user' THEN 1 ELSE 0 END) as clients
    FROM users u
    LEFT JOIN roles r ON u.role_id = r.id
")->fetch_assoc();

$totalUsers   = $statsResult['total'] ?? 0;
$totalAdmins  = $statsResult['admins'] ?? 0;
$totalClients = $statsResult['clients'] ?? 0;

// 3. Sales Data
$salesQuery = $connection->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, SUM(total) AS total
    FROM orders WHERE status != 'cancelled'
    GROUP BY month ORDER BY month ASC
");

$months = []; $sales = [];
while ($row = $salesQuery->fetch_assoc()) {
    $months[] = $row['month'];
    $sales[] = (float)$row['total'];
}

// 4. Status Data
$statusQuery = $connection->query("SELECT status, COUNT(*) AS total FROM orders GROUP BY status");
$statusLabels = []; $statusValues = [];
while ($row = $statusQuery->fetch_assoc()) {
    $statusLabels[] = ucfirst($row['status']);
    $statusValues[] = (int)$row['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Commerce</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    
    <style>
        .info-box-icon { border-radius: 5px; }
        .chart-container { position: relative; height: 300px; width: 100%; }
        #goBackBtn { font-size: 1.5rem; margin-right: 15px; vertical-align: middle; }
        .main-sidebar { min-height: 100vh !important; }
        
    </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <span class="nav-link"><strong>Welcome, Admin</strong></span>
            </li>
        </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="user-dashboard.php" class="brand-link">
            <span class="brand-text font-weight-light ps-3">🛒 COMMERCE</span>
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                    <li class="nav-item"><a href="user-dashboard.php" class="nav-link active"><i class="nav-icon fas fa-tachometer-alt"></i> <p>Dashboard</p></a></li>
                    <li class="nav-item"><a href="orders_management.php" class="nav-link"><i class="nav-icon fas fa-shopping-cart"></i> <p>Orders</p></a></li>
                    <li class="nav-item"><a href="users.php" class="nav-link"><i class="nav-icon fas fa-users"></i> <p>Users</p></a></li>
                    <li class="nav-item"><a href="shop_management.php" class="nav-link"><i class="nav-icon fas fa-store"></i> <p>Shop</p></a></li>
                    <li class="nav-header">ACCOUNT</li>
                    <li class="nav-item"><a href="logout.php" class="nav-link text-danger"><i class="nav-icon fas fa-sign-out-alt"></i> <p>Logout</p></a></li>
                </ul>
            </nav>
        </div>
    </aside>

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2 align-items-center">
                    <div class="col-sm-6">
                        <i id="goBackBtn" class='bx bx-arrow-back cursor-pointer' onclick="history.back()"></i>
                        <h1 class="m-0"> Dashboard Overview</h1>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12 col-sm-6 col-md-4">
                        <div class="info-box shadow-sm">
                            <span class="info-box-icon bg-info elevation-1"><i class="fas fa-users"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Users</span>
                                <span class="info-box-number"><?= $totalUsers ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4">
                        <div class="info-box shadow-sm">
                            <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-user-shield"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Admins</span>
                                <span class="info-box-number"><?= $totalAdmins ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-4">
                        <div class="info-box shadow-sm">
                            <span class="info-box-icon bg-success elevation-1"><i class="fas fa-shopping-bag"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Clients</span>
                                <span class="info-box-number"><?= $totalClients ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8">
                        <div class="card card-outline card-primary">
                            <div class="card-header"><h3 class="card-title">📈 Monthly Sales</h3></div>
                            <div class="card-body"><canvas id="salesChart" style="min-height: 250px;"></canvas></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card card-outline card-info">
                            <div class="card-header"><h3 class="card-title">📦 Order Status</h3></div>
                            <div class="card-body"><canvas id="statusChart" style="min-height: 250px;"></canvas></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <footer class="main-footer">
        <strong>&copy; <?= date("Y") ?> Commerce Dashboard.</strong> All rights reserved.
    </footer>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Chart Defaults
Chart.defaults.font.family = 'Arial';

// 📈 Sales Chart
new Chart(document.getElementById('salesChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($months) ?>,
        datasets: [{
            label: 'Sales ($)',
            data: <?= json_encode($sales) ?>,
            borderColor: '#007bff',
            backgroundColor: 'rgba(0, 123, 255, 0.1)',
            fill: true,
            tension: 0.3
        }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});

// 📦 Status Chart
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($statusLabels) ?>,
        datasets: [{
            data: <?= json_encode($statusValues) ?>,
            backgroundColor: ['#28a745', '#ffc107', '#dc3545', '#17a2b8', '#6c757d']
        }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});
</script>
</body>
</html>