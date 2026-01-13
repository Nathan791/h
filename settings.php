<?php
session_start();

// 1. Centralized Auth Check
if (!isset($_SESSION["email"])) {
    header("Location: /COMMERCE/login.php");
    exit();
}

// 2. CSRF Token Generation (for future form actions)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$userName = htmlspecialchars($_SESSION["name"] ?? 'User');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings | Commerce</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    
    <style>
        :root {
            --bg-color: #f4f7f6;
            --text-color: #333;
            --card-bg: #ffffff;
            --accent-color: #3498db;
            --danger-color: #e74c3c;
        }

        body.dark {
            --bg-color: #1a1a2e;
            --text-color: #f4f7f6;
            --card-bg: #16213e;
            --accent-color: #4cc9f0;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; transition: all 0.3s ease; }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            padding: 20px;
        }

        .settings-container {
            max-width: 600px;
            margin: 40px auto;
        }

        .header-nav {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
        }

        .back-btn {
            font-size: 2rem;
            cursor: pointer;
            color: var(--text-color);
        }

        .back-btn:hover { color: var(--accent-color); }

        .settings-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .settings-item {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            text-decoration: none;
            color: var(--text-color);
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .settings-item:hover {
            transform: translateX(10px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
            color: var(--accent-color);
        }

        .item-info { display: flex; align-items: center; gap: 15px; }
        .item-info i { font-size: 1.5rem; }

        .danger-item { color: var(--danger-color); }
        .danger-item:hover { color: #c0392b; }

        /* Dark Mode Toggle Position */
        #themeToggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--card-bg);
            padding: 10px;
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            font-size: 1.5rem;
        }
    </style>
</head>
<body>

<div class="settings-container">
    <div class="header-nav">
        <i id="goBackBtn" class='bx bx-left-arrow-alt back-btn'></i>
        <h2>Account Settings</h2>
    </div>

    <div class="settings-list">
        <a href="profile.php" class="settings-item">
            <div class="item-info">
                <i class='bx bx-user-circle'></i>
                <span>Edit Profile</span>
            </div>
            <i class='bx bx-chevron-right'></i>
        </a>

        <a href="change-password.php" class="settings-item">
            <div class="item-info">
                <i class='bx bx-lock-alt'></i>
                <span>Change Password</span>
            </div>
            <i class='bx bx-chevron-right'></i>
        </a>

        <a href="privacy.php" class="settings-item">
            <div class="item-info">
                <i class='bx bx-shield-quarter'></i>
                <span>Privacy & Security</span>
            </div>
            <i class='bx bx-chevron-right'></i>
        </a>

        <hr style="opacity: 0.1; margin: 10px 0;">

        <a href="delete-account.php" class="settings-item danger-item">
            <div class="item-info">
                <i class='bx bx-user-x'></i>
                <span>Delete Account</span>
            </div>
            <i class='bx bx-chevron-right'></i>
        </a>

        <a href="/COMMERCE/logout.php" class="settings-item text-danger">
            <div class="item-info">
                <i class='bx bx-log-out'></i>
                <span>Logout</span>
            </div>
        </a>
    </div>
</div>

<div id="themeToggle">🌙</div>

<script>
    // Navigation
    document.getElementById('goBackBtn').onclick = () => window.history.back();

    // Theme Logic (Persistent with Dashboard)
    const body = document.body;
    const themeToggle = document.getElementById('themeToggle');
    const currentTheme = localStorage.getItem("theme");

    if (currentTheme === "dark") {
        body.classList.add("dark");
        themeToggle.textContent = "☀️";
    }

    themeToggle.onclick = () => {
        body.classList.toggle("dark");
        const isDark = body.classList.contains("dark");
        localStorage.setItem("theme", isDark ? "dark" : "light");
        themeToggle.textContent = isDark ? "☀️" : "🌙";
    };
</script>

</body>
</html>