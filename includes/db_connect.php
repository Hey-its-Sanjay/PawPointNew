<?php
// Database connection configuration
$hostname = "localhost";
$username = "root";  // Default XAMPP MySQL username
$password = "";      // Default XAMPP MySQL password
$database = "pawpoint";

// Create connection
$conn = new mysqli($hostname, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

// Disable strict mode for better compatibility
$conn->query("SET SESSION sql_mode = ''");

// Set timezone
date_default_timezone_set('Asia/Kathmandu');
?>
