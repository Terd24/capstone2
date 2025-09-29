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
    // Search for employee and check account status
    $stmt = $conn->prepare("
        SELECT e.*, ea.username, ea.role 
        FROM employees e 
        LEFT JOIN employee_accounts ea ON e.id_number = ea.employee_id 
        WHERE e.id_number = ?
    ");
    $stmt->bind_param("s", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        exit;
    }
    
    $employee = $result->fetch_assoc();
    
    // Determine account status
    if ($employee['username']) {
        $account_status = strtoupper($employee['role']) . ' Account';
    } else {
        $account_status = 'No Account';
    }
    
    $employee['account_status'] = $account_status;
    
    echo json_encode([
        'success' => true,
        'employee' => $employee
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
