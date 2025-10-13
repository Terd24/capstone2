-- Add must_change_password column to employee_accounts table
-- This enables first-time login password change feature for employees

ALTER TABLE employee_accounts 
ADD COLUMN IF NOT EXISTS must_change_password TINYINT(1) DEFAULT 1 
COMMENT 'Force password change on first login (1=yes, 0=no)';

-- Set existing employees to not require password change (optional)
-- Uncomment the line below if you want existing employees to keep their passwords
-- UPDATE employee_accounts SET must_change_password = 0;

-- For new employees created by admin, must_change_password will be 1 by default
