<?php
/**
 * Create balance in Cashier system when student requests a document with a fee
 * 
 * @param mysqli $conn Database connection
 * @param string $student_id Student ID number
 * @param string $document_type Document type requested
 * @return bool Success status
 */
function createDocumentBalance($conn, $student_id, $document_type) {
    try {
        error_log("=== CREATE DOCUMENT BALANCE START ===");
        error_log("Student ID: $student_id");
        error_log("Document Type: $document_type");
        
        // Get the fee for this document type
        $stmt = $conn->prepare("SELECT fee_amount FROM document_fees WHERE document_name = ? AND is_active = 1 LIMIT 1");
        $stmt->bind_param("s", $document_type);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // No fee configured, don't create balance
            error_log("No fee configured for document: $document_type");
            return true;
        }
        
        $row = $result->fetch_assoc();
        $fee_amount = floatval($row['fee_amount']);
        error_log("Fee amount found: ₱$fee_amount");
        
        // If fee is 0, don't create balance
        if ($fee_amount <= 0) {
            error_log("Fee is 0, not creating balance");
            return true;
        }
        
        // Get student information
        $stmt = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) as full_name, grade_level FROM student_account WHERE id_number = ? LIMIT 1");
        $stmt->bind_param("s", $student_id);
        $stmt->execute();
        $student_result = $stmt->get_result();
        
        if ($student_result->num_rows === 0) {
            return false;
        }
        
        $student = $student_result->fetch_assoc();
        $student_name = $student['full_name'];
        $grade_level = $student['grade_level'];
        
        // Get the latest school year term for this student
        $term_stmt = $conn->prepare("SELECT school_year_term FROM student_fee_items WHERE id_number = ? ORDER BY school_year_term DESC LIMIT 1");
        $term_stmt->bind_param("s", $student_id);
        $term_stmt->execute();
        $term_result = $term_stmt->get_result();
        
        if ($term_result->num_rows > 0) {
            $term_row = $term_result->fetch_assoc();
            $school_year_term = $term_row['school_year_term'];
        } else {
            // If no existing term, use current school year
            $current_year = date('Y');
            $next_year = $current_year + 1;
            $school_year_term = "$current_year-$next_year 1st Semester";
        }
        $term_stmt->close();
        
        // Create balance in student_fee_items table (used by Cashier)
        $fee_description = "Document Request Fee - " . $document_type;
        error_log("Creating balance:");
        error_log("  Student: $student_name ($student_id)");
        error_log("  Grade: $grade_level");
        error_log("  Term: $school_year_term");
        error_log("  Fee: $fee_description");
        error_log("  Amount: ₱$fee_amount");
        
        // Check if date_added column exists, if not, don't include it
        $check_column = $conn->query("SHOW COLUMNS FROM student_fee_items LIKE 'date_added'");
        $has_date_added = ($check_column && $check_column->num_rows > 0);
        
        if ($has_date_added) {
            $stmt = $conn->prepare("
                INSERT INTO student_fee_items (id_number, student_name, grade_level, school_year_term, fee_type, amount, paid, date_added)
                VALUES (?, ?, ?, ?, ?, ?, 0, NOW())
            ");
        } else {
            $stmt = $conn->prepare("
                INSERT INTO student_fee_items (id_number, student_name, grade_level, school_year_term, fee_type, amount, paid)
                VALUES (?, ?, ?, ?, ?, ?, 0)
            ");
        }
        $stmt->bind_param("sssssd", $student_id, $student_name, $grade_level, $school_year_term, $fee_description, $fee_amount);
        
        if ($stmt->execute()) {
            $insert_id = $conn->insert_id;
            error_log("✅ Balance created successfully! Insert ID: $insert_id");
            error_log("=== CREATE DOCUMENT BALANCE END ===");
            return true;
        } else {
            error_log("❌ Failed to create document balance: " . $stmt->error);
            error_log("=== CREATE DOCUMENT BALANCE END ===");
            return false;
        }
        
    } catch (Exception $e) {
        error_log("Error in createDocumentBalance: " . $e->getMessage());
        return false;
    }
}
?>
