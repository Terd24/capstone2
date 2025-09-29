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
    // Remove employee account (login access only)
    $stmt = $conn->prepare("DELETE FROM employee_accounts WHERE employee_id = ?");
    $stmt->bind_param("s", $employee_id);
    
    if ($stmt->execute()) {
        if ($conn->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Login access removed successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No account found for this employee']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove access']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
