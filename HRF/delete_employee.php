<?php
session_start();
include '../StudentLogin/db_conn.php';
include '../includes/log_system_notification.php';

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
        
        // Get employee info before deleting for notification
        $employee_info_stmt = $conn->prepare("SELECT first_name, last_name, id_number, position, department FROM employees WHERE id_number = ?");
        $employee_info_stmt->bind_param("s", $employee_id);
        $employee_info_stmt->execute();
        $employee_info_result = $employee_info_stmt->get_result();
        $employee_info = $employee_info_result->fetch_assoc();
        $employee_info_stmt->close();
        
        // Ensure soft delete columns exist in employees table
        $conn->query("ALTER TABLE employees ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL DEFAULT NULL");
        $conn->query("ALTER TABLE employees ADD COLUMN IF NOT EXISTS deleted_by VARCHAR(255) NULL");
        $conn->query("ALTER TABLE employees ADD COLUMN IF NOT EXISTS deletion_reason TEXT NULL");
        
        // Use soft delete instead of hard delete - mark as deleted but keep in database
        $delete_employee = $conn->prepare("UPDATE employees SET deleted_at = NOW(), deleted_by = ?, deletion_reason = ? WHERE id_number = ?");
        $deleted_by = $_SESSION['hr_name'] ?? $_SESSION['superadmin_name'] ?? 'HR User';
        $deletion_reason = 'Deleted by HR for administrative purposes';
        $delete_employee->bind_param("sss", $deleted_by, $deletion_reason, $employee_id);
        
        // Log the deletion attempt for debugging
        error_log("Attempting to soft delete employee ID: " . $employee_id . " by " . $deleted_by);
        
        if ($delete_employee->execute()) {
            if ($delete_employee->affected_rows > 0) {
                // Log system notification for Owner
                if ($employee_info) {
                    try {
                        $employee_name = $employee_info['first_name'] . ' ' . $employee_info['last_name'];
                        $notif_title = "Employee Account Deleted";
                        $notif_message = formatActionMessage('employee_deleted', $employee_name, $employee_id);
                        
                        logSystemNotification(
                            $conn,
                            $notif_title,
                            $notif_message,
                            'warning',
                            'HR',
                            $deleted_by,
                            'HR',
                            'employee_deleted',
                            'employees',
                            $employee_id,
                            $employee_info,
                            null
                        );
                    } catch (Exception $notif_error) {
                        error_log("Error logging notification: " . $notif_error->getMessage());
                    }
                }
                
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
