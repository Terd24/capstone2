<?php
include '../StudentLogin/db_conn.php';

header('Content-Type: application/json');

$id_number = $_POST['id_number'] ?? '';
$violation_date = $_POST['violation_date'] ?? '';
$violation_type = $_POST['violation_type'] ?? '';

if (!$id_number || !$violation_date || !$violation_type) {
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$sql = "INSERT INTO guidance_records (id_number, remarks, record_date) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $id_number, $violation_type, $violation_date);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Failed to add violation']);
}

$stmt->close();
$conn->close();
