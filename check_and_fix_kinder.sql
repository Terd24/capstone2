-- ============================================================================
-- CHECK AND FIX KINDER - Smart migration that handles any situation
-- ============================================================================

-- STEP 1: See what you currently have
SELECT '=== WHAT YOU HAVE NOW ===' as '---';
SELECT 
    grade_level,
    COUNT(*) as total_records,
    GROUP_CONCAT(DISTINCT school_year ORDER BY school_year) as school_years
FROM tuition_fee_structure
WHERE grade_level LIKE '%Kinder%'
GROUP BY grade_level
ORDER BY grade_level;

-- STEP 2: Show detailed breakdown
SELECT '=== DETAILED BREAKDOWN ===' as '---';
SELECT 
    grade_level,
    school_year,
    term,
    COUNT(*) as count
FROM tuition_fee_structure
WHERE grade_level LIKE '%Kinder%'
GROUP BY grade_level, school_year, term
ORDER BY school_year, grade_level, term;

-- STEP 3: Create missing Kinder 1 records (from old "Kinder")
-- This will only insert if Kinder 1 doesn't exist for that combination
INSERT IGNORE INTO tuition_fee_structure 
    (grade_level, academic_track, tuition_fee, other_fees, total_fee, school_year, term, created_at, updated_at)
SELECT 
    'Kinder 1' as grade_level,
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

-- STEP 4: Create missing Kinder 2 records
-- Copy from Kinder 1 (or Kinder if Kinder 1 doesn't exist)
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
WHERE grade_level IN ('Kinder 1', 'Kinder');

-- STEP 5: Delete old "Kinder" records (now that we have Kinder 1 and Kinder 2)
DELETE FROM tuition_fee_structure 
WHERE grade_level = 'Kinder';

-- STEP 6: Show final result
SELECT '=== FINAL RESULT ===' as '---';
SELECT 
    grade_level,
    COUNT(*) as total_records,
    GROUP_CONCAT(DISTINCT school_year ORDER BY school_year) as school_years
FROM tuition_fee_structure
WHERE grade_level LIKE '%Kinder%'
GROUP BY grade_level
ORDER BY grade_level;

-- STEP 7: Verify all combinations exist
SELECT '=== VERIFICATION ===' as '---';
SELECT 
    grade_level,
    school_year,
    term,
    COUNT(*) as count
FROM tuition_fee_structure
WHERE grade_level IN ('Kinder 1', 'Kinder 2')
GROUP BY grade_level, school_year, term
ORDER BY school_year, grade_level, term;

SELECT '=== MIGRATION COMPLETE! ===' as '---';
SELECT 'Check your dashboard - you should see Kinder 1 and Kinder 2' as Message;
