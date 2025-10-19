-- ============================================================================
-- SAFE KINDER MIGRATION - Handles duplicates properly
-- ============================================================================

-- Step 1: Check current state
SELECT '=== CURRENT STATE ===' as Status;
SELECT grade_level, COUNT(*) as total_records
FROM tuition_fee_structure
WHERE grade_level LIKE '%Kinder%'
GROUP BY grade_level
ORDER BY grade_level;

-- Step 2: Create Kinder 2 records (only if they don't exist)
INSERT IGNORE INTO tuition_fee_structure 
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
WHERE grade_level = 'Kinder';

-- Step 3: Update Kinder to Kinder 1 (only records that are still "Kinder")
UPDATE tuition_fee_structure 
SET grade_level = 'Kinder 1', 
    updated_at = NOW()
WHERE grade_level = 'Kinder';

-- Step 4: Show final state
SELECT '=== AFTER MIGRATION ===' as Status;
SELECT grade_level, COUNT(*) as total_records
FROM tuition_fee_structure
WHERE grade_level LIKE '%Kinder%'
GROUP BY grade_level
ORDER BY grade_level;

-- Step 5: Show all Kinder records
SELECT '=== ALL KINDER RECORDS ===' as Status;
SELECT id, grade_level, academic_track, school_year, term, tuition_fee, other_fees
FROM tuition_fee_structure
WHERE grade_level IN ('Kinder', 'Kinder 1', 'Kinder 2')
ORDER BY school_year DESC, grade_level, term;

SELECT '=== DONE! ===' as Status;
