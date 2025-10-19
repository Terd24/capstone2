-- ============================================================================
-- ADD MISSING 2ND SEMESTER RECORDS
-- This will duplicate all 1st Semester records to create 2nd Semester records
-- ============================================================================

-- Step 1: Check current situation
SELECT '=== CURRENT RECORDS BY SEMESTER ===' as '---';
SELECT 
    school_year,
    term,
    COUNT(*) as total_records
FROM tuition_fee_structure
GROUP BY school_year, term
ORDER BY school_year DESC, term;

-- Step 2: Show which school years are missing 2nd semester
SELECT '=== SCHOOL YEARS MISSING 2ND SEMESTER ===' as '---';
SELECT DISTINCT school_year
FROM tuition_fee_structure
WHERE school_year NOT IN (
    SELECT DISTINCT school_year 
    FROM tuition_fee_structure 
    WHERE term = '2nd Semester'
)
ORDER BY school_year DESC;

-- Step 3: Create 2nd Semester records by copying from 1st Semester
-- This will copy all 1st Semester records and create 2nd Semester versions
INSERT IGNORE INTO tuition_fee_structure 
    (grade_level, academic_track, tuition_fee, other_fees, total_fee, school_year, term, created_at, updated_at)
SELECT 
    grade_level,
    academic_track,
    tuition_fee,
    other_fees,
    total_fee,
    school_year,
    '2nd Semester' as term,
    NOW() as created_at,
    NOW() as updated_at
FROM tuition_fee_structure
WHERE term = '1st Semester';

-- Step 4: Verify the results
SELECT '=== AFTER ADDING 2ND SEMESTER ===' as '---';
SELECT 
    school_year,
    term,
    COUNT(*) as total_records
FROM tuition_fee_structure
GROUP BY school_year, term
ORDER BY school_year DESC, term;

-- Step 5: Show detailed breakdown
SELECT '=== DETAILED BREAKDOWN ===' as '---';
SELECT 
    school_year,
    term,
    COUNT(DISTINCT grade_level) as unique_grades,
    COUNT(*) as total_records
FROM tuition_fee_structure
GROUP BY school_year, term
ORDER BY school_year DESC, term;

SELECT '=== DONE! ===' as '---';
SELECT 'All school years now have both 1st and 2nd Semester records' as Message;

-- ============================================================================
-- EXPECTED RESULT:
-- Each school year should have:
-- - 1st Semester: 34 records (Kinder 1, Kinder 2, Grade 1-12, 1st-4th Year)
-- - 2nd Semester: 34 records (same as 1st Semester)
-- Total: 68 records per school year
-- ============================================================================
