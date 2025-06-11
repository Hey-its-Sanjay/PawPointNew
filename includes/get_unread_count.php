<?php
// Initialize the session
session_start();

// Set header as JSON
header('Content-Type: application/json');

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['unread_count' => 0, 'chats' => []]);
    exit;
}

// Include functions file
require_once "functions.php";

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
    echo json_encode(['unread_count' => 0, 'chats' => []]);
    exit;
}

// Get total unread count
$count_sql = "SELECT COUNT(*) as unread_count FROM chat_messages 
             WHERE receiver_id = ? AND (sender_type != ? OR sender_id != ?) AND is_read = 0";

$unread_count = 0;

if ($stmt = mysqli_prepare($conn, $count_sql)) {
    mysqli_stmt_bind_param($stmt, "isi", $current_id, $current_type, $current_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $unread_count = $row['unread_count'];
        }
    }
    
    mysqli_stmt_close($stmt);
}

// Get recent chats with unread counts
$chats = [];

if ($current_type == 'doctor') {
    // Get patients who have chatted with this doctor
    $chats_sql = "SELECT p.id, p.name, p.profile_picture, 
                 (SELECT COUNT(*) FROM chat_messages WHERE sender_id = p.id AND receiver_id = ? AND sender_type = 'patient' AND is_read = 0) as unread_count,
                 (SELECT created_at FROM chat_messages 
                  WHERE (sender_id = p.id AND receiver_id = ? AND sender_type = 'patient') 
                  OR (sender_id = ? AND receiver_id = p.id AND sender_type = 'doctor') 
                  ORDER BY created_at DESC LIMIT 1) as last_message_time
                 FROM patients p
                 WHERE p.id IN (
                    SELECT DISTINCT 
                        CASE 
                            WHEN sender_id = ? AND sender_type = 'doctor' THEN receiver_id
                            WHEN receiver_id = ? AND sender_type = 'patient' THEN sender_id
                        END
                    FROM chat_messages
                    WHERE (sender_id = ? AND sender_type = 'doctor') OR (receiver_id = ? AND sender_type = 'patient')
                 )
                 ORDER BY last_message_time DESC";
    
    if ($stmt = mysqli_prepare($conn, $chats_sql)) {
        mysqli_stmt_bind_param($stmt, "iiiiiii", $current_id, $current_id, $current_id, $current_id, $current_id, $current_id, $current_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $chats[] = $row;
            }
        }
        
        mysqli_stmt_close($stmt);
    }
} else {
    // Get doctors who have chatted with this patient
    $chats_sql = "SELECT d.id, d.name, d.speciality, d.profile_picture, 
                 (SELECT COUNT(*) FROM chat_messages WHERE sender_id = d.id AND receiver_id = ? AND sender_type = 'doctor' AND is_read = 0) as unread_count,
                 (SELECT created_at FROM chat_messages 
                  WHERE (sender_id = d.id AND receiver_id = ? AND sender_type = 'doctor') 
                  OR (sender_id = ? AND receiver_id = d.id AND sender_type = 'patient') 
                  ORDER BY created_at DESC LIMIT 1) as last_message_time
                 FROM doctors d
                 WHERE d.status = 'approved' AND d.id IN (
                    SELECT DISTINCT 
                        CASE 
                            WHEN sender_id = ? AND sender_type = 'patient' THEN receiver_id
                            WHEN receiver_id = ? AND sender_type = 'doctor' THEN sender_id
                        END
                    FROM chat_messages
                    WHERE (sender_id = ? AND sender_type = 'patient') OR (receiver_id = ? AND sender_type = 'doctor')
                 )
                 ORDER BY last_message_time DESC";
    
    if ($stmt = mysqli_prepare($conn, $chats_sql)) {
        mysqli_stmt_bind_param($stmt, "iiiiiii", $current_id, $current_id, $current_id, $current_id, $current_id, $current_id, $current_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($result)) {
                $chats[] = $row;
            }
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Return unread count and chats
echo json_encode([
    'unread_count' => $unread_count,
    'chats' => $chats
]);

mysqli_close($conn);
?>