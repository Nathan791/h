<?php
// 1. Session & Admin Security Guard
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in AND is an admin
if (!isset($_SESSION['id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: /COMMERCE/login.php?error=unauthorized");
    exit();
}

// 2. Database Connection
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $db = new mysqli("localhost", "root", "", "commerce");
    $db->set_charset("utf8mb4");
} catch (Exception $e) {
    error_log($e->getMessage());
    die("A system error occurred. Please try again later.");
}

// 3. CSRF Protection
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

// 4. Fetch Users with Role Info
// Added 'created_at' to show how long they've been members
$query = "SELECT u.id, u.name, u.email, r.name AS role_name, u.create_at 
          FROM users u 
          LEFT JOIN roles r ON r.id = u.role_id 
          ORDER BY u.id DESC";

$result = $db->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Directory | Admin Panel</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Inter', sans-serif; }
        .user-avatar {
            width: 40px; height: 40px;
            background: #6366f1; border-radius: 10px;
            display: inline-flex; align-items: center; justify-content: center;
            margin-right: 12px; font-weight: 600; color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .table-middle td { vertical-align: middle; padding: 1rem 0.75rem; }
        .badge-subtle { padding: 0.5em 0.8em; border-radius: 6px; font-weight: 500; }
        .card { border-radius: 12px; }
        .btn-action { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; }
    </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <nav class="main-header navbar navbar-expand navbar-white navbar-light border-bottom-0">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
        </ul>
        <ul class="navbar-nav ms-auto pe-3">
            <li class="nav-item dropdown">
                <a class="nav-link" href="/COMMERCE/logout.php">
                    <i class="fas fa-sign-out-alt text-danger"></i> Logout
                </a>
            </li>
        </ul>
    </nav>

    <aside class="main-sidebar sidebar-dark-indigo elevation-4">
        <a href="admin-dashboard.php" class="brand-link border-bottom-0 text-center">
            <span class="brand-text font-weight-bold">COMMERCE CMS</span>
        </a>
        <div class="sidebar">
            <nav class="mt-3">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                    <li class="nav-item">
                        <a href="admin-dashboard.php" class="nav-link">
                            <i class="nav-icon fas fa-chart-pie"></i> <p>Insights</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="users_management.php" class="nav-link active">
                            <i class="nav-icon fas fa-user-shield"></i> <p>User Controls</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="shop_management.php" class="nav-link">
                            <i class="nav-icon fas fa-boxes"></i> <p>Products</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="orders_management.php" class="nav-link">
                            <i class="nav-icon fas fa-receipt"></i> <p>Orders</p>
                        </a>
                </ul>
            </nav>
        </div>
    </aside>

    <div class="content-wrapper bg-light">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row align-items-center">
                    <div class="col-sm-6">
                        <h1 class="m-0 fw-bold text-dark">User Management</h1>
                        <p class="text-muted small">Manage permissions and account status for all members.</p>
                    </div>
                    <div class="col-sm-6 text-sm-end">
                        <a href="create_users.php" class="btn btn-indigo shadow-sm px-4">
                            <i class="fas fa-plus-circle me-2"></i> Add Account
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-middle mb-0">
                                <thead class="bg-light text-uppercase small fw-bold">
                                    <tr>
                                        <th class="ps-4" style="width: 80px;">ID</th>
                                        <th>User Identity</th>
                                        <th>Email Address</th>
                                        <th>Access Level</th>
                                        <th class="text-end pe-4">Manage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows > 0): ?>
                                        <?php while ($u = $result->fetch_assoc()): 
                                            $isAdmin = (strtolower($u['role_name']) === 'admin');
                                            $badgeColor = $isAdmin ? 'danger' : 'primary';
                                            $initials = strtoupper(substr($u['name'], 0, 1));
                                        ?>
                                        <tr>
                                            <td class="ps-4 text-muted">#<?= (int)$u['id'] ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="user-avatar" style="background: <?= $isAdmin ? '#ef4444' : '#6366f1' ?>;">
                                                        <?= $initials ?>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark"><?= htmlspecialchars($u['name']) ?></div>
                                                        <div class="small text-muted">Joined <?= date('M Y', strtotime($u['create_at'])) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="text-dark"><?= htmlspecialchars($u['email']) ?></span>
                                            </td>
                                            <td>
                                                <span class="badge badge-subtle bg-<?= $badgeColor ?>-subtle text-<?= $badgeColor ?>">
                                                    <i class="fas <?= $isAdmin ? 'fa-user-cog' : 'fa-user' ?> me-1"></i>
                                                    <?= ucfirst(htmlspecialchars($u['role_name'] ?? 'User')) ?>
                                                </span>
                                            </td>
                                            <td class="text-end pe-4">
                                                <div class="d-flex justify-content-end gap-2">
                                                    <a href="users-edit.php?id=<?= (int)$u['id'] ?>" 
                                                       class="btn btn-action btn-outline-warning" title="Edit Permissions">
                                                        <i class="fas fa-pen-nib"></i>
                                                    </a>
                                                    
                                                    <form action="user-delete.php" method="POST" class="d-inline">
                                                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                                        <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                                                        <button type="submit" class="btn btn-action btn-outline-danger" 
                                                                onclick="return confirm('WARNING: Are you sure? This user will be permanently removed.')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center p-5 text-muted">
                                                <i class="fas fa-users-slash fa-3x mb-3"></i>
                                                <p>No records found in the user database.</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <footer class="main-footer bg-white border-top-0 small text-muted">
        <div class="float-right d-none d-sm-inline">v2.1.0</div>
        <strong>&copy; <?= date('Y') ?> Commerce Admin Ecosystem.</strong>
    </footer>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
</body>
</html>