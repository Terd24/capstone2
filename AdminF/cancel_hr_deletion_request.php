<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || !in_array(strtolower($_SESSION['role']), ['superadmin', 'hr'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../StudentLogin/db_conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_deletion_request') {
    $employeeId = $_POST['employee_id'] ?? '';
    
    if (empty($employeeId)) {
        echo json_encode(['success' => false, 'message' => 'Employee ID is required']);
        exit;
    }
    
    try {
        // Get requester name based on role
        $user_role = strtolower($_SESSION['role']);
        if ($user_role === 'superadmin') {
            $requester_name = $_SESSION['superadmin_name'] ?? $_SESSION['username'] ?? 'Super Admin';
        } else {
            $requester_name = $_SESSION['hr_name'] ?? $_SESSION['username'] ?? 'HR Staff';
        }
        
        // Delete the pending request
        $stmt = $conn->prepare("DELETE FROM owner_approval_requests 
                                WHERE target_id = ? 
                                AND request_type IN ('hr_employee_deletion', 'delete_hr_employee') 
                                AND status = 'pending'
                                AND requester_name = ?");
        $stmt->bind_param('ss', $employeeId, $requester_name);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Deletion request cancelled successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'No pending deletion request found for this employee'
                ]);
            }
        } else {
            throw new Exception("Failed to cancel deletion request: " . $stmt->error);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

$conn->close();
?>
