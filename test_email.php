<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/phpmailer_setup.php';
require_once __DIR__ . '/includes/email_functions.php';

// Test if functions are defined
var_dump(function_exists('send_appointment_accept_email'));
var_dump(function_exists('send_appointment_reject_email'));

// Try to call the function
$result = send_appointment_accept_email(
    'test@example.com',
    'Test Patient',
    '2025-05-17',
    '10:00 AM',
    'Dr. Test'
);
var_dump($result);

// Test sending an email
$result = pawpoint_send_email(
    'Shresthasanjay087@gmail.com',  // Replace with your test email
    'Test User',
    'Test Email from PawPoint',
    '<h1>Test Email</h1><p>This is a test email from PawPoint system.</p>'
);

if ($result) {
    echo "Test email sent successfully!";
} else {
    echo "Failed to send test email. Check error logs for details.";
}
?>
