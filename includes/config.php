<?php
// Database configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'pawpoint');

// Attempt to connect to MySQL database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);

// Check connection
if($conn === false){
    die("ERROR: Could not connect to database. " . mysqli_connect_error());
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS ".DB_NAME;
if(mysqli_query($conn, $sql)){
    // Connect to the database
    mysqli_select_db($conn, DB_NAME);
    
    // Create doctor table
    $doctor_table = "CREATE TABLE IF NOT EXISTS doctors (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        age INT NOT NULL,
        address TEXT NOT NULL,
        speciality VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    mysqli_query($conn, $doctor_table);
    
    // Create patient table
    $patient_table = "CREATE TABLE IF NOT EXISTS patients (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        age INT NOT NULL,
        address TEXT NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    mysqli_query($conn, $patient_table);
} else {
    echo "ERROR: Could not create database $database. " . mysqli_error($conn);
}
?> 