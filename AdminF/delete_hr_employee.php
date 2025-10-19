<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../StudentLogin/db_conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_hr_employee') {
    $employeeId = $_POST['employee_id'] ?? '';
    $deletionReason = $_POST['deletion_reason'] ?? 'Deleted by Super Admin';
    
    if (empty($employeeId)) {
        echo json_encode(['success' => false, 'message' => 'Employee ID is required']);
        exit;
    }
    
    try {
        // Check if employee exists
        $stmt = $conn->prepare("SELECT id, id_number, first_name, last_name FROM employees WHERE id_number = ? AND deleted_at IS NULL");
        $stmt->bind_param('s', $employeeId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Employee not found or already deleted']);
            exit;
        }
        
        $employee = $result->fetch_assoc();
        $stmt->close();
        
        // Soft delete the employee
        $deleted_by = $_SESSION['superadmin_name'] ?? 'Super Admin';
        $update_stmt = $conn->prepare("UPDATE employees SET deleted_at = NOW(), deleted_by = ?, deleted_reason = ? WHERE id_number = ?");
        $update_stmt->bind_param('sss', $deleted_by, $deletionReason, $employeeId);
        
        if ($update_stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Employee deleted successfully and moved to Deleted Items'
            ]);
        } else {
            throw new Exception("Failed to delete employee: " . $update_stmt->error);
        }
        
        $update_stmt->close();
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

$conn->close();
?>
