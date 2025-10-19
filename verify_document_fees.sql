-- Verify Document Fee System is Working
-- Run these queries to check if everything is set up correctly

-- 1. Check if document_fees table exists and has data
SELECT 'Document Fees Configuration:' as info;
SELECT document_name, fee_amount, 
       CASE WHEN fee_amount > 0 THEN 'Creates Balance' ELSE 'Free' END as status
FROM document_fees 
WHERE is_active = 1
ORDER BY document_name;

-- 2. Check recent document requests
SELECT 'Recent Document Requests:' as info;
SELECT dr.id, dr.student_id, dr.student_name, dr.document_type, 
       dr.status, dr.date_requested
FROM document_requests dr
ORDER BY dr.date_requested DESC
LIMIT 10;

-- 3. Check if balances were created for approved requests
SELECT 'Document Fee Balances Created:' as info;
SELECT sfi.id, sfi.id_number, sfi.student_name, sfi.fee_type, 
       sfi.amount, sfi.paid, (sfi.amount - sfi.paid) as balance,
       sfi.school_year_term, sfi.date_added
FROM student_fee_items sfi
WHERE sfi.fee_type LIKE '%Document Request Fee%'
ORDER BY sfi.date_added DESC
LIMIT 10;

-- 4. Check for approved requests that should have balances
SELECT 'Approved Requests (should have balances if fee > 0):' as info;
SELECT dr.student_id, dr.student_name, dr.document_type, dr.status,
       df.fee_amount,
       CASE 
           WHEN df.fee_amount > 0 THEN 'Should have balance'
           ELSE 'Free - no balance needed'
       END as expected_result
FROM document_requests dr
LEFT JOIN document_fees df ON dr.document_type = df.document_name AND df.is_active = 1
WHERE dr.status = 'Approved'
ORDER BY dr.date_requested DESC
LIMIT 10;

-- 5. Find any mismatches (approved requests with fees but no balance)
SELECT 'Potential Issues (approved with fee but no balance):' as info;
SELECT dr.id as request_id, dr.student_id, dr.student_name, 
       dr.document_type, df.fee_amount
FROM document_requests dr
INNER JOIN document_fees df ON dr.document_type = df.document_name AND df.is_active = 1
WHERE dr.status = 'Approved' 
  AND df.fee_amount > 0
  AND NOT EXISTS (
      SELECT 1 FROM student_fee_items sfi 
      WHERE sfi.id_number = dr.student_id 
        AND sfi.fee_type LIKE CONCAT('%', dr.document_type, '%')
  )
LIMIT 10;

-- 6. Summary statistics
SELECT 'System Statistics:' as info;
SELECT 
    (SELECT COUNT(*) FROM document_fees WHERE is_active = 1) as total_document_types,
    (SELECT COUNT(*) FROM document_fees WHERE is_active = 1 AND fee_amount > 0) as paid_documents,
    (SELECT COUNT(*) FROM document_fees WHERE is_active = 1 AND fee_amount = 0) as free_documents,
    (SELECT COUNT(*) FROM document_requests WHERE status = 'Pending') as pending_requests,
    (SELECT COUNT(*) FROM document_requests WHERE status = 'Approved') as approved_requests,
    (SELECT COUNT(*) FROM student_fee_items WHERE fee_type LIKE '%Document Request Fee%') as total_balances_created,
    (SELECT SUM(amount - paid) FROM student_fee_items WHERE fee_type LIKE '%Document Request Fee%') as total_unpaid_balance;
