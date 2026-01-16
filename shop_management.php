<?php
require_once 'db.php'; 


// 1. AJAX Status Toggle Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    header('Content-Type: application/json');
    
    $id = intval($_POST['id']);
    $currentStatus = $_POST['status'];
    // Standardizing to 'deactivate' to match your shop.php and CSS
    $newStatus = ($currentStatus === 'active') ? 'deactivate' : 'active';

    $stmt = $db->prepare("UPDATE products SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $newStatus, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'newStatus' => $newStatus]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    exit; 
}

// 2. Pagination & Data Retrieval
$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$countQuery = "SELECT COUNT(id) FROM products";
$totalResults = $db->query($countQuery)->fetch_row()[0];
$totalPages = ceil($totalResults / $limit);

$query = "SELECT id, name, price, stock, status, created_at 
          FROM products 
          ORDER BY created_at DESC 
          LIMIT ? OFFSET ?";
$stmt = $db->prepare($query);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Store Catalog | Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        .product-name { font-weight: 600; }
        .btn-status { min-width: 110px; transition: all 0.3s ease; }
        /* Visually distinguish hidden products */
        tr.status-deactivate { opacity: 0.6; background-color: #f8f9fa; border-left: 4px solid #6c757d; }
        .back-btn { cursor: pointer; font-size: 1.5rem; margin-bottom: 15px; display: inline-block; }
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
                    <li class="nav-item"><a href="admin-dashboard.php" class="nav-link"><i class="nav-icon fas fa-tachometer-alt"></i> <p>Dashboard</p></a></li>
                    <li class="nav-item"><a href="orders_management.php" class="nav-link"><i class="nav-icon fas fa-shopping-cart"></i> <p>Manage Orders</p></a></li>
                    <li class="nav-item"><a href="shop_management.php" class="nav-link active"><i class="nav-icon fas fa-store"></i> <p>Inventory</p></a></li>
                    <li class="nav-item"><a href="users.php"><i class="nav-icon fas fa-user"></i><p>User Management</p></a></li>
                </ul>
            </nav>
        </div>
    </aside>

    <div class="content-wrapper p-4">
        <i class='bx bx-arrow-back back-btn' onclick="window.history.back();"></i>
        <section class="content">
            <div class="container-fluid">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-header bg-white py-3">
                        <h3 class="card-title fw-bold">Product Inventory</h3>
                        <div class="card-tools">
                            <a href="product-create.php" class="btn btn-primary btn-sm rounded-pill px-3">
                                <i class="fas fa-plus me-1"></i> Add New
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">ID</th>
                                        <th>Details</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                        <th>Store Visibility</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($p = $result->fetch_assoc()): ?>
                                        <tr id="row-<?= $p['id'] ?>" class="status-<?= $p['status'] ?>">
                                            <td class="ps-4 text-muted small">#<?= $p['id'] ?></td>
                                            <td class="product-name"><?= htmlspecialchars($p['name']) ?></td>
                                            <td class="fw-bold"><?= number_format($p['price'], 2) ?> €</td>
                                            <td>
                                                <?php $sColor = ($p['stock'] < 5) ? 'danger' : 'success'; ?>
                                                <span class="badge bg-<?= $sColor ?>-subtle text-<?= $sColor ?>">
                                                    <?= $p['stock'] ?> units
                                                </span>
                                            </td>
                                            <td id="status-container-<?= $p['id'] ?>">
                                                <button 
                                                    onclick="toggleStatus(<?= $p['id'] ?>, '<?= $p['status'] ?>')" 
                                                    class="btn btn-sm rounded-pill btn-status btn-<?= $p['status'] === 'active' ? 'success' : 'secondary' ?>">
                                                    <i class="fas <?= $p['status'] === 'active' ? 'fa-check-circle' : 'fa-eye-slash' ?> me-1"></i>
                                                    <?= $p['status'] === 'active' ? 'Active' : 'Deactivate' ?>
                                                </button>
                                            </td>
                                            <td class="text-center">
                                                <a href="product-edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-warning border-0"><i class="fas fa-edit"></i></a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

<script>
function toggleStatus(id, currentStatus) {
    const container = $(`#status-container-${id}`);
    const row = $(`#row-${id}`);
    const btn = container.find('button');
    
    btn.addClass('disabled').html('<span class="spinner-border spinner-border-sm"></span>');

    $.ajax({
        url: window.location.href,
        method: 'POST',
        data: { 
            action: 'toggle_status', 
            id: id, 
            status: currentStatus 
        },
        success: function(response) {
            if (response.success) {
                const isActive = response.newStatus === 'active';
                const label = isActive ? 'Active' : 'Deactivate';
                const icon = isActive ? 'fa-check-circle' : 'fa-eye-slash';
                
                // Update Button
                btn.removeClass('disabled btn-success btn-secondary')
                   .addClass(isActive ? 'btn-success' : 'btn-secondary')
                   .attr('onclick', `toggleStatus(${id}, '${response.newStatus}')`)
                   .html(`<i class="fas ${icon} me-1"></i> ${label}`);
                
                // Update Row styling for visual feedback
                row.removeClass('status-active status-deactivate').addClass(`status-${response.newStatus}`);
            }
        },
        error: function() {
            alert("Connection error. Status not updated.");
            location.reload();
        }
    });
}
</script>
</body>
</html>