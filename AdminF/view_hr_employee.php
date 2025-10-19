<?php
session_start();

// Check if user is logged in as superadmin
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../StudentLogin/db_conn.php';

header('Content-Type: application/json');

$employee_id = $_GET['id'] ?? '';

if (empty($employee_id)) {
    echo json_encode(['success' => false, 'message' => 'Employee ID is required']);
    exit;
}

try {
    // Get employee details
    $query = "SELECT e.*, ea.username, ea.role 
              FROM employees e
              LEFT JOIN employee_accounts ea ON e.id_number = ea.employee_id
              WHERE e.id_number = ? AND e.deleted_at IS NULL";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $employee = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'employee' => $employee
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Employee not found'
        ]);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
