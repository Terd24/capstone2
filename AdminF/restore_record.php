<?php
// Prevent any output before JSON
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once '../StudentLogin/db_conn.php';

// Clear any output buffer
ob_end_clean();

// Set JSON header first
header('Content-Type: application/json');

// Require Super Admin login
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

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
                               WHERE id = ? AND deleted_at IS NOT NULL");
        $stmt->bind_param("i", $record_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            // Get the student's id_number for the response
            $id_stmt = $conn->prepare("SELECT id_number FROM student_account WHERE id = ?");
            $id_stmt->bind_param("i", $record_id);
            $id_stmt->execute();
            $id_result = $id_stmt->get_result();
            $student_id_number = $id_result->fetch_assoc()['id_number'] ?? $record_id;
            
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
                $details = "Restored student record ID: " . $record_id . " (" . $student_id_number . ")";
                $performed_by = $_SESSION['superadmin_name'] ?? 'Super Admin';
                $log_stmt->bind_param("ss", $details, $performed_by);
                $log_stmt->execute();
            } catch (Exception $e) {
                // Logging failed but restoration succeeded
            }
            
            echo json_encode(['success' => true, 'message' => 'Student record restored successfully', 'student_id' => $student_id_number]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Student record not found or already restored']);
        }
        
    } else if ($record_type === 'employee') {
        // Restore employee record
        $stmt = $conn->prepare("UPDATE employees 
                               SET deleted_at = NULL, 
                                   deleted_by = NULL, 
                                   deleted_reason = NULL 
                               WHERE id = ? AND deleted_at IS NOT NULL");
        $stmt->bind_param("i", $record_id);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            // Get the employee's id_number for the response
            $id_stmt = $conn->prepare("SELECT id_number FROM employees WHERE id = ?");
            $id_stmt->bind_param("i", $record_id);
            $id_stmt->execute();
            $id_result = $id_stmt->get_result();
            $employee_id_number = $id_result->fetch_assoc()['id_number'] ?? $record_id;
            
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
                $details = "Restored employee record ID: " . $record_id . " (" . $employee_id_number . ")";
                $performed_by = $_SESSION['superadmin_name'] ?? 'Super Admin';
                $log_stmt->bind_param("ss", $details, $performed_by);
                $log_stmt->execute();
            } catch (Exception $e) {
                // Logging failed but restoration succeeded
            }
            
            echo json_encode(['success' => true, 'message' => 'Employee record restored successfully', 'employee_id' => $employee_id_number]);
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
