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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .table { border-collapse: collapse; margin: 25px 0; width: 100%; }
        .table th, .table td { border: 1px solid #ddd; padding: 12px 15px; text-align: left; }
        .table th { background-color: #4a7c59; color: white; font-weight: bold; }
        .table tr:nth-child(even) { background-color: #f5f5f5; }
        .table tr:hover { background-color: #f1f1f1; }
        
        .status-pending { background-color: #F39C12; color: white; }
        .status-confirmed { background-color: #27AE60; color: white; }
        .status-completed { background-color: #3498DB; color: white; }
        .status-cancelled { background-color: #E74C3C; color: white; }
        
        .status-pending, .status-confirmed, .status-completed, .status-cancelled {
            border-radius: 4px;
            display: inline-block;
            font-size: 0.75rem;
            padding: 3px 8px;
        }
        
        .btn-danger { background-color: #E74C3C; }
        .btn-danger:hover { background-color: #C0392B; }
        
        .rating { display: inline-block; }
        .rating input { display: none; }
        .rating label {
            float: right;
            cursor: pointer;
            color: #ccc;
            font-size: 24px;
        }
        .rating label:before { content: '\2605'; }
        .rating input:checked ~ label { color: #ffd700; }
        .rating:not(:checked) label:hover,
        .rating:not(:checked) label:hover ~ label { color: #ffd700; }
        
        .review-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }
        
        .review-modal-content {
            background-color: #fff;
            max-width: 500px;
            margin: 100px auto;
            padding: 20px;
            border-radius: 8px;
            position: relative;
        }
        
        .close-modal {
            position: absolute;
            right: 15px;
            top: 10px;
            font-size: 24px;
            cursor: pointer;
        }
        
        .review-form textarea {
            width: 100%;
            min-height: 100px;
            margin: 10px 0;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .reviewed-badge {
            background-color: #4a7c59;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-left: 10px;
        }
    </style>
</head>
<body>    <?php include "header.php"; ?>
    
    <div class="container">
        <h2>My Appointments</h2>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <div class="form-container">
            <?php if (count($appointments) > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Doctor</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $appointment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($appointment['appointment_date']); ?></td>
                                <td><?php echo htmlspecialchars($appointment['appointment_time']); ?></td>
                                <td>
                                    Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?>
                                    <br>
                                    <small><?php echo htmlspecialchars($appointment['doctor_speciality']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($appointment['reason']); ?></td>
                                <td>
                                    <span class="status-<?php echo strtolower($appointment['status']); ?>">
                                        <?php echo ucfirst($appointment['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($appointment['status'] === 'completed'): ?>
                                        <?php if (empty($appointment['review_id'])): ?>
                                            <button type="button" class="btn btn-primary" onclick="openReviewModal(<?php 
                                                echo $appointment['id']; ?>, <?php 
                                                echo $appointment['doctor_id']; ?>, '<?php 
                                                echo htmlspecialchars($appointment['doctor_name'], ENT_QUOTES); ?>')">
                                                Leave Review
                                            </button>
                                        <?php else: ?>
                                            <span class="reviewed-badge">
                                                <i class="fas fa-check"></i> Reviewed
                                            </span>
                                        <?php endif; ?>
                                    <?php elseif ($appointment['status'] === 'pending'): ?>
                                        <a href="?action=cancel&id=<?php echo $appointment['id']; ?>" 
                                           class="btn btn-danger" 
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
    
    <!-- Review Modal -->
    <div id="reviewModal" class="review-modal">
        <div class="review-modal-content">
            <span class="close-modal" onclick="closeReviewModal()">&times;</span>
            <h3>Review Appointment</h3>
            <form class="review-form" method="POST">
                <input type="hidden" name="appointment_id" id="appointment_id">
                <input type="hidden" name="doctor_id" id="doctor_id">
                
                <p>Doctor: <span id="doctor_name"></span></p>
                
                <div class="rating">
                    <input type="radio" id="star5" name="rating" value="5" required>
                    <label for="star5"></label>
                    <input type="radio" id="star4" name="rating" value="4">
                    <label for="star4"></label>
                    <input type="radio" id="star3" name="rating" value="3">
                    <label for="star3"></label>
                    <input type="radio" id="star2" name="rating" value="2">
                    <label for="star2"></label>
                    <input type="radio" id="star1" name="rating" value="1">
                    <label for="star1"></label>
                </div>
                
                <textarea name="review_text" placeholder="Write your review here..." required></textarea>
                
                <button type="submit" name="submit_review" class="btn btn-primary">Submit Review</button>
            </form>
        </div>
    </div>

    <script>
        function openReviewModal(appointmentId, doctorId, doctorName) {
            document.getElementById('reviewModal').style.display = 'block';
            document.getElementById('appointment_id').value = appointmentId;
            document.getElementById('doctor_id').value = doctorId;
            document.getElementById('doctor_name').textContent = 'Dr. ' + doctorName;
        }

        function closeReviewModal() {
            document.getElementById('reviewModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('reviewModal')) {
                closeReviewModal();
            }
        }
    </script>

    <?php include "../includes/footer.php"; ?>
</body>
</html>