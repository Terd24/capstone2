<?php
session_start();
include('../StudentLogin/db_conn.php');

header('Content-Type: application/json');

if (!isset($_SESSION['parent_id']) || !isset($_SESSION['child_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$notification_id = isset($data['notification_id']) ? (int)$data['notification_id'] : 0;

if ($notification_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
    exit();
}

$parent_id = $_SESSION['parent_id'];
$child_id = $_SESSION['child_id'];

$stmt = $conn->prepare("UPDATE parent_notifications SET is_read = 1 WHERE id = ? AND parent_id = ? AND child_id = ?");
$stmt->bind_param("iss", $notification_id, $parent_id, $child_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update notification']);
}

$stmt->close();
$conn->close();
?>
