-- Add pet_age and pet_gender columns to patients table if they don't exist
ALTER TABLE patients 
ADD COLUMN IF NOT EXISTS pet_age INT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS pet_gender ENUM('Male', 'Female') DEFAULT NULL;
