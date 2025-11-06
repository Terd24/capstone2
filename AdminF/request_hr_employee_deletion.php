<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || !in_array(strtolower($_SESSION['role']), ['superadmin', 'hr'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../StudentLogin/db_conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_hr_deletion') {
    $employeeId = $_POST['employee_id'] ?? '';
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
        // Check if employee exists and get full details
        $stmt = $conn->prepare("SELECT id, id_number, first_name, last_name, middle_name, position, department, 
                                       email, address, hire_date 
                                FROM employees WHERE id_number = ? AND deleted_at IS NULL");
        $stmt->bind_param('s', $employeeId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Employee not found or already deleted']);
            exit;
        }
        
        $employee = $result->fetch_assoc();
        $stmt->close();
        
        // Check if there's already a pending request for this employee
        $check_stmt = $conn->prepare("SELECT id FROM owner_approval_requests WHERE target_id = ? AND request_type = 'hr_employee_deletion' AND status = 'pending'");
        $check_stmt->bind_param('s', $employeeId);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'A deletion request for this employee is already pending approval']);
            exit;
        }
        $check_stmt->close();
        
        // Create approval request table if not exists
        $conn->query("CREATE TABLE IF NOT EXISTS owner_approval_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            request_type VARCHAR(50) NOT NULL,
            request_title VARCHAR(255) NOT NULL,
            request_description TEXT,
            requester_name VARCHAR(100) NOT NULL,
            requester_role VARCHAR(50) NOT NULL,
            requester_module VARCHAR(50) NOT NULL,
            target_id VARCHAR(100),
            target_data JSON,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
            requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            reviewed_at DATETIME NULL,
            reviewed_by VARCHAR(100) NULL,
            owner_comments TEXT NULL,
            INDEX idx_status (status),
            INDEX idx_type (request_type),
            INDEX idx_requested_at (requested_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        // Add request_details column if it doesn't exist
        $conn->query("ALTER TABLE owner_approval_requests ADD COLUMN IF NOT EXISTS request_details JSON");
        
        // Prepare request data
        $employee_name = $employee['first_name'] . ' ' . $employee['last_name'];
        $user_role = strtolower($_SESSION['role']);
        
        if ($user_role === 'superadmin') {
            $requester_name = $_SESSION['superadmin_name'] ?? $_SESSION['username'] ?? 'Super Admin';
            $requester_role = 'Super Admin';
        } else {
            $requester_name = $_SESSION['hr_name'] ?? $_SESSION['username'] ?? 'HR Staff';
            $requester_role = 'HR';
        }
        
        $requester_module = 'HR Management';
        
        $request_title = "Delete Employee: " . $employee_name;
        $request_description = "Request to delete employee account for " . $employee_name . " (ID: " . $employeeId . ")";
        
        $target_data = json_encode([
            'id_number' => $employeeId,
            'employee_id' => $employeeId,
            'employee_name' => $employee_name,
            'first_name' => $employee['first_name'],
            'middle_name' => $employee['middle_name'] ?? '',
            'last_name' => $employee['last_name'],
            'position' => $employee['position'] ?? '',
            'department' => $employee['department'] ?? '',
            'email' => $employee['email'] ?? '',
            'address' => $employee['address'] ?? '',
            'hire_date' => $employee['hire_date'] ?? ''
        ]);
        
        $request_details = json_encode([
            'deletion_reason' => $deletionReason,
            'requested_by' => $requester_name,
            'requested_at' => date('Y-m-d H:i:s')
        ]);
        
        // Insert approval request
        $insert_stmt = $conn->prepare("INSERT INTO owner_approval_requests 
            (request_type, request_title, request_description, requester_name, requester_role, requester_module, 
             target_id, target_data, request_details, priority) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'high')");
        
        $request_type = 'hr_employee_deletion';
        $insert_stmt->bind_param('sssssssss', 
            $request_type, 
            $request_title, 
            $request_description, 
            $requester_name, 
            $requester_role, 
            $requester_module, 
            $employeeId, 
            $target_data, 
            $request_details
        );
        
        if ($insert_stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Deletion request sent to Owner for approval',
                'request_id' => $insert_stmt->insert_id
            ]);
        } else {
            throw new Exception("Failed to create approval request: " . $insert_stmt->error);
        }
        
        $insert_stmt->close();
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

$conn->close();
?>
