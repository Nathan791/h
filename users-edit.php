<?php
require_once 'db.php';
session_start();




// 2. CSRF Initialization
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = "";
$success = "";

// 3. Robust Data Fetching
// Fetch roles once for the dropdown
$roles = $db->query("SELECT id, name FROM roles ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

// Fetch target user details if an ID is provided (for a better UX)
$targetUserId = intval($_GET['id'] ?? $_POST['user_id'] ?? 0);
$targetUser = null;
if ($targetUserId > 0) {
    $uStmt = $db->prepare("SELECT id, name, role_id FROM users WHERE id = ?");
    $uStmt->bind_param("i", $targetUserId);
    $uStmt->execute();
    $targetUser = $uStmt->get_result()->fetch_assoc();
}

// 4. Form Processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Security token mismatch.";
    } else {
        $userId = intval($_POST['user_id']);
        $roleId = intval($_POST['role_id']);

        // Prevent admin from accidentally demoting themselves (Safety Check)
        if ($userId === $_SESSION['id'] && $roleId !== 1) {
            $error = "You cannot remove your own administrative privileges.";
        } elseif ($userId > 0 && $roleId > 0) {
            $stmt = $db->prepare("UPDATE users SET role_id = ? WHERE id = ?");
            $stmt->bind_param("ii", $roleId, $userId);
            
            if ($stmt->execute()) {
                $success = "User role updated successfully!";
                // Refresh target user data
                $targetUser['role_id'] = $roleId;
            } else {
                $error = "Database update failed.";
            }
        } else {
            $error = "Please select a valid user and role.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User Role | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; }
        .card { max-width: 500px; margin: 50px auto; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.1); border-radius: 12px; }
        .btn-primary { background-color: #4e73df; border: none; }
    </style>
</head>
<body>

<div class="container">
    <div class="card p-4">
        <h3 class="mb-4 text-center">Update Permissions</h3>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success py-2"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <div class="mb-3">
                <label class="form-label fw-bold">User Information</label>
                <input type="number" name="user_id" class="form-control bg-light" 
                       value="<?= $targetUserId ?>" readonly>
                <?php if ($targetUser): ?>
                    <div class="form-text text-primary">Modifying: <strong><?= htmlspecialchars($targetUser['name']) ?></strong></div>
                <?php endif; ?>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Assign Role</label>
                <select name="role_id" class="form-select" required>
                    <option value="" disabled>Select a role...</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?= $role['id'] ?>" <?= ($targetUser && $targetUser['role_id'] == $role['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($role['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="d-grid mt-4">
                <button type="submit" class="btn btn-primary btn-lg">Save Changes</button>
            </div>
            
            <div class="mt-3 text-center">
                <a href="users.php" class="text-muted small text-decoration-none">← Back to User List</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>