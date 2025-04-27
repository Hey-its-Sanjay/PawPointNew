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

// Define variables
$success_message = $error_message = "";

// Process appointment cancellation
if(isset($_GET['action']) && $_GET['action'] == 'cancel' && isset($_GET['id'])) {
    $appointment_id = intval($_GET['id']);
    
    // Check if the appointment belongs to the current patient
    $check_sql = "SELECT id FROM appointments WHERE id = ? AND patient_id = ?";
    if($check_stmt = mysqli_prepare($conn, $check_sql)) {
        mysqli_stmt_bind_param($check_stmt, "ii", $appointment_id, $_SESSION["patient_id"]);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if(mysqli_stmt_num_rows($check_stmt) > 0) {
            // Update appointment status to cancelled
            $update_sql = "UPDATE appointments SET status = 'cancelled' WHERE id = ?";
            if($update_stmt = mysqli_prepare($conn, $update_sql)) {
                mysqli_stmt_bind_param($update_stmt, "i", $appointment_id);
                if(mysqli_stmt_execute($update_stmt)) {
                    $success_message = "Your appointment has been cancelled successfully.";
                } else {
                    $error_message = "Oops! Something went wrong. Please try again later.";
                }
                mysqli_stmt_close($update_stmt);
            }
        } else {
            $error_message = "Invalid appointment or you don't have permission to cancel this appointment.";
        }
        
        mysqli_stmt_close($check_stmt);
    }
}

// Get patient's appointments
$appointments = [];
$sql = "SELECT a.id, a.appointment_date, a.appointment_time, a.reason, a.status, 
        d.name as doctor_name, d.speciality as doctor_speciality
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.id
        WHERE a.patient_id = ?
        ORDER BY a.appointment_date DESC, a.appointment_time ASC";

if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["patient_id"]);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while($row = mysqli_fetch_assoc($result)) {
        $appointments[] = $row;
    }
    
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments - PawPoint</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
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
            background-color: #4a7c59;
            color: white;
            font-weight: bold;
        }
        .table tr:nth-child(even) {
            background-color: #f5f5f5;
        }
        .table tr:hover {
            background-color: #f1f1f1;
        }
        .status-pending {
            background-color: #F39C12;
            border-radius: 4px;
            color: white;
            display: inline-block;
            font-size: 0.75rem;
            padding: 3px 8px;
        }
        .status-confirmed {
            background-color: #27AE60;
            border-radius: 4px;
            color: white;
            display: inline-block;
            font-size: 0.75rem;
            padding: 3px 8px;
        }
        .status-completed {
            background-color: #3498DB;
            border-radius: 4px;
            color: white;
            display: inline-block;
            font-size: 0.75rem;
            padding: 3px 8px;
        }
        .status-cancelled {
            background-color: #E74C3C;
            border-radius: 4px;
            color: white;
            display: inline-block;
            font-size: 0.75rem;
            padding: 3px 8px;
        }
        .btn-small {
            padding: 5px 10px;
            font-size: 0.8rem;
        }
        .btn-cancel {
            background-color: #E74C3C;
        }
        .btn-cancel:hover {
            background-color: #C0392B;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
    </style>
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
            <li><a href="find_doctor.php">Find Doctor</a></li>
            <li><a href="appointments.php">My Appointments</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
    
    <div class="container">
        <h2>My Appointments</h2>
        
        <?php 
            if(!empty($success_message)) {
                echo '<div class="alert alert-success">' . $success_message . '</div>';
            }
            if(!empty($error_message)) {
                echo '<div class="alert alert-error">' . $error_message . '</div>';
            }
        ?>
        
        <div class="form-container">
            <?php if(count($appointments) > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Doctor</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($appointments as $appointment): ?>
                            <tr>
                                <td>Dr. <?= htmlspecialchars($appointment['doctor_name']) ?><br>
                                    <small><?= htmlspecialchars($appointment['doctor_speciality']) ?></small>
                                </td>
                                <td><?= date('M d, Y', strtotime($appointment['appointment_date'])) ?></td>
                                <td><?= date('h:i A', strtotime($appointment['appointment_time'])) ?></td>
                                <td><?= htmlspecialchars($appointment['reason']) ?></td>
                                <td>
                                    <?php if($appointment['status'] == 'pending'): ?>
                                        <span class="status-pending">Pending</span>
                                    <?php elseif($appointment['status'] == 'confirmed'): ?>
                                        <span class="status-confirmed">Confirmed</span>
                                    <?php elseif($appointment['status'] == 'completed'): ?>
                                        <span class="status-completed">Completed</span>
                                    <?php elseif($appointment['status'] == 'cancelled'): ?>
                                        <span class="status-cancelled">Cancelled</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($appointment['status'] == 'pending' || $appointment['status'] == 'confirmed'): ?>
                                        <a href="appointments.php?action=cancel&id=<?= $appointment['id'] ?>" 
                                           class="btn btn-cancel btn-small"
                                           onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                            Cancel
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>You don't have any appointments yet.</p>
            <?php endif; ?>
            
            <a href="book_appointment.php" class="btn btn-primary">Book New Appointment</a>
        </div>
    </div>
    
    <footer>
        <p>&copy; <?php echo date("Y"); ?> PawPoint. All rights reserved.</p>
    </footer>
</body>
</html> 