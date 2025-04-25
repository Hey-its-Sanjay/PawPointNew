<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["doctor_id"])){
    header("location: login.php");
    exit;
}

// Include config file
require_once "../includes/functions.php";
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - PawPoint</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header>
        <h1>PawPoint</h1>
        <p>Your Pet's Healthcare Companion</p>
    </header>
    
    <nav>
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="profile.php">My Profile</a></li>
            <li><a href="appointments.php">Appointments</a></li>
            <li><a href="patients.php">Patients</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
    
    <div class="container">
        <h2>Welcome, Dr. <?php echo htmlspecialchars($_SESSION["name"]); ?>!</h2>
        <div class="alert alert-success">
            You have successfully logged in to your doctor account.
        </div>
        
        <div style="display: flex; justify-content: space-between; margin-top: 40px;">
            <div class="form-container" style="width: 45%;">
                <h3>Quick Stats</h3>
                <div style="margin-top: 20px;">
                    <p><strong>Today's Appointments:</strong> 0</p>
                    <p><strong>Total Patients:</strong> 0</p>
                    <p><strong>Upcoming Appointments:</strong> 0</p>
                </div>
            </div>
            
            <div class="form-container" style="width: 45%;">
                <h3>Recent Activity</h3>
                <div style="margin-top: 20px;">
                    <p>No recent activity to show.</p>
                </div>
            </div>
        </div>
        
        <div class="form-container" style="margin-top: 30px;">
            <h3>Quick Actions</h3>
            <div style="display: flex; justify-content: space-between; margin-top: 20px;">
                <a href="appointments.php" class="btn btn-primary" style="width: 30%;">View Appointments</a>
                <a href="patients.php" class="btn btn-primary" style="width: 30%;">Manage Patients</a>
                <a href="profile.php" class="btn btn-primary" style="width: 30%;">Update Profile</a>
            </div>
        </div>
    </div>
    
    <footer>
        <p>&copy; <?php echo date("Y"); ?> PawPoint. All rights reserved.</p>
    </footer>
</body>
</html> 