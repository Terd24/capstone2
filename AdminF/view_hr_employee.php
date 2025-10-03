<?php
session_start();
include("../StudentLogin/db_conn.php");

// Require Super Admin access
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Employee ID not provided']);
    exit;
}

$employee_id = $_GET['id'];

// Fetch HR employee details (only active employees - not soft deleted)
$stmt = $conn->prepare("SELECT e.*, ea.username, ea.role as account_role 
                       FROM employees e 
                       LEFT JOIN employee_accounts ea ON e.id_number = ea.employee_id AND ea.role = 'hr'
                       WHERE e.id_number = ? AND e.deleted_at IS NULL
                       LIMIT 1");
$stmt->bind_param("s", $employee_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'HR Employee not found or database error']);
    exit;
}

$employee = $result->fetch_assoc();
echo json_encode(['success' => true, 'employee' => $employee]);
?>
