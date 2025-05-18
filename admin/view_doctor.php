<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["admin_id"])){
    header("location: login.php");
    exit;
}

require_once "../includes/functions.php";

// Define variables
$doctor = null;
$appointments = [];
$error = "";

// Check if id parameter is present
if(isset($_GET["id"]) && !empty(trim($_GET["id"]))){
    $id = trim($_GET["id"]);
    
    // Get doctor data
    $sql = "SELECT * FROM doctors WHERE id = ?";
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "i", $param_id);
        $param_id = $id;
        
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            
            if(mysqli_num_rows($result) == 1){
                $doctor = mysqli_fetch_array($result, MYSQLI_ASSOC);
                
                // Get doctor's appointments
                $sql = "SELECT a.*, p.name as patient_name 
                       FROM appointments a 
                       JOIN patients p ON a.patient_id = p.id 
                       WHERE a.doctor_id = ? 
                       ORDER BY a.appointment_date DESC, a.appointment_time DESC";
                
                if($stmt2 = mysqli_prepare($conn, $sql)){
                    mysqli_stmt_bind_param($stmt2, "i", $id);
                    if(mysqli_stmt_execute($stmt2)){
                        $result2 = mysqli_stmt_get_result($stmt2);
                        while($row = mysqli_fetch_array($result2)){
                            $appointments[] = $row;
                        }
                    }
                    mysqli_stmt_close($stmt2);
                }
            } else {
                $error = "No doctor found with this ID.";
            }
        } else {
            $error = "Oops! Something went wrong. Please try again later.";
        }
        mysqli_stmt_close($stmt);
    }
} else {
    $error = "Invalid request. Please provide a doctor ID.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Doctor - PawPoint Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-header {
            background-color: #34495E;
        }
        .admin-nav {
            background-color: #2C3E50;
        }
        .container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
        }
        .doctor-info {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .doctor-info h3 {
            color: #2C3E50;
            margin-bottom: 15px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        .info-item {
            margin-bottom: 15px;
        }
        .info-label {
            font-weight: bold;
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        .info-value {
            color: #2c3e50;
        }
        .appointments-section {
            margin-top: 30px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .table th, .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .table th {
            background-color: #2C3E50;
            color: white;
        }
        .table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: bold;
        }
        .status-pending { background: #f1c40f; color: #fff; }
        .status-approved { background: #2ecc71; color: #fff; }
        .status-rejected { background: #e74c3c; color: #fff; }
        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #34495E;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .back-btn:hover {
            background-color: #2C3E50;
        }
        .action-btn {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            color: white;
            margin-right: 10px;
        }
        .edit-btn {
            background-color: #3498db;
        }
        .edit-btn:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <?php include "header.php"; ?>
    
    <div class="container">
        <a href="manage_doctors.php" class="back-btn">← Back to Doctors List</a>
        
        <?php if(!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php elseif($doctor): ?>
            <div class="doctor-info">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h3>Doctor Information</h3>
                    <a href="edit_doctor.php?id=<?php echo $doctor['id']; ?>" class="action-btn edit-btn">Edit Doctor</a>
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Name</div>
                        <div class="info-value"><?php echo htmlspecialchars($doctor['name']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?php echo htmlspecialchars($doctor['email']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Age</div>
                        <div class="info-value"><?php echo $doctor['age']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Speciality</div>
                        <div class="info-value"><?php echo htmlspecialchars($doctor['speciality']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Address</div>
                        <div class="info-value"><?php echo htmlspecialchars($doctor['address']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Status</div>
                        <div class="info-value">
                            <span class="status status-<?php echo $doctor['status']; ?>">
                                <?php echo ucfirst($doctor['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="appointments-section">
                <h3>Recent Appointments</h3>
                <?php if(count($appointments) > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Patient</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($appointments as $appointment): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></td>
                                    <td><?php echo htmlspecialchars($appointment['patient_name']); ?></td>
                                    <td>
                                        <span class="status status-<?php echo $appointment['status']; ?>">
                                            <?php echo ucfirst($appointment['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No appointments found for this doctor.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include "footer.php"; ?>
</body>
</html>
