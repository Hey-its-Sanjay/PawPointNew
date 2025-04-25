<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["patient_id"])){
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
    <title>Patient Dashboard - PawPoint</title>
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
            <li><a href="pets.php">My Pets</a></li>
            <li><a href="appointments.php">Appointments</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
    
    <div class="container">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION["name"]); ?>!</h2>
        <div class="alert alert-success">
            You have successfully logged in to your patient account.
        </div>
        
        <div style="display: flex; justify-content: space-between; margin-top: 40px;">
            <div class="form-container" style="width: 45%;">
                <h3>Your Pets</h3>
                <div style="margin-top: 20px;">
                    <p>You have no registered pets yet.</p>
                    <a href="add_pet.php" class="btn btn-primary" style="margin-top: 15px;">Add a Pet</a>
                </div>
            </div>
            
            <div class="form-container" style="width: 45%;">
                <h3>Upcoming Appointments</h3>
                <div style="margin-top: 20px;">
                    <p>You have no upcoming appointments.</p>
                    <a href="book_appointment.php" class="btn btn-primary" style="margin-top: 15px;">Book an Appointment</a>
                </div>
            </div>
        </div>
        
        <div class="form-container" style="margin-top: 30px;">
            <h3>Quick Actions</h3>
            <div style="display: flex; justify-content: space-between; margin-top: 20px;">
                <a href="find_doctor.php" class="btn btn-primary" style="width: 30%;">Find a Doctor</a>
                <a href="medical_records.php" class="btn btn-primary" style="width: 30%;">View Medical Records</a>
                <a href="profile.php" class="btn btn-primary" style="width: 30%;">Update Profile</a>
            </div>
        </div>
    </div>
    
    <footer>
        <p>&copy; <?php echo date("Y"); ?> PawPoint. All rights reserved.</p>
    </footer>
</body>
</html> 