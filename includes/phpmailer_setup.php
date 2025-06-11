<?php
// Define path to PHPMailer
define('PHPMAILER_DIR', dirname(__FILE__) . '/../vendor/PHPMailer/');

// Function to download PHPMailer if it does not exist
function download_phpmailer() {
    global $conn;
    
    // Create the vendor directory if it doesn't exist
    if (!file_exists(dirname(__FILE__) . '/../vendor')) {
        mkdir(dirname(__FILE__) . '/../vendor', 0777, true);
    }
    
    // Create PHPMailer directory if it doesn't exist
    if (!file_exists(PHPMAILER_DIR)) {
        mkdir(PHPMAILER_DIR, 0777, true);
    }
    
    // Files to download
    $files = [
        'src/PHPMailer.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/PHPMailer.php',
        'src/SMTP.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/SMTP.php',
        'src/Exception.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/Exception.php'
    ];
    
    // Download each file
    foreach ($files as $path => $url) {
        $dir = PHPMAILER_DIR . dirname($path);
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        $file_content = file_get_contents($url);
        if ($file_content !== false) {
            file_put_contents(PHPMAILER_DIR . $path, $file_content);
        } else {
            return false;
        }
    }
    
    return true;
}

// Load PHPMailer (downloading it first if necessary)
if (!file_exists(PHPMAILER_DIR . 'src/PHPMailer.php')) {
    $download_result = download_phpmailer();
    if (!$download_result) {
        die("Failed to download PHPMailer. Please check your internet connection or manually install PHPMailer.");
    }
}

// Include PHPMailer classes
require_once PHPMAILER_DIR . 'src/Exception.php';
require_once PHPMAILER_DIR . 'src/PHPMailer.php';
require_once PHPMAILER_DIR . 'src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Send email using PHPMailer
 * 
 * @param string $to_email Recipient's email address
 * @param string $to_name Recipient's name
 * @param string $subject Email subject
 * @param string $body Email body (HTML)
 * @param string $from_email Sender's email address
 * @param string $from_name Sender's name
 * @return bool True if email was sent successfully, false otherwise
 */
function send_email_with_phpmailer($to_email, $to_name, $subject, $body, $from_email = "Shresthasanjay087@gmail.com", $from_name = "PawPoint Veterinary Care") {
    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        // $mail->SMTPDebug = SMTP::DEBUG_SERVER;  // Enable verbose debug output (uncomment for debugging)
        $mail->isSMTP();                           // Send using SMTP
        $mail->Host       = 'smtp.gmail.com';      // Set the SMTP server to send through (change if not using Gmail)
        $mail->SMTPAuth   = true;                  // Enable SMTP authentication
        $mail->Username   = "Shresthasanjay087@gmail.com";           // SMTP username (your email)
        
        // ========================================================================
        // IMPORTANT: REPLACE 'your_app_password' WITH YOUR GOOGLE APP PASSWORD
        // Go to https://myaccount.google.com/security
        // Then "App passwords" > Select app: Mail > Select device: Other > Enter "PawPoint"
        // Copy the 16-character generated password and paste it below
        // ========================================================================
        $mail->Password   = 'eeym wlbu jvyu gwsc';   // SMTP password (use an app password for Gmail)
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
        $mail->Port       = 587;                   // TCP port to connect to (use 465 for SSL)
        
        // Recipients
        $mail->setFrom($from_email, $from_name);
        $mail->addAddress($to_email, $to_name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        
        // Generate plain-text version automatically
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\r\n", $body));
        
        // Send the email
        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log error message
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Function to get email configuration instructions
 */
function get_email_setup_instructions() {
    $instructions = "<h3>Email Configuration Instructions:</h3>";
    $instructions .= "<ol>";
    $instructions .= "<li>Edit the <code>send_email_with_phpmailer</code> function in <code>includes/phpmailer_setup.php</code> on line 56</li>";
    $instructions .= "<li>Change <code>your_app_password</code> to your Google App Password</li>";
    $instructions .= "<li>To generate an App Password:</li>";
    $instructions .= "<ul>";
    $instructions .= "<li>Go to your Google Account at <a href='https://myaccount.google.com/security' target='_blank'>https://myaccount.google.com/security</a></li>";
    $instructions .= "<li>Under 'Signing in to Google', select 'App passwords' (you may need to enable 2-Step Verification first)</li>"; 
    $instructions .= "<li>At the bottom, choose 'Select app' and pick 'Mail'</li>";
    $instructions .= "<li>Choose 'Other' for device and enter 'PawPoint'</li>";
    $instructions .= "<li>Click 'Generate'</li>";
    $instructions .= "<li>Copy the 16-character password and replace 'your_app_password' with it</li>";
    $instructions .= "</ul>";
    $instructions .= "<li>Save the file and your email functionality will work!</li>";
    $instructions .= "</ol>";
    $instructions .= "<p><strong>Important:</strong> The sender email is set to Shresthasanjay087@gmail.com - make sure you use YOUR App Password for this Gmail account.</p>";
    
    return $instructions;
}
?> 