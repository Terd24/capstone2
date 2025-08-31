# Database Migration Steps

Follow these steps in phpMyAdmin to complete the database migration:

## Step 1: Check Foreign Key Constraints
Run this query in phpMyAdmin SQL tab to identify any foreign key constraints:

```sql
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
```

## Step 2: Execute Migration (Safe Method)
Run these commands one by one in phpMyAdmin:

```sql
-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- Drop the old student_account table
DROP TABLE IF EXISTS student_account;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Verify current tables
SHOW TABLES;
```

## Step 3: Rename Tables
```sql
-- Rename 'students' to 'student_account'
RENAME TABLE students TO student_account;

-- Rename 'registrar' to 'registrar_account'
RENAME TABLE registrar TO registrar_account;
```

## Step 4: Create Parent Account Table
```sql
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
```

## Step 5: Verify Migration
```sql
-- Check all tables exist with correct names
SHOW TABLES;

-- Verify parent_account table structure
DESCRIBE parent_account;
```

After completing these steps, return here and I'll update the PHP code to use the new table names.
