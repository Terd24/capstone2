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
    'restored_employees' => [],
    'has_new_restorations' => false,
    'current_time' => date('Y-m-d H:i:s')
];

// Check for approved restore_employee requests since last check
// Join with employees table to get full details in one query
$stmt = $conn->prepare("SELECT oar.target_id, oar.reviewed_at,
                               e.id_number, e.first_name, e.last_name, e.middle_name, 
                               e.position, e.department, e.hire_date
                        FROM owner_approval_requests oar
                        INNER JOIN employees e ON oar.target_id = e.id_number
                        WHERE oar.request_type = 'restore_employee'
                        AND oar.status = 'approved'
                        AND oar.reviewed_at > ?
                        AND e.deleted_at IS NULL
                        ORDER BY oar.reviewed_at DESC");

if ($stmt) {
    $stmt->bind_param("s", $lastCheck);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $response['restored_employees'][] = $row;
        $response['has_new_restorations'] = true;
    }
    $stmt->close();
}

echo json_encode($response);
?>
