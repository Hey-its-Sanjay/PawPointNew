<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["admin_id"])){
    header("location: login.php");
    exit;
}

require_once "../includes/functions.php";

// Check if patient ID is provided
if(!isset($_GET["id"]) || empty(trim($_GET["id"]))){
    header("location: manage_patients.php");
    exit;
}

$id = trim($_GET["id"]);
$patient = [];
$appointments = [];

// Get patient details
$sql = "SELECT * FROM patients WHERE id = ?";
if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) == 1){
            $patient = mysqli_fetch_array($result, MYSQLI_ASSOC);
        } else{            header("location: manage_patients.php");
            exit();
        }
    } else{
        $error = "Something went wrong. Please try again later.";
    }
    mysqli_stmt_close($stmt);
}

// Get patient's appointments
$sql = "SELECT a.*, d.name as doctor_name 
        FROM appointments a 
        LEFT JOIN doctors d ON a.doctor_id = d.id 
        WHERE a.patient_id = ? 
        ORDER BY a.appointment_date DESC, a.appointment_time DESC";

if($stmt = mysqli_prepare($conn, $sql)){
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if(mysqli_stmt_execute($stmt)){
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_array($result)){
            $appointments[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Patient - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .container { max-width: 1000px; margin: 30px auto; padding: 0 20px; }
        .patient-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .patient-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        .info-section {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .info-section h3 {
            margin-top: 0;
            color: #2c3e50;
            font-size: 1.2em;
        }
        .info-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .info-list li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .info-list li:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: bold;
            color: #7f8c8d;
        }
        .appointments-section {
            margin-top: 30px;
        }
        .appointment-card {
            background: #f8f9fa;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 10px;
        }
        .appointment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .appointment-date {
            font-weight: bold;
            color: #2c3e50;
        }
        .appointment-status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
        }
        .status-pending { background: #f39c12; color: white; }
        .status-confirmed { background: #3498db; color: white; }
        .status-completed { background: #2ecc71; color: white; }
        .status-cancelled { background: #e74c3c; color: white; }
        .btn-back {
            display: inline-block;
            padding: 10px 20px;
            background-color: #95a5a6;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include "header.php"; ?>

    <div class="container">
        <a href="patients.php" class="btn-back">← Back to Patients</a>

        <div class="patient-card">
            <h2>Patient Information</h2>
            <div class="patient-info">
                <div class="info-section">
                    <h3>Personal Details</h3>
                    <ul class="info-list">
                        <li><span class="info-label">Name:</span> <?php echo htmlspecialchars($patient["name"]); ?></li>
                        <li><span class="info-label">Email:</span> <?php echo htmlspecialchars($patient["email"]); ?></li>
                        <li><span class="info-label">Address:</span> <?php echo htmlspecialchars($patient["address"]); ?></li>
                        <li><span class="info-label">Registered:</span> <?php echo date("F j, Y", strtotime($patient["created_at"])); ?></li>
                        <li><span class="info-label">Email Status:</span> 
                            <?php if($patient["email_verified"]): ?>
                                <span class="appointment-status status-confirmed">Verified</span>
                            <?php else: ?>
                                <span class="appointment-status status-pending">Not Verified</span>
                            <?php endif; ?>
                        </li>
                    </ul>
                </div>
                <div class="info-section">
                    <h3>Pet Information</h3>
                    <ul class="info-list">
                        <li><span class="info-label">Pet Name:</span> <?php echo htmlspecialchars($patient["pet_name"]); ?></li>
                        <li><span class="info-label">Pet Type:</span> <?php echo htmlspecialchars($patient["pet_type"]); ?></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="appointments-section">
            <h2>Appointment History</h2>
            <?php if(empty($appointments)): ?>
                <p>No appointments found for this patient.</p>
            <?php else: ?>
                <?php foreach($appointments as $appointment): ?>
                    <div class="appointment-card">
                        <div class="appointment-header">
                            <div class="appointment-date">
                                <?php echo date("F j, Y", strtotime($appointment["appointment_date"])); ?> at 
                                <?php echo date("g:i A", strtotime($appointment["appointment_time"])); ?>
                            </div>
                            <span class="appointment-status status-<?php echo $appointment["status"]; ?>">
                                <?php echo ucfirst($appointment["status"]); ?>
                            </span>
                        </div>
                        <div>
                            <p><strong>Doctor:</strong> <?php echo htmlspecialchars($appointment["doctor_name"]); ?></p>
                            <p><strong>Reason:</strong> <?php echo htmlspecialchars($appointment["reason"]); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php include "footer.php"; ?>
</body>
</html>
