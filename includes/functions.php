<?php
// Include database configuration
require_once "config.php";
require_once "phpmailer_setup.php";

// Database connection configuration
$db_host = "localhost";
$db_user = "root";
$db_password = "";
$db_name = "pawpoint";

// Create database connection
$conn = mysqli_connect($db_host, $db_user, $db_password, $db_name);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

/**
 * Sanitize user input to prevent XSS and SQL injection
 * 
 * @param string $input User input to be sanitized
 * @return string Sanitized input
 */
function sanitize_input($input) {
    global $conn;
    $input = trim($input);
    $input = stripslashes($input);
    $input = htmlspecialchars($input);
    if ($conn) {
        $input = mysqli_real_escape_string($conn, $input);
    }
    return $input;
}

/**
 * Generate a random token for email verification
 * 
 * @param int $length Length of the token to generate
 * @return string Generated token
 */
function generate_token($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Send verification email to user
 * 
 * @param string $to Recipient email
 * @param string $token Verification token
 * @return bool True if email sent successfully, false otherwise
 */
function send_verification_email($to, $token) {
    $subject = "Verify Your Email Address - PawPoint";
    
    // Use the IP address directly instead of hostname for local network access
    $ip_address = "192.168.1.88";
    $protocol = "http";
    
    // Create a verification link that works on the local network
    $verification_link = $protocol . "://" . $ip_address . "/Vetcare/pawpoint/patient/verify_email.php?email=" . urlencode($to) . "&token=" . $token;
    
    // Create HTML email message
    $message = "
    <html>
    <head>
        <title>Verify Your Email Address</title>
    </head>
    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
        <div style='max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;'>
            <h2 style='color: #4a7c59;'>Welcome to PawPoint!</h2>
            <p>Thank you for registering with PawPoint, your pet's healthcare companion.</p>
            <p>Please click the button below to verify your email address:</p>
            <div style='text-align: center; margin: 25px 0;'>
                <a href='{$verification_link}' style='display: inline-block; padding: 10px 20px; background-color: #4a7c59; color: white; text-decoration: none; border-radius: 5px;'>Verify Email Address</a>
            </div>
            <p>Or copy and paste this link into your browser:</p>
            <p style='word-break: break-all;'><a href='{$verification_link}'>{$verification_link}</a></p>
            <p>This link will expire in 24 hours.</p>
            <p>If you did not create an account with PawPoint, please ignore this email.</p>
            <hr style='border: none; border-top: 1px solid #ddd; margin: 20px 0;'>
            <p style='font-size: 12px; color: #777;'>This is an automated email. Please do not reply.</p>
        </div>
    </body>
    </html>
    ";
    
    // Try to use PHPMailer if available
    if (function_exists('send_email_with_phpmailer')) {
        return send_email_with_phpmailer($to, '', $subject, $message);
    }
    
    // Fallback to PHP mail function
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: PawPoint <Shresthasanjay087@gmail.com>" . "\r\n";
    
    return mail($to, $subject, $message, $headers);
}

/**
 * Check if user is logged in
 * 
 * @return bool True if user is logged in, false otherwise
 */
function is_logged_in() {
    return isset($_SESSION["patient_loggedin"]) && $_SESSION["patient_loggedin"] === true;
}

/**
 * Redirect to a URL
 * 
 * @param string $url URL to redirect to
 * @return void
 */
function redirect($url) {
    header("Location: " . $url);
    exit;
}

/**
 * Set a flash message to be displayed on the next page
 * 
 * @param string $type Message type (success, error, warning, info)
 * @param string $message Message content
 * @return void
 */
function set_flash_message($type, $message) {
    $_SESSION["flash_message"] = [
        "type" => $type,
        "message" => $message
    ];
}

/**
 * Display and clear flash message
 * 
 * @return void
 */
function display_flash_message() {
    if (isset($_SESSION["flash_message"])) {
        $type = $_SESSION["flash_message"]["type"];
        $message = $_SESSION["flash_message"]["message"];
        
        echo "<div class='alert alert-{$type}'>{$message}</div>";
        
        // Clear the flash message
        unset($_SESSION["flash_message"]);
    }
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

// Start session if not already started
function session_start_if_not_started() {
    if(session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

// Function to generate a verification token
function generate_verification_token() {
    return bin2hex(random_bytes(32));
}

// Function to set verification token for a patient
function set_verification_token($patient_id, $email) {
    global $conn;
    
    // Generate a token and set expiry time (24 hours from now)
    $token = generate_verification_token();
    $expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    $sql = "UPDATE patients SET verification_token = ?, token_expiry = ? WHERE id = ?";
    
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ssi", $token, $expiry, $patient_id);
        
        if(mysqli_stmt_execute($stmt)) {
            return $token;
        }
    }
    
    return false;
}

// Function to verify a patient's email
function verify_patient_email($token) {
    global $conn;
    
    $current_time = date('Y-m-d H:i:s');
    
    $sql = "SELECT id FROM patients WHERE verification_token = ? AND token_expiry > ?";
    
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $token, $current_time);
        
        if(mysqli_stmt_execute($stmt)) {
            mysqli_stmt_store_result($stmt);
            
            if(mysqli_stmt_num_rows($stmt) == 1) {
                mysqli_stmt_bind_result($stmt, $patient_id);
                if(mysqli_stmt_fetch($stmt)) {
                    // Update the patient record to mark as verified
                    $update_sql = "UPDATE patients SET email_verified = 1, verification_token = NULL, token_expiry = NULL WHERE id = ?";
                    
                    if($update_stmt = mysqli_prepare($conn, $update_sql)) {
                        mysqli_stmt_bind_param($update_stmt, "i", $patient_id);
                        
                        if(mysqli_stmt_execute($update_stmt)) {
                            mysqli_stmt_close($update_stmt);
                            return true;
                        }
                        
                        mysqli_stmt_close($update_stmt);
                    }
                }
            }
        }
        
        mysqli_stmt_close($stmt);
    }
    
    return false;
}

// Function to check if patient email is verified
function is_patient_email_verified($email) {
    global $conn;
    
    $sql = "SELECT email_verified FROM patients WHERE email = ?";
    
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $email);
        
        if(mysqli_stmt_execute($stmt)) {
            mysqli_stmt_bind_result($stmt, $email_verified);
            
            if(mysqli_stmt_fetch($stmt)) {
                mysqli_stmt_close($stmt);
                return $email_verified == 1;
            }
        }
        
        mysqli_stmt_close($stmt);
    }
    
    return false;
}

// Function to send verification email
function send_verification_email_local($to_email, $name, $token) {
    $subject = "Verify Your Email - PawPoint Veterinary Care";
    
    $verification_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/../patient/verify.php?token=" . $token;
    
    $message = "
    <html>
    <head>
        <title>Verify Your Email</title>
    </head>
    <body>
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background-color: #3498DB; color: white; padding: 20px; text-align: center;'>
                <h1>PawPoint Veterinary Care</h1>
            </div>
            <div style='padding: 20px; border: 1px solid #ddd; background-color: #f9f9f9;'>
                <h2>Hello, $name!</h2>
                <p>Thank you for registering with PawPoint Veterinary Care. To complete your registration, please verify your email address by clicking the button below:</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='$verification_link' style='background-color: #3498DB; color: white; padding: 12px 25px; text-decoration: none; border-radius: 4px; font-weight: bold;'>Verify Email</a>
                </div>
                <p>Or copy and paste the following link into your browser:</p>
                <p><a href='$verification_link'>$verification_link</a></p>
                <p>This link will expire in 24 hours.</p>
                <p>If you did not sign up for a PawPoint account, please ignore this email.</p>
                <p>Thank you,<br>The PawPoint Team</p>
            </div>
            <div style='text-align: center; padding: 10px; color: #777; font-size: 12px;'>
                <p>&copy; " . date('Y') . " PawPoint Veterinary Care. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Create emails directory if it doesn't exist
    $email_dir = dirname(__FILE__) . '/../emails/';
    if (!file_exists($email_dir)) {
        mkdir($email_dir, 0777, true);
    }
    
    // Save email to file
    $filename = $email_dir . 'verification_' . time() . '_' . md5($to_email) . '.html';
    file_put_contents($filename, $message);
    
    // Log message
    error_log("Verification email for $to_email saved to $filename");
    
    return true;
}

/**
 * Get a setting value from the database
 * 
 * @param string $key The setting key to retrieve
 * @param mixed $default Default value if setting doesn't exist
 * @param bool $public_only Only retrieve public settings
 * @return mixed The setting value or default if not found
 */
function get_setting($key, $default = '', $public_only = false) {
    global $conn;
    
    $sql = "SELECT setting_value FROM settings WHERE setting_key = ?";
    
    if($public_only) {
        $sql .= " AND is_public = 1";
    }
    
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $key);
        
        if(mysqli_stmt_execute($stmt)) {
            mysqli_stmt_store_result($stmt);
            
            if(mysqli_stmt_num_rows($stmt) == 1) {
                mysqli_stmt_bind_result($stmt, $value);
                mysqli_stmt_fetch($stmt);
                mysqli_stmt_close($stmt);
                return $value;
            }
        }
        
        mysqli_stmt_close($stmt);
    }
    
    return $default;
}

/**
 * Update a setting value in the database
 * 
 * @param string $key The setting key to update
 * @param string $value The new value
 * @return bool True if successful, false otherwise
 */
function update_setting($key, $value) {
    global $conn;
    
    $sql = "UPDATE settings SET setting_value = ? WHERE setting_key = ?";
    
    if($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $value, $key);
        
        if(mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return true;
        }
        
        mysqli_stmt_close($stmt);
    }
    
    return false;
}

/**
 * Get all settings from the database
 * 
 * @param bool $public_only Only retrieve public settings
 * @return array Array of settings
 */
function get_all_settings($public_only = false) {
    global $conn;
    
    $settings = [];
    $sql = "SELECT * FROM settings";
    
    if($public_only) {
        $sql .= " WHERE is_public = 1";
    }
    
    $sql .= " ORDER BY id ASC";
    
    $result = mysqli_query($conn, $sql);
    
    if($result) {
        while($row = mysqli_fetch_assoc($result)) {
            $settings[] = $row;
        }
    }
    
    return $settings;
}
?> 