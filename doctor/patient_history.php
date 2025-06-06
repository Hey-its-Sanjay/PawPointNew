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
$error_message = "";
$debug_info = [];

// Get patient details and appointment history
$patient = null;
$appointments = [];

// First check if the patient exists
$check_patient_sql = "SELECT id FROM patients WHERE id = ?";
if($stmt = mysqli_prepare($conn, $check_patient_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $patient_id);
    if(mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if(!mysqli_fetch_assoc($result)) {
            $error_message = "Patient not found.";
            $debug_info[] = "Patient ID $patient_id does not exist in the database.";
        }
    }
    mysqli_stmt_close($stmt);
}

// Then check if the doctor has appointments with this patient
if(empty($error_message)) {
    $check_appointments_sql = "SELECT COUNT(*) as count FROM appointments WHERE patient_id = ? AND doctor_id = ?";
    if($stmt = mysqli_prepare($conn, $check_appointments_sql)) {
        mysqli_stmt_bind_param($stmt, "ii", $patient_id, $_SESSION["doctor_id"]);
        if(mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($result);
            if($row['count'] == 0) {
                $error_message = "You don't have any appointments with this patient.";
                $debug_info[] = "No appointments found for doctor ID " . $_SESSION["doctor_id"] . " and patient ID $patient_id";
            }
        }
        mysqli_stmt_close($stmt);
    }
}

// If no errors, get patient details
if(empty($error_message)) {
    $sql = "SELECT p.*, 
            COALESCE(p.pet_age, 'Not Specified') as pet_age,
            COALESCE(p.pet_gender, 'Not Specified') as pet_gender,
            COALESCE(p.phone, 'Not Available') as phone
            FROM patients p 
            WHERE p.id = ?";

    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $patient_id);
        if(mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if($row = mysqli_fetch_assoc($result)) {
                $patient = $row;
            }
        } else {
            $error_message = "Failed to retrieve patient details.";
            $debug_info[] = "MySQL Error: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
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

<?php include('header.php'); ?>

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
        </div>    <?php else: ?>
        <div class="alert">
            <p><?php echo !empty($error_message) ? htmlspecialchars($error_message) : "No patient found or you don't have permission to view this patient's history."; ?></p>
            <?php if(!empty($debug_info) && isset($_SESSION['is_dev']) && $_SESSION['is_dev']): ?>
                <div class="debug-info">
                    <h4>Debug Information:</h4>
                    <ul>
                        <?php foreach($debug_info as $info): ?>
                            <li><?php echo htmlspecialchars($info); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include('../includes/footer.php'); ?>
