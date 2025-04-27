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

// Get doctor stats
$today = date('Y-m-d');
$doctor_id = $_SESSION["doctor_id"];

// Count today's appointments
$today_appointments = 0;
$sql = "SELECT COUNT(*) as count FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND status != 'cancelled'";
if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "is", $doctor_id, $today);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if($row = mysqli_fetch_assoc($result)) {
        $today_appointments = $row['count'];
    }
    mysqli_stmt_close($stmt);
}

// Count total patients
$total_patients = 0;
$sql = "SELECT COUNT(DISTINCT patient_id) as count FROM appointments WHERE doctor_id = ? AND status != 'cancelled'";
if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $doctor_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if($row = mysqli_fetch_assoc($result)) {
        $total_patients = $row['count'];
    }
    mysqli_stmt_close($stmt);
}

// Count upcoming appointments (future date with confirmed status)
$upcoming_appointments = 0;
$sql = "SELECT COUNT(*) as count FROM appointments WHERE doctor_id = ? AND appointment_date > ? AND status = 'confirmed'";
if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "is", $doctor_id, $today);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if($row = mysqli_fetch_assoc($result)) {
        $upcoming_appointments = $row['count'];
    }
    mysqli_stmt_close($stmt);
}

// Get recent activity (latest 5 appointments)
$recent_appointments = [];
$sql = "SELECT a.id, a.appointment_date, a.appointment_time, a.status, a.created_at, 
        p.name as patient_name, p.pet_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        WHERE a.doctor_id = ?
        ORDER BY a.created_at DESC
        LIMIT 5";

if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $doctor_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while($row = mysqli_fetch_assoc($result)) {
        $recent_appointments[] = $row;
    }
    
    mysqli_stmt_close($stmt);
}
?>
 
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - PawPoint</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .quick-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .stat-card {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            flex: 1;
            margin-right: 10px;
            text-align: center;
        }
        .stat-card:last-child {
            margin-right: 0;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #4a7c59;
            margin: 10px 0;
        }
        .activity-item {
            border-bottom: 1px solid #ddd;
            padding: 10px 0;
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .activity-time {
            color: #666;
            font-size: 0.85rem;
        }
        .status-pending, .status-confirmed, .status-completed, .status-cancelled {
            border-radius: 4px;
            color: white;
            display: inline-block;
            font-size: 0.75rem;
            padding: 3px 8px;
            margin-left: 5px;
        }
        .status-pending {
            background-color: #F39C12;
        }
        .status-confirmed {
            background-color: #27AE60;
        }
        .status-completed {
            background-color: #3498DB;
        }
        .status-cancelled {
            background-color: #E74C3C;
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
        <h2>Welcome, Dr. <?php echo htmlspecialchars($_SESSION["name"]); ?>!</h2>
        <div class="alert alert-success">
            You have successfully logged in to your doctor account.
        </div>
        
        <div class="quick-stats">
            <div class="stat-card">
                <h3>Today's Appointments</h3>
                <div class="stat-number"><?= $today_appointments ?></div>
                <?php if($today_appointments > 0): ?>
                    <a href="appointments.php" class="btn btn-small">View</a>
                <?php endif; ?>
            </div>
            
            <div class="stat-card">
                <h3>Total Patients</h3>
                <div class="stat-number"><?= $total_patients ?></div>
                <?php if($total_patients > 0): ?>
                    <a href="patients.php" class="btn btn-small">View</a>
                <?php endif; ?>
            </div>
            
            <div class="stat-card">
                <h3>Upcoming Appointments</h3>
                <div class="stat-number"><?= $upcoming_appointments ?></div>
                <?php if($upcoming_appointments > 0): ?>
                    <a href="appointments.php" class="btn btn-small">View</a>
                <?php endif; ?>
            </div>
        </div>
        
        <div style="display: flex; justify-content: space-between; margin-top: 40px;">
            <div class="form-container" style="width: 45%;">
                <h3>Quick Stats</h3>
                <div style="margin-top: 20px;">
                    <p><strong>Available Time Slots:</strong> 10AM to 4PM (except 1PM lunch hour)</p>
                    <?php if($today_appointments > 0): ?>
                        <p><strong>Next Appointment:</strong>
                            <?php
                                $next_appointment_sql = "SELECT appointment_time FROM appointments 
                                    WHERE doctor_id = ? AND appointment_date = ? AND status = 'confirmed' 
                                    AND appointment_time >= CURTIME()
                                    ORDER BY appointment_time ASC LIMIT 1";
                                if($next_stmt = mysqli_prepare($conn, $next_appointment_sql)) {
                                    mysqli_stmt_bind_param($next_stmt, "is", $doctor_id, $today);
                                    mysqli_stmt_execute($next_stmt);
                                    $next_result = mysqli_stmt_get_result($next_stmt);
                                    if($next_row = mysqli_fetch_assoc($next_result)) {
                                        echo date('h:i A', strtotime($next_row['appointment_time']));
                                    } else {
                                        echo "No more appointments today";
                                    }
                                    mysqli_stmt_close($next_stmt);
                                }
                            ?>
                        </p>
                    <?php endif; ?>
                    <p><strong>Pending Confirmations:</strong> 
                        <?php
                            $pending_sql = "SELECT COUNT(*) as count FROM appointments 
                                WHERE doctor_id = ? AND status = 'pending'";
                            if($pending_stmt = mysqli_prepare($conn, $pending_sql)) {
                                mysqli_stmt_bind_param($pending_stmt, "i", $doctor_id);
                                mysqli_stmt_execute($pending_stmt);
                                $pending_result = mysqli_stmt_get_result($pending_stmt);
                                if($pending_row = mysqli_fetch_assoc($pending_result)) {
                                    echo $pending_row['count'];
                                } else {
                                    echo "0";
                                }
                                mysqli_stmt_close($pending_stmt);
                            }
                        ?>
                    </p>
                </div>
            </div>
            
            <div class="form-container" style="width: 45%;">
                <h3>Recent Activity</h3>
                <div style="margin-top: 20px;">
                    <?php if(count($recent_appointments) > 0): ?>
                        <?php foreach($recent_appointments as $appointment): ?>
                            <div class="activity-item">
                                <p>
                                    <strong><?= htmlspecialchars($appointment['patient_name']) ?></strong> 
                                    (<?= htmlspecialchars($appointment['pet_name']) ?>) 
                                    booked an appointment for 
                                    <?= date('M d, Y', strtotime($appointment['appointment_date'])) ?> 
                                    at <?= date('h:i A', strtotime($appointment['appointment_time'])) ?>
                                    <span class="status-<?= $appointment['status'] ?>"><?= ucfirst($appointment['status']) ?></span>
                                </p>
                                <p class="activity-time"><?= date('M d, Y h:i A', strtotime($appointment['created_at'])) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No recent activity to show.</p>
                    <?php endif; ?>
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