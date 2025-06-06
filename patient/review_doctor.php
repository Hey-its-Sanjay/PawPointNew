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

// Initialize variables
$appointment_id = $doctor_id = $doctor_name = $appointment_date = null;
$error_message = "";

// Check if valid appointment ID is provided
if (!isset($_GET['appointment_id']) || empty($_GET['appointment_id'])) {
    header("location: appointments.php");
    exit;
}

$appointment_id = intval($_GET['appointment_id']);

// Fetch appointment and doctor details
$sql = "SELECT a.*, d.name as doctor_name, d.id as doctor_id 
        FROM appointments a 
        JOIN doctors d ON a.doctor_id = d.id 
        WHERE a.id = ? AND a.patient_id = ? AND a.status = 'completed'";

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "ii", $appointment_id, $_SESSION["patient_id"]);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $doctor_id = $row['doctor_id'];
        $doctor_name = $row['doctor_name'];
        $appointment_date = date('F d, Y', strtotime($row['appointment_date']));
    } else {
        header("location: appointments.php");
        exit;
    }
    mysqli_stmt_close($stmt);
}

// Check if review already exists
$sql = "SELECT id FROM reviews WHERE appointment_id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $appointment_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    
    if (mysqli_stmt_num_rows($stmt) > 0) {
        header("location: appointments.php");
        exit;
    }
    mysqli_stmt_close($stmt);
}

// Include header
include('header.php');
?>

<div class="container">
    <h2>Review Your Visit</h2>
    <div class="review-form-container">
        <div class="doctor-info">
            <h3>Dr. <?php echo htmlspecialchars($doctor_name); ?></h3>
            <p>Appointment Date: <?php echo htmlspecialchars($appointment_date); ?></p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="appointments.php" method="post" class="review-form">
            <input type="hidden" name="appointment_id" value="<?php echo $appointment_id; ?>">
            <input type="hidden" name="doctor_id" value="<?php echo $doctor_id; ?>">
            
            <div class="rating-container">
                <label>Your Rating:</label>
                <div class="star-rating">
                    <?php for($i = 5; $i >= 1; $i--): ?>
                    <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
                    <label for="star<?php echo $i; ?>" title="<?php echo $i; ?> stars">★</label>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="review_text">Your Review:</label>
                <textarea name="review_text" id="review_text" rows="5" required 
                    placeholder="Share your experience with Dr. <?php echo htmlspecialchars($doctor_name); ?>"></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" name="submit_review" class="btn btn-primary">Submit Review</button>
                <a href="appointments.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<style>
.review-form-container {
    max-width: 600px;
    margin: 2rem auto;
    padding: 2rem;
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.doctor-info {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
}

.star-rating {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    margin: 1rem 0;
}

.star-rating input {
    display: none;
}

.star-rating label {
    font-size: 2rem;
    color: #ddd;
    cursor: pointer;
    padding: 0 0.2rem;
}

.star-rating input:checked ~ label,
.star-rating label:hover,
.star-rating label:hover ~ label {
    color: #ffd700;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
}

.form-group textarea {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
}

.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.btn-primary {
    background-color: #4a7c59;
    color: white;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
    text-decoration: none;
}
</style>

<?php include('../includes/footer.php'); ?>
