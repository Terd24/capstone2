-- ============================================================================
-- PRODUCTION MIGRATION SCRIPT FOR K-12 vs COLLEGE GRADING SYSTEM
-- ============================================================================
-- Date: 2025-10-20
-- Description: Adds support for dual grading systems (K-12 Quarters and College Terms)
-- 
-- IMPORTANT: This script is safe to run multiple times (idempotent)
-- It checks for existing columns before adding them
-- ============================================================================

-- Set SQL mode for safety
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';

-- ============================================================================
-- STEP 1: ADD K-12 QUARTER COLUMNS TO grades_record TABLE
-- ============================================================================

-- Add first_quarter column if it doesn't exist
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'grades_record' 
  AND COLUMN_NAME = 'first_quarter';

SET @query = IF(@col_exists = 0,
    'ALTER TABLE grades_record ADD COLUMN first_quarter DECIMAL(5,2) DEFAULT NULL COMMENT "K-12 First Quarter Grade"',
    'SELECT "Column first_quarter already exists" AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add second_quarter column if it doesn't exist
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'grades_record' 
  AND COLUMN_NAME = 'second_quarter';

SET @query = IF(@col_exists = 0,
    'ALTER TABLE grades_record ADD COLUMN second_quarter DECIMAL(5,2) DEFAULT NULL COMMENT "K-12 Second Quarter Grade"',
    'SELECT "Column second_quarter already exists" AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add third_quarter column if it doesn't exist
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'grades_record' 
  AND COLUMN_NAME = 'third_quarter';

SET @query = IF(@col_exists = 0,
    'ALTER TABLE grades_record ADD COLUMN third_quarter DECIMAL(5,2) DEFAULT NULL COMMENT "K-12 Third Quarter Grade"',
    'SELECT "Column third_quarter already exists" AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add fourth_quarter column if it doesn't exist
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'grades_record' 
  AND COLUMN_NAME = 'fourth_quarter';

SET @query = IF(@col_exists = 0,
    'ALTER TABLE grades_record ADD COLUMN fourth_quarter DECIMAL(5,2) DEFAULT NULL COMMENT "K-12 Fourth Quarter Grade"',
    'SELECT "Column fourth_quarter already exists" AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- STEP 2: ADD GRADING SYSTEM COLUMN
-- ============================================================================

-- Add grading_system column if it doesn't exist
SET @col_exists = 0;
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'grades_record' 
  AND COLUMN_NAME = 'grading_system';

SET @query = IF(@col_exists = 0,
    'ALTER TABLE grades_record ADD COLUMN grading_system VARCHAR(10) DEFAULT "COLLEGE" COMMENT "K12 or COLLEGE grading system"',
    'SELECT "Column grading_system already exists" AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- STEP 3: ADD INDEXES FOR PERFORMANCE
-- ============================================================================

-- Add index on grading_system if it doesn't exist
SET @index_exists = 0;
SELECT COUNT(*) INTO @index_exists 
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'grades_record' 
  AND INDEX_NAME = 'idx_grading_system';

SET @query = IF(@index_exists = 0,
    'ALTER TABLE grades_record ADD INDEX idx_grading_system (grading_system)',
    'SELECT "Index idx_grading_system already exists" AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add composite index for better query performance if it doesn't exist
SET @index_exists = 0;
SELECT COUNT(*) INTO @index_exists 
FROM information_schema.STATISTICS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'grades_record' 
  AND INDEX_NAME = 'idx_student_subject_term';

SET @query = IF(@index_exists = 0,
    'ALTER TABLE grades_record ADD INDEX idx_student_subject_term (id_number, subject, school_year_term)',
    'SELECT "Index idx_student_subject_term already exists" AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- STEP 4: CREATE TEACHER SUBJECT ASSIGNMENTS TABLE
-- ============================================================================

CREATE TABLE IF NOT EXISTS teacher_subject_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id VARCHAR(50) NOT NULL COMMENT 'Employee ID of the teacher',
    subject_id INT NOT NULL COMMENT 'Subject ID from subjects table',
    subject_name VARCHAR(255) NOT NULL COMMENT 'Subject name for quick reference',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_teacher_subject (teacher_id, subject_id),
    INDEX idx_teacher (teacher_id),
    INDEX idx_subject (subject_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Assigns subjects to teachers for grade management';

-- ============================================================================
-- STEP 5: CREATE SUBJECTS TABLE (IF NOT EXISTS)
-- ============================================================================

CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Master list of subjects';

-- ============================================================================
-- STEP 6: CREATE SUBJECT OFFERINGS TABLE (IF NOT EXISTS)
-- ============================================================================

CREATE TABLE IF NOT EXISTS subject_offerings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_id INT NOT NULL,
    grade_level VARCHAR(20) NOT NULL COMMENT 'e.g., Grade 1, 1st Year',
    strand VARCHAR(50) NULL COMMENT 'e.g., STEM, ABM, HUMSS for SHS; BSIT, BSED for College',
    semester ENUM('1st','2nd') NOT NULL COMMENT 'Term/Semester',
    school_year_term VARCHAR(50) NULL COMMENT 'e.g., 2024-2025 1st Term',
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_subject_offerings_subject FOREIGN KEY(subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_offer (subject_id, grade_level, strand, semester, school_year_term),
    INDEX idx_grade_level (grade_level),
    INDEX idx_semester (semester)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Defines which subjects are offered for which grade levels and terms';

-- ============================================================================
-- STEP 7: UPDATE EXISTING RECORDS WITH GRADING SYSTEM
-- ============================================================================

-- Update existing records to set grading_system based on student grade_level
-- This only updates records where grading_system is NULL
UPDATE grades_record gr
INNER JOIN student_account sa ON gr.id_number = sa.id_number
SET gr.grading_system = CASE
    -- K-12 patterns
    WHEN LOWER(sa.grade_level) LIKE '%kinder%' THEN 'K12'
    WHEN LOWER(sa.grade_level) REGEXP 'grade[[:space:]]*[1-9]' THEN 'K12'
    WHEN LOWER(sa.grade_level) REGEXP 'grade[[:space:]]*1[0-2]' THEN 'K12'
    -- College patterns
    WHEN LOWER(sa.grade_level) REGEXP '[1-4](st|nd|rd|th)[[:space:]]*year' THEN 'COLLEGE'
    ELSE 'COLLEGE'
END
WHERE gr.grading_system IS NULL OR gr.grading_system = '';

-- ============================================================================
-- STEP 8: VERIFICATION QUERIES (OPTIONAL - FOR CHECKING RESULTS)
-- ============================================================================

-- Check if all columns were added successfully
SELECT 
    'grades_record columns check' AS verification,
    COUNT(*) AS total_columns
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'grades_record' 
  AND COLUMN_NAME IN ('first_quarter', 'second_quarter', 'third_quarter', 'fourth_quarter', 'grading_system');

-- Check grading system distribution
SELECT 
    grading_system,
    COUNT(*) AS record_count
FROM grades_record
GROUP BY grading_system;

-- ============================================================================
-- MIGRATION COMPLETE
-- ============================================================================

SELECT 'Migration completed successfully!' AS status,
       NOW() AS completed_at;
