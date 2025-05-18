<?php
error_log('Loading email_functions.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../vendor/PHPMailer/src/Exception.php';

/**
 * Helper to send email via PHPMailer SMTP
 */
function pawpoint_send_email($to, $to_name, $subject, $body_html) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Change to your SMTP server if needed
        $mail->SMTPAuth = true;
        $mail->Username = 'Shresthasanjay087@gmail.com'; // CHANGE THIS
        $mail->Password = 'vthg kkgm cqqs qtnc';    // CHANGE THIS (App Password for Gmail)
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('Shresthasanjay087@gmail.com', 'PawPoint');
        $mail->addAddress($to, $to_name);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body_html;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('PHPMailer Error: ' . $mail->ErrorInfo);
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
 * @return bool Whether the email was sent successfully
 */
function send_password_reset_email($recipient_email, $reset_link) {
    // Set email headers
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: PawPoint <noreply@pawpoint.com>" . "\r\n";
    
    // Email subject
    $subject = "Password Reset Request - PawPoint";
    
    // Email body
    $message = '
    <html>
    <head>
        <title>Reset Your Password</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
                border: 1px solid #ddd;
                border-radius: 5px;
            }
            .header {
                background-color: #4a7c59;
                color: white;
                padding: 15px;
                text-align: center;
                border-radius: 5px 5px 0 0;
            }
            .content {
                padding: 20px;
            }
            .button {
                display: inline-block;
                background-color: #4a7c59;
                color: white;
                padding: 12px 25px;
                text-decoration: none;
                border-radius: 4px;
                margin: 20px 0;
            }
            .footer {
                text-align: center;
                margin-top: 20px;
                font-size: 12px;
                color: #777;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>Password Reset Request</h2>
            </div>
            <div class="content">
                <p>Hello,</p>
                <p>We received a request to reset your password for your PawPoint account. Click the button below to reset your password. If you did not request a password reset, you can ignore this email and your password will remain unchanged.</p>
                <p style="text-align: center;">
                    <a href="' . $reset_link . '" class="button">Reset Password</a>
                </p>
                <p>Or copy and paste the following URL into your browser:</p>
                <p>' . $reset_link . '</p>
                <p>This password reset link is only valid for 1 hour.</p>
                <p>Thank you,<br>The PawPoint Team</p>
            </div>
            <div class="footer">
                <p>This is an automated email. Please do not reply to this message.</p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    // Send email
    return mail($recipient_email, $subject, $message, $headers);
}