<?php
session_start();
include("../StudentLogin/db_conn.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_number = $_POST['id_number'];
    $document_name = $_POST['document_name'];
    $remarks = $_POST['remarks'] ?? '';

    $stmt = $conn->prepare("INSERT INTO submitted_documents (id_number, document_name, date_submitted, remarks) VALUES (?, ?, NOW(), ?)");
    $stmt->bind_param("sss", $id_number, $document_name, $remarks);

    if ($stmt->execute()) {
        header("Location: student_info.php?student_id=" . urlencode($id_number) . "&type=submitted");
        exit;
    } else {
        echo "Error: " . $stmt->error;
    }
}
