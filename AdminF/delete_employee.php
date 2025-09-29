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
    
    // Delete employee account first (if exists)
    $stmt = $conn->prepare("DELETE FROM employee_accounts WHERE employee_id = ?");
    $stmt->bind_param("s", $employee_id);
    $stmt->execute();
    
    // Delete employee record
    $stmt = $conn->prepare("DELETE FROM employees WHERE id_number = ?");
    $stmt->bind_param("s", $employee_id);
    
    if ($stmt->execute()) {
        if ($conn->affected_rows > 0) {
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
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
