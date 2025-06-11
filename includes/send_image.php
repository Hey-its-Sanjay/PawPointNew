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

// Validate data
if ($receiver_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid receiver ID']);
    exit;
}

// Check if an image was uploaded
if (!isset($_FILES["image"]) || $_FILES["image"]["error"] != 0) {
    echo json_encode(['success' => false, 'message' => 'No image uploaded or upload error']);
    exit;
}

// Validate image file
$allowed_types = ["image/jpeg", "image/jpg", "image/png", "image/gif"];
$max_size = 5 * 1024 * 1024; // 5MB

// Validate file type
if (!in_array($_FILES["image"]["type"], $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Only JPG, JPEG, PNG, and GIF files are allowed']);
    exit;
}

// Validate file size
if ($_FILES["image"]["size"] > $max_size) {
    echo json_encode(['success' => false, 'message' => 'File size should not exceed 5MB']);
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

// Create uploads directory if it doesn't exist
$upload_dir = "../uploads/chat_images/";
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Generate a unique filename
$file_extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
$filename = "chat_" . time() . "_" . uniqid() . "." . $file_extension;
$target_file = $upload_dir . $filename;

// Upload the file
if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
    // Create a message with the image path
    $image_path = "uploads/chat_images/" . $filename;
    $message = "[IMAGE:" . $image_path . "]";
    
    // Insert message into database
    $insert_sql = "INSERT INTO chat_messages (sender_id, receiver_id, sender_type, message, is_image) VALUES (?, ?, ?, ?, 1)";
    if ($stmt = mysqli_prepare($conn, $insert_sql)) {
        mysqli_stmt_bind_param($stmt, "iiss", $sender_id, $receiver_id, $sender_type, $message);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['success' => true, 'message' => 'Image sent successfully', 'image_path' => $image_path]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send image']);
        }
        
        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
}

mysqli_close($conn);
?>