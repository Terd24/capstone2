<?php
session_start();
require_once '../StudentLogin/db_conn.php';

header('Content-Type: application/json');

// Check if user is Super Admin
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$response = ['success' => true, 'tables' => []];

try {
    // Check archived_students table
    $result = $conn->query("SHOW TABLES LIKE 'archived_students'");
    $response['tables']['archived_students'] = $result->num_rows > 0;
    
    if ($result->num_rows > 0) {
        $columns = $conn->query("DESCRIBE archived_students");
        $response['archived_students_columns'] = [];
        while ($col = $columns->fetch_assoc()) {
            $response['archived_students_columns'][] = $col['Field'];
        }
    }
    
    // Check archived_employees table
    $result = $conn->query("SHOW TABLES LIKE 'archived_employees'");
    $response['tables']['archived_employees'] = $result->num_rows > 0;
    
    if ($result->num_rows > 0) {
        $columns = $conn->query("DESCRIBE archived_employees");
        $response['archived_employees_columns'] = [];
        while ($col = $columns->fetch_assoc()) {
            $response['archived_employees_columns'][] = $col['Field'];
        }
    }
    
    // Check archive_log table
    $result = $conn->query("SHOW TABLES LIKE 'archive_log'");
    $response['tables']['archive_log'] = $result->num_rows > 0;
    
    // Check for deleted students
    $result = $conn->query("SELECT COUNT(*) as count FROM student_account WHERE deleted_at IS NOT NULL");
    $row = $result->fetch_assoc();
    $response['deleted_students_count'] = $row['count'];
    
    // Get a sample deleted student if exists
    if ($row['count'] > 0) {
        $result = $conn->query("SELECT id, id_number, first_name, last_name, deleted_at FROM student_account WHERE deleted_at IS NOT NULL LIMIT 1");
        $response['sample_deleted_student'] = $result->fetch_assoc();
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = $e->getMessage();
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>
