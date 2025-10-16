<?php
include("../StudentLogin/db_conn.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $status = $_POST['status'] ?? '';

    if ($id && $status) {
        // Update status in document_requests
        $stmt = $conn->prepare("UPDATE document_requests SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();

        // Fetch student_id & document_type for notifications
        $stmt2 = $conn->prepare("SELECT student_id, document_type FROM document_requests WHERE id=?");
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        $result = $stmt2->get_result()->fetch_assoc();

        if ($result) {
            $student_id = $result['student_id'];
            $doc_name = $result['document_type'];

            // Send notification to student
            $message = "📄 Your document '$doc_name' status has been updated to '$status'.";
            $stmt3 = $conn->prepare("INSERT INTO notifications (student_id, message, date_sent, is_read) VALUES (?, ?, NOW(), 0)");
            $stmt3->bind_param("ss", $student_id, $message);
            $stmt3->execute();
            $stmt3->close();

            // Check if student is Kinder and send notification to parent
            $kinder_check = $conn->prepare("SELECT academic_track, grade_level FROM student_account WHERE id_number = ?");
            $kinder_check->bind_param("s", $student_id);
            $kinder_check->execute();
            $kinder_result = $kinder_check->get_result();
            
            if ($kinder_result && $kinder_result->num_rows > 0) {
                $student_info = $kinder_result->fetch_assoc();
                $is_kinder = (stripos($student_info['academic_track'], 'kinder') !== false || 
                             stripos($student_info['academic_track'], 'pre-elementary') !== false ||
                             stripos($student_info['grade_level'], 'kinder') !== false);
                
                if ($is_kinder) {
                    // Get parent_id for this child
                    $parent_check = $conn->prepare("SELECT parent_id FROM parent_account WHERE child_id = ?");
                    $parent_check->bind_param("s", $student_id);
                    $parent_check->execute();
                    $parent_result = $parent_check->get_result();
                    
                    if ($parent_result && $parent_result->num_rows > 0) {
                        $parent_row = $parent_result->fetch_assoc();
                        $parent_id = $parent_row['parent_id'];
                        
                        // Send notification to parent
                        $parent_message = "📄 Your child's document request for '$doc_name' has been updated to '$status'.";
                        $stmt_parent = $conn->prepare("INSERT INTO parent_notifications (parent_id, child_id, message, date_sent, is_read) VALUES (?, ?, ?, NOW(), 0)");
                        $stmt_parent->bind_param("sss", $parent_id, $student_id, $parent_message);
                        $stmt_parent->execute();
                        $stmt_parent->close();
                    }
                    $parent_check->close();
                }
            }
            $kinder_check->close();

            // If Claimed or Declined, auto-mark notifications as read
            if ($status === 'Claimed' || $status === 'Declined' || $status === 'Decline') {
                // Auto-mark all related notifications as read
                $stmt_mark_read = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE student_id = ? AND message LIKE ? AND is_read = 0");
                $doc_pattern = "%$doc_name%";
                $stmt_mark_read->bind_param("ss", $student_id, $doc_pattern);
                $stmt_mark_read->execute();
                $stmt_mark_read->close();

                // Also mark parent notifications as read (for Kinder students)
                $stmt_parent_read = $conn->prepare("UPDATE parent_notifications SET is_read = 1 WHERE child_id = ? AND message LIKE ? AND is_read = 0");
                $stmt_parent_read->bind_param("ss", $student_id, $doc_pattern);
                $stmt_parent_read->execute();
                $stmt_parent_read->close();
            }

            // If Claimed specifically, update date_claimed and add to submitted_documents
            if ($status === 'Claimed') {
                $stmt4 = $conn->prepare("UPDATE document_requests SET date_claimed = NOW() WHERE id=?");
                $stmt4->bind_param("i", $id);
                $stmt4->execute();
                $stmt4->close();

                // Check if already in submitted_documents
                $stmt5 = $conn->prepare("SELECT id FROM submitted_documents WHERE id_number=? AND document_name=?");
                $stmt5->bind_param("ss", $student_id, $doc_name);
                $stmt5->execute();
                $res5 = $stmt5->get_result();

                if ($res5->num_rows > 0) {
                    // Already exists → update remarks only
                    $stmt6 = $conn->prepare("UPDATE submitted_documents SET remarks='Claimed' WHERE id_number=? AND document_name=?");
                    $stmt6->bind_param("ss", $student_id, $doc_name);
                    $stmt6->execute();
                    $stmt6->close();
                } else {
                    // Insert new submitted document
                    $stmt7 = $conn->prepare("INSERT INTO submitted_documents (id_number, document_name, date_submitted, remarks) VALUES (?, ?, NOW(), 'Claimed')");
                    $stmt7->bind_param("ss", $student_id, $doc_name);
                    $stmt7->execute();
                    $stmt7->close();
                }
                $stmt5->close();
            }
        }

        $stmt2->close();
        echo "Status updated successfully.";
    } else {
        echo "Invalid request.";
    }
}
?>
