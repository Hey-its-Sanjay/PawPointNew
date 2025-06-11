<?php
// Check if session is started, if not start it
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$is_logged_in = isset($_SESSION["loggedin"]) && isset($_SESSION["doctor_id"]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PawPoint - Doctor Portal</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/modern.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <div class="logo">
                    <img src="../images/Pawpoint.png" alt="PawPoint Logo">
                    <span>Paw</span>Point
                </div>
                <div class="menu-toggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <?php if ($is_logged_in): ?>
                <ul class="nav-menu">
                    <li>
                        <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="appointments.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'appointments.php' ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-check"></i> Appointments
                        </a>
                    </li>
                    <li>
                        <a href="patients.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'patients.php' ? 'active' : ''; ?>">
                            <i class="fas fa-users"></i> Patients
                        </a>
                    </li>
                    <li>
                        <a href="profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                            <i class="fas fa-user-md"></i> Profile
                        </a>
                    </li>
                    <li>
                        <a href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
                <?php else: ?>
                <ul class="nav-menu">
                    <li><a href="../index.php">Home</a></li>
                    <li><a href="login.php" class="btn btn-primary btn-sm">Login</a></li>
                    <li><a href="register.php" class="btn btn-outline-primary btn-sm">Register</a></li>
                </ul>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
<!-- Add this to the navigation menu -->
<li><a href="chat.php" <?php echo basename($_SERVER['PHP_SELF']) == 'chat.php' ? 'class="active"' : ''; ?>>Messages <span id="navUnreadBadge" class="unread-badge" style="display: none;"></span></a></li>
<!-- Add this script before the closing body tag -->
<script>
    // Function to check for unread messages
    function checkUnreadMessages() {
        fetch('../includes/get_unread_count.php')
            .then(response => response.json())
            .then(data => {
                const unreadBadge = document.getElementById('navUnreadBadge');
                if (data.unread_count > 0) {
                    unreadBadge.textContent = data.unread_count;
                    unreadBadge.style.display = 'inline-flex';
                } else {
                    unreadBadge.style.display = 'none';
                }
            })
            .catch(error => console.error('Error checking unread messages:', error));
    }
    
    // Check for unread messages initially
    checkUnreadMessages();
    
    // Check for unread messages every 30 seconds
    setInterval(checkUnreadMessages, 30000);
</script>
</body>
