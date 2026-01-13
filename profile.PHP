<?php
session_start();

// 1. Auth Guard
if (!isset($_SESSION['id'], $_SESSION['email'])) {
    header("Location: /COMMERCE/login.php");
    exit();
}

// 2. DB Connection (Recommend moving to an include file like db.php)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $db = new mysqli("localhost", "root", "", "commerce");
    $db->set_charset("utf8mb4");
} catch (Exception $e) {
    die("Connection error. Please try again later.");
}

// 3. CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = "";
$success = "";

// 4. Form Processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['csrf_token'] ?? '') !== $_SESSION['csrf_token']) {
        $error = "Security token mismatch.";
    } else {
        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!$name || !$email) {
            $error = "Name and email are required fields.";
        }

        // Check if email is unique
        if (!$error && $email !== $_SESSION['email']) {
            $stmt = $db->prepare("SELECT id FROM users WHERE email=? AND id != ?");
            $stmt->bind_param("si", $email, $_SESSION['id']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $error = "This email address is already registered.";
            }
            $stmt->close();
        }

        // Handle Password Change Logic
        $password_hash = null;
        if (!$error && !empty($new)) {
            if (empty($current) || empty($confirm)) {
                $error = "To change your password, please fill all password fields.";
            } elseif ($new !== $confirm) {
                $error = "The new passwords do not match.";
            } else {
                $stmt = $db->prepare("SELECT password FROM users WHERE id=?");
                $stmt->bind_param("i", $_SESSION['id']);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!password_verify($current, $user['password'])) {
                    $error = "Current password is incorrect.";
                } else {
                    $password_hash = password_hash($new, PASSWORD_DEFAULT);
                }
            }
        }

        // Execute Updates
        if (!$error) {
            $emailChanged = ($email !== $_SESSION['email']);

            if ($emailChanged) {
                // Change with email verification token
                $token = bin2hex(random_bytes(16));
                $stmt = $db->prepare("UPDATE users SET name=?, email_new=?, email_token=? WHERE id=?");
                $stmt->bind_param("sssi", $name, $email, $token, $_SESSION['id']);
                $success = "Profile saved. Check your new email to confirm the change.";
            } elseif ($password_hash) {
                // Change name and password
                $stmt = $db->prepare("UPDATE users SET name=?, password=? WHERE id=?");
                $stmt->bind_param("ssi", $name, $password_hash, $_SESSION['id']);
                $success = "Profile and password updated successfully!";
            } else {
                // Change name only
                $stmt = $db->prepare("UPDATE users SET name=? WHERE id=?");
                $stmt->bind_param("si", $name, $_SESSION['id']);
                $success = "Profile updated!";
            }

            if ($stmt->execute()) {
                $_SESSION['name'] = $name; // Sync session
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | Commerce</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-color: #f4f7f6; --text-color: #333; --card-bg: #ffffff;
            --accent-color: #2563eb; --input-border: #ddd;
        }
        body.dark {
            --bg-color: #1a1a2e; --text-color: #f4f7f6; --card-bg: #16213e;
            --accent-color: #4cc9f0; --input-border: #2e3a59;
        }
        body { font-family: 'Poppins', sans-serif; background: var(--bg-color); color: var(--text-color); transition: 0.3s; padding: 20px; }
        .container { max-width: 450px; margin: 40px auto; background: var(--card-bg); padding: 30px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        
        .header { display: flex; align-items: center; gap: 15px; margin-bottom: 25px; }
        .back-btn { font-size: 1.8rem; cursor: pointer; color: var(--text-color); }

        label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; opacity: 0.8; }
        input { 
            width: 100%; padding: 12px; margin-bottom: 20px; border-radius: 8px; border: 1px solid var(--input-border);
            background: transparent; color: inherit; outline: none; transition: 0.2s;
        }
        input:focus { border-color: var(--accent-color); box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1); }
        
        .pass-group { position: relative; }
        .pass-group i { position: absolute; right: 12px; top: 12px; cursor: pointer; opacity: 0.6; font-size: 1.2rem; }
        
        button { 
            width: 100%; padding: 12px; background: var(--accent-color); color: #fff; border: none; 
            border-radius: 8px; font-weight: 600; cursor: pointer; margin-top: 10px;
        }
        button:hover { opacity: 0.9; transform: translateY(-1px); }

        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem; text-align: center; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #10b981; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #ef4444; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <i id="goBackBtn" class='bx bx-left-arrow-alt back-btn'></i>
        <h2 style="margin:0">Edit Profile</h2>
    </div>

    <?php if($error): ?> <div class="alert alert-error"><?= $error ?></div> <?php endif; ?>
    <?php if($success): ?> <div class="alert alert-success"><?= $success ?></div> <?php endif; ?>

    <form method="post" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <label>Full Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars($_SESSION['name']) ?>" required>

        <label>Email Address</label>
        <input type="email" name="email" value="<?= htmlspecialchars($_SESSION['email']) ?>" required>

        <p style="font-size: 0.75rem; opacity: 0.6; margin: -10px 0 20px;">* Email changes require verification.</p>

        <hr style="opacity: 0.1; margin-bottom: 20px;">

        <label>Current Password (to authorize changes)</label>
        <input type="password" name="current_password">

        <label>New Password</label>
        <div class="pass-group">
            <input type="password" name="new_password" id="new_p">
            <i class='bx bx-show' onclick="toggle('new_p', this)"></i>
        </div>

        <label>Confirm New Password</label>
        <div class="pass-group">
            <input type="password" name="confirm_password" id="conf_p">
            <i class='bx bx-show' onclick="toggle('conf_p', this)"></i>
        </div>

        <button type="submit">Update Account</button>
    </form>
</div>

<script>
    // Navigation
    document.getElementById('goBackBtn').onclick = () => window.history.back();

    // Password Visibility Toggle
    function toggle(id, icon) {
        const input = document.getElementById(id);
        const isPass = input.type === "password";
        input.type = isPass ? "text" : "password";
        icon.classList.toggle('bx-show', !isPass);
        icon.classList.toggle('bx-hide', isPass);
    }

    // Sync Theme with Dashboard
    if (localStorage.getItem("theme") === "dark") {
        document.body.classList.add("dark");
    }
</script>

</body>
</html>