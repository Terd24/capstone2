-- ============================================================================
-- SIMPLE: CREATE 2ND SEMESTER RECORDS
-- Copy these 2 lines and run in phpMyAdmin SQL tab
-- ============================================================================

-- Create 2nd Semester records
INSERT IGNORE INTO tuition_fee_structure (grade_level, academic_track, tuition_fee, other_fees, total_fee, school_year, term, created_at, updated_at)
SELECT grade_level, academic_track, tuition_fee, other_fees, total_fee, school_year, '2nd Semester', NOW(), NOW()
FROM tuition_fee_structure WHERE term = '1st Semester';

-- Show results
SELECT school_year, term, COUNT(*) as total FROM tuition_fee_structure GROUP BY school_year, term ORDER BY school_year DESC, term;
