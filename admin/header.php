<?php
// Check if session is started, if not start it
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$is_logged_in = isset($_SESSION["loggedin"]) && isset($_SESSION["admin_id"]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PawPoint Admin</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --accent-color: #e74c3c;
            --light-gray: #f8f9fa;
            --dark-gray: #7f8c8d;
            --border-color: #e9ecef;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
        }
        
        .admin-layout {
            display: flex;
            min-height: 100vh;
            position: relative;
        }
        
        /* Override any conflicting nav styles from style.css */
        .admin-layout nav.sidebar-menu {
            background-color: transparent;
            padding: 0;
        }
        
        .admin-layout nav.sidebar-menu ul {
            flex-direction: column;
            justify-content: flex-start;
        }
        
        .admin-layout nav.sidebar-menu li {
            margin: 0 0 5px 0;
            width: 100%;
        }
        
        .sidebar {
            width: 250px;
            background-color: var(--secondary-color);
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            overflow-y: auto;
            z-index: 1000; /* Increased z-index */
            transition: all 0.3s ease;
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-header h1 {
            margin: 0;
            font-size: 24px;
            color: white;
        }
        
        .sidebar-header p {
            margin: 5px 0 0 0;
            font-size: 14px;
            opacity: 0.7;
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .sidebar-menu ul {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .sidebar-menu a:hover, 
        .sidebar-menu a.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .sidebar-menu i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s ease;
            position: relative;
            z-index: 900;
        }
        
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            background-color: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            border-radius: 8px;
            margin-bottom: 20px;
            position: relative;
            z-index: 950;
        }
        
        .top-bar-title h2 {
            margin: 0;
            font-size: 20px;
            color: var(--secondary-color);
        }
        
        .top-bar-actions {
            display: flex;
            align-items: center;
        }
        
        .user-menu {
            position: relative;
            display: inline-block;
        }
        
        .user-menu-toggle {
            display: flex;
            align-items: center;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 4px;
            transition: background-color 0.2s ease;
        }
        
        .user-menu-toggle:hover {
            background-color: var(--light-gray);
        }
        
        .user-name {
            margin: 0 10px;
            color: var(--secondary-color);
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        /* Mobile menu toggle */
        .mobile-menu-toggle {
            display: none;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            position: fixed;
            top: 10px;
            left: 10px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 4px;
            cursor: pointer;
            z-index: 1001;
        }
        
        /* General button styling */
        .btn {
            display: inline-block;
            font-weight: 400;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            user-select: none;
            border: 1px solid transparent;
            padding: 0.375rem 0.75rem;
            font-size: 1rem;
            line-height: 1.5;
            border-radius: 0.25rem;
            transition: color 0.15s ease-in-out, background-color 0.15s ease-in-out, border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
            text-decoration: none;
        }

        .btn-primary {
            color: #fff;
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: flex;
            }
            
            .sidebar {
                width: 0;
                transform: translateX(-100%);
                box-shadow: 5px 0 15px rgba(0,0,0,0.1);
            }
            
            .sidebar.active {
                width: 250px;
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            body.sidebar-active {
                overflow: hidden;
            }
            
            body.sidebar-active:after {
                content: '';
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0,0,0,0.5);
                z-index: 999;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <div class="mobile-menu-toggle">
            <i class="fas fa-bars"></i>
        </div>
        
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1>PawPoint</h1>
                <p>Admin Portal</p>
            </div>
            <nav class="sidebar-menu">
                <ul>
                    <li>
                        <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="doctors.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'doctors.php' ? 'active' : ''; ?>">
                            <i class="fas fa-user-md"></i>
                            <span>Doctors</span>
                        </a>
                    </li>
                    <li>
                        <a href="patients.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'patients.php' ? 'active' : ''; ?>">
                            <i class="fas fa-paw"></i>
                            <span>Patients</span>
                        </a>
                    </li>
                    <li>
                        <a href="appointments.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'appointments.php' ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Appointments</span>
                        </a>
                    </li>
                    <li>
                        <a href="manage_products.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_products.php' ? 'active' : ''; ?>">
                            <i class="fas fa-box"></i>
                            <span>Products</span>
                        </a>
                    </li>
                    <li>
                        <a href="reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                            <i class="fas fa-chart-bar"></i>
                            <span>Reports</span>
                        </a>
                    </li>
                    <li>
                        <a href="settings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                    <li>
                        <a href="profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                            <i class="fas fa-user-circle"></i>
                            <span>Profile</span>
                        </a>
                    </li>
                    <li>
                        <a href="logout.php">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                    <li>
                        <a href="manage_doctors.php" <?php echo basename($_SERVER['PHP_SELF']) == 'manage_doctors.php' ? 'class="active"' : ''; ?>>
                            <i class="fas fa-user-md"></i> Manage Doctors
                        </a>
                    </li>
                    <li>
                        <a href="manage_patients.php" <?php echo basename($_SERVER['PHP_SELF']) == 'manage_patients.php' ? 'class="active"' : ''; ?>>
                            <i class="fas fa-users"></i> Manage Patients
                        </a>
                    </li>
                    <li>
                        <a href="manage_reviews.php" <?php echo basename($_SERVER['PHP_SELF']) == 'manage_reviews.php' ? 'class="active"' : ''; ?>>
                            <i class="fas fa-star"></i> Manage Reviews
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>
        
        <main class="main-content">
            <div class="top-bar">
                <div class="top-bar-title">
                    <h2>
                        <?php 
                        $current_page = basename($_SERVER['PHP_SELF'], '.php');
                        echo ucfirst(str_replace('_', ' ', $current_page));
                        ?>
                    </h2>
                </div>
                <?php if($is_logged_in): ?>
                <div class="top-bar-actions">
                    <div class="user-menu">
                        <div class="user-menu-toggle">
                            <div class="user-avatar">
                                <?php 
                                $username = isset($_SESSION["username"]) ? $_SESSION["username"] : "A";
                                echo strtoupper(substr($username, 0, 1)); 
                                ?>
                            </div>
                            <span class="user-name"><?php echo isset($_SESSION["username"]) ? htmlspecialchars($_SESSION["username"]) : "Admin"; ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="user-dropdown-menu" style="display: none; position: absolute; right: 0; top: 45px; background: white; border-radius: 4px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); min-width: 180px; z-index: 1000;">
                            <a href="profile.php" style="display: block; padding: 10px 15px; color: var(--secondary-color); text-decoration: none; border-bottom: 1px solid var(--border-color);">
                                <i class="fas fa-user-circle" style="margin-right: 10px;"></i> Profile
                            </a>
                            <a href="settings.php" style="display: block; padding: 10px 15px; color: var(--secondary-color); text-decoration: none; border-bottom: 1px solid var(--border-color);">
                                <i class="fas fa-cog" style="margin-right: 10px;"></i> Settings
                            </a>
                            <a href="logout.php" style="display: block; padding: 10px 15px; color: var(--accent-color); text-decoration: none;">
                                <i class="fas fa-sign-out-alt" style="margin-right: 10px;"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Main content starts here -->