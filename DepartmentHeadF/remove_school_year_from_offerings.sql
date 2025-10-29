-- ============================================
-- REMOVE SCHOOL YEAR FROM SUBJECT OFFERINGS
-- ============================================
-- This script updates all subject_offerings records to remove the school_year_term
-- so that subjects are filtered only by semester (1st or 2nd), not by year
-- ============================================

-- Update all existing records to have empty school_year_term
UPDATE subject_offerings SET school_year_term = '' WHERE school_year_term IS NOT NULL;

-- Verify the update
SELECT 
    COUNT(*) as total_offerings,
    SUM(CASE WHEN school_year_term = '' OR school_year_term IS NULL THEN 1 ELSE 0 END) as without_year,
    SUM(CASE WHEN school_year_term != '' AND school_year_term IS NOT NULL THEN 1 ELSE 0 END) as with_year
FROM subject_offerings;

SELECT 'All subject offerings updated - school_year_term removed!' AS Status;
