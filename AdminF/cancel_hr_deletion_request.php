<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || !in_array(strtolower($_SESSION['role']), ['superadmin', 'hr', 'registrar'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../StudentLogin/db_conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Support both old format (action + employee_id) and new format (target_id + request_type)
    $action = $_POST['action'] ?? '';
    $targetId = $_POST['target_id'] ?? $_POST['employee_id'] ?? '';
    $requestType = $_POST['request_type'] ?? '';
    
    if (empty($targetId)) {
        echo json_encode(['success' => false, 'message' => 'Target ID is required']);
        exit;
    }
    
    try {
        // Get requester name based on role
        $user_role = strtolower($_SESSION['role']);
        if ($user_role === 'superadmin') {
            $requester_name = $_SESSION['superadmin_name'] ?? $_SESSION['username'] ?? 'Super Admin';
        } elseif ($user_role === 'hr') {
            $requester_name = $_SESSION['hr_name'] ?? $_SESSION['username'] ?? 'HR Staff';
        } else {
            $requester_name = $_SESSION['registrar_name'] ?? $_SESSION['username'] ?? 'Registrar';
        }
        
        // Determine which request types to cancel based on the request_type parameter
        if ($requestType === 'student_deletion') {
            $request_types = ['student_deletion'];
        } else {
            // Default to HR employee deletion types
            $request_types = ['hr_employee_deletion', 'delete_hr_employee'];
        }
        
        // Build the IN clause for request types
        $placeholders = implode(',', array_fill(0, count($request_types), '?'));
        
        // Delete the pending request
        $query = "DELETE FROM owner_approval_requests 
                  WHERE target_id = ? 
                  AND request_type IN ($placeholders) 
                  AND status = 'pending'
                  AND requester_name = ?";
        
        $stmt = $conn->prepare($query);
        
        // Bind parameters dynamically
        $types = 's' . str_repeat('s', count($request_types)) . 's';
        $params = array_merge([$targetId], $request_types, [$requester_name]);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $entityType = ($requestType === 'student_deletion') ? 'student' : 'employee';
                echo json_encode([
                    'success' => true,
                    'message' => 'Deletion request cancelled successfully'
                ]);
            } else {
                $entityType = ($requestType === 'student_deletion') ? 'student' : 'employee';
                echo json_encode([
                    'success' => false,
                    'message' => "No pending deletion request found for this $entityType"
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
