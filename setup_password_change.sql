-- Add must_change_password column to student_account table
-- This enables first-time login password change feature

ALTER TABLE student_account 
ADD COLUMN IF NOT EXISTS must_change_password TINYINT(1) DEFAULT 1 
COMMENT 'Force password change on first login (1=yes, 0=no)';

-- Set existing students to not require password change (optional)
-- Uncomment the line below if you want existing students to keep their passwords
-- UPDATE student_account SET must_change_password = 0;

-- For new students created by registrar, must_change_password will be 1 by default
