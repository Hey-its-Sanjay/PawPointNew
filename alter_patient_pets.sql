-- Make pet_name and pet_type nullable in patients table
ALTER TABLE patients MODIFY COLUMN pet_name VARCHAR(100) NULL;
ALTER TABLE patients MODIFY COLUMN pet_type VARCHAR(100) NULL;
