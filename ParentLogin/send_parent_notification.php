<?php
// Helper function to send notifications to parents of Kinder students
// This can be included in registrar document processing files

function sendParentNotification($conn, $student_id, $message) {
    // Get parent information for this student
    $parent_query = $conn->prepare("SELECT parent_id FROM parent_accounts WHERE child_id = ? LIMIT 1");
    $parent_query->bind_param("s", $student_id);
    $parent_query->execute();
    $parent_result = $parent_query->get_result();
    
    if ($parent_result->num_rows > 0) {
        $parent = $parent_result->fetch_assoc();
        $parent_id = $parent['parent_id'];
        
        // Check if student is Kinder
        $student_query = $conn->prepare("SELECT academic_track, grade_level FROM student_account WHERE id_number = ?");
        $student_query->bind_param("s", $student_id);
        $student_query->execute();
        $student_result = $student_query->get_result();
        
        if ($student_result->num_rows > 0) {
            $student = $student_result->fetch_assoc();
            $is_kinder = (stripos($student['academic_track'], 'kinder') !== false || 
                         stripos($student['academic_track'], 'pre-elementary') !== false ||
                         stripos($student['grade_level'], 'kinder') !== false);
            
            if ($is_kinder) {
                // Insert notification
                $notif_stmt = $conn->prepare("INSERT INTO parent_notifications (parent_id, child_id, message, date_sent, is_read) VALUES (?, ?, ?, NOW(), 0)");
                $notif_stmt->bind_param("sss", $parent_id, $student_id, $message);
                $notif_stmt->execute();
                $notif_stmt->close();
                return true;
            }
        }
        $student_query->close();
    }
    $parent_query->close();
    return false;
}

// Example usage in registrar document processing:
// When document status changes to "Ready to Claim":
// sendParentNotification($conn, $student_id, "✅ Your document request for {$document_type} is now ready to claim at the Registrar's Office.");

// When document status changes to "Claimed":
// sendParentNotification($conn, $student_id, "✓ Your document request for {$document_type} has been claimed.");

// When document is rejected:
// sendParentNotification($conn, $student_id, "❌ Your document request for {$document_type} has been declined. Please contact the Registrar's Office for more information.");
?>
