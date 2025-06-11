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
$receiver_id = isset($_POST['receiver_id']) ? intval($_POST['receiver_id']) : 0;
$message = isset($_POST['message']) ? sanitize_input($_POST['message']) : '';

// Validate data
if ($receiver_id <= 0 || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Invalid data provided']);
    exit;
}

// Determine sender type and ID
$sender_id = 0;
$sender_type = '';
$receiver_exists = false;

if (isset($_SESSION["doctor_id"])) {
    $sender_id = $_SESSION["doctor_id"];
    $sender_type = 'doctor';
    
    // Check if patient exists
    $check_sql = "SELECT id FROM patients WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $check_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $receiver_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $receiver_exists = mysqli_stmt_num_rows($stmt) > 0;
        mysqli_stmt_close($stmt);
    }
} elseif (isset($_SESSION["patient_id"])) {
    $sender_id = $_SESSION["patient_id"];
    $sender_type = 'patient';
    
    // Check if doctor exists and is approved
    $check_sql = "SELECT id FROM doctors WHERE id = ? AND status = 'approved'";
    if ($stmt = mysqli_prepare($conn, $check_sql)) {
        mysqli_stmt_bind_param($stmt, "i", $receiver_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        $receiver_exists = mysqli_stmt_num_rows($stmt) > 0;
        mysqli_stmt_close($stmt);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid user type']);
    exit;
}

// Check if receiver exists
if (!$receiver_exists) {
    echo json_encode(['success' => false, 'message' => 'Receiver not found']);
    exit;
}

// Insert message into database
$insert_sql = "INSERT INTO chat_messages (sender_id, receiver_id, sender_type, message) VALUES (?, ?, ?, ?)";
if ($stmt = mysqli_prepare($conn, $insert_sql)) {
    mysqli_stmt_bind_param($stmt, "iiss", $sender_id, $receiver_id, $sender_type, $message);
    
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send message']);
    }
    
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

mysqli_close($conn);
?>