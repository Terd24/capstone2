<?php
session_start();
include("../StudentLogin/db_conn.php");

// Require Super Admin login
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Set content type to JSON
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

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
    $stmt = $conn->prepare("UPDATE employees SET deleted_at = NOW(), deleted_by = ?, deleted_reason = ? WHERE id_number = ?");
    $deleted_by = $_SESSION['superadmin_name'] ?? 'Super Admin';
    $deleted_reason = 'Deleted by Super Admin for administrative purposes';
    $stmt->bind_param("sss", $deleted_by, $deleted_reason, $employee_id);
    
    if ($stmt->execute()) {
        if ($conn->affected_rows > 0) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Employee soft deleted successfully']);
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
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
