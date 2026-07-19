-- Add profile_picture column to patients table
ALTER TABLE patients ADD COLUMN profile_picture VARCHAR(255) DEFAULT NULL;
