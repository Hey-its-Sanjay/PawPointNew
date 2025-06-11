<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["patient_id"])){
    header("location: login.php");
    exit;
}

// Include required files
require_once "../includes/config.php";
require_once "../includes/db_connect.php";
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

// Check for review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $appointment_id = intval($_POST['appointment_id']);
    $doctor_id = intval($_POST['doctor_id']);
    $rating = intval($_POST['rating']);
    $review_text = trim($_POST['review_text']);
    
    // Validate inputs
    if ($rating < 1 || $rating > 5) {
        $error_message = "Invalid rating value.";
    } elseif (empty($review_text)) {
        $error_message = "Please enter your review.";
    } else {
        // Check if this appointment has already been reviewed
        $check_sql = "SELECT id FROM reviews WHERE appointment_id = ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($check_stmt, "i", $appointment_id);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $error_message = "You have already reviewed this appointment.";
        } else {
            // Insert the review
            $insert_sql = "INSERT INTO reviews (doctor_id, patient_id, appointment_id, rating, review_text) 
                          VALUES (?, ?, ?, ?, ?)";
            if ($insert_stmt = mysqli_prepare($conn, $insert_sql)) {
                mysqli_stmt_bind_param($insert_stmt, "iiiis", $doctor_id, $_SESSION["patient_id"], 
                                     $appointment_id, $rating, $review_text);
                if (mysqli_stmt_execute($insert_stmt)) {
                    $success_message = "Your review has been submitted successfully.";
                } else {
                    $error_message = "Error submitting review. Please try again.";
                }
                mysqli_stmt_close($insert_stmt);
            }
        }
        mysqli_stmt_close($check_stmt);
    }
}

// Get patient's appointments with review information
$appointments = [];
$sql = "SELECT a.id, a.appointment_date, a.appointment_time, a.reason, a.status, 
        d.id as doctor_id, d.name as doctor_name, d.speciality as doctor_speciality,
        r.id as review_id, r.rating, r.review_text
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.id
        LEFT JOIN reviews r ON a.id = r.appointment_id AND r.status = 'active'
        WHERE a.patient_id = ?
        ORDER BY a.appointment_date DESC, a.appointment_time ASC";

if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $_SESSION["patient_id"]);
    if(mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while($row = mysqli_fetch_assoc($result)) {
            $appointments[] = $row;
        }
    }
    mysqli_stmt_close($stmt);
}
?>

<?php include('header.php'); ?>

<div class="container">
    <h2>My Appointments</h2>
    
    <?php if($success_message): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
    <?php endif; ?>
    
    <?php if($error_message): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <?php if(empty($appointments)): ?>
        <div class="alert alert-info">You have no appointments yet.</div>
    <?php else: ?>
        <div class="appointments-container">
            <?php foreach($appointments as $appointment): ?>
                <div class="appointment-card">
                    <div class="appointment-header">
                        <h3>Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?></h3>
                        <span class="speciality"><?php echo htmlspecialchars($appointment['doctor_speciality']); ?></span>
                    </div>
                    
                    <div class="appointment-details">
                        <p><strong>Date:</strong> <?php echo date('F d, Y', strtotime($appointment['appointment_date'])); ?></p>
                        <p><strong>Time:</strong> <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></p>
                        <p><strong>Reason:</strong> <?php echo htmlspecialchars($appointment['reason']); ?></p>
                        <p><strong>Status:</strong> <span class="status-<?php echo $appointment['status']; ?>"><?php echo ucfirst($appointment['status']); ?></span></p>
                    </div>
                    
                    <div class="appointment-actions">
                        <?php if($appointment['status'] == 'pending' || $appointment['status'] == 'confirmed'): ?>
                            <a href="?action=cancel&id=<?php echo $appointment['id']; ?>" 
                               class="btn btn-danger" 
                               onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                Cancel Appointment
                            </a>
                        <?php endif; ?>
                        
                        <?php if($appointment['status'] == 'completed'): ?>
                            <?php if(!$appointment['review_id']): ?>
                                <a href="review_doctor.php?appointment_id=<?php echo $appointment['id']; ?>" 
                                   class="btn btn-primary">
                                    Leave a Review
                                </a>
                            <?php else: ?>
                                <div class="review-section">
                                    <div class="rating">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <span class="star <?php echo $i <= $appointment['rating'] ? 'filled' : ''; ?>">★</span>
                                        <?php endfor; ?>
                                    </div>
                                    <p class="review-text"><?php echo htmlspecialchars($appointment['review_text']); ?></p>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.appointments-container {
    display: grid;
    gap: 1.5rem;
    padding: 1rem 0;
}

.appointment-card {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.appointment-header {
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #eee;
}

.appointment-header h3 {
    margin: 0;
    color: #4a7c59;
}

.speciality {
    color: #666;
    font-size: 0.9rem;
}

.appointment-details p {
    margin: 0.5rem 0;
}

.status-completed { color: #28a745; }
.status-pending { color: #ffc107; }
.status-confirmed { color: #17a2b8; }
.status-cancelled { color: #dc3545; }

.appointment-actions {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #eee;
}

.btn {
    display: inline-block;
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    font-size: 0.9rem;
}

.btn-primary {
    background-color: #4a7c59;
    color: white;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
}

.review-section {
    margin-top: 1rem;
    padding: 1rem;
    background: #f9f9f9;
    border-radius: 4px;
}

.rating {
    margin-bottom: 0.5rem;
}

.star {
    color: #ddd;
    font-size: 1.2rem;
}

.star.filled {
    color: #ffd700;
}

.review-text {
    margin: 0;
    font-style: italic;
    color: #666;
}
</style>

<?php include('../includes/footer.php'); ?>