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

            // Send notification
            $message = "📄 Your document '$doc_name' status has been updated to '$status'.";
            $stmt3 = $conn->prepare("INSERT INTO notifications (student_id, message, date_sent, is_read) VALUES (?, ?, NOW(), 0)");
            $stmt3->bind_param("ss", $student_id, $message);
            $stmt3->execute();
            $stmt3->close();

            // If Claimed, update date_claimed and add to submitted_documents if not exists
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
