<?php
error_log('Loading email_functions.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once __DIR__ . '/../vendor/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../vendor/PHPMailer/src/Exception.php';

/**
 * Helper to send email via PHPMailer SMTP
 */
function pawpoint_send_email($to, $to_name, $subject, $body_html) {
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->SMTPDebug = SMTP::DEBUG_OFF;    // Enable verbose debug output if needed
        $mail->isSMTP();                       // Send using SMTP
        $mail->Host       = 'smtp.gmail.com';  // Set the SMTP server to send through
        $mail->SMTPAuth   = true;              // Enable SMTP authentication
        $mail->Username   = 'Shresthasanjay087@gmail.com'; // SMTP username
        $mail->Password   = 'vthg kkgm cqqs qtnc';        // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
        $mail->Port       = 587;               // TCP port to connect to

        // Recipients
        $mail->setFrom('Shresthasanjay087@gmail.com', 'PawPoint');
        $mail->addAddress($to, $to_name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body_html;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], ["\n", "\n\n"], $body_html));

        $mail->send();
        error_log("Email sent successfully to: " . $to);
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Send appointment acceptance email to patient
 */
function send_appointment_accept_email($patient_email, $patient_name, $appointment_date, $appointment_time, $doctor_name) {
    $subject = "Your Appointment is Confirmed - PawPoint";
    $body = '<html><head><title>Appointment Confirmed</title></head><body>' .
        '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">'
        . '<h2 style="color: #4a7c59;">Appointment Confirmed</h2>'
        . '<p>Dear ' . htmlspecialchars($patient_name) . ',</p>'
        . '<p>Your appointment request has been <b>accepted</b> by Dr. ' . htmlspecialchars($doctor_name) . '.</p>'
        . '<p><b>Date:</b> ' . htmlspecialchars($appointment_date) . '<br>'
        . '<b>Time:</b> ' . htmlspecialchars($appointment_time) . '</p>'
        . '<p>We look forward to seeing you and your pet!</p>'
        . '<p>Thank you,<br>PawPoint Team</p>'
        . '</div></body></html>';
    return pawpoint_send_email($patient_email, $patient_name, $subject, $body);
}

/**
 * Send appointment rejection email to patient
 */
function send_appointment_reject_email($patient_email, $patient_name, $appointment_date, $appointment_time, $doctor_name) {
    $subject = "Your Appointment Request was Rejected - PawPoint";
    $body = '<html><head><title>Appointment Rejected</title></head><body>' .
        '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">'
        . '<h2 style="color: #e74c3c;">Appointment Rejected</h2>'
        . '<p>Dear ' . htmlspecialchars($patient_name) . ',</p>'
        . '<p>We regret to inform you that your appointment request with Dr. ' . htmlspecialchars($doctor_name) . ' on ' . htmlspecialchars($appointment_date) . ' at ' . htmlspecialchars($appointment_time) . ' has been <b>rejected</b>.</p>'
        . '<p>You may book another appointment at a different time.</p>'
        . '<p>Thank you,<br>PawPoint Team</p>'
        . '</div></body></html>';
    return pawpoint_send_email($patient_email, $patient_name, $subject, $body);
}


/**
 * Send a password reset email to the user
 * 
 * @param string $recipient_email The email address to send to
 * @param string $reset_link The password reset link
 * @param string $user_type The type of user ('patient' or 'doctor')
 * @param string $name The name of the recipient
 * @return bool Whether the email was sent successfully
 */
/**
 * Send doctor approval email
 */
function send_doctor_approval_email($doctor_email, $doctor_name) {
    $subject = "Your PawPoint Doctor Account Has Been Approved";
    $body = '<html><head><title>Account Approved</title></head><body>' .
        '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">' .
        '<div style="background-color: #27AE60; color: white; padding: 20px; text-align: center;">' .
        '<h2>Account Approved!</h2>' .
        '</div>' .
        '<div style="padding: 20px; border: 1px solid #ddd;">' .
        '<p>Dear Dr. ' . htmlspecialchars($doctor_name) . ',</p>' .
        '<p>We are pleased to inform you that your PawPoint veterinary doctor account has been approved!</p>' .
        '<p>You can now log in to your account and start using the platform to manage your appointments and patients.</p>' .
        '<p style="text-align: center; margin: 30px 0;">' .
        '<a href="http://localhost/Vetcare/pawpoint/doctor/login.php" style="background-color: #27AE60; color: white; padding: 12px 25px; text-decoration: none; border-radius: 4px;">Log in to your account</a>' .
        '</p>' .
        '<p>If you have any questions or need assistance, please don\'t hesitate to contact our support team.</p>' .
        '<p>Thank you for choosing PawPoint!</p>' .
        '<p>Best regards,<br>The PawPoint Admin Team</p>' .
        '</div></div></body></html>';
    return pawpoint_send_email($doctor_email, $doctor_name, $subject, $body);
}

/**
 * Send doctor rejection email
 */
function send_doctor_rejection_email($doctor_email, $doctor_name) {
    $subject = "Your PawPoint Doctor Account Application Status";
    $body = '<html><head><title>Application Status Update</title></head><body>' .
        '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">' .
        '<div style="background-color: #E74C3C; color: white; padding: 20px; text-align: center;">' .
        '<h2>Application Status Update</h2>' .
        '</div>' .
        '<div style="padding: 20px; border: 1px solid #ddd;">' .
        '<p>Dear Dr. ' . htmlspecialchars($doctor_name) . ',</p>' .
        '<p>Thank you for your interest in joining PawPoint as a veterinary doctor.</p>' .
        '<p>After careful review of your application, we regret to inform you that we are unable to approve your account at this time.</p>' .
        '<p>If you have any questions or would like more information about this decision, please contact our admin team for clarification.</p>' .
        '<p>You may reapply in the future with updated information if you wish.</p>' .
        '<p>Best regards,<br>The PawPoint Admin Team</p>' .
        '</div></div></body></html>';
    return pawpoint_send_email($doctor_email, $doctor_name, $subject, $body);
}

function send_password_reset_email($recipient_email, $reset_link, $user_type, $name) {
    $subject = "Password Reset Request - PawPoint";
    
    $body = '<html><head><title>Reset Your Password</title></head><body>' .
        '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">' .
        '<h2 style="color: #4a7c59;">Password Reset Request</h2>' .
        '<p>Dear ' . htmlspecialchars($name) . ',</p>' .
        '<p>We received a request to reset your password for your ' . ucfirst($user_type) . ' account at PawPoint.</p>' .
        '<p>To reset your password, click the button below:</p>' .
        '<p style="text-align: center;">' .
        '<a href="' . $reset_link . '" style="display: inline-block; background-color: #4a7c59; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px;">Reset Password</a>' .
        '</p>' .
        '<p>If you did not request a password reset, please ignore this email or contact us if you have concerns.</p>' .
        '<p>This link will expire in 1 hour for security reasons.</p>' .
        '<p>Thank you,<br>PawPoint Team</p>' .
        '</div></body></html>';

    return pawpoint_send_email($recipient_email, $name, $subject, $body);
}