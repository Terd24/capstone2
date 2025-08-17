<?php
session_start();
include("../StudentLogin/db_conn.php"); // adjust path if needed

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $status = $_POST['status'] ?? '';

    if (!$id || !$status) {
        echo "Invalid request";
        exit;
    }

    // fetch student_id first (so we can notify the right student)
    $stmt0 = $conn->prepare("SELECT student_id, document_type FROM document_requests WHERE id = ?");
    $stmt0->bind_param("i", $id);
    $stmt0->execute();
    $result0 = $stmt0->get_result();
    $req = $result0->fetch_assoc();
    $stmt0->close();

    if (!$req) {
        echo "Request not found.";
        exit;
    }

    $student_id = $req['student_id'];
    $docType = $req['document_type'];

    if ($status === "Claimed") {
        $stmt = $conn->prepare("UPDATE document_requests SET status = ?, date_claimed = NOW() WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
    } else {
        $stmt = $conn->prepare("UPDATE document_requests SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
    }

    if ($stmt->execute()) {
        // create a notification message
        if ($status === "Ready to Claim") {
            $message = "Your requested document ($docType) is ready to claim.";
        } elseif ($status === "Claimed") {
            $message = "You have claimed your requested document ($docType).";
        } else {
            $message = "Your request for $docType is pending.";
        }

        // insert into notifications table
        $stmt2 = $conn->prepare("INSERT INTO notifications (student_id, message, date_sent, is_read) VALUES (?, ?, NOW(), 0)");
        $stmt2->bind_param("ss", $student_id, $message);
        $stmt2->execute();
        $stmt2->close();

        echo "Status and notification updated successfully";
    } else {
        echo "Error updating status: " . $conn->error;
    }
    $stmt->close();
}
