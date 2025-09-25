<?php
session_start();
include("db_conn.php");

header('Content-Type: application/json');

if (!isset($_SESSION['id_number'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$notif_id = isset($input['id']) ? intval($input['id']) : 0;
if ($notif_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid notification id']);
    exit();
}

$student_id = $_SESSION['id_number'];

$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND student_id = ?");
$stmt->bind_param("is", $notif_id, $student_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}

$stmt->close();
$conn->close();
