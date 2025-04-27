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

// Define variables
$success_message = $error_message = "";

// Process appointment actions (confirm, complete, cancel)
if(isset($_GET['action']) && isset($_GET['id'])) {
    $appointment_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    // Check if the appointment belongs to the current doctor
    $check_sql = "SELECT id FROM appointments WHERE id = ? AND doctor_id = ?";
    if($check_stmt = mysqli_prepare($conn, $check_sql)) {
        mysqli_stmt_bind_param($check_stmt, "ii", $appointment_id, $_SESSION["doctor_id"]);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if(mysqli_stmt_num_rows($check_stmt) > 0) {
            // Get the new status based on action
            $new_status = "";
            if($action == "confirm") {
                $new_status = "confirmed";
            } elseif($action == "complete") {
                $new_status = "completed";
            } elseif($action == "cancel") {
                $new_status = "cancelled";
            }
            
            if(!empty($new_status)) {
                // Update appointment status
                $update_sql = "UPDATE appointments SET status = ? WHERE id = ?";
                if($update_stmt = mysqli_prepare($conn, $update_sql)) {
                    mysqli_stmt_bind_param($update_stmt, "si", $new_status, $appointment_id);
                    if(mysqli_stmt_execute($update_stmt)) {
                        $action_text = ucfirst($new_status);
                        $success_message = "Appointment has been {$action_text} successfully.";
                    } else {
                        $error_message = "Oops! Something went wrong. Please try again later.";
                    }
                    mysqli_stmt_close($update_stmt);
                }
            }
        } else {
            $error_message = "Invalid appointment or you don't have permission to manage this appointment.";
        }
        
        mysqli_stmt_close($check_stmt);
    }
}

// Get doctor's appointments
$appointments = [];
$sql = "SELECT a.id, a.appointment_date, a.appointment_time, a.reason, a.status, 
        p.name as patient_name, p.pet_name, p.pet_type
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        WHERE a.doctor_id = ?
        ORDER BY 
            CASE
                WHEN a.appointment_date = CURDATE() THEN 0
                WHEN a.appointment_date > CURDATE() THEN 1
                ELSE 2
            END,
            a.appointment_date ASC,
            a.appointment_time ASC";

if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["doctor_id"]);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while($row = mysqli_fetch_assoc($result)) {
        $appointments[] = $row;
    }
    
    mysqli_stmt_close($stmt);
}

// Count today's appointments
$today_appointments = 0;
$today = date('Y-m-d');
foreach ($appointments as $appointment) {
    if ($appointment['appointment_date'] === $today && $appointment['status'] !== 'cancelled') {
        $today_appointments++;
    }
}

// Filter appointments by status if requested
$filtered_status = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$filtered_appointments = [];

if ($filtered_status === 'all') {
    $filtered_appointments = $appointments;
} else {
    foreach ($appointments as $appointment) {
        if ($appointment['status'] === $filtered_status) {
            $filtered_appointments[] = $appointment;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Appointments - PawPoint</title>
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
            margin-right: 5px;
        }
        .btn-confirm {
            background-color: #27AE60;
        }
        .btn-complete {
            background-color: #3498DB;
        }
        .btn-cancel {
            background-color: #E74C3C;
        }
        .filter-tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        .filter-tab {
            padding: 10px 15px;
            margin-right: 5px;
            cursor: pointer;
            border-radius: 5px 5px 0 0;
            text-decoration: none;
            color: #333;
        }
        .filter-tab:hover {
            background-color: #f1f1f1;
        }
        .filter-tab.active {
            background-color: #4a7c59;
            color: white;
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
        .today-highlight {
            background-color: #fcf8e3 !important;
        }
        .appointment-summary {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .summary-card {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            flex: 1;
            margin-right: 10px;
            text-align: center;
        }
        .summary-card:last-child {
            margin-right: 0;
        }
        .summary-number {
            font-size: 1.8rem;
            font-weight: bold;
            color: #4a7c59;
            margin: 10px 0;
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
            <li><a href="appointments.php">Appointments</a></li>
            <li><a href="patients.php">Patients</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
    
    <div class="container">
        <h2>Manage Appointments</h2>
        
        <?php 
            if(!empty($success_message)) {
                echo '<div class="alert alert-success">' . $success_message . '</div>';
            }
            if(!empty($error_message)) {
                echo '<div class="alert alert-error">' . $error_message . '</div>';
            }
        ?>
        
        <div class="appointment-summary">
            <div class="summary-card">
                <h3>Today's Appointments</h3>
                <div class="summary-number"><?= $today_appointments ?></div>
            </div>
            <div class="summary-card">
                <h3>Pending Confirmations</h3>
                <div class="summary-number">
                    <?php 
                        $pending_count = 0;
                        foreach($appointments as $appointment) {
                            if($appointment['status'] === 'pending') $pending_count++;
                        }
                        echo $pending_count;
                    ?>
                </div>
            </div>
            <div class="summary-card">
                <h3>Total Appointments</h3>
                <div class="summary-number"><?= count($appointments) ?></div>
            </div>
        </div>
        
        <div class="form-container">
            <div class="filter-tabs">
                <a href="appointments.php" class="filter-tab <?= ($filtered_status === 'all') ? 'active' : '' ?>">All</a>
                <a href="appointments.php?filter=pending" class="filter-tab <?= ($filtered_status === 'pending') ? 'active' : '' ?>">Pending</a>
                <a href="appointments.php?filter=confirmed" class="filter-tab <?= ($filtered_status === 'confirmed') ? 'active' : '' ?>">Confirmed</a>
                <a href="appointments.php?filter=completed" class="filter-tab <?= ($filtered_status === 'completed') ? 'active' : '' ?>">Completed</a>
                <a href="appointments.php?filter=cancelled" class="filter-tab <?= ($filtered_status === 'cancelled') ? 'active' : '' ?>">Cancelled</a>
            </div>
            
            <?php if(count($filtered_appointments) > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Pet</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($filtered_appointments as $appointment): 
                            // Check if appointment is today
                            $is_today = ($appointment['appointment_date'] === date('Y-m-d'));
                            $row_class = $is_today ? 'today-highlight' : '';
                        ?>
                            <tr class="<?= $row_class ?>">
                                <td><?= htmlspecialchars($appointment['patient_name']) ?></td>
                                <td>
                                    <?= htmlspecialchars($appointment['pet_name']) ?><br>
                                    <small><?= htmlspecialchars($appointment['pet_type']) ?></small>
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
                                    <?php if($appointment['status'] == 'pending'): ?>
                                        <a href="appointments.php?action=confirm&id=<?= $appointment['id'] ?>" class="btn btn-confirm btn-small">Confirm</a>
                                        <a href="appointments.php?action=cancel&id=<?= $appointment['id'] ?>" class="btn btn-cancel btn-small" onclick="return confirm('Are you sure you want to cancel this appointment?')">Cancel</a>
                                    <?php elseif($appointment['status'] == 'confirmed'): ?>
                                        <a href="appointments.php?action=complete&id=<?= $appointment['id'] ?>" class="btn btn-complete btn-small">Complete</a>
                                        <a href="appointments.php?action=cancel&id=<?= $appointment['id'] ?>" class="btn btn-cancel btn-small" onclick="return confirm('Are you sure you want to cancel this appointment?')">Cancel</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No <?= ($filtered_status !== 'all') ? $filtered_status : '' ?> appointments found.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <footer>
        <p>&copy; <?php echo date("Y"); ?> PawPoint. All rights reserved.</p>
    </footer>
</body>
</html> 