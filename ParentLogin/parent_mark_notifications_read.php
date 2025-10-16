<?php
session_start();
include('../StudentLogin/db_conn.php');

header('Content-Type: application/json');

if (!isset($_SESSION['parent_id']) || !isset($_SESSION['child_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$parent_id = $_SESSION['parent_id'];
$child_id = $_SESSION['child_id'];

$stmt = $conn->prepare("UPDATE parent_notifications SET is_read = 1 WHERE parent_id = ? AND child_id = ? AND is_read = 0");
$stmt->bind_param("ss", $parent_id, $child_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update notifications']);
}

$stmt->close();
$conn->close();
?>
