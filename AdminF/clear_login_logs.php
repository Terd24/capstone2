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
$conn->query("CREATE TABLE IF NOT EXISTS login_logs_archive (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_id INT,
    username VARCHAR(100),
    login_time DATETIME,
    ip_address VARCHAR(45),
    user_agent TEXT,
    user_type VARCHAR(50),
    status VARCHAR(20),
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archived_by VARCHAR(100),
    archived_reason VARCHAR(255),
    INDEX idx_username (username),
    INDEX idx_login_time (login_time),
    INDEX idx_archived_at (archived_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$tableCheck = $conn->query("SHOW TABLES LIKE 'login_activity'");
$tableName = ($tableCheck && $tableCheck->num_rows > 0) ? 'login_activity' : 'system_logs';
$dateCol = ($tableName === 'login_activity') ? 'login_time' : 'timestamp';

// Get records to archive
$selectQuery = "SELECT * FROM $tableName WHERE DATE($dateCol) BETWEEN ? AND ?";
if ($tableName === 'system_logs') {
    $selectQuery .= " AND action LIKE '%login%'";
}

$selectStmt = $conn->prepare($selectQuery);
$selectStmt->bind_param('ss', $start, $end);
$selectStmt->execute();
$result = $selectStmt->get_result();

$count = 0;
$archived_by = $_SESSION['user_id'] ?? $_SESSION['id_number'] ?? 'SuperAdmin';
$archived_reason = "Archived login logs from $start to $end";

// Archive each record
while ($row = $result->fetch_assoc()) {
    $archiveStmt = $conn->prepare("INSERT INTO login_logs_archive 
        (original_id, username, login_time, logout_time, last_activity, session_duration, ip_address, user_agent, user_type, status, archived_by, archived_reason) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $original_id = $row['id'];
    $username = $row['username'] ?? $row['user_id'] ?? 'Unknown';
    $login_time = $row[$dateCol];
    $logout_time = $row['logout_time'] ?? null;
    $last_activity = $row['last_activity'] ?? null;
    $session_duration = $row['session_duration'] ?? null;
    $ip_address = $row['ip_address'] ?? 'N/A';
    $user_agent = $row['user_agent'] ?? 'N/A';
    $user_type = $row['user_type'] ?? $row['role'] ?? 'Unknown';
    $status = $row['status'] ?? 'success';
    
    $archiveStmt->bind_param('issssissssss', 
        $original_id, $username, $login_time, $logout_time, $last_activity, 
        $session_duration, $ip_address, $user_agent, $user_type, $status, 
        $archived_by, $archived_reason
    );
    
    if ($archiveStmt->execute()) {
        $count++;
    }
    $archiveStmt->close();
}

// Delete archived records from original table
if ($count > 0) {
    $deleteQuery = "DELETE FROM $tableName WHERE DATE($dateCol) BETWEEN ? AND ?";
    if ($tableName === 'system_logs') {
        $deleteQuery .= " AND action LIKE '%login%'";
    }
    
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bind_param('ss', $start, $end);
    $deleteStmt->execute();
    $deleteStmt->close();
}

echo json_encode([
    'success' => true, 
    'message' => "$count login records archived successfully",
    'records_archived' => $count
]);

$selectStmt->close();
$conn->close();
ob_end_flush();
