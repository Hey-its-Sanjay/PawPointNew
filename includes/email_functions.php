<?php
/**
 * Email functions for PawPoint
 */

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