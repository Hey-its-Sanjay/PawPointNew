<?php
// Include database configuration
require_once "config.php";

// Function to sanitize input data
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to validate email
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to check if user exists
function check_email_exists($email, $table) {
    global $conn;
    $sql = "SELECT id FROM $table WHERE email = ?";
    
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $param_email);
        $param_email = $email;
        
        if(mysqli_stmt_execute($stmt)) {
            mysqli_stmt_store_result($stmt);
            return mysqli_stmt_num_rows($stmt) > 0;
        }
    }
    
    return false;
}

// Function to hash password
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Function to verify password
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

// Function to redirect
function redirect($url) {
    header("location: $url");
    exit;
}

// Start session if not already started
function session_start_if_not_started() {
    if(session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}
?> 