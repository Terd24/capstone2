<?php
ob_start();
session_start();
ob_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    ob_end_flush();
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'onecci_db');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    ob_end_flush();
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$start = $data['start_date'] ?? '';
$end = $data['end_date'] ?? '';

if (empty($start) || empty($end)) {
    echo json_encode(['success' => false, 'message' => 'Dates required']);
    ob_end_flush();
    exit;
}

$tableName = 'attendance_record';
$check = $conn->query("SHOW TABLES LIKE '$tableName'");
if (!$check || $check->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Attendance table not found']);
    ob_end_flush();
    exit;
}

$dateCol = 'date';
$cols = $conn->query("SHOW COLUMNS FROM $tableName");
while ($col = $cols->fetch_assoc()) {
    $field = strtolower($col['Field']);
    if (in_array($field, ['date', 'attendance_date', 'created_at', 'timestamp', 'check_in_time'])) {
        $dateCol = $col['Field'];
        break;
    }
}

$countStmt = $conn->prepare("SELECT COUNT(*) as count FROM $tableName WHERE DATE($dateCol) BETWEEN ? AND ?");
$countStmt->bind_param('ss', $start, $end);
$countStmt->execute();
$result = $countStmt->get_result();
$count = $result->fetch_assoc()['count'];

$stmt = $conn->prepare("DELETE FROM $tableName WHERE DATE($dateCol) BETWEEN ? AND ?");
$stmt->bind_param('ss', $start, $end);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Cleared successfully', 'records_deleted' => $count]);
} else {
    echo json_encode(['success' => false, 'message' => 'Clear failed']);
}

$stmt->close();
$conn->close();
ob_end_flush();