-- Database Migration Script for Parent Account System
-- Run these commands in phpMyAdmin SQL tab in order

-- IMPORTANT: Run each step separately and check results before proceeding

-- Step 1: Check for foreign key constraints on student_account table
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    CONSTRAINT_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM 
    INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
WHERE 
    REFERENCED_TABLE_SCHEMA = 'onecci_db' 
    AND REFERENCED_TABLE_NAME = 'student_account';

-- Step 2: Disable foreign key checks temporarily (safer approach)
SET FOREIGN_KEY_CHECKS = 0;

-- Step 3: Drop the old student_account table (if it exists)
DROP TABLE IF EXISTS student_account;

-- Step 4: Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Step 5: Verify current table structure
SHOW TABLES;

-- Step 6: Rename existing tables to new naming convention
-- Rename 'students' table to 'student_account'
RENAME TABLE students TO student_account;

-- Rename 'registrar' table to 'registrar_account'  
RENAME TABLE registrar TO registrar_account;

-- Step 5: Create parent_account table
CREATE TABLE IF NOT EXISTS `parent_account` (
  `parent_id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `child_id` varchar(50) NOT NULL,
  `child_name` varchar(200) NOT NULL,
  `id_number` varchar(50) NOT NULL UNIQUE,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`parent_id`),
  UNIQUE KEY `unique_child` (`child_id`),
  UNIQUE KEY `unique_id_number` (`id_number`),
  UNIQUE KEY `unique_username` (`username`),
  KEY `idx_child_id` (`child_id`),
  KEY `idx_id_number` (`id_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Step 6: Re-create foreign key constraints if needed (optional)
-- Example: ALTER TABLE table_name ADD CONSTRAINT constraint_name 
-- FOREIGN KEY (column_name) REFERENCES student_account(id_number);
