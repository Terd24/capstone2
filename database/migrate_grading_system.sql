-- Migration Script: Add K-12 vs College Grading System Support
-- Date: 2025-10-20
-- Description: Adds quarter columns and grading_system field to grades_record table

-- Step 1: Add first_quarter column
ALTER TABLE grades_record ADD COLUMN first_quarter DECIMAL(5,2) DEFAULT NULL;

-- Step 2: Add second_quarter column
ALTER TABLE grades_record ADD COLUMN second_quarter DECIMAL(5,2) DEFAULT NULL;

-- Step 3: Add third_quarter column
ALTER TABLE grades_record ADD COLUMN third_quarter DECIMAL(5,2) DEFAULT NULL;

-- Step 4: Add fourth_quarter column
ALTER TABLE grades_record ADD COLUMN fourth_quarter DECIMAL(5,2) DEFAULT NULL;

-- Step 5: Add grading_system column
ALTER TABLE grades_record ADD COLUMN grading_system VARCHAR(10) DEFAULT NULL;

-- Step 6: Add index for grading_system
ALTER TABLE grades_record ADD INDEX idx_grading_system (grading_system);

-- Step 7: Add composite index for better query performance
ALTER TABLE grades_record ADD INDEX idx_student_subject_term (id_number, subject, school_year_term);

-- Step 8: Update existing records to set grading_system based on student grade_level
UPDATE grades_record gr
INNER JOIN student_account sa ON gr.id_number = sa.id_number
SET gr.grading_system = CASE
    WHEN LOWER(sa.grade_level) LIKE '%kinder%' THEN 'K12'
    WHEN LOWER(sa.grade_level) REGEXP 'grade[[:space:]]*[1-9]' THEN 'K12'
    WHEN LOWER(sa.grade_level) REGEXP 'grade[[:space:]]*1[0-2]' THEN 'K12'
    WHEN LOWER(sa.grade_level) REGEXP '[1-4](st|nd|rd|th)[[:space:]]*year' THEN 'COLLEGE'
    ELSE 'COLLEGE'
END
WHERE gr.grading_system IS NULL;
