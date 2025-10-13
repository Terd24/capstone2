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

require_once '../StudentLogin/db_conn.php';

$data = json_decode(file_get_contents('php://input'), true);
$start = $data['start_date'] ?? '';
$end = $data['end_date'] ?? '';

if (empty($start) || empty($end)) {
    echo json_encode(['success' => false, 'message' => 'Dates required']);
    ob_end_flush();
    exit;
}

$tableCheck = $conn->query("SHOW TABLES LIKE 'login_activity'");
$tableName = ($tableCheck && $tableCheck->num_rows > 0) ? 'login_activity' : 'system_logs';
$dateCol = ($tableName === 'login_activity') ? 'login_time' : 'timestamp';

// Get records to export
$exportQuery = "SELECT * FROM $tableName WHERE DATE($dateCol) BETWEEN ? AND ?";
if ($tableName === 'system_logs') {
    $exportQuery .= " AND action LIKE '%login%'";
}

$exportStmt = $conn->prepare($exportQuery);
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
$filename = "Login Logs {$start} to {$end}.csv";

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
            if (in_array($key, ['id', 'id_number', 'student_id', 'employee_id', 'user_id']) && is_numeric($value)) {
                $row[] = '"' . "'" . $value . '"';
            } else {
                $row[] = '"' . str_replace('"', '""', $value) . '"';
            }
        }
        $csvContent .= implode(',', $row) . "\n";
    }
}

// Delete records
$deleteQuery = "DELETE FROM $tableName WHERE DATE($dateCol) BETWEEN ? AND ?";
if ($tableName === 'system_logs') {
    $deleteQuery .= " AND action LIKE '%login%'";
}

$stmt = $conn->prepare($deleteQuery);
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
    echo json_encode(['success' => false, 'message' => 'Delete failed']);
}

$stmt->close();
$conn->close();
ob_end_flush();
