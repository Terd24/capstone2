<?php
ob_start();
session_start();
ob_clean();
header('Content-Type: application/json');

// Check if user is Super Admin
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'onecci_db');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$startDate = $data['start_date'] ?? '';
$endDate = $data['end_date'] ?? '';

if (empty($startDate) || empty($endDate)) {
    echo json_encode(['success' => false, 'message' => 'Start date and end date are required']);
    exit;
}

// Check if login_activity table exists, if not use system_logs
$tableCheck = $conn->query("SHOW TABLES LIKE 'login_activity'");
$tableName = ($tableCheck && $tableCheck->num_rows > 0) ? 'login_activity' : 'system_logs';
$dateColumn = ($tableName === 'login_activity') ? 'login_time' : 'timestamp';

// Count records to be deleted
$countQuery = "SELECT COUNT(*) as count FROM $tableName 
               WHERE DATE($dateColumn) BETWEEN ? AND ?";
if ($tableName === 'system_logs') {
    $countQuery .= " AND action LIKE '%login%'";
}

$countStmt = $conn->prepare($countQuery);
$countStmt->bind_param('ss', $startDate, $endDate);
$countStmt->execute();
$countResult = $countStmt->get_result();
$count = $countResult->fetch_assoc()['count'];

// Delete login logs
$deleteQuery = "DELETE FROM $tableName WHERE DATE($dateColumn) BETWEEN ? AND ?";
if ($tableName === 'system_logs') {
    $deleteQuery .= " AND action LIKE '%login%'";
}

$stmt = $conn->prepare($deleteQuery);
$stmt->bind_param('ss', $startDate, $endDate);

if ($stmt->execute()) {
    // Try to log the action (optional)
    try {
        $conn->query("CREATE TABLE IF NOT EXISTS system_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            action VARCHAR(100),
            user_type VARCHAR(50),
            user_id VARCHAR(50),
            details TEXT,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        $logStmt = $conn->prepare("INSERT INTO system_logs (action, user_type, user_id, details) 
                                   VALUES ('clear_login_logs', 'superadmin', ?, ?)");
        $userId = $_SESSION['user_id'] ?? $_SESSION['id_number'] ?? 'SA001';
        $details = "Cleared $count login logs from $startDate to $endDate";
        $logStmt->bind_param('ss', $userId, $details);
        $logStmt->execute();
    } catch (Exception $e) {
        // Logging failed but operation succeeded
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Login logs cleared successfully',
        'records_deleted' => $count
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to clear login logs: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
ob_end_flush();
?>
