-- Create the pawpoint database if it doesn't exist
CREATE DATABASE IF NOT EXISTS pawpoint;

-- Use the pawpoint database
USE pawpoint;

-- Create the doctors table if it doesn't exist
CREATE TABLE IF NOT EXISTS doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    age INT NOT NULL,
    address TEXT NOT NULL,
    speciality VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create the patients table if it doesn't exist
CREATE TABLE IF NOT EXISTS patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    age INT NOT NULL,
    address TEXT NOT NULL,
    pet_name VARCHAR(100) NOT NULL,
    pet_type VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email_verified TINYINT(1) DEFAULT 0,
    verification_token VARCHAR(255) DEFAULT NULL,
    token_expiry DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create the admins table if it doesn't exist
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin if it doesn't exist
INSERT INTO admins (username, password) 
SELECT 'admin', '$2y$10$yUbMSZpRTM1/BH.qSXJAR.x7yECUhhX3RUCSpMJ.5iGvDKXl.ql3e' -- admin123
FROM dual 
WHERE NOT EXISTS (SELECT 1 FROM admins WHERE username = 'admin');

-- Create the appointments table if it doesn't exist
CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    patient_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    reason TEXT NOT NULL,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);

-- Add status column to doctors table if it doesn't exist
DELIMITER //
CREATE PROCEDURE AddStatusColumnIfNotExists()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = 'pawpoint' 
        AND TABLE_NAME = 'doctors' 
        AND COLUMN_NAME = 'status'
    ) THEN
        ALTER TABLE doctors ADD COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending';
        
        -- Update all existing doctors to approved status
        UPDATE doctors SET status = 'approved';
    END IF;
END //
DELIMITER ;

-- Add email verification columns to patients table if they don't exist
DELIMITER //
CREATE PROCEDURE AddEmailVerificationColumnsIfNotExist()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = 'pawpoint' 
        AND TABLE_NAME = 'patients' 
        AND COLUMN_NAME = 'email_verified'
    ) THEN
        ALTER TABLE patients 
        ADD COLUMN email_verified TINYINT(1) DEFAULT 0,
        ADD COLUMN verification_token VARCHAR(255) DEFAULT NULL,
        ADD COLUMN token_expiry DATETIME DEFAULT NULL;
        
        -- Update all existing patients to verified status
        UPDATE patients SET email_verified = 1;
    END IF;
END //
DELIMITER ;

-- Add pet columns to patients table if they don't exist
DELIMITER //
CREATE PROCEDURE AddPetColumnsIfNotExist()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = 'pawpoint' 
        AND TABLE_NAME = 'patients' 
        AND COLUMN_NAME = 'pet_name'
    ) THEN
        ALTER TABLE patients 
        ADD COLUMN pet_name VARCHAR(100) DEFAULT 'Not specified',
        ADD COLUMN pet_type VARCHAR(100) DEFAULT 'Not specified';
    END IF;
END //
DELIMITER ;

CALL AddStatusColumnIfNotExists();
CALL AddEmailVerificationColumnsIfNotExist();
CALL AddPetColumnsIfNotExist();
DROP PROCEDURE IF EXISTS AddStatusColumnIfNotExists;
DROP PROCEDURE IF EXISTS AddEmailVerificationColumnsIfNotExist;
DROP PROCEDURE IF EXISTS AddPetColumnsIfNotExist; 