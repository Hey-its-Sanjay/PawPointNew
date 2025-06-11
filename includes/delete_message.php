<?php
// Initialize the session
session_start();

// Set header as JSON
header('Content-Type: application/json');

// Check if the user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Include functions file
require_once "functions.php";

// Check if it's a POST request
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get POST data
$message_id = isset($_POST['message_id']) ? intval($_POST['message_id']) : 0;

// Validate data
if ($message_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid message ID']);
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
    echo json_encode(['success' => false, 'message' => 'Invalid user type']);
    exit;
}

// Check if the message exists and belongs to the current user (as sender)
$check_sql = "SELECT id FROM chat_messages WHERE id = ? AND sender_id = ? AND sender_type = ?";
$can_delete = false;

if ($stmt = mysqli_prepare($conn, $check_sql)) {
    mysqli_stmt_bind_param($stmt, "iis", $message_id, $current_id, $current_type);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $can_delete = mysqli_stmt_num_rows($stmt) > 0;
    mysqli_stmt_close($stmt);
}

if (!$can_delete) {
    echo json_encode(['success' => false, 'message' => 'You cannot delete this message']);
    exit;
}

// Delete the message
$delete_sql = "DELETE FROM chat_messages WHERE id = ?";
if ($stmt = mysqli_prepare($conn, $delete_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $message_id);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Message deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete message']);
    }
    
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

mysqli_close($conn);
?>