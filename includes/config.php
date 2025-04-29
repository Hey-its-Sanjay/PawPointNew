<?php
// Database credentials - modify these according to your setup
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); 
define('DB_PASSWORD', '');  // Default XAMPP password is empty
define('DB_NAME', 'pawpoint');

// Attempt to connect to MySQL database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if($conn === false){
    die("ERROR: Could not connect to database. " . mysqli_connect_error());
}

// Set charset to UTF8
mysqli_set_charset($conn, "utf8");

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
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        phone VARCHAR(20) DEFAULT NULL,
        profile_picture VARCHAR(255) DEFAULT 'default.jpg',
        bio TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    mysqli_query($conn, $doctor_table);
    
    // Create patient table
    $patient_table = "CREATE TABLE IF NOT EXISTS patients (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        age INT NOT NULL,
        address TEXT NOT NULL,
        pet_name VARCHAR(100) DEFAULT 'Not specified',
        pet_type VARCHAR(100) DEFAULT 'Not specified',
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        phone VARCHAR(20) DEFAULT NULL,
        profile_picture VARCHAR(255) DEFAULT 'default.jpg',
        pet_details TEXT DEFAULT NULL,
        email_verified TINYINT(1) DEFAULT 0,
        verification_token VARCHAR(255) DEFAULT NULL,
        token_expiry DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    mysqli_query($conn, $patient_table);
    
    // Check if profile_picture column exists in doctors table
    $check_doctor_profile = "SHOW COLUMNS FROM doctors LIKE 'profile_picture'";
    $result = mysqli_query($conn, $check_doctor_profile);
    
    if(mysqli_num_rows($result) == 0) {
        // Add profile_picture column to doctors table
        $add_doctor_profile = "ALTER TABLE doctors 
                              ADD COLUMN profile_picture VARCHAR(255) DEFAULT 'default.jpg',
                              ADD COLUMN phone VARCHAR(20) DEFAULT NULL,
                              ADD COLUMN bio TEXT DEFAULT NULL";
        mysqli_query($conn, $add_doctor_profile);
    }
    
    // Check if profile_picture column exists in patients table
    $check_patient_profile = "SHOW COLUMNS FROM patients LIKE 'profile_picture'";
    $result = mysqli_query($conn, $check_patient_profile);
    
    if(mysqli_num_rows($result) == 0) {
        // Add profile_picture column to patients table
        $add_patient_profile = "ALTER TABLE patients 
                               ADD COLUMN profile_picture VARCHAR(255) DEFAULT 'default.jpg',
                               ADD COLUMN phone VARCHAR(20) DEFAULT NULL,
                               ADD COLUMN pet_details TEXT DEFAULT NULL";
        mysqli_query($conn, $add_patient_profile);
    }
    
    // Check if pet columns exist in patients table
    $check_pet_columns = "SHOW COLUMNS FROM patients LIKE 'pet_name'";
    $result = mysqli_query($conn, $check_pet_columns);
    
    if(mysqli_num_rows($result) == 0) {
        // Add pet_name and pet_type columns
        $add_pet_columns = "ALTER TABLE patients 
                          ADD COLUMN pet_name VARCHAR(100) DEFAULT 'Not specified',
                          ADD COLUMN pet_type VARCHAR(100) DEFAULT 'Not specified'";
        mysqli_query($conn, $add_pet_columns);
    }
    
    // Create admin table
    $admin_table = "CREATE TABLE IF NOT EXISTS admins (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    mysqli_query($conn, $admin_table);
    
    // Create settings table for system configuration
    $settings_table = "CREATE TABLE IF NOT EXISTS settings (
        id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT NOT NULL,
        setting_description TEXT,
        setting_type ENUM('text', 'number', 'email', 'select', 'textarea', 'color', 'checkbox') DEFAULT 'text',
        setting_options TEXT,
        is_public TINYINT(1) DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    mysqli_query($conn, $settings_table);
    
    // Check if default settings exist
    $check_settings = "SELECT COUNT(*) as count FROM settings";
    $result = mysqli_query($conn, $check_settings);
    $row = mysqli_fetch_assoc($result);
    
    // Insert default settings if none exist
    if($row['count'] == 0) {
        $default_settings = [
            [
                'key' => 'site_name',
                'value' => 'PawPoint',
                'description' => 'The name of the veterinary care system',
                'type' => 'text',
                'options' => '',
                'is_public' => 1
            ],
            [
                'key' => 'site_description',
                'value' => 'Your Pet\'s Healthcare Companion',
                'description' => 'Short description/tagline for the site',
                'type' => 'text',
                'options' => '',
                'is_public' => 1
            ],
            [
                'key' => 'contact_email',
                'value' => 'contact@pawpoint.com',
                'description' => 'Primary contact email address',
                'type' => 'email',
                'options' => '',
                'is_public' => 1
            ],
            [
                'key' => 'contact_phone',
                'value' => '(123) 456-7890',
                'description' => 'Primary contact phone number',
                'type' => 'text',
                'options' => '',
                'is_public' => 1
            ],
            [
                'key' => 'address',
                'value' => '123 Pet Street, Animal City',
                'description' => 'Physical address of the clinic',
                'type' => 'textarea',
                'options' => '',
                'is_public' => 1
            ],
            [
                'key' => 'appointment_interval',
                'value' => '30',
                'description' => 'Time interval in minutes between appointments',
                'type' => 'select',
                'options' => '15,30,45,60',
                'is_public' => 0
            ],
            [
                'key' => 'primary_color',
                'value' => '#4a7c59',
                'description' => 'Primary color theme for the site',
                'type' => 'color',
                'options' => '',
                'is_public' => 0
            ],
            [
                'key' => 'enable_doctor_registration',
                'value' => '1',
                'description' => 'Allow doctors to register through the site',
                'type' => 'checkbox',
                'options' => '',
                'is_public' => 0
            ],
            [
                'key' => 'enable_patient_registration',
                'value' => '1',
                'description' => 'Allow patients to register through the site',
                'type' => 'checkbox',
                'options' => '',
                'is_public' => 0
            ]
        ];
        
        foreach($default_settings as $setting) {
            $insert_setting = "INSERT INTO settings (setting_key, setting_value, setting_description, setting_type, setting_options, is_public) 
                              VALUES (?, ?, ?, ?, ?, ?)";
            if($stmt = mysqli_prepare($conn, $insert_setting)) {
                mysqli_stmt_bind_param($stmt, "sssssi", 
                    $setting['key'], 
                    $setting['value'], 
                    $setting['description'], 
                    $setting['type'], 
                    $setting['options'], 
                    $setting['is_public']
                );
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }
    }
    
    // Insert default admin if not exists
    $check_admin = "SELECT id FROM admins LIMIT 1";
    $result = mysqli_query($conn, $check_admin);
    
    if(mysqli_num_rows($result) == 0) {
        // No admin exists, create default admin
        $default_username = "admin";
        $default_email = "admin@pawpoint.com";
        $default_password = password_hash("admin123", PASSWORD_DEFAULT);
        
        $insert_admin = "INSERT INTO admins (username, email, password) VALUES ('$default_username', '$default_email', '$default_password')";
        mysqli_query($conn, $insert_admin);
    }
} else {
    echo "ERROR: Could not create database $database. " . mysqli_error($conn);
}
?> 