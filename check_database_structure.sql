-- ============================================================================
-- CHECK DATABASE STRUCTURE AND FIX CONSTRAINTS
-- ============================================================================

-- Step 1: Show current table structure
SELECT '=== TABLE STRUCTURE ===' as '---';
SHOW CREATE TABLE tuition_fee_structure;

-- Step 2: Show all indexes and constraints
SELECT '=== INDEXES AND CONSTRAINTS ===' as '---';
SHOW INDEX FROM tuition_fee_structure;

-- Step 3: Check for unique constraints
SELECT '=== UNIQUE CONSTRAINTS ===' as '---';
SELECT 
    CONSTRAINT_NAME,
    COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_NAME = 'tuition_fee_structure'
AND TABLE_SCHEMA = DATABASE()
ORDER BY CONSTRAINT_NAME, ORDINAL_POSITION;

-- Step 4: Drop problematic unique constraint if it exists
-- UNCOMMENT THE LINE BELOW IF YOU SEE A UNIQUE CONSTRAINT CAUSING ISSUES
-- ALTER TABLE tuition_fee_structure DROP INDEX unique_grade_track_year;

-- Step 5: Verify no duplicate records exist
SELECT '=== CHECK FOR DUPLICATES ===' as '---';
SELECT 
    grade_level,
    academic_track,
    school_year,
    term,
    COUNT(*) as count
FROM tuition_fee_structure
GROUP BY grade_level, academic_track, school_year, term
HAVING COUNT(*) > 1;

SELECT '=== DONE ===' as '---';
SELECT 'If you see duplicates above, you need to clean them first' as Message;
