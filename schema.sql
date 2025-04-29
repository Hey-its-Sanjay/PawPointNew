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
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user (password: admin123)
INSERT INTO `admins` (`username`, `password`, `email`) VALUES
('admin', '$2y$10$3Qq0M1VeXdKfRUiT8mMGTOzXOvBcXfXa.DSUwcmX0NSvvLzPSVhVu', 'admin@pawpoint.com');

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

-- Add settings table
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `setting_type` varchar(50) NOT NULL DEFAULT 'text',
  `category` varchar(50) NOT NULL DEFAULT 'general',
  `label` varchar(255) NOT NULL,
  `description` text,
  `options` text,
  `sort_order` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `category`, `label`, `description`, `options`, `sort_order`) VALUES
('site_name', 'PawPoint', 'text', 'general', 'Site Name', 'Name of your veterinary practice', NULL, 10),
('site_tagline', 'Caring for your pets', 'text', 'general', 'Site Tagline', 'A short description of your practice', NULL, 20),
('contact_email', 'contact@pawpoint.com', 'email', 'contact', 'Contact Email', 'Main contact email address', NULL, 10),
('contact_phone', '+1 (555) 123-4567', 'text', 'contact', 'Contact Phone', 'Main contact phone number', NULL, 20),
('contact_address', '123 Pet Street, Animal City, AC 12345', 'textarea', 'contact', 'Address', 'Physical address of your practice', NULL, 30),
('business_hours', '9 AM - 6 PM Monday to Friday, 10 AM - 4 PM Weekends', 'textarea', 'contact', 'Business Hours', 'Your regular operating hours', NULL, 40),
('emergency_phone', '+1 (555) 999-8888', 'text', 'contact', 'Emergency Phone', 'Phone number for emergencies', NULL, 50),
('theme_color', '#3498db', 'color', 'appearance', 'Primary Color', 'Main theme color of the website', NULL, 10),
('enable_online_bookings', 'yes', 'select', 'features', 'Enable Online Bookings', 'Allow patients to book appointments online', 'yes:Yes,no:No', 10),
('max_daily_appointments', '20', 'number', 'features', 'Max Daily Appointments', 'Maximum appointments per day', NULL, 20),
('appointment_interval', '30', 'select', 'features', 'Appointment Interval (minutes)', 'Time between appointment slots', '15:15 minutes,30:30 minutes,45:45 minutes,60:60 minutes', 30),
('auto_confirm_appointments', 'no', 'select', 'features', 'Auto-confirm Appointments', 'Automatically confirm new appointments', 'yes:Yes,no:No', 40),
('notification_email', 'notifications@pawpoint.com', 'email', 'notifications', 'Notification Email', 'Email address for sending notifications', NULL, 10),
('send_appointment_reminders', 'yes', 'select', 'notifications', 'Send Appointment Reminders', 'Send email reminders before appointments', 'yes:Yes,no:No', 20),
('reminder_hours_before', '24', 'number', 'notifications', 'Reminder Hours Before', 'Hours before appointment to send reminder', NULL, 30),
('sms_notifications', 'no', 'select', 'notifications', 'SMS Notifications', 'Send SMS notifications and reminders', 'yes:Yes,no:No', 40);

CALL AddStatusColumnIfNotExists();
CALL AddEmailVerificationColumnsIfNotExist();
CALL AddPetColumnsIfNotExist();
DROP PROCEDURE IF EXISTS AddStatusColumnIfNotExists;
DROP PROCEDURE IF EXISTS AddEmailVerificationColumnsIfNotExist;
DROP PROCEDURE IF EXISTS AddPetColumnsIfNotExist; 