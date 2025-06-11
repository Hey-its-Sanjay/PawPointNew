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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, sans-serif;
        }

        body {
            background-color: #f4f7fa;
            color: #333;
        }

        header {
            background-color: #003f6b;
            color: white;
            padding: 20px;
            text-align: center;
        }

        nav {
            background-color: #005b96;
            padding: 10px 0;
        }

        nav ul {
            display: flex;
            justify-content: center;
            list-style: none;
        }

        nav ul li {
            margin: 0 20px;
        }

        nav ul li a {
            color: white;
            text-decoration: none;
            font-weight: bold;
            transition: color 0.3s;
        }

        nav ul li a:hover {
            color: #d4f0fc;
        }

        .container {
            padding: 30px;
            max-width: 1100px;
            margin: auto;
        }

        h2 {
            margin-bottom: 10px;
            color: #003f6b;
        }

        .alert {
            background-color: #d4edda;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 5px solid #28a745;
            border-radius: 5px;
        }

        .quick-stats {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: #fff;
            border-radius: 10px;
            flex: 1;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: transform 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card h3 {
            margin-bottom: 10px;
            color: #555;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #005b96;
            margin: 10px 0;
        }

        .btn {
            display: inline-block;
            margin-top: 10px;
            background-color: #005b96;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.9rem;
            transition: background 0.3s;
        }

        .btn:hover {
            background-color: #004b7d;
        }

        .form-container {
            background-color: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .activity-item {
            border-bottom: 1px solid #eee;
            padding: 12px 0;
        }

        .activity-time {
            color: #888;
            font-size: 0.85rem;
        }

        .status-pending {
            background-color: #f39c12;
        }

        .status-confirmed {
            background-color: #27ae60;
        }

        .status-completed {
            background-color: #3498db;
        }

        .status-cancelled {
            background-color: #e74c3c;
        }

        .status-pending, .status-confirmed, .status-completed, .status-cancelled {
            color: white;
            padding: 3px 8px;
            font-size: 0.75rem;
            border-radius: 4px;
            margin-left: 5px;
            display: inline-block;
        }

        .btn-primary {
            background-color: #28a745;
        }

        .btn-primary:hover {
            background-color: #218838;
        }

        .quick-actions {
            display: flex;
            gap: 20px;
            justify-content: space-between;
        }

        footer {
            text-align: center;
            padding: 20px;
            color: #777;
            margin-top: 40px;
            border-top: 1px solid #ddd;
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

        <div class="alert">
            You have successfully logged in to your doctor account.
        </div>

        <div class="quick-stats">
            <div class="stat-card">
                <h3>Today's Appointments</h3>
                <div class="stat-number"><?= $today_appointments ?></div>
                <?php if ($today_appointments > 0): ?>
                    <a href="appointments.php" class="btn">View</a>
                <?php endif; ?>
            </div>

            <div class="stat-card">
                <h3>Total Patients</h3>
                <div class="stat-number"><?= $total_patients ?></div>
                <?php if ($total_patients > 0): ?>
                    <a href="patients.php" class="btn">View</a>
                <?php endif; ?>
            </div>

            <div class="stat-card">
                <h3>Upcoming Appointments</h3>
                <div class="stat-number"><?= $upcoming_appointments ?></div>
                <?php if ($upcoming_appointments > 0): ?>
                    <a href="appointments.php" class="btn">View</a>
                <?php endif; ?>
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; gap: 20px;">
            <div class="form-container" style="width: 48%;">
                <h3>Quick Stats</h3>
                <div style="margin-top: 20px;">
                    <p><strong>Available Time Slots:</strong> 10AM to 4PM (except 1PM lunch hour)</p>
                    <?php if ($today_appointments > 0): ?>
                        <p><strong>Next Appointment:</strong>
                            <?php
                            $next_appointment_sql = "SELECT appointment_time FROM appointments 
                                WHERE doctor_id = ? AND appointment_date = ? AND status = 'confirmed' 
                                AND appointment_time >= CURTIME()
                                ORDER BY appointment_time ASC LIMIT 1";
                            if ($next_stmt = mysqli_prepare($conn, $next_appointment_sql)) {
                                mysqli_stmt_bind_param($next_stmt, "is", $doctor_id, $today);
                                mysqli_stmt_execute($next_stmt);
                                $next_result = mysqli_stmt_get_result($next_stmt);
                                if ($next_row = mysqli_fetch_assoc($next_result)) {
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
                        if ($pending_stmt = mysqli_prepare($conn, $pending_sql)) {
                            mysqli_stmt_bind_param($pending_stmt, "i", $doctor_id);
                            mysqli_stmt_execute($pending_stmt);
                            $pending_result = mysqli_stmt_get_result($pending_stmt);
                            if ($pending_row = mysqli_fetch_assoc($pending_result)) {
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

            <div class="form-container" style="width: 48%;">
                <h3>Recent Activity</h3>
                <div style="margin-top: 20px;">
                    <?php if (count($recent_appointments) > 0): ?>
                        <?php foreach ($recent_appointments as $appointment): ?>
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

        <div class="form-container">
            <h3>Quick Actions</h3>
            <!-- Modify the quick-actions div around line 390 -->
            <div class="quick-actions">
                <a href="appointments.php" class="btn btn-primary">View Appointments</a>
                <a href="patients.php" class="btn btn-primary">Manage Patients</a>
                <a href="chat.php" class="btn btn-primary">Messages <span id="unreadBadge" class="unread-badge" style="display: none;"></span></a>
                <a href="profile.php" class="btn btn-primary">Update Profile</a>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> PawPoint. All rights reserved.</p>
    </footer>
</body>
</html>

<!-- Add this to the style section around line 250 -->
.unread-badge {
    background-color: #e74c3c;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    margin-left: 5px;
}

<!-- Add this script before the closing body tag -->
<script>
    // Function to check for unread messages
    function checkUnreadMessages() {
        fetch('../includes/get_unread_count.php')
            .then(response => response.json())
            .then(data => {
                const unreadBadge = document.getElementById('unreadBadge');
                if (data.unread_count > 0) {
                    unreadBadge.textContent = data.unread_count;
                    unreadBadge.style.display = 'inline-flex';
                } else {
                    unreadBadge.style.display = 'none';
                }
            })
            .catch(error => console.error('Error checking unread messages:', error));
    }
    
    // Check for unread messages initially
    checkUnreadMessages();
    
    // Check for unread messages every 30 seconds
    setInterval(checkUnreadMessages, 30000);
</script>
