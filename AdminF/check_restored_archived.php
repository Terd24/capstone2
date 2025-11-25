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
    'restored_students' => [],
    'restored_employees' => [],
    'archived_students' => [],
    'archived_employees' => [],
    'has_changes' => false,
    'current_time' => date('Y-m-d H:i:s')
];

// Check for approved restore/archive requests since last check
$stmt = $conn->prepare("SELECT id, request_type, target_id, reviewed_at as approved_at 
                        FROM owner_approval_requests 
                        WHERE status = 'approved' 
                        AND reviewed_at > ? 
                        AND request_type IN ('restore_student', 'restore_employee', 'archive_student', 'archive_employee')
                        ORDER BY reviewed_at DESC");

if ($stmt) {
    $stmt->bind_param("s", $lastCheck);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $response['has_changes'] = true;
        
        switch ($row['request_type']) {
            case 'restore_student':
                $response['restored_students'][] = [
                    'id_number' => $row['target_id'],
                    'approved_at' => $row['approved_at']
                ];
                break;
                
            case 'restore_employee':
                $response['restored_employees'][] = [
                    'id_number' => $row['target_id'],
                    'approved_at' => $row['approved_at']
                ];
                break;
                
            case 'archive_student':
                $response['archived_students'][] = [
                    'id_number' => $row['target_id'],
                    'approved_at' => $row['approved_at']
                ];
                break;
                
            case 'archive_employee':
                $response['archived_employees'][] = [
                    'id_number' => $row['target_id'],
                    'approved_at' => $row['approved_at']
                ];
                break;
        }
    }
    $stmt->close();
}

// Also check for records that no longer have deleted_at (were restored directly)
// Check students that were deleted but now aren't
$stmt = $conn->prepare("SELECT s.id_number 
                        FROM student_account s
                        WHERE s.deleted_at IS NULL
                        AND EXISTS (
                            SELECT 1 FROM owner_approval_requests oar
                            WHERE oar.target_id = s.id_number
                            AND oar.request_type = 'restore_student'
                            AND oar.status = 'approved'
                            AND oar.reviewed_at > ?
                        )");
if ($stmt) {
    $stmt->bind_param("s", $lastCheck);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // Check if not already in the list
        $found = false;
        foreach ($response['restored_students'] as $restored) {
            if ($restored['id_number'] === $row['id_number']) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $response['restored_students'][] = [
                'id_number' => $row['id_number'],
                'approved_at' => date('Y-m-d H:i:s')
            ];
            $response['has_changes'] = true;
        }
    }
    $stmt->close();
}

// Check employees that were deleted but now aren't
$stmt = $conn->prepare("SELECT e.id_number 
                        FROM employees e
                        WHERE e.deleted_at IS NULL
                        AND EXISTS (
                            SELECT 1 FROM owner_approval_requests oar
                            WHERE oar.target_id = e.id_number
                            AND oar.request_type = 'restore_employee'
                            AND oar.status = 'approved'
                            AND oar.reviewed_at > ?
                        )");
if ($stmt) {
    $stmt->bind_param("s", $lastCheck);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // Check if not already in the list
        $found = false;
        foreach ($response['restored_employees'] as $restored) {
            if ($restored['id_number'] === $row['id_number']) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $response['restored_employees'][] = [
                'id_number' => $row['id_number'],
                'approved_at' => date('Y-m-d H:i:s')
            ];
            $response['has_changes'] = true;
        }
    }
    $stmt->close();
}

echo json_encode($response);
?>
