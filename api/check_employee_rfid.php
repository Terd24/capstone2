<?php
header('Content-Type: application/json');
require_once '../StudentLogin/db_conn.php';

if (!isset($_GET['rfid'])) {
    echo json_encode(['error' => 'No RFID provided']);
    exit;
}

$rfid = strtoupper(trim($_GET['rfid']));

// Check if this RFID belongs to an employee
$stmt = $conn->prepare("SELECT id_number FROM employees WHERE UPPER(rfid_uid) = ? LIMIT 1");
$stmt->bind_param("s", $rfid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['is_employee' => true]);
} else {
    echo json_encode(['is_employee' => false]);
}

$stmt->close();
$conn->close();
?>
