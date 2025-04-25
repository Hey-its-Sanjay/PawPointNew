<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["admin_id"])){
    header("location: login.php");
    exit;
}

// Include config file
require_once "../includes/functions.php";

// Get count of doctors
$doctors_count = 0;
$sql_doctors = "SELECT COUNT(*) AS total FROM doctors";
$result_doctors = mysqli_query($conn, $sql_doctors);
if($result_doctors){
    $row = mysqli_fetch_assoc($result_doctors);
    $doctors_count = $row['total'];
}

// Get count of pending doctor applications
$pending_doctors_count = 0;
$sql_pending_doctors = "SELECT COUNT(*) AS total FROM doctors WHERE status = 'pending'";
$result_pending_doctors = mysqli_query($conn, $sql_pending_doctors);
if($result_pending_doctors){
    $row = mysqli_fetch_assoc($result_pending_doctors);
    $pending_doctors_count = $row['total'];
}

// Get count of patients
$patients_count = 0;
$sql_patients = "SELECT COUNT(*) AS total FROM patients";
$result_patients = mysqli_query($conn, $sql_patients);
if($result_patients){
    $row = mysqli_fetch_assoc($result_patients);
    $patients_count = $row['total'];
}

// Get recent pending doctor applications
$pending_doctors = [];
$sql_recent_pending = "SELECT id, name, email, speciality, status FROM doctors WHERE status = 'pending' ORDER BY id DESC LIMIT 5";
$result_recent_pending = mysqli_query($conn, $sql_recent_pending);
if($result_recent_pending){
    while($row = mysqli_fetch_assoc($result_recent_pending)){
        $pending_doctors[] = $row;
    }
}
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PawPoint</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-header {
            background-color: #34495E;
        }
        .admin-nav {
            background-color: #2C3E50;
        }
        .admin-panel {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            margin-top: 20px;
        }
        .admin-panel-item {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            flex-basis: 48%;
            margin-bottom: 20px;
            padding: 20px;
        }
        .admin-stats {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-card {
            background-color: #f5f5f5;
            border-radius: 8px;
            flex: 1;
            min-width: 200px;
            padding: 15px;
            text-align: center;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 10px 0;
        }
        .admin-action-btn {
            background-color: #2C3E50;
            border: none;
            border-radius: 4px;
            color: white;
            cursor: pointer;
            display: block;
            font-size: 16px;
            margin: 10px 0;
            padding: 10px;
            text-align: center;
            text-decoration: none;
            width: 100%;
        }
        .admin-action-btn:hover {
            background-color: #1A252F;
        }
        .status-pending {
            background-color: #F39C12;
            border-radius: 4px;
            color: white;
            display: inline-block;
            font-size: 0.75rem;
            padding: 3px 8px;
        }
        .table {
            border-collapse: collapse;
            margin: 15px 0;
            width: 100%;
        }
        .table th, .table td {
            border: 1px solid #ddd;
            padding: 8px 12px;
            text-align: left;
        }
        .table th {
            background-color: #2C3E50;
            color: white;
        }
        .btn-small {
            padding: 3px 8px;
            font-size: 0.8rem;
            margin: 2px;
        }
        .btn-approve {
            background-color: #27AE60;
        }
        .highlight-count {
            background-color: #F39C12;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <h1>PawPoint Admin</h1>
        <p>Administration Portal</p>
    </header>
    
    <nav class="admin-nav">
        <ul>
            <li><a href="dashboard.php">Dashboard</a></li>
            <li><a href="manage_doctors.php">Manage Doctors</a></li>
            <li><a href="manage_patients.php">Manage Patients</a></li>
            <li><a href="settings.php">Settings</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
    
    <div class="container">
        <h2>Welcome, Admin <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h2>
        <div class="alert alert-success">
            You have successfully logged in to the admin panel.
        </div>
        
        <div class="admin-stats">
            <div class="stat-card">
                <h3>Total Doctors</h3>
                <div class="stat-number"><?php echo $doctors_count; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Pending Doctor Applications</h3>
                <div class="stat-number highlight-count"><?php echo $pending_doctors_count; ?></div>
                <?php if($pending_doctors_count > 0): ?>
                <a href="manage_doctors.php" class="btn btn-approve btn-small">Review Applications</a>
                <?php endif; ?>
            </div>
            
            <div class="stat-card">
                <h3>Total Patients</h3>
                <div class="stat-number"><?php echo $patients_count; ?></div>
            </div>
        </div>
        
        <?php if(count($pending_doctors) > 0): ?>
        <div class="admin-panel-item" style="width: 100%;">
            <h3>Recent Doctor Applications</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Speciality</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($pending_doctors as $doctor): ?>
                    <tr>
                        <td><?= htmlspecialchars($doctor['name']) ?></td>
                        <td><?= htmlspecialchars($doctor['email']) ?></td>
                        <td><?= htmlspecialchars($doctor['speciality']) ?></td>
                        <td><span class="status-pending">Pending</span></td>
                        <td>
                            <a href="manage_doctors.php?action=approve&id=<?= $doctor['id'] ?>" class="btn btn-approve btn-small">Approve</a>
                            <a href="manage_doctors.php?action=reject&id=<?= $doctor['id'] ?>" class="btn btn-small btn-reject">Reject</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <a href="manage_doctors.php" class="admin-action-btn">Manage All Doctors</a>
        </div>
        <?php endif; ?>
        
        <div class="admin-panel">
            <div class="admin-panel-item">
                <h3>Doctor Management</h3>
                <p>Add, edit, and delete doctors from the system.</p>
                <a href="manage_doctors.php" class="admin-action-btn">Manage Doctors</a>
                <a href="add_doctor.php" class="admin-action-btn">Add New Doctor</a>
            </div>
            
            <div class="admin-panel-item">
                <h3>Patient Management</h3>
                <p>Add, edit, and delete patient accounts.</p>
                <a href="manage_patients.php" class="admin-action-btn">Manage Patients</a>
                <a href="add_patient.php" class="admin-action-btn">Add New Patient</a>
            </div>
            
            <div class="admin-panel-item">
                <h3>Appointment Management</h3>
                <p>View and manage appointments between doctors and patients.</p>
                <a href="manage_appointments.php" class="admin-action-btn">Manage Appointments</a>
            </div>
            
            <div class="admin-panel-item">
                <h3>System Settings</h3>
                <p>Configure system settings and admin account details.</p>
                <a href="settings.php" class="admin-action-btn">System Settings</a>
                <a href="change_password.php" class="admin-action-btn">Change Admin Password</a>
            </div>
        </div>
    </div>
    
    <footer>
        <p>&copy; <?php echo date("Y"); ?> PawPoint. All rights reserved.</p>
    </footer>
</body>
</html> 