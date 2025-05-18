<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["doctor_id"])){
    header("location: login.php");
    exit;
}

// Include config file
require_once "D:/xampp/htdocs/Vetcare/pawpoint/includes/functions.php";

// Check if patient ID is provided
if(!isset($_GET["id"]) || empty(trim($_GET["id"]))) {
    header("location: patients.php");
    exit;
}

$patient_id = trim($_GET["id"]);

// Get patient details and appointment history
$patient = null;
$appointments = [];

// First get patient details
$sql = "SELECT p.* FROM patients p 
        INNER JOIN appointments a ON p.id = a.patient_id 
        WHERE p.id = ? AND a.doctor_id = ? 
        LIMIT 1";

if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "ii", $patient_id, $_SESSION["doctor_id"]);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if($row = mysqli_fetch_assoc($result)) {
        $patient = $row;
    } else {
        // If patient not found or not associated with this doctor, redirect back
        header("location: patients.php");
        exit;
    }
    mysqli_stmt_close($stmt);
}

// Then get appointment history
$sql = "SELECT * FROM appointments 
        WHERE patient_id = ? AND doctor_id = ? 
        ORDER BY appointment_date DESC, appointment_time DESC";

if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "ii", $patient_id, $_SESSION["doctor_id"]);
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
    <title>Patient History - PawPoint</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .patient-profile {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .patient-profile h3 {
            color: #4a7c59;
            margin-top: 0;
            grid-column: 1 / -1;
        }

        .profile-section {
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
        }

        .profile-section h4 {
            margin-top: 0;
            color: #4a7c59;
        }

        .appointment-history {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .appointment-list {
            margin-top: 20px;
        }

        .appointment-card {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background: #f9f9f9;
        }

        .appointment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .appointment-date {
            font-weight: bold;
            color: #4a7c59;
        }

        .appointment-status {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
        }

        .status-completed { background: #c8e6c9; color: #2e7d32; }
        .status-confirmed { background: #bbdefb; color: #1976d2; }
        .status-pending { background: #fff9c4; color: #f57f17; }
        .status-cancelled { background: #ffcdd2; color: #c62828; }

        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4a7c59;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .back-btn:hover {
            background-color: #3e5c47;
        }

        @media (max-width: 768px) {
            .patient-profile {
                grid-template-columns: 1fr;
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
        <a href="patients.php" class="back-btn">← Back to Patients</a>
        
        <div class="patient-profile">
            <h3>Patient Profile</h3>
            
            <div class="profile-section">
                <h4>Patient Details</h4>
                <p><strong>Name:</strong> <?= htmlspecialchars($patient['name']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($patient['email']) ?></p>
                <p><strong>Phone:</strong> <?= htmlspecialchars($patient['phone']) ?></p>
                <p><strong>Address:</strong> <?= htmlspecialchars($patient['address']) ?></p>
            </div>

            <div class="profile-section">
                <h4>Pet Details</h4>
                <p><strong>Pet Name:</strong> <?= htmlspecialchars($patient['pet_name']) ?></p>
                <p><strong>Pet Type:</strong> <?= htmlspecialchars($patient['pet_type']) ?></p>
                <p><strong>Pet Age:</strong> <?= htmlspecialchars($patient['pet_age']) ?> years</p>
                <p><strong>Pet Gender:</strong> <?= htmlspecialchars($patient['pet_gender']) ?></p>
            </div>
        </div>

        <div class="appointment-history">
            <h3>Appointment History</h3>
            
            <?php if(empty($appointments)): ?>
                <p>No appointment history found.</p>
            <?php else: ?>
                <div class="appointment-list">
                    <?php foreach($appointments as $appointment): ?>
                        <div class="appointment-card">
                            <div class="appointment-header">
                                <span class="appointment-date">
                                    <?= date('M d, Y', strtotime($appointment['appointment_date'])) ?> at 
                                    <?= date('h:i A', strtotime($appointment['appointment_time'])) ?>
                                </span>
                                <span class="appointment-status status-<?= $appointment['status'] ?>">
                                    <?= ucfirst($appointment['status']) ?>
                                </span>
                            </div>
                            <p><strong>Reason:</strong> <?= htmlspecialchars($appointment['reason']) ?></p>
                            <?php if(!empty($appointment['notes'])): ?>
                                <p><strong>Notes:</strong> <?= htmlspecialchars($appointment['notes']) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer>
        <p>&copy; <?= date("Y") ?> PawPoint. All rights reserved.</p>
    </footer>
</body>
</html>
