<?php
session_start();

// 1. Centralized Auth Check
if (!isset($_SESSION["email"])) {
    header("Location: /COMMERCE/login.php");
    exit();
}

$userName = htmlspecialchars($_SESSION["name"] ?? 'User');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard | Commerce</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    
    <style>
        :root {
            --bg-color: #f4f7f6;
            --text-color: #333;
            --card-bg: #ffffff;
            --accent-color: #3498db;
            --danger-color: #e74c3c;
            --header-shadow: rgba(0,0,0,0.1);
        }

        body.dark {
            --bg-color: #1a1a2e;
            --text-color: #f4f7f6;
            --card-bg: #16213e;
            --accent-color: #4cc9f0;
            --header-shadow: rgba(0,0,0,0.3);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; transition: background 0.3s ease, color 0.3s ease; }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-color);
            color: var(--text-color);
            overflow-x: hidden;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 5%;
            background: var(--card-bg);
            box-shadow: 0 2px 10px var(--header-shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .logo-section { display: flex; align-items: center; gap: 15px; }

        /* Desktop Navigation */
        .navbar { display: flex; align-items: center; gap: 25px; }
        .navbar a {
            text-decoration: none;
            color: var(--text-color);
            font-weight: 500;
            position: relative;
        }
        .navbar a:hover { color: var(--accent-color); }

        #menu-icon { font-size: 2rem; cursor: pointer; display: none; }

        /* Grid Layout */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            padding: 40px 5%;
        }

        .card {
            background: var(--card-bg);
            padding: 35px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .card:hover { transform: translateY(-8px); box-shadow: 0 12px 25px rgba(0,0,0,0.1); }
        .card i { font-size: 3rem; color: var(--accent-color); margin-bottom: 20px; display: block; }
        .btn-link { 
            text-decoration: none; 
            color: var(--accent-color); 
            font-weight: 600; 
            margin-top: 15px; 
            display: inline-block; 
        }

        .toggle-btn { cursor: pointer; font-size: 1.4rem; margin-left: 10px; }

        /* Mobile Specific Styles */
        @media (max-width: 768px) {
            #menu-icon { display: block; order: 3; }
            
            .navbar {
                position: absolute;
                top: 100%;
                right: -100%;
                width: 250px;
                height: 100vh;
                background: var(--card-bg);
                flex-direction: column;
                align-items: flex-start;
                padding: 40px;
                gap: 30px;
                transition: 0.4s ease;
                box-shadow: -5px 0 15px var(--header-shadow);
            }

            .navbar.active { right: 0; }
            .text-danger { color: var(--danger-color); }
        }
    </style>
</head>
<body>

<header>
    <div class="logo-section">
        <i id="goBackBtn" class='bx bx-left-arrow-alt' style="cursor:pointer; font-size: 1.8rem;"></i>
        <h2>Dashboard</h2>
    </div>
    
    <nav class="navbar">
        <span>Hi, <strong><?= $userName ?></strong></span>
        <a href="/COMMERCE/profile.php">Profile</a>
        <a href="/COMMERCE/cart.php">My Cart</a>
        <a href="/COMMERCE/logout.php" class="text-danger">Logout</a>
        <span class="toggle-btn" id="themeToggle">🌙</span>
    </nav>

    <i class='bx bx-menu' id="menu-icon"></i>
</header>

<main class="grid">
    <div class="card">
        <i class='bx bx-shopping-bag'></i>
        <h4>My Orders</h4>
        <p>Track shipments and view past purchases.</p>
        <a href="/Commerce/order_history.php" class="btn-link">History &rarr;</a>
    </div>

    <div class="card">
        <i class='bx bx-store-alt'></i>
        <h4>Shop</h4>
        <p>Discover new arrivals and top deals.</p>
        <a href="/COMMERCE/shop.php" class="btn-link">Go to Shop &rarr;</a>
    </div>

    <div class="card">
        <i class='bx bx-heart-circle'></i>
        <h4>Wishlist</h4>
        <p>Save items you love for later.</p>
        <a href="wishlist.php" class="btn-link">View Saved &rarr;</a>
    </div>

    <div class="card">
        <i class='bx bx-user-voice'></i>
        <h4>Settings</h4>
        <p>Manage privacy and account security.</p>
        <a href="settings.php" class="btn-link">Manage &rarr;</a>
    </div>
</main>

<script>
    // Theme Logic
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

    // Mobile Menu Toggle Logic
    const menuIcon = document.querySelector('#menu-icon');
    const navbar = document.querySelector('.navbar');

    menuIcon.onclick = () => {
        menuIcon.classList.toggle('bx-x');
        navbar.classList.toggle('active');
    };

    // Close menu when clicking outside or on a link
    window.onscroll = () => {
        menuIcon.classList.remove('bx-x');
        navbar.classList.remove('active');
    };

    document.getElementById('goBackBtn').onclick = () => window.history.back();
</script>

</body>
</html>