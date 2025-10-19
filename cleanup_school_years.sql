-- ============================================================================
-- CLEANUP SCHOOL YEARS SCRIPT (OPTIONAL)
-- Run this AFTER migrate_kinder_direct.sql if you want to delete old years
-- ============================================================================

-- Step 1: Show what will be deleted
SELECT 'Records that will be DELETED:' as Status;
SELECT school_year, COUNT(*) as total_records
FROM tuition_fee_structure
WHERE school_year IN ('2024-2025', '2025-2026', '2026-2027')
GROUP BY school_year
ORDER BY school_year;

-- Step 2: Show detailed records before deletion
SELECT 'Detailed records before deletion:' as Status;
SELECT id, grade_level, academic_track, school_year, term, tuition_fee, other_fees, total_fee
FROM tuition_fee_structure
WHERE school_year IN ('2024-2025', '2025-2026', '2026-2027')
ORDER BY school_year, grade_level, term;

-- ============================================================================
-- UNCOMMENT THE LINES BELOW TO ACTUALLY DELETE THE RECORDS
-- (Remove the -- at the start of each line)
-- ============================================================================

-- DELETE FROM tuition_fee_structure 
-- WHERE school_year = '2024-2025';

-- DELETE FROM tuition_fee_structure 
-- WHERE school_year = '2025-2026';

-- DELETE FROM tuition_fee_structure 
-- WHERE school_year = '2026-2027';

-- Step 3: Verify deletion (uncomment after running delete)
-- SELECT 'AFTER DELETION - Remaining records:' as Status;
-- SELECT school_year, COUNT(*) as total_records
-- FROM tuition_fee_structure
-- GROUP BY school_year
-- ORDER BY school_year DESC;

-- ============================================================================
-- INSTRUCTIONS:
-- 1. First run the SELECT statements above to see what will be deleted
-- 2. If you're sure, uncomment the DELETE statements (remove the --)
-- 3. Run the script again to delete the records
-- 4. Uncomment the verification SELECT to confirm deletion
-- ============================================================================
