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

// Ensure archive table exists
$conn->query("CREATE TABLE IF NOT EXISTS attendance_archive (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_id INT,
    id_number VARCHAR(50),
    name VARCHAR(255),
    date DATE,
    time_in TIME,
    time_out TIME,
    status VARCHAR(50),
    user_type VARCHAR(50),
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archived_by VARCHAR(100),
    archived_reason VARCHAR(255),
    INDEX idx_id_number (id_number),
    INDEX idx_date (date),
    INDEX idx_archived_at (archived_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

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

// Get records to archive
$selectStmt = $conn->prepare("SELECT * FROM $tableName WHERE DATE($dateCol) BETWEEN ? AND ?");
$selectStmt->bind_param('ss', $start, $end);
$selectStmt->execute();
$result = $selectStmt->get_result();

$count = 0;
$archived_by = $_SESSION['user_id'] ?? $_SESSION['id_number'] ?? 'SuperAdmin';
$archived_reason = "Archived attendance records from $start to $end";

// Archive each record
while ($row = $result->fetch_assoc()) {
    $archiveStmt = $conn->prepare("INSERT INTO attendance_archive 
        (original_id, id_number, name, date, time_in, time_out, status, user_type, archived_by, archived_reason) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $original_id = $row['id'];
    $id_number = $row['id_number'] ?? 'N/A';
    $name = $row['name'] ?? 'Unknown';
    $date = $row[$dateCol];
    $time_in = $row['time_in'] ?? null;
    $time_out = $row['time_out'] ?? null;
    $status = $row['status'] ?? 'Present';
    $user_type = $row['user_type'] ?? 'Unknown';
    
    $archiveStmt->bind_param('isssssssss', 
        $original_id, $id_number, $name, $date, $time_in, 
        $time_out, $status, $user_type, $archived_by, $archived_reason
    );
    
    if ($archiveStmt->execute()) {
        $count++;
    }
    $archiveStmt->close();
}

// Delete archived records from original table
if ($count > 0) {
    $deleteStmt = $conn->prepare("DELETE FROM $tableName WHERE DATE($dateCol) BETWEEN ? AND ?");
    $deleteStmt->bind_param('ss', $start, $end);
    $deleteStmt->execute();
    $deleteStmt->close();
}

echo json_encode([
    'success' => true, 
    'message' => "$count attendance records archived successfully",
    'records_archived' => $count
]);

$selectStmt->close();
$conn->close();
ob_end_flush();