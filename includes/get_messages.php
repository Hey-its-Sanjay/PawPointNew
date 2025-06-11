<?php
// Initialize the session
session_start();

// Set header as JSON
header('Content-Type: application/json');

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode([]);
    exit;
}

// Include functions file
require_once "functions.php";

// Get the other user's ID
$other_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

// Validate data
if ($other_id <= 0) {
    echo json_encode([]);
    exit;
}

// Determine current user type and ID
$current_id = 0;
$current_type = '';

if (isset($_SESSION["doctor_id"])) {
    $current_id = $_SESSION["doctor_id"];
    $current_type = 'doctor';
} elseif (isset($_SESSION["patient_id"])) {
    $current_id = $_SESSION["patient_id"];
    $current_type = 'patient';
} else {
    echo json_encode([]);
    exit;
}

// Get messages between the two users
$messages_sql = "SELECT * FROM chat_messages 
                WHERE (sender_id = ? AND receiver_id = ? AND sender_type = ?) 
                OR (sender_id = ? AND receiver_id = ? AND sender_type != ?)
                ORDER BY created_at ASC";

$messages = [];

if ($stmt = mysqli_prepare($conn, $messages_sql)) {
    mysqli_stmt_bind_param($stmt, "iisiss", $current_id, $other_id, $current_type, $other_id, $current_id, $current_type);
    
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        
        while ($row = mysqli_fetch_assoc($result)) {
            // Determine if the message is from the current user
            $row['is_mine'] = ($row['sender_id'] == $current_id && $row['sender_type'] == $current_type);
            
            // Ensure is_image is properly set in the response
            $row['is_image'] = (int)$row['is_image'];
            
            $messages[] = $row;
            
            // Mark message as read if it's to the current user and not read yet
            if (!$row['is_mine'] && !$row['is_read']) {
                $update_sql = "UPDATE chat_messages SET is_read = 1 WHERE id = ?";
                if ($update_stmt = mysqli_prepare($conn, $update_sql)) {
                    mysqli_stmt_bind_param($update_stmt, "i", $row['id']);
                    mysqli_stmt_execute($update_stmt);
                    mysqli_stmt_close($update_stmt);
                }
            }
        }
    }
    
    mysqli_stmt_close($stmt);
}

// Get user details
$user_details = [];

if ($current_type == 'doctor') {
    // Get patient details
    $user_sql = "SELECT id, name, pet_name, profile_picture FROM patients WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $user_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $other_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                $user_details = $row;
                $user_details['type'] = 'patient';
            }
        }
        
        mysqli_stmt_close($stmt);
    }
} else {
    // Get doctor details
    $user_sql = "SELECT id, name, speciality, profile_picture FROM doctors WHERE id = ? AND status = 'approved'";
    if ($stmt = mysqli_prepare($conn, $user_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $other_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                $user_details = $row;
                $user_details['type'] = 'doctor';
            }
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Return messages and user details
echo json_encode([
    'messages' => $messages,
    'user' => $user_details
]);

mysqli_close($conn);
?>