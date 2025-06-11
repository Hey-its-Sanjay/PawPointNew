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
require_once "../includes/email_functions.php";

// Process approval/rejection
if(isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $action = $_GET['action'];
    
    // Get doctor's email before updating status
    $doctor_email = '';
    $doctor_name = '';
    $get_doctor_sql = "SELECT email, name FROM doctors WHERE id = ?";
    if($get_stmt = mysqli_prepare($conn, $get_doctor_sql)) {
        mysqli_stmt_bind_param($get_stmt, "i", $id);
        mysqli_stmt_execute($get_stmt);
        mysqli_stmt_bind_result($get_stmt, $doctor_email, $doctor_name);
        mysqli_stmt_fetch($get_stmt);
        mysqli_stmt_close($get_stmt);
    }
    
    if($action == 'approve') {
        $sql = "UPDATE doctors SET status = 'approved' WHERE id = ?";
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            // Send approval email to doctor
            if(!empty($doctor_email)) {
                send_doctor_approval_email($doctor_email, $doctor_name);
            }
            
            // Set success message
            $success_message = "Doctor has been approved successfully and notification email has been sent.";
        }
    } elseif($action == 'reject') {
        $sql = "UPDATE doctors SET status = 'rejected' WHERE id = ?";
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            // Send rejection email to doctor
            if(!empty($doctor_email)) {
                send_doctor_rejection_email($doctor_email, $doctor_name);
            }
            
            // Set success message
            $success_message = "Doctor has been rejected and notification email has been sent.";
        }
    } elseif($action == 'delete') {
        $sql = "DELETE FROM doctors WHERE id = ?";
        if($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            // Set success message
            $success_message = "Doctor has been deleted successfully.";
        }
    }
}

// Get list of doctors
$doctors = [];
$sql = "SELECT id, name, age, speciality, email, status FROM doctors ORDER BY FIELD(status, 'pending', 'rejected', 'approved'), name ASC";
$result = mysqli_query($conn, $sql);

if($result) {
    while($row = mysqli_fetch_assoc($result)) {
        $doctors[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Doctors - PawPoint Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-header {
            background-color: #34495E;
        }
        .admin-nav {
            background-color: #2C3E50;
        }
        .table {
            border-collapse: collapse;
            margin: 25px 0;
            width: 100%;
        }
        .table th, .table td {
            border: 1px solid #ddd;
            padding: 12px 15px;
            text-align: left;
        }
        .table th {
            background-color: #2C3E50;
            color: white;
            font-weight: bold;
        }
        .table tr:nth-child(even) {
            background-color: #f5f5f5;
        }
        .table tr:hover {
            background-color: #f1f1f1;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        .btn-small {
            padding: 5px 10px;
            font-size: 0.8rem;
        }
        .btn-approve {
            background-color: #27AE60;
        }
        .btn-approve:hover {
            background-color: #219A52;
        }
        .btn-reject {
            background-color: #E74C3C;
        }
        .btn-reject:hover {
            background-color: #C0392B;
        }
        .status-pending {
            background-color: #F39C12;
            border-radius: 4px;
            color: white;
            display: inline-block;
            font-size: 0.75rem;
            padding: 3px 8px;
        }
        .status-approved {
            background-color: #27AE60;
            border-radius: 4px;
            color: white;
            display: inline-block;
            font-size: 0.75rem;
            padding: 3px 8px;
        }
        .status-rejected {
            background-color: #E74C3C;
            border-radius: 4px;
            color: white;
            display: inline-block;
            font-size: 0.75rem;
            padding: 3px 8px;
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
        <h2>Manage Doctors</h2>
        
        <?php 
            if(isset($success_message)) {
                echo '<div class="alert alert-success">' . $success_message . '</div>';
            }
        ?>
        
        <?php if(count($doctors) > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Age</th>
                        <th>Speciality</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($doctors as $doctor): ?>
                    <tr>
                        <td><?= $doctor['id'] ?></td>
                        <td><?= htmlspecialchars($doctor['name']) ?></td>
                        <td><?= $doctor['age'] ?></td>
                        <td><?= htmlspecialchars($doctor['speciality']) ?></td>
                        <td><?= htmlspecialchars($doctor['email']) ?></td>
                        <td>
                            <?php if($doctor['status'] == 'pending'): ?>
                                <span class="status-pending">Pending</span>
                            <?php elseif($doctor['status'] == 'approved'): ?>
                                <span class="status-approved">Approved</span>
                            <?php elseif($doctor['status'] == 'rejected'): ?>
                                <span class="status-rejected">Rejected</span>
                            <?php endif; ?>
                        </td>                        <td>
                            <div class="action-buttons">
                                <a href="view_doctor.php?id=<?= $doctor['id'] ?>" class="btn btn-info btn-small">View</a>
                                <?php if($doctor['status'] == 'pending'): ?>
                                    <a href="manage_doctors.php?action=approve&id=<?= $doctor['id'] ?>" class="btn btn-approve btn-small">Approve</a>
                                    <a href="manage_doctors.php?action=reject&id=<?= $doctor['id'] ?>" class="btn btn-reject btn-small">Reject</a>
                                <?php elseif($doctor['status'] == 'rejected'): ?>
                                    <a href="manage_doctors.php?action=approve&id=<?= $doctor['id'] ?>" class="btn btn-approve btn-small">Approve</a>
                                <?php elseif($doctor['status'] == 'approved'): ?>
                                    <a href="manage_doctors.php?action=reject&id=<?= $doctor['id'] ?>" class="btn btn-reject btn-small">Reject</a>
                                <?php endif; ?>
                                <a href="edit_doctor.php?id=<?= $doctor['id'] ?>" class="btn btn-small">Edit</a>
                                <a href="manage_doctors.php?action=delete&id=<?= $doctor['id'] ?>" class="btn btn-reject btn-small" onclick="return confirm('Are you sure you want to delete this doctor?')">Delete</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No doctors found in the system.</p>
        <?php endif; ?>
        
        <a href="add_doctor.php" class="btn btn-primary">Add New Doctor</a>
    </div>
    
    <footer>
        <p>&copy; <?php echo date("Y"); ?> PawPoint. All rights reserved.</p>
    </footer>
</body>
</html>
