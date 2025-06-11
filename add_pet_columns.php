<?php
// Include database configuration
require_once "includes/config.php";

echo "<h1>Database Update Script</h1>";

// Check if pet columns exist in patients table
$check_pet_columns = "SHOW COLUMNS FROM patients LIKE 'pet_name'";
$result = mysqli_query($conn, $check_pet_columns);

if(mysqli_num_rows($result) == 0) {
    // Add pet_name and pet_type columns
    $add_pet_columns = "ALTER TABLE patients 
                      ADD COLUMN pet_name VARCHAR(100) DEFAULT 'Not specified',
                      ADD COLUMN pet_type VARCHAR(100) DEFAULT 'Not specified'";
    
    if(mysqli_query($conn, $add_pet_columns)) {
        echo "<p style='color: green;'>Successfully added pet_name and pet_type columns to the patients table!</p>";
    } else {
        echo "<p style='color: red;'>Error adding columns: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p style='color: blue;'>The pet_name column already exists in the patients table.</p>";
}

// Check for email verification columns
$check_email_verified = "SHOW COLUMNS FROM patients LIKE 'email_verified'";
$result = mysqli_query($conn, $check_email_verified);

if(mysqli_num_rows($result) == 0) {
    // Add email verification columns
    $add_verification_columns = "ALTER TABLE patients 
                              ADD COLUMN email_verified TINYINT(1) DEFAULT 1,
                              ADD COLUMN verification_token VARCHAR(255) DEFAULT NULL,
                              ADD COLUMN token_expiry DATETIME DEFAULT NULL";
    
    if(mysqli_query($conn, $add_verification_columns)) {
        echo "<p style='color: green;'>Successfully added email verification columns to the patients table!</p>";
    } else {
        echo "<p style='color: red;'>Error adding verification columns: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p style='color: blue;'>The email_verified column already exists in the patients table.</p>";
}

echo "<p>Database update complete. <a href='index.php'>Go back to homepage</a></p>";
?> 