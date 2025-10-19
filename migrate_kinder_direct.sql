-- ============================================================================
-- KINDER MIGRATION SCRIPT
-- Run this directly in phpMyAdmin or your MySQL client
-- ============================================================================

-- Step 1: Show current Kinder records
SELECT 'BEFORE MIGRATION - Current Kinder Records:' as Status;
SELECT id, grade_level, academic_track, school_year, term, tuition_fee, other_fees, total_fee 
FROM tuition_fee_structure 
WHERE grade_level = 'Kinder'
ORDER BY school_year, term;

-- Step 2: Create Kinder 2 records (duplicates of existing Kinder)
INSERT INTO tuition_fee_structure 
    (grade_level, academic_track, tuition_fee, other_fees, total_fee, school_year, term, created_at, updated_at)
SELECT 
    'Kinder 2' as grade_level,
    academic_track,
    tuition_fee,
    other_fees,
    total_fee,
    school_year,
    term,
    NOW() as created_at,
    NOW() as updated_at
FROM tuition_fee_structure
WHERE grade_level = 'Kinder'
AND NOT EXISTS (
    SELECT 1 FROM tuition_fee_structure t2 
    WHERE t2.grade_level = 'Kinder 2' 
    AND t2.academic_track = tuition_fee_structure.academic_track
    AND t2.school_year = tuition_fee_structure.school_year
    AND t2.term = tuition_fee_structure.term
);

-- Step 3: Update existing Kinder to Kinder 1
UPDATE tuition_fee_structure 
SET grade_level = 'Kinder 1',
    updated_at = NOW()
WHERE grade_level = 'Kinder';

-- Step 4: Verify the migration
SELECT 'AFTER MIGRATION - Verification:' as Status;
SELECT 
    grade_level,
    COUNT(*) as total_records,
    COUNT(DISTINCT school_year) as school_years,
    COUNT(DISTINCT term) as terms
FROM tuition_fee_structure
WHERE grade_level IN ('Kinder 1', 'Kinder 2', 'Kinder')
GROUP BY grade_level
ORDER BY grade_level;

-- Step 5: Show all Kinder 1 and Kinder 2 records
SELECT 'All Kinder 1 and Kinder 2 Records:' as Status;
SELECT id, grade_level, academic_track, school_year, term, tuition_fee, other_fees, total_fee 
FROM tuition_fee_structure 
WHERE grade_level IN ('Kinder 1', 'Kinder 2')
ORDER BY school_year DESC, grade_level, term;

-- ============================================================================
-- MIGRATION COMPLETE!
-- You should now see:
-- - All old "Kinder" records renamed to "Kinder 1"
-- - New "Kinder 2" records created with same prices
-- - No more "Kinder" records (only "Kinder 1" and "Kinder 2")
-- ============================================================================
