<?php
session_start();

// Check if user is logged in as superadmin
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../StudentLogin/db_conn.php';

header('Content-Type: application/json');

// Get the last check timestamp from the request
$last_check = isset($_GET['last_check']) ? $_GET['last_check'] : date('Y-m-d H:i:s', strtotime('-5 minutes'));

// Get superadmin name
$superadmin_name = $_SESSION['superadmin_name'] ?? $_SESSION['username'] ?? 'Super Admin';

// Check for newly approved requests since last check
$query = "SELECT * FROM owner_approval_requests 
    WHERE status = 'approved' 
    AND requester_name = ? 
    AND reviewed_at > ? 
    ORDER BY reviewed_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $superadmin_name, $last_check);
$stmt->execute();
$result = $stmt->get_result();

$new_approvals = [];
while ($row = $result->fetch_assoc()) {
    $new_approvals[] = [
        'id' => $row['id'],
        'title' => $row['request_title'],
        'type' => $row['request_type'],
        'reviewedAt' => date('M d, Y h:i A', strtotime($row['reviewed_at'])),
        'reviewedBy' => $row['reviewed_by'],
        'requestDetails' => $row['request_details'] ?? '',
        'timestamp' => $row['reviewed_at']
    ];
}

echo json_encode([
    'success' => true,
    'approvals' => $new_approvals,
    'current_time' => date('Y-m-d H:i:s')
]);

$stmt->close();
$conn->close();
?>
