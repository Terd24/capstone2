-- ============================================================================
-- ALL-IN-ONE MIGRATION SCRIPT
-- Copy and paste this entire file into phpMyAdmin SQL tab
-- ============================================================================

-- PART 1: MIGRATE KINDER TO KINDER 1 AND KINDER 2
-- ============================================================================

-- Show current state
SELECT '=== BEFORE MIGRATION ===' as '---';
SELECT grade_level, COUNT(*) as count FROM tuition_fee_structure 
WHERE grade_level LIKE '%Kinder%' GROUP BY grade_level;

-- Create Kinder 2 records
INSERT INTO tuition_fee_structure 
    (grade_level, academic_track, tuition_fee, other_fees, total_fee, school_year, term, created_at, updated_at)
SELECT 
    'Kinder 2', academic_track, tuition_fee, other_fees, total_fee, school_year, term, NOW(), NOW()
FROM tuition_fee_structure
WHERE grade_level = 'Kinder'
AND NOT EXISTS (
    SELECT 1 FROM tuition_fee_structure t2 
    WHERE t2.grade_level = 'Kinder 2' 
    AND t2.academic_track = tuition_fee_structure.academic_track
    AND t2.school_year = tuition_fee_structure.school_year
    AND t2.term = tuition_fee_structure.term
);

-- Rename Kinder to Kinder 1
UPDATE tuition_fee_structure 
SET grade_level = 'Kinder 1', updated_at = NOW()
WHERE grade_level = 'Kinder';

-- Show results
SELECT '=== AFTER MIGRATION ===' as '---';
SELECT grade_level, COUNT(*) as count FROM tuition_fee_structure 
WHERE grade_level LIKE '%Kinder%' GROUP BY grade_level;

SELECT '=== MIGRATION COMPLETE! ===' as '---';
SELECT 'You should now see Kinder 1 and Kinder 2 in your dashboard' as Message;

-- ============================================================================
-- DONE! Check your Owner Dashboard → Tuition Fees to verify
-- ============================================================================
