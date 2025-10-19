-- Fix College Course Names to Match Registrar Format
-- This updates the database to use the format: "BPEd (Bachelor of Physical Education)"
-- instead of "Bachelor of Physical Education (BPed)"

-- Update tuition_fee_structure table
UPDATE tuition_fee_structure 
SET academic_track = 'BPEd (Bachelor of Physical Education)' 
WHERE academic_track = 'Bachelor of Physical Education (BPed)';

UPDATE tuition_fee_structure 
SET academic_track = 'BECEd (Bachelor of Early Childhood Education)' 
WHERE academic_track = 'Bachelor of Early Childhood Education (BECEd)';

-- Update student_account table
UPDATE student_account 
SET academic_track = 'BPEd (Bachelor of Physical Education)' 
WHERE academic_track = 'Bachelor of Physical Education (BPed)';

UPDATE student_account 
SET academic_track = 'BECEd (Bachelor of Early Childhood Education)' 
WHERE academic_track = 'Bachelor of Early Childhood Education (BECEd)';

-- Check results
SELECT 'tuition_fee_structure' as table_name, academic_track, COUNT(*) as count 
FROM tuition_fee_structure 
WHERE academic_track LIKE '%BPEd%' OR academic_track LIKE '%BECEd%'
GROUP BY academic_track

UNION ALL

SELECT 'student_account' as table_name, academic_track, COUNT(*) as count 
FROM student_account 
WHERE academic_track LIKE '%BPEd%' OR academic_track LIKE '%BECEd%'
GROUP BY academic_track;
