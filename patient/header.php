<?php
// Check if session is started, if not start it
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once "../includes/config.php";
require_once "../includes/functions.php";

// Check if user is logged in
$is_logged_in = isset($_SESSION["loggedin"]) && isset($_SESSION["patient_id"]);

// Get cart count
$cart_count = 0;
if ($is_logged_in) {
    $cart_sql = "SELECT SUM(quantity) as total FROM cart WHERE patient_id = ?";
    if ($cart_stmt = mysqli_prepare($conn, $cart_sql)) {
        mysqli_stmt_bind_param($cart_stmt, "i", $_SESSION["patient_id"]);
        mysqli_stmt_execute($cart_stmt);
        $cart_result = mysqli_stmt_get_result($cart_stmt);
        if ($cart_row = mysqli_fetch_assoc($cart_result)) {
            $cart_count = $cart_row['total'] ?: 0;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PawPoint - Patient Portal</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <nav class="navbar">
        <?php if ($is_logged_in): ?>
        <ul class="nav-menu">
            <li>
                <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="find_doctor.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'find_doctor.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-md"></i> Find Doctor
                </a>
            </li>
            <li>
                <a href="appointments.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'appointments.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i> Appointments
                </a>
            </li>
            <li>
                <a href="products.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">
                    <i class="fas fa-box"></i> Products
                </a>
            </li>
            <li>
                <a href="cart.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'cart.php' ? 'active' : ''; ?>" style="position: relative;">
                    <i class="fas fa-shopping-cart"></i> Cart
                    <?php if ($cart_count > 0): ?>
                        <span class="cart-badge"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-circle"></i> Profile
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
            <li>
                <a href="login.php">Login</a>
            </li>
            <li>
                <a href="register.php">Register</a>
            </li>
        </ul>
        <?php endif; ?>
        <div class="hamburger">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </div>
    </nav>
    <div class="container">
