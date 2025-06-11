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
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/modern.css">
    <style>
        /* Admin-specific styles */
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background-color: var(--dark);
            color: var(--white);
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            overflow-y: auto;
            z-index: 1000;
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
            color: var(--white);
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
            display: block;
            padding: 10px 20px;
            color: var(--light-gray);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: var(--white);
            border-left-color: var(--primary);
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
            transition: all 0.3s ease;
        }
        
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background-color: var(--white);
            box-shadow: var(--shadow-sm);
            margin-bottom: 20px;
        }
        
        .toggle-sidebar {
            display: none;
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: var(--dark);
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            margin-right: 10px;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .toggle-sidebar {
                display: block;
            }
            
            .sidebar {
                left: -250px;
            }
            
            .sidebar.active {
                left: 0;
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <?php if ($is_logged_in): ?>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1>PawPoint</h1>
                <p>Admin Panel</p>
            </div>
            <nav class="sidebar-menu">
                <ul>
                    <li>
                        <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="doctors.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'doctors.php' || basename($_SERVER['PHP_SELF']) == 'manage_doctors.php' ? 'active' : ''; ?>">
                            <i class="fas fa-user-md"></i> Doctors
                        </a>
                    </li>
                    <li>
                        <a href="patients.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'patients.php' || basename($_SERVER['PHP_SELF']) == 'manage_patients.php' ? 'active' : ''; ?>">
                            <i class="fas fa-users"></i> Patients
                        </a>
                    </li>
                    <li>
                        <a href="appointments.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'appointments.php' ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-check"></i> Appointments
                        </a>
                    </li>
                    <li>
                        <a href="manage_products.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_products.php' ? 'active' : ''; ?>">
                            <i class="fas fa-shopping-bag"></i> Products
                        </a>
                    </li>
                    <li>
                        <a href="manage_reviews.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_reviews.php' ? 'active' : ''; ?>">
                            <i class="fas fa-star"></i> Reviews
                        </a>
                    </li>
                    <li>
                        <a href="reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                            <i class="fas fa-chart-bar"></i> Reports
                        </a>
                    </li>
                    <li>
                        <a href="settings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                    </li>
                    <li>
                        <a href="profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                            <i class="fas fa-user"></i> Profile
                        </a>
                    </li>
                    <li>
                        <a href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="main-content">
            <div class="topbar">
                <button class="toggle-sidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <span>Admin</span>
                </div>
            </div>
    <?php else: ?>
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <div class="logo">
                    <img src="../images/Pawpoint.png" alt="PawPoint Logo">
                    <span>Paw</span>Point
                </div>
                <ul class="nav-menu">
                    <li><a href="../index.php">Home</a></li>
                    <li><a href="login.php" class="btn btn-primary btn-sm">Login</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <main class="main-content">
        <div class="container">
    <?php endif; ?>