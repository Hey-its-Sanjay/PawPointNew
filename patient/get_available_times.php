<?php
// Initialize the session
session_start();
 
// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || !isset($_SESSION["patient_id"])) {
    // Return empty JSON if not logged in
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

// Include config file
require_once "../includes/functions.php";

// Check if doctor_id and appointment_date parameters are set
if(!isset($_GET['doctor_id']) || !isset($_GET['appointment_date'])) {
    // Return empty JSON if parameters are missing
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

// Sanitize input
$doctor_id = sanitize_input($_GET['doctor_id']);
$appointment_date = sanitize_input($_GET['appointment_date']);

// Check if the doctor exists and is approved
$doctor_check_sql = "SELECT id FROM doctors WHERE id = ? AND status = 'approved'";
if($stmt = mysqli_prepare($conn, $doctor_check_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $doctor_id);
    
    if(mysqli_stmt_execute($stmt)) {
        mysqli_stmt_store_result($stmt);
        
        if(mysqli_stmt_num_rows($stmt) == 0) {
            // Return empty JSON if doctor does not exist or is not approved
            header('Content-Type: application/json');
            echo json_encode([]);
            exit;
        }
    }
    
    mysqli_stmt_close($stmt);
}

// Define all available appointment times
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

// Initialize available times with all times
$available_times = $all_available_times;

// Get booked appointments for the selected doctor and date
$sql = "SELECT appointment_time FROM appointments 
        WHERE doctor_id = ? AND appointment_date = ? AND status != 'cancelled'";

if($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "is", $doctor_id, $appointment_date);
    
    if(mysqli_stmt_execute($stmt)) {
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

// Return available times as JSON
header('Content-Type: application/json');
echo json_encode($available_times);
?> 