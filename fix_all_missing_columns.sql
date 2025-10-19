-- Fix student_fee_items table - Add ALL missing columns
-- Run this in phpMyAdmin

-- First, check current structure
DESCRIBE student_fee_items;

-- Add missing columns one by one (IF NOT EXISTS doesn't work in older MySQL, so we'll use a different approach)

-- Add student_name if missing
ALTER TABLE student_fee_items 
ADD COLUMN student_name VARCHAR(200) AFTER id_number;

-- Add grade_level if missing
ALTER TABLE student_fee_items 
ADD COLUMN grade_level VARCHAR(50) AFTER student_name;

-- Add date_added if missing
ALTER TABLE student_fee_items 
ADD COLUMN date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Verify all columns are now present
DESCRIBE student_fee_items;

-- Show sample data
SELECT * FROM student_fee_items LIMIT 3;

-- If you get errors about columns already existing, that's OK - it means they're already there
-- Just continue with the next ALTER statement
