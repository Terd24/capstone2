<?php
ob_start();
session_start();
ob_clean();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Check if user is Super Admin
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Role: ' . ($_SESSION['role'] ?? 'none')]);
    exit;
}

require_once '../StudentLogin/db_conn.php';

// Generate backup filename with timestamp
$timestamp = date('Y-m-d_H-i-s');
$filename = 'onecci_db_backup_' . $timestamp . '.sql';

// Get all tables
$tables = [];
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_array()) {
    $tables[] = $row[0];
}

// Start building SQL dump
$sqlDump = "-- OneCCI Database Backup\n";
$sqlDump .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
$sqlDump .= "-- Database: onecci_db\n\n";
$sqlDump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

foreach ($tables as $table) {
    // Get CREATE TABLE statement
    $createTableResult = $conn->query("SHOW CREATE TABLE `$table`");
    $createTableRow = $createTableResult->fetch_array();
    $sqlDump .= "\n-- Table: $table\n";
    $sqlDump .= "DROP TABLE IF EXISTS `$table`;\n";
    $sqlDump .= $createTableRow[1] . ";\n\n";
    
    // Get table data
    $dataResult = $conn->query("SELECT * FROM `$table`");
    if ($dataResult->num_rows > 0) {
        $sqlDump .= "-- Data for table: $table\n";
        while ($row = $dataResult->fetch_assoc()) {
            $sqlDump .= "INSERT INTO `$table` VALUES (";
            $values = [];
            foreach ($row as $value) {
                if ($value === null) {
                    $values[] = 'NULL';
                } else {
                    $values[] = "'" . $conn->real_escape_string($value) . "'";
                }
            }
            $sqlDump .= implode(', ', $values) . ");\n";
        }
        $sqlDump .= "\n";
    }
}

$sqlDump .= "SET FOREIGN_KEY_CHECKS=1;\n";

// Log the action
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
                               VALUES ('database_backup', 'superadmin', ?, ?)");
    $userId = $_SESSION['user_id'] ?? $_SESSION['id_number'] ?? 'SA001';
    $details = "Database backup downloaded: " . $filename;
    $logStmt->bind_param('ss', $userId, $details);
    $logStmt->execute();
} catch (Exception $e) {
    // Logging failed but continue with download
}

$conn->close();

// Trigger browser download with "Save As" dialog
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($sqlDump));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

echo $sqlDump;
ob_end_flush();
exit;
?>
