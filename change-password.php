<?php
session_start();

// 1. Auth Guard
if (!isset($_SESSION['id'])) {
    header("Location: /COMMERCE/login.php");
    exit();
}

// 2. Database Connection
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $db = new mysqli("localhost", "root", "", "commerce");
    $db->set_charset("utf8mb4");
} catch (Exception $e) {
    die("Connection failed. Please try again later.");
}

// 3. CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errorMessage = "";
$successMessage = "";

// 4. Processing Logic
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $token   = $_POST['csrf_token'] ?? '';

    if ($token !== $_SESSION['csrf_token']) {
        $errorMessage = "Security token mismatch.";
    } elseif (empty($current) || empty($new) || empty($confirm)) {
        $errorMessage = "All fields are required.";
    } elseif ($new !== $confirm) {
        $errorMessage = "New passwords do not match.";
    } elseif (strlen($new) < 8) {
        $errorMessage = "New password must be at least 8 characters.";
    } else {
        // Verify current password
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['id']);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($current, $user['password'])) {
            // Check if new password is same as current
            if (password_verify($new, $user['password'])) {
                $errorMessage = "New password cannot be the same as your old one.";
            } else {
                // Update Password
                $newHash = password_hash($new, PASSWORD_DEFAULT);
                $update = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update->bind_param("si", $newHash, $_SESSION['id']);
                
                if ($update->execute()) {
                    $successMessage = "Password updated successfully!";
                } else {
                    $errorMessage = "Failed to update password.";
                }
            }
        } else {
            $errorMessage = "The current password you entered is incorrect.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password | Commerce</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-color: #f4f7f6; --text-color: #333; --card-bg: #ffffff;
            --accent-color: #3498db; --danger-color: #e74c3c;
        }
        body.dark {
            --bg-color: #1a1a2e; --text-color: #f4f7f6; --card-bg: #16213e;
            --accent-color: #4cc9f0;
        }
        body { font-family: 'Poppins', sans-serif; background: var(--bg-color); color: var(--text-color); padding: 20px; transition: 0.3s; }
        .container { max-width: 450px; margin: 40px auto; background: var(--card-bg); padding: 30px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        
        .header-area { display: flex; align-items: center; gap: 15px; margin-bottom: 25px; }
        .back-icon { font-size: 1.8rem; cursor: pointer; color: var(--text-color); }

        .form-label { font-weight: 600; font-size: 0.9rem; margin-bottom: 5px; }
        .form-control { 
            background: transparent; border: 1px solid #ccc; color: inherit; padding: 12px;
            border-radius: 8px; margin-bottom: 15px; width: 100%;
        }
        .btn-primary { 
            width: 100%; padding: 12px; background: var(--accent-color); border: none; 
            border-radius: 8px; font-weight: 600; color: white; cursor: pointer;
        }
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 0.85rem; }
        .alert-danger { background: #fee2e2; color: #991b1b; }
        .alert-success { background: #d1fae5; color: #065f46; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-area">
        <i id="goBackBtn" class='bx bx-left-arrow-alt back-icon'></i>
        <h2 style="margin:0; font-size: 1.4rem;">Change Password</h2>
    </div>

    <?php if ($errorMessage): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <?php if ($successMessage): ?>
        <div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <div class="mb-3">
            <label class="form-label">Current Password</label>
            <input type="password" class="form-control" name="current_password" required>
        </div>

        <div class="mb-3">
            <label class="form-label">New Password</label>
            <input type="password" class="form-control" name="new_password" required minlength="8">
        </div>

        <div class="mb-3">
            <label class="form-label">Confirm New Password</label>
            <input type="password" class="form-control" name="confirm_password" required>
        </div>

        <button type="submit" class="btn-primary">Update Password</button>
    </form>
</div>

<script>
    document.getElementById('goBackBtn').onclick = () => window.history.back();

    // Dark Mode persistence
    if (localStorage.getItem("theme") === "dark") {
        document.body.classList.add("dark");
    }
</script>

</body>
</html>