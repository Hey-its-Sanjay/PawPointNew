<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["doctor_id"])){
    header("location: login.php");
    exit;
}

// Include config and email functions
require_once "D:/xampp/htdocs/Vetcare/pawpoint/includes/functions.php";
require_once "D:/xampp/htdocs/Vetcare/pawpoint/includes/phpmailer_setup.php";
require_once "D:/xampp/htdocs/Vetcare/pawpoint/includes/email_functions.php";

// Define variables
$success_message = $error_message = "";

// Process appointment actions (confirm, complete, cancel)
if(isset($_GET['action']) && isset($_GET['id'])) {
    $appointment_id = intval($_GET['id']);
    $action = $_GET['action'];

    // Check if the appointment belongs to the current doctor and get patient info
    $check_sql = "SELECT a.id, a.appointment_date, a.appointment_time, a.status, p.email as patient_email, p.name as patient_name, d.name as doctor_name FROM appointments a JOIN patients p ON a.patient_id = p.id JOIN doctors d ON a.doctor_id = d.id WHERE a.id = ? AND a.doctor_id = ?";
    if($check_stmt = mysqli_prepare($conn, $check_sql)) {
        mysqli_stmt_bind_param($check_stmt, "ii", $appointment_id, $_SESSION["doctor_id"]);
        mysqli_stmt_execute($check_stmt);
        $result = mysqli_stmt_get_result($check_stmt);
        if($row = mysqli_fetch_assoc($result)) {
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

                        // Send email notification to patient
                        if ($action == "confirm") {
                            send_appointment_accept_email(
                                $row['patient_email'],
                                $row['patient_name'],
                                $row['appointment_date'],
                                $row['appointment_time'],
                                $row['doctor_name']
                            );
                        } elseif ($action == "cancel") {
                            send_appointment_reject_email(
                                $row['patient_email'],
                                $row['patient_name'],
                                $row['appointment_date'],
                                $row['appointment_time'],
                                $row['doctor_name']
                            );
                        }
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
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
        }        header {
            background-color: #2c3e50;
            color: white;
            padding: 20px;
            text-align: center;
        }
        nav {
            background-color: #34495e;
        }
        nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
        }
        nav ul li {
            margin: 0;
        }
        nav ul li a {
            color: white;
            text-decoration: none;
            padding: 12px 18px;
            display: block;
        }
        nav ul li a:hover {
            background-color: #2c3e50;
        }

        .container {
            max-width: 1100px;
            margin: 30px auto;
            padding: 0 20px;
        }        h2 {
            color: #2c3e50;
        }

        .table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .table th, .table td {
            border: 1px solid #ddd;
            padding: 15px;
            text-align: left;
        }        .table th {
            background-color: #2c3e50;
            color: white;
        }
        .table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .table tr:hover {
            background-color: #eef3f0;
        }

        .status-pending,
        .status-confirmed,
        .status-completed,
        .status-cancelled {
            padding: 6px 10px;
            font-size: 0.8rem;
            border-radius: 20px;
            font-weight: 600;
            display: inline-block;
        }        .status-pending { background-color: #f39c12; color: white; }
        .status-confirmed { background-color: #3498db; color: white; }
        .status-completed { background-color: #2ecc71; color: white; }
        .status-cancelled { background-color: #e74c3c; color: white; }

        .btn-small {
            padding: 7px 12px;
            font-size: 0.8rem;
            margin-right: 5px;
            border: none;
            color: white;
            border-radius: 4px;
            text-decoration: none;
            transition: background-color 0.3s;
        }        .btn-confirm { background-color: #3498db; }
        .btn-complete { background-color: #2ecc71; }
        .btn-cancel { background-color: #e74c3c; }

        .btn-confirm:hover { background-color: #2980b9; }
        .btn-complete:hover { background-color: #27ae60; }
        .btn-cancel:hover { background-color: #c0392b; }

        .filter-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 25px;
            border-bottom: 2px solid #ccc;
        }
        .filter-tab {
            padding: 10px 15px;
            border-radius: 5px 5px 0 0;
            background-color: #e0e0e0;
            text-decoration: none;
            color: #333;
            font-weight: bold;
        }
        .filter-tab:hover {
            background-color: #ccc;
        }        .filter-tab.active {
            background-color: #2c3e50;
            color: white;
        }

        .alert-success,
        .alert-error {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
        }

        .today-highlight {
            background-color: #fffdd0 !important;
        }

        .appointment-summary {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }
        .summary-card {
            flex: 1;
            background-color: #ffffff;
            border: 1px solid #ccc;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            box-shadow: 0 1px 6px rgba(0,0,0,0.1);
        }        .summary-number {
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
        }

        footer {
            background-color: #2c3e50;
            color: white;
            text-align: center;
            padding: 15px;
            margin-top: 40px;
        }

        @media (max-width: 768px) {
            .appointment-summary {
                flex-direction: column;
            }
            .filter-tabs {
                flex-direction: column;
            }
            .btn-small {
                display: block;
                margin-bottom: 5px;
            }
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
                echo '<div class="alert-success">' . $success_message . '</div>';
            }
            if(!empty($error_message)) {
                echo '<div class="alert-error">' . $error_message . '</div>';
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
                                <span class="status-<?= $appointment['status'] ?>">
                                    <?= ucfirst($appointment['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if($appointment['status'] == 'pending'): ?>
                                    <a href="appointments.php?action=confirm&id=<?= $appointment['id'] ?>" class="btn-confirm btn-small">Confirm</a>
                                    <a href="appointments.php?action=cancel&id=<?= $appointment['id'] ?>" class="btn-cancel btn-small" onclick="return confirm('Cancel this appointment?')">Cancel</a>
                                <?php elseif($appointment['status'] == 'confirmed'): ?>
                                    <a href="appointments.php?action=complete&id=<?= $appointment['id'] ?>" class="btn-complete btn-small">Complete</a>
                                    <a href="appointments.php?action=cancel&id=<?= $appointment['id'] ?>" class="btn-cancel btn-small" onclick="return confirm('Cancel this appointment?')">Cancel</a>
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

    <footer>
        <p>&copy; <?= date("Y") ?> PawPoint. All rights reserved.</p>
    </footer>
</body>
</html>
