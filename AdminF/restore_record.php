<?php
session_start();
header('Content-Type: application/json');

// Require Super Admin login
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

require_once '../StudentLogin/db_conn.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$record_type = $input['record_type'] ?? '';
$record_id = $input['record_id'] ?? '';

if ($action !== 'restore' || empty($record_type) || empty($record_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request parameters']);
    exit;
}

try {
    if ($record_type === 'student') {
        // Restore student record
        $stmt = $conn->prepare("UPDATE student_account 
                               SET deleted_at = NULL, 
                                   deleted_by = NULL, 
                                   deleted_reason = NULL 
                               WHERE id_number = ? AND deleted_at IS NOT NULL");
        $stmt->bind_param("s", $record_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            // Log the restoration (optional - won't fail if table doesn't exist)
            try {
                $conn->query("CREATE TABLE IF NOT EXISTS system_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    action VARCHAR(100) NOT NULL,
                    details TEXT,
                    performed_by VARCHAR(100),
                    performed_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )");
                
                $log_stmt = $conn->prepare("INSERT INTO system_logs (action, details, performed_by) 
                                           VALUES ('RESTORE_STUDENT', ?, ?)");
                $details = "Restored student record: " . $record_id;
                $performed_by = $_SESSION['superadmin_name'] ?? 'Super Admin';
                $log_stmt->bind_param("ss", $details, $performed_by);
                $log_stmt->execute();
            } catch (Exception $e) {
                // Logging failed but restoration succeeded
            }
            
            echo json_encode(['success' => true, 'message' => 'Student record restored successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Student record not found or already restored']);
        }
        
    } else if ($record_type === 'employee') {
        // Restore employee record
        $stmt = $conn->prepare("UPDATE employees 
                               SET deleted_at = NULL, 
                                   deleted_by = NULL, 
                                   deleted_reason = NULL 
                               WHERE id_number = ? AND deleted_at IS NOT NULL");
        $stmt->bind_param("s", $record_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            // Log the restoration (optional - won't fail if table doesn't exist)
            try {
                $conn->query("CREATE TABLE IF NOT EXISTS system_logs (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    action VARCHAR(100) NOT NULL,
                    details TEXT,
                    performed_by VARCHAR(100),
                    performed_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )");
                
                $log_stmt = $conn->prepare("INSERT INTO system_logs (action, details, performed_by) 
                                           VALUES ('RESTORE_EMPLOYEE', ?, ?)");
                $details = "Restored employee record: " . $record_id;
                $performed_by = $_SESSION['superadmin_name'] ?? 'Super Admin';
                $log_stmt->bind_param("ss", $details, $performed_by);
                $log_stmt->execute();
            } catch (Exception $e) {
                // Logging failed but restoration succeeded
            }
            
            echo json_encode(['success' => true, 'message' => 'Employee record restored successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Employee record not found or already restored']);
        }
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid record type']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
