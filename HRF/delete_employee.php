<?php
session_start();
include '../StudentLogin/db_conn.php';

// Check if user is HR or Super Admin
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'hr' && $_SESSION['role'] !== 'superadmin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = $_POST['employee_id'] ?? '';
    
    if (empty($employee_id)) {
        echo json_encode(['success' => false, 'message' => 'Employee ID is required']);
        exit;
    }
    
    try {
        $conn->begin_transaction();
        
        // First, delete from employee_accounts table (if exists)
        $delete_account = $conn->prepare("DELETE FROM employee_accounts WHERE employee_id = ?");
        $delete_account->bind_param("s", $employee_id);
        $delete_account->execute();
        
        // Then delete from employee_attendance table (if exists)
        $delete_attendance = $conn->prepare("DELETE FROM employee_attendance WHERE employee_id = ?");
        $delete_attendance->bind_param("s", $employee_id);
        $delete_attendance->execute();
        
        // Finally, delete from employees table
        $delete_employee = $conn->prepare("DELETE FROM employees WHERE id_number = ?");
        $delete_employee->bind_param("s", $employee_id);
        
        if ($delete_employee->execute()) {
            if ($delete_employee->affected_rows > 0) {
                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Employee deleted successfully']);
            } else {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Employee not found']);
            }
        } else {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Failed to delete employee']);
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Delete employee error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
