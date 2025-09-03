<?php
session_start();
include("db_conn.php");

header('Content-Type: application/json');

if (!isset($_SESSION['id_number'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$student_id = $_SESSION['id_number'];

$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE student_id = ?");
$stmt->bind_param("s", $student_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}

$stmt->close();
$conn->close();
?>
