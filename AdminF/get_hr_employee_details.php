<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../StudentLogin/db_conn.php';

$employeeId = $_GET['id'] ?? '';

if (empty($employeeId)) {
    echo json_encode(['success' => false, 'message' => 'Employee ID is required']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT * FROM employees WHERE id_number = ?");
    $stmt->bind_param('s', $employeeId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        exit;
    }
    
    $employee = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'employee' => $employee
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
