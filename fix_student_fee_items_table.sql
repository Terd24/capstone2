-- Fix student_fee_items table structure
-- Add missing date_added column if it doesn't exist

-- Check current structure
DESCRIBE student_fee_items;

-- Add date_added column if it doesn't exist
ALTER TABLE student_fee_items 
ADD COLUMN IF NOT EXISTS date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Verify the column was added
DESCRIBE student_fee_items;

-- Show sample data
SELECT * FROM student_fee_items LIMIT 5;
