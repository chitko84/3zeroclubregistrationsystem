<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3ZERO Club Registration System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #1a5276;
            --primary-dark: #154360;
            --secondary: #28b463;
            --accent: #f39c12;
            --light: #f8f9fa;
            --dark: #212529;
            --gray: #6c757d;
            --gray-light: #e9ecef;
            --border-radius: 8px;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f7fb;
            color: var(--dark);
            line-height: 1.6;
            padding-top: 0;
        }

        /* Header Styles */
        .main-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 1rem 0;
            box-shadow: var(--shadow);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }

        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .club-logo {
            width: 50px;
            height: 50px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 1.3rem;
            font-weight: bold;
        }

        .logo-text h1 {
            font-size: 1.5rem;
            margin: 0;
            font-weight: 700;
        }

        .logo-text p {
            font-size: 0.9rem;
            opacity: 0.9;
            margin: 0;
        }

        /* Mobile Menu Button */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 5px;
        }

        /* User Actions */
        .user-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .notification-icon, .user-profile {
            position: relative;
            cursor: pointer;
        }

        .notification-icon i {
            font-size: 1.2rem;
            color: white;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--accent);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-profile img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background: linear-gradient(to bottom, var(--primary), var(--primary-dark));
            color: white;
            padding: 20px 0;
            transition: var(--transition);
            box-shadow: var(--shadow);
            z-index: 999;
            position: fixed;
            top: 80px;
            left: 0;
            bottom: 0;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
            text-align: center;
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-menu li {
            margin-bottom: 5px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: var(--transition);
            border-left: 3px solid transparent;
        }

        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            border-left-color: var(--secondary);
        }

        .sidebar-menu a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Main Content Area */
        .main-content {
            margin-left: 250px;
            margin-top: 80px;
            padding: 20px;
            transition: var(--transition);
        }

        /* Mobile Styles */
        @media (max-width: 992px) {
            .mobile-menu-btn {
                display: block;
            }

            .sidebar {
                transform: translateX(-100%);
                width: 280px;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .logo-text h1 {
                font-size: 1.3rem;
            }

            .logo-text p {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .header-content {
                padding: 0 1rem;
            }

            .club-logo {
                width: 40px;
                height: 40px;
                font-size: 1.1rem;
            }

            .user-actions {
                gap: 15px;
            }
        }

        /* Overlay for mobile menu */
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 998;
        }

        .overlay.active {
            display: block;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="header-content">
            <div class="logo-section">
                <button class="mobile-menu-btn" id="mobileMenuBtn">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="club-logo">3Z</div>
                <div class="logo-text">
                    <h1>3ZERO Club</h1>
                    <p>Registration System</p>
                </div>
            </div>
            
            <div class="user-actions">
                <div class="notification-icon">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </div>
                <div class="user-profile">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <img src="https://i.pravatar.cc/150?img=32" alt="User Profile">
                    <?php else: ?>
                        <a href="login.php" style="color: white; text-decoration: none;">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Mobile Overlay -->
    <div class="overlay" id="overlay"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <ul class="sidebar-menu">
            <li><a href="dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                <i class="fas fa-home"></i> <span>Dashboard</span>
            </a></li>
            <li><a href="club_registration.php" class="<?= basename($_SERVER['PHP_SELF']) == 'club_registration.php' ? 'active' : '' ?>">
                <i class="fas fa-user-plus"></i> <span>Register Club</span>
            </a></li>
            <li><a href="myclubs.php" class="<?= basename($_SERVER['PHP_SELF']) == 'myclubs.php' ? 'active' : '' ?>">
                <i class="fas fa-users"></i> <span>My Clubs</span>
            </a></li>
            <li><a href="events.php" class="<?= basename($_SERVER['PHP_SELF']) == 'events.php' ? 'active' : '' ?>">
                <i class="fas fa-calendar-alt"></i> <span>Events</span>
            </a></li>
            <li><a href="projects.php" class="<?= basename($_SERVER['PHP_SELF']) == 'projects.php' ? 'active' : '' ?>">
                <i class="fas fa-tasks"></i> <span>Projects</span>
            </a></li>
            <li><a href="achievements.php" class="<?= basename($_SERVER['PHP_SELF']) == 'achievements.php' ? 'active' : '' ?>">
                <i class="fas fa-certificate"></i> <span>Achievements</span>
            </a></li>
            <!-- <li><a href="discussions.php" class="<?= basename($_SERVER['PHP_SELF']) == 'discussions.php' ? 'active' : '' ?>">
                <i class="fas fa-comments"></i> <span>Discussions</span>
            </a></li> -->
            <li><a href="profile.php" class="<?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : '' ?>">
                <i class="fas fa-user-edit"></i> <span>Profile</span>
            </a></li>
            <!-- <li><a href="settings.php" class="<?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>">
                <i class="fas fa-cog"></i> <span>Settings</span>
            </a></li> -->
            <?php if (isset($_SESSION['user_id'])): ?>
            <li><a href="logout.php">
                <i class="fas fa-sign-out-alt"></i> <span>Logout</span>
            </a></li>
            <?php endif; ?>
        </ul>
    </aside>

    <!-- Main Content Wrapper -->
    <div class="main-content" id="mainContent">