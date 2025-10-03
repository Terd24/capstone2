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
        
        // Ensure soft delete columns exist in employees table
        $conn->query("ALTER TABLE employees ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL DEFAULT NULL");
        $conn->query("ALTER TABLE employees ADD COLUMN IF NOT EXISTS deleted_by VARCHAR(255) NULL");
        $conn->query("ALTER TABLE employees ADD COLUMN IF NOT EXISTS deleted_reason TEXT NULL");
        
        // Use soft delete instead of hard delete - mark as deleted but keep in database
        $delete_employee = $conn->prepare("UPDATE employees SET deleted_at = NOW(), deleted_by = ?, deleted_reason = ? WHERE id_number = ? AND deleted_at IS NULL");
        $deleted_by = $_SESSION['hr_name'] ?? $_SESSION['superadmin_name'] ?? 'HR User';
        $deleted_reason = 'Deleted by HR for administrative purposes';
        $delete_employee->bind_param("sss", $deleted_by, $deleted_reason, $employee_id);
        
        if ($delete_employee->execute()) {
            if ($delete_employee->affected_rows > 0) {
                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Employee account deleted successfully! The record has been moved to Super Admin for review.']);
            } else {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Employee not found or already deleted']);
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
