<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["patient_id"])){
    header("location: login.php");
    exit;
}

// Include config file
require_once "../includes/functions.php";

// Define variables and initialize with empty values
$doctor_id = $appointment_date = $appointment_time = $reason = "";
$doctor_id_err = $appointment_date_err = $appointment_time_err = $reason_err = "";
$success_message = "";

// Check if a doctor_id was passed in the URL (from find_doctor.php)
if(isset($_GET['doctor_id']) && !empty($_GET['doctor_id'])) {
    $doctor_id = trim($_GET['doctor_id']);
    // Validate if the doctor exists and is approved
    $check_doctor_sql = "SELECT id FROM doctors WHERE id = ? AND status = 'approved'";
    if($check_stmt = mysqli_prepare($conn, $check_doctor_sql)) {
        mysqli_stmt_bind_param($check_stmt, "i", $doctor_id);
        if(mysqli_stmt_execute($check_stmt)) {
            mysqli_stmt_store_result($check_stmt);
            if(mysqli_stmt_num_rows($check_stmt) == 0) {
                // Invalid doctor ID, reset it
                $doctor_id = "";
            }
        }
        mysqli_stmt_close($check_stmt);
    }
}

// Define available appointment times (10AM to 4PM in 30 min intervals, excluding 1PM for lunch)
$all_available_times = [
    "10:00:00" => "10:00 AM",
    "10:30:00" => "10:30 AM",
    "11:00:00" => "11:00 AM",
    "11:30:00" => "11:30 AM",
    "12:00:00" => "12:00 PM",
    "12:30:00" => "12:30 PM",
    "14:00:00" => "2:00 PM",
    "14:30:00" => "2:30 PM",
    "15:00:00" => "3:00 PM",
    "15:30:00" => "3:30 PM",
    "16:00:00" => "4:00 PM",
    "16:30:00" => "4:30 PM"
];

// Get list of approved doctors
$doctors = [];
$sql = "SELECT id, name, speciality FROM doctors WHERE status = 'approved' ORDER BY name ASC";
$result = mysqli_query($conn, $sql);

if($result) {
    while($row = mysqli_fetch_assoc($result)) {
        $doctors[] = $row;
    }
}

// Function to get available time slots for a specific doctor and date
function getAvailableTimeSlots($doctor_id, $appointment_date, $all_available_times, $conn) {
    $available_times = $all_available_times;
    
    // Get booked appointments for the selected doctor and date
    $sql = "SELECT appointment_time FROM appointments 
            WHERE doctor_id = ? AND appointment_date = ? AND status != 'cancelled'";
    
    if($stmt = mysqli_prepare($conn, $sql)){
        mysqli_stmt_bind_param($stmt, "is", $doctor_id, $appointment_date);
        
        if(mysqli_stmt_execute($stmt)){
            $result = mysqli_stmt_get_result($stmt);
            
            // Remove only the exact booked time slots
            while($row = mysqli_fetch_assoc($result)) {
                $booked_time = $row['appointment_time'];
                
                // Remove the booked time slot
                if(isset($available_times[$booked_time])) {
                    unset($available_times[$booked_time]);
                }
            }
        }
        
        mysqli_stmt_close($stmt);
    }
    
    return $available_times;
}

// Processing form data when form is submitted
if($_SERVER["REQUEST_METHOD"] == "POST"){
    
    // Validate doctor
    if(empty(trim($_POST["doctor_id"]))){
        $doctor_id_err = "Please select a doctor.";
    } else {
        $doctor_id = trim($_POST["doctor_id"]);
    }
    
    // Validate appointment date
    if(empty(trim($_POST["appointment_date"]))){
        $appointment_date_err = "Please select an appointment date.";
    } else {
        $appointment_date = trim($_POST["appointment_date"]);
        
        // Check if date is in the future
        $current_date = date("Y-m-d");
        if($appointment_date < $current_date) {
            $appointment_date_err = "Appointment date must be a future date.";
        }
    }
    
    // Validate appointment time
    if(empty(trim($_POST["appointment_time"]))){
        $appointment_time_err = "Please select an appointment time.";
    } else {
        $appointment_time = trim($_POST["appointment_time"]);
        
        // Get available time slots and check if selected time is valid
        if(!empty($doctor_id) && !empty($appointment_date)) {
            $available_times = getAvailableTimeSlots($doctor_id, $appointment_date, $all_available_times, $conn);
            
            if(!array_key_exists($appointment_time, $available_times)) {
                $appointment_time_err = "The selected time is no longer available. Please choose another time.";
            }
        } else {
            // If doctor or date is not selected, use all available times for validation
            if(!array_key_exists($appointment_time, $all_available_times)) {
                $appointment_time_err = "Please select a valid appointment time.";
            }
        }
    }
    
    // Validate reason
    if(empty(trim($_POST["reason"]))){
        $reason_err = "Please enter the reason for your appointment.";
    } else {
        $reason = trim($_POST["reason"]);
    }
    
    // Check input errors before inserting into database
    if(empty($doctor_id_err) && empty($appointment_date_err) && empty($appointment_time_err) && empty($reason_err)){
        
        // Double-check if the time slot is still available
        $available_times = getAvailableTimeSlots($doctor_id, $appointment_date, $all_available_times, $conn);
        
        if(array_key_exists($appointment_time, $available_times)) {
            // Prepare an insert statement
            $sql = "INSERT INTO appointments (doctor_id, patient_id, appointment_date, appointment_time, reason, status) VALUES (?, ?, ?, ?, ?, 'pending')";
            
            if($stmt = mysqli_prepare($conn, $sql)){
                // Bind variables to the prepared statement as parameters
                mysqli_stmt_bind_param($stmt, "iisss", $doctor_id, $_SESSION["patient_id"], $appointment_date, $appointment_time, $reason);
                
                // Attempt to execute the prepared statement
                if(mysqli_stmt_execute($stmt)){
                    // Set success message
                    $success_message = "Your appointment has been booked successfully. Please wait for confirmation from the doctor.";
                    
                    // Clear form inputs
                    $doctor_id = $appointment_date = $appointment_time = $reason = "";
                } else{
                    echo "Oops! Something went wrong. Please try again later.";
                }
                
                // Close statement
                mysqli_stmt_close($stmt);
            }
        } else {
            $appointment_time_err = "This time slot is no longer available. Please select a different time.";
        }
    }
}

// Initialize available_times for the selected doctor and date
$available_times = $all_available_times;
if(!empty($doctor_id) && !empty($appointment_date)) {
    $available_times = getAvailableTimeSlots($doctor_id, $appointment_date, $all_available_times, $conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book an Appointment - PawPoint</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .form-group {
            margin-bottom: 20px;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        .btn-book {
            background-color: #4a7c59;
        }
        .btn-book:hover {
            background-color: #3e6b4a;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
    </style>
    <script>
        // Function to update available time slots when doctor or date changes
        function updateAvailableTimeSlots() {
            const doctorId = document.getElementById("doctor_id").value;
            const appointmentDate = document.getElementById("appointment_date").value;
            const timeSelect = document.getElementById("appointment_time");
            
            if (doctorId && appointmentDate) {
                // Clear the current options
                timeSelect.innerHTML = '<option value="">-- Select a Time --</option>';
                
                // Disable the select while loading
                timeSelect.disabled = true;
                
                // Make an AJAX request to get available time slots
                fetch(`get_available_times.php?doctor_id=${doctorId}&appointment_date=${appointmentDate}`)
                    .then(response => response.json())
                    .then(data => {
                        // Re-enable the select
                        timeSelect.disabled = false;
                        
                        // Add the available time slots
                        if (data.length === 0) {
                            const noOption = document.createElement("option");
                            noOption.text = "No available times";
                            noOption.disabled = true;
                            timeSelect.add(noOption);
                        } else {
                            for (const time in data) {
                                const option = document.createElement("option");
                                option.value = time;
                                option.text = data[time];
                                timeSelect.add(option);
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        timeSelect.disabled = false;
                    });
            }
        }
    </script>
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
            <li><a href="find_doctor.php">Find Doctor</a></li>
            <li><a href="appointments.php">My Appointments</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
    
    <div class="container">
        <h2>Book an Appointment</h2>
        
        <?php 
            if(!empty($success_message)) {
                echo '<div class="alert alert-success">' . $success_message . '</div>';
            }
        ?>
        
        <div class="form-container">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label>Select Doctor</label>
                    <select id="doctor_id" name="doctor_id" class="form-control <?php echo (!empty($doctor_id_err)) ? 'is-invalid' : ''; ?>" onchange="updateAvailableTimeSlots()">
                        <option value="">-- Select a Doctor --</option>
                        <?php foreach($doctors as $doctor): ?>
                            <option value="<?php echo $doctor['id']; ?>" <?php echo ($doctor_id == $doctor['id']) ? 'selected' : ''; ?>>
                                Dr. <?php echo htmlspecialchars($doctor['name']); ?> - <?php echo htmlspecialchars($doctor['speciality']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="invalid-feedback"><?php echo $doctor_id_err; ?></span>
                </div>
                
                <div class="form-group">
                    <label>Appointment Date</label>
                    <input type="date" id="appointment_date" name="appointment_date" class="form-control <?php echo (!empty($appointment_date_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $appointment_date; ?>" min="<?php echo date('Y-m-d'); ?>" onchange="updateAvailableTimeSlots()">
                    <span class="invalid-feedback"><?php echo $appointment_date_err; ?></span>
                </div>
                
                <div class="form-group">
                    <label>Appointment Time</label>
                    <select id="appointment_time" name="appointment_time" class="form-control <?php echo (!empty($appointment_time_err)) ? 'is-invalid' : ''; ?>">
                        <option value="">-- Select a Time --</option>
                        <?php foreach($available_times as $time_value => $time_label): ?>
                            <option value="<?php echo $time_value; ?>" <?php echo ($appointment_time == $time_value) ? 'selected' : ''; ?>>
                                <?php echo $time_label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="invalid-feedback"><?php echo $appointment_time_err; ?></span>
                    <small style="display: block; margin-top: 5px; color: #666;">* Note: 1:00 PM is lunch hour and not available for appointments.</small>
                    <small style="display: block; margin-top: 5px; color: #666;">* Available time slots are shown after selecting both doctor and date.</small>
                </div>
                
                <div class="form-group">
                    <label>Reason for Appointment</label>
                    <textarea name="reason" class="form-control <?php echo (!empty($reason_err)) ? 'is-invalid' : ''; ?>" rows="4"><?php echo $reason; ?></textarea>
                    <span class="invalid-feedback"><?php echo $reason_err; ?></span>
                </div>
                
                <div class="form-group">
                    <input type="submit" class="btn btn-book" value="Book Appointment">
                    <a href="dashboard.php" class="btn">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    
    <footer>
        <p>&copy; <?php echo date("Y"); ?> PawPoint. All rights reserved.</p>
    </footer>
</body>
</html> 