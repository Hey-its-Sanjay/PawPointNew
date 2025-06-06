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
$sql = "SELECT p.*, 
        COALESCE(p.pet_age, 'Not Specified') as pet_age,
        COALESCE(p.pet_gender, 'Not Specified') as pet_gender,
        COALESCE(p.phone, 'Not Available') as phone
        FROM patients p 
        WHERE p.id = ? AND EXISTS (
            SELECT 1 FROM appointments a 
            WHERE a.patient_id = p.id 
            AND a.doctor_id = ?
        )";

if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "ii", $patient_id, $_SESSION["doctor_id"]);
    if(mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if($row = mysqli_fetch_assoc($result)) {
            $patient = $row;
        }
    }
    mysqli_stmt_close($stmt);
}

// Then get appointment history if patient was found
if ($patient) {
    $sql = "SELECT * FROM appointments 
            WHERE patient_id = ? AND doctor_id = ? 
            ORDER BY appointment_date DESC, appointment_time DESC";

    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $patient_id, $_SESSION["doctor_id"]);
        if(mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            while($row = mysqli_fetch_assoc($result)) {
                $appointments[] = $row;
            }
        }
        mysqli_stmt_close($stmt);
    }
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

        .alert {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
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

        /* ... rest of your existing styles ... */
    </style>
</head>
<body>
    <?php include "header.php"; ?>

    <div class="container">
        <a href="patients.php" class="back-btn">← Back to Patients</a>
        
        <?php if ($patient): ?>
            <div class="patient-profile">
                <h3>Patient Profile</h3>
                
                <div class="profile-section">
                    <h4>Patient Details</h4>
                    <p><strong>Name:</strong> <?= htmlspecialchars($patient['name'] ?? 'Not available') ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($patient['email'] ?? 'Not available') ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($patient['phone'] ?? 'Not available') ?></p>
                    <p><strong>Address:</strong> <?= htmlspecialchars($patient['address'] ?? 'Not available') ?></p>
                </div>

                <div class="profile-section">
                    <h4>Pet Details</h4>
                    <p><strong>Pet Name:</strong> <?= htmlspecialchars($patient['pet_name'] ?? 'Not available') ?></p>
                    <p><strong>Pet Type:</strong> <?= htmlspecialchars($patient['pet_type'] ?? 'Not available') ?></p>
                    <p><strong>Pet Age:</strong> <?= ($patient['pet_age'] && $patient['pet_age'] !== 'Not Specified') ? htmlspecialchars($patient['pet_age']) . ' years' : 'Not specified' ?></p>
                    <p><strong>Pet Gender:</strong> <?= ($patient['pet_gender'] && $patient['pet_gender'] !== 'Not Specified') ? htmlspecialchars($patient['pet_gender']) : 'Not specified' ?></p>
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
                                <p><strong>Reason:</strong> <?= htmlspecialchars($appointment['reason'] ?? 'Not specified') ?></p>
                                <?php if(!empty($appointment['notes'])): ?>
                                    <p><strong>Notes:</strong> <?= htmlspecialchars($appointment['notes']) ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="alert">
                <p>No patient information found or you don't have permission to view this patient's details.</p>
                <p><a href="patients.php" class="btn">Return to Patients List</a></p>
            </div>
        <?php endif; ?>
    </div>

    <?php include "../includes/footer.php"; ?>
</body>
</html>
