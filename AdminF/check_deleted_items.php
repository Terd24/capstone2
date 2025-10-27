<?php
session_start();

// Require Super Admin login
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../StudentLogin/db_conn.php';

header('Content-Type: application/json');

// Get the last check timestamp from request
$lastCheck = isset($_GET['last_check']) ? $_GET['last_check'] : date('Y-m-d H:i:s', strtotime('-1 hour'));

$response = [
    'new_students' => [],
    'new_employees' => [],
    'has_new_items' => false,
    'current_time' => date('Y-m-d H:i:s')
];

// Check for new deleted students
$stmt = $conn->prepare("SELECT id, id_number, first_name, last_name, middle_name, grade_level, academic_track, deleted_at, deleted_by, deleted_reason 
                        FROM student_account 
                        WHERE deleted_at IS NOT NULL AND deleted_at > ? 
                        ORDER BY deleted_at DESC");
if ($stmt) {
    $stmt->bind_param("s", $lastCheck);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // Check for pending requests
        $row['pending_restore'] = false;
        $row['pending_archive'] = false;
        
        $check_stmt = $conn->prepare("SELECT request_type FROM owner_approval_requests WHERE target_id = ? AND request_type IN ('restore_student', 'archive_student') AND status = 'pending' LIMIT 1");
        if ($check_stmt) {
            $check_stmt->bind_param("s", $row['id_number']);
            $check_stmt->execute();
            $check_res = $check_stmt->get_result();
            if ($check_row = $check_res->fetch_assoc()) {
                if ($check_row['request_type'] === 'restore_student') {
                    $row['pending_restore'] = true;
                } else if ($check_row['request_type'] === 'archive_student') {
                    $row['pending_archive'] = true;
                }
            }
            $check_stmt->close();
        }
        
        $response['new_students'][] = $row;
        $response['has_new_items'] = true;
    }
    $stmt->close();
}

// Check for new deleted employees
$stmt = $conn->prepare("SELECT e.id, e.id_number, e.first_name, e.last_name, e.middle_name, e.position, e.department, e.deleted_at, e.deleted_by, e.deleted_reason, ea.role 
                        FROM employees e 
                        LEFT JOIN employee_accounts ea ON e.id_number = ea.employee_id 
                        WHERE e.deleted_at IS NOT NULL AND e.deleted_at > ? 
                        ORDER BY e.deleted_at DESC");
if ($stmt) {
    $stmt->bind_param("s", $lastCheck);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // Check for pending requests
        $row['pending_restore'] = false;
        $row['pending_archive'] = false;
        
        $check_stmt = $conn->prepare("SELECT request_type FROM owner_approval_requests WHERE target_id = ? AND request_type IN ('restore_employee', 'archive_employee') AND status = 'pending' LIMIT 1");
        if ($check_stmt) {
            $check_stmt->bind_param("s", $row['id_number']);
            $check_stmt->execute();
            $check_res = $check_stmt->get_result();
            if ($check_row = $check_res->fetch_assoc()) {
                if ($check_row['request_type'] === 'restore_employee') {
                    $row['pending_restore'] = true;
                } else if ($check_row['request_type'] === 'archive_employee') {
                    $row['pending_archive'] = true;
                }
            }
            $check_stmt->close();
        }
        
        $response['new_employees'][] = $row;
        $response['has_new_items'] = true;
    }
    $stmt->close();
}

echo json_encode($response);
?>
