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

// Get current employee IDs from the request (to check if they've been restored)
$currentIds = isset($_GET['current_ids']) ? explode(',', $_GET['current_ids']) : [];
$currentIds = array_filter($currentIds, function($id) { return !empty(trim($id)); });

$response = [
    'restored_employees' => [],
    'has_new_restorations' => false,
    'current_time' => date('Y-m-d H:i:s')
];

// Method 1: Check for approved restore requests since last check
$stmt = $conn->prepare("SELECT DISTINCT oar.target_id
                        FROM owner_approval_requests oar
                        WHERE oar.request_type = 'restore_employee'
                        AND oar.status = 'approved'
                        AND oar.approved_at > ?");

$restoredIds = [];
if ($stmt) {
    $stmt->bind_param("s", $lastCheck);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $restoredIds[] = $row['target_id'];
    }
    $stmt->close();
}

// Method 2: Check if any employees that were NOT in the current list are now active
// This catches employees that were restored while we weren't watching
if (!empty($currentIds)) {
    // Get all active employees
    $placeholders = implode(',', array_fill(0, count($currentIds), '?'));
    $query = "SELECT e.id_number
              FROM employees e
              WHERE e.deleted_at IS NULL
              AND e.id_number NOT IN ($placeholders)";
    
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $types = str_repeat('s', count($currentIds));
        $stmt->bind_param($types, ...$currentIds);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            // Check if this employee was recently restored
            $check_stmt = $conn->prepare("SELECT 1 FROM owner_approval_requests 
                                          WHERE target_id = ? 
                                          AND request_type = 'restore_employee' 
                                          AND status = 'approved' 
                                          AND approved_at > ?");
            if ($check_stmt) {
                $check_stmt->bind_param("ss", $row['id_number'], $lastCheck);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                if ($check_result->num_rows > 0) {
                    $restoredIds[] = $row['id_number'];
                }
                $check_stmt->close();
            }
        }
        $stmt->close();
    }
}

// Get full details for all restored employees
$restoredIds = array_unique($restoredIds);
foreach ($restoredIds as $employee_id) {
    $emp_stmt = $conn->prepare("SELECT e.id, e.id_number, e.first_name, e.last_name, e.middle_name, 
                                        e.position, e.department, e.contact_number, e.email, 
                                        e.address, e.date_of_birth, e.hire_date, e.employment_status,
                                        ea.role, ea.username
                                 FROM employees e
                                 LEFT JOIN employee_accounts ea ON e.id_number = ea.employee_id
                                 WHERE e.id_number = ? AND e.deleted_at IS NULL");
    
    if ($emp_stmt) {
        $emp_stmt->bind_param("s", $employee_id);
        $emp_stmt->execute();
        $emp_result = $emp_stmt->get_result();
        
        if ($emp_row = $emp_result->fetch_assoc()) {
            $response['restored_employees'][] = $emp_row;
            $response['has_new_restorations'] = true;
        }
        
        $emp_stmt->close();
    }
}

echo json_encode($response);
?>
