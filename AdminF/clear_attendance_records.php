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

// Get records to export before deleting
$exportStmt = $conn->prepare("SELECT * FROM $tableName WHERE DATE($dateCol) BETWEEN ? AND ?");
$exportStmt->bind_param('ss', $start, $end);
$exportStmt->execute();
$exportResult = $exportStmt->get_result();

$records = [];
while ($row = $exportResult->fetch_assoc()) {
    $records[] = $row;
}
$count = count($records);

// Generate CSV content
$csvContent = '';
$filename = "Attendance Record {$start} to {$end}.csv";

if ($count > 0) {
    // Add UTF-8 BOM
    $csvContent .= chr(0xEF).chr(0xBB).chr(0xBF);
    
    // Add header
    $csvContent .= implode(',', array_keys($records[0])) . "\n";
    
    // Add data rows
    foreach ($records as $record) {
        $row = [];
        foreach ($record as $key => $value) {
            // Format numeric IDs as text
            if (in_array($key, ['id', 'id_number', 'student_id', 'employee_id']) && is_numeric($value)) {
                $row[] = '"' . "'" . $value . '"';
            } else {
                $row[] = '"' . str_replace('"', '""', $value) . '"';
            }
        }
        $csvContent .= implode(',', $row) . "\n";
    }
}

$stmt = $conn->prepare("DELETE FROM $tableName WHERE DATE($dateCol) BETWEEN ? AND ?");
$stmt->bind_param('ss', $start, $end);

if ($stmt->execute()) {
    $message = $count > 0 ? "Exported $count records and deleted from system" : "No records found";
    echo json_encode([
        'success' => true, 
        'message' => $message, 
        'records_deleted' => $count, 
        'csv_data' => base64_encode($csvContent),
        'filename' => $filename
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Clear failed']);
}

$stmt->close();
$conn->close();
ob_end_flush();