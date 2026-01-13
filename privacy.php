<?php 
session_start();

// 1. Centralized Auth Check
if (!isset($_SESSION["email"])) {
    header("Location: /COMMERCE/login.php");
    exit();
}

// 2. CSRF Token
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
    <title>Privacy & Security | Commerce</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    
    <style>
        :root {
            --bg-color: #f4f7f6;
            --text-color: #333;
            --card-bg: #ffffff;
            --accent-color: #3498db;
            --danger-color: #e74c3c;
            --border-color: #eee;
        }

        body.dark {
            --bg-color: #1a1a2e;
            --text-color: #f4f7f6;
            --card-bg: #16213e;
            --accent-color: #4cc9f0;
            --border-color: #2e3a59;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; transition: background 0.3s ease, color 0.3s ease; }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            padding: 20px;
            line-height: 1.6;
        }

        .privacy-container {
            max-width: 800px;
            margin: 40px auto;
        }

        /* Navigation Header */
        .header-nav {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
            font-size: 0.9rem;
        }

        .back-btn { font-size: 1.5rem; cursor: pointer; color: var(--text-color); }
        .back-btn:hover { color: var(--accent-color); }
        
        .header-nav a { text-decoration: none; color: var(--accent-color); font-weight: 500; }
        .header-nav span { opacity: 0.6; }

        /* Section Cards */
        .settings-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .settings-card h3 {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.2rem;
            color: var(--accent-color);
        }

        /* Toggle & Option Rows */
        .option-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .option-row:last-child { border-bottom: none; }

        .option-info h4 { font-size: 1rem; font-weight: 600; }
        .option-info p { font-size: 0.85rem; opacity: 0.7; }

        /* Switch UI */
        .switch {
            position: relative;
            display: inline-block;
            width: 45px;
            height: 24px;
        }

        .switch input { opacity: 0; width: 0; height: 0; }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px; width: 18px;
            left: 3px; bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider { background-color: var(--accent-color); }
        input:checked + .slider:before { transform: translateX(21px); }

        .btn-action {
            background: transparent;
            border: 1px solid var(--accent-color);
            color: var(--accent-color);
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .btn-action:hover {
            background: var(--accent-color);
            color: #fff;
        }

    </style>
</head>
<body>

<div class="privacy-container">
    <header class="header-nav">
        <i class='bx bx-arrow-back back-btn' onclick="window.history.back();"></i>
        <a href="user-dashboard.php">Dashboard</a> 
        <span>&gt;</span> 
        <a href="settings.php">Settings</a> 
        <span>&gt;</span> Privacy
    </header>

    <h1>Privacy & Security</h1>

    <section class="settings-card">
        <h3><i class='bx bx-shield-quarter'></i> Account Protection</h3>
        
        <div class="option-row">
            <div class="option-info">
                <h4>Two-Factor Authentication</h4>
                <p>Add an extra layer of security to your account.</p>
            </div>
            <button class="btn-action">Enable</button>
        </div>

        <div class="option-row">
            <div class="option-info">
                <h4>Login Notifications</h4>
                <p>Get notified when someone logs into your account.</p>
            </div>
            <label class="switch">
                <input type="checkbox" checked>
                <span class="slider"></span>
            </label>
        </div>
    </section>

    <section class="settings-card">
        <h3><i class='bx bx-lock-alt'></i> Personal Data</h3>
        
        <div class="option-row">
            <div class="option-info">
                <h4>Profile Visibility</h4>
                <p>Allow others to search for your profile by email.</p>
            </div>
            <label class="switch">
                <input type="checkbox">
                <span class="slider"></span>
            </label>
        </div>

        <div class="option-row">
            <div class="option-info">
                <h4>Marketing Preferences</h4>
                <p>Receive emails about new products and special offers.</p>
            </div>
            <label class="switch">
                <input type="checkbox" checked>
                <span class="slider"></span>
            </label>
        </div>
    </section>

    <section class="settings-card">
        <h3><i class='bx bx-data'></i> Your Information</h3>
        
        <div class="option-row">
            <div class="option-info">
                <h4>Download Your Data</h4>
                <p>Get a copy of your orders and profile information.</p>
            </div>
            <button class="btn-action">Request Export</button>
        </div>

        <div class="option-row">
            <div class="option-info">
                <h4>Active Sessions</h4>
                <p>Currently logged in on <strong>1 device</strong>.</p>
            </div>
            <a href="sessions.php" class="btn-action" style="text-decoration:none">View All</a>
        </div>
    </section>
</div>

<script>
    // Theme Sync (Matches your previous logic)
    if (localStorage.getItem("theme") === "dark") {
        document.body.classList.add("dark");
    }

    // Example handler for toggle changes
    document.querySelectorAll('.switch input').forEach(toggle => {
        toggle.onchange = (e) => {
            const state = e.target.checked ? "Enabled" : "Disabled";
            console.log("Setting updated to: " + state);
            // Here you would typically trigger an AJAX call to update the DB
        };
    });
</script>

</body>
</html>