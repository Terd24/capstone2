<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../StudentLogin/db_conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_delete_hr_request') {
    $employeeId = $_POST['employee_id'] ?? '';
    $employeeName = $_POST['employee_name'] ?? '';
    $position = $_POST['position'] ?? '';
    $department = $_POST['department'] ?? '';
    $deletionReason = $_POST['deletion_reason'] ?? '';
    
    if (empty($employeeId)) {
        echo json_encode(['success' => false, 'message' => 'Employee ID is required']);
        exit;
    }
    
    if (empty($deletionReason)) {
        echo json_encode(['success' => false, 'message' => 'Deletion reason is required']);
        exit;
    }
    
    try {
        // Get full employee details
        $stmt = $conn->prepare("SELECT * FROM employees WHERE id_number = ?");
        $stmt->bind_param('s', $employeeId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Employee not found']);
            exit;
        }
        
        $employee = $result->fetch_assoc();
        $stmt->close();
        
        // Create approval request
        $request_title = "Delete HR Employee: $employeeName";
        $request_description = "Request to delete HR employee with ID: $employeeId ($position - $department)";
        $request_type = 'delete_hr_employee';
        $priority = 'high';
        $requester_name = $_SESSION['superadmin_name'] ?? 'Super Admin';
        $requester_role = 'superadmin';
        $requester_module = 'HR Management';
        
        // Add deletion reason to employee data
        $employee['deletion_reason'] = $deletionReason;
        $target_data = json_encode($employee);
        
        // Ensure table exists
        $conn->query("CREATE TABLE IF NOT EXISTS owner_approval_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            request_title VARCHAR(255) NOT NULL,
            request_description TEXT NOT NULL,
            request_type ENUM('delete_account', 'restore_account', 'system_maintenance', 'data_modification', 'user_management', 'add_hr_employee', 'delete_hr_employee', 'other') NOT NULL,
            priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
            requester_name VARCHAR(100) NOT NULL,
            requester_role VARCHAR(50) NOT NULL,
            requester_module VARCHAR(50) NOT NULL,
            target_table VARCHAR(50),
            target_id VARCHAR(50),
            target_data JSON,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            owner_comments TEXT,
            requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            reviewed_at TIMESTAMP NULL,
            reviewed_by VARCHAR(100)
        )");
        
        // Insert approval request
        $approval_stmt = $conn->prepare("INSERT INTO owner_approval_requests (request_title, request_description, request_type, priority, requester_name, requester_role, requester_module, target_id, target_data, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        $approval_stmt->bind_param("sssssssss", $request_title, $request_description, $request_type, $priority, $requester_name, $requester_role, $requester_module, $employeeId, $target_data);
        
        if ($approval_stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Delete HR Employee request submitted successfully! Waiting for Owner approval.'
            ]);
        } else {
            throw new Exception("Failed to create approval request: " . $approval_stmt->error);
        }
        
        $approval_stmt->close();
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

$conn->close();
?>
