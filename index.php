<?php
// Start the session
session_start();

// Check if user is already logged in
if(isset($_SESSION["doctor_id"])) {
    header("location: doctor/dashboard.php");
    exit;
} elseif(isset($_SESSION["patient_id"])) {
    header("location: patient/dashboard.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PawPoint - Veterinary Care Platform</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <h1>PawPoint</h1>
        <p>Your Pet's Healthcare Companion</p>
    </header>
    
    <nav>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="doctor/login.php">Doctor Login</a></li>
            <li><a href="patient/login.php">Patient Login</a></li>
        </ul>
    </nav>
    
    <div class="container">
        <div style="text-align: center; margin-top: 50px;">
            <h2>Welcome to PawPoint</h2>
            <p>Your all-in-one platform for veterinary care management.</p>
            
            <div style="display: flex; justify-content: center; gap: 30px; margin-top: 40px;">
                <div class="form-container" style="width: 300px;">
                    <h3>For Doctors</h3>
                    <p>Veterinary professionals can manage appointments, patient records, and more.</p>
                    <a href="doctor/login.php" class="btn btn-primary btn-block">Doctor Login</a>
                    <p style="text-align: center; margin-top: 15px;">New to PawPoint?</p>
                    <a href="doctor/register.php" class="btn btn-block">Register as a Doctor</a>
                </div>
                
                <div class="form-container" style="width: 300px;">
                    <h3>For Pet Owners</h3>
                    <p>Schedule appointments, access your pet's health records, and communicate with vets.</p>
                    <a href="patient/login.php" class="btn btn-primary btn-block">Patient Login</a>
                    <p style="text-align: center; margin-top: 15px;">New to PawPoint?</p>
                    <a href="patient/register.php" class="btn btn-block">Register as a Patient</a>
                </div>
            </div>
        </div>
    </div>
    
    <footer>
        <p>&copy; <?php echo date("Y"); ?> PawPoint. All rights reserved.</p>
    </footer>
</body>
</html> 