<?php
session_start();

// Check if user is logged in as owner
if (!isset($_SESSION['owner_id']) || $_SESSION['role'] !== 'owner') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../StudentLogin/db_conn.php';

header('Content-Type: application/json');

// Get the last check timestamp from the request
$last_check = isset($_GET['last_check']) ? $_GET['last_check'] : date('Y-m-d H:i:s', strtotime('-5 minutes'));

// Check for new pending requests since last check
$query = "SELECT * FROM owner_approval_requests 
    WHERE status = 'pending' 
    AND requested_at > ? 
    ORDER BY requested_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $last_check);
$stmt->execute();
$result = $stmt->get_result();

$new_requests = [];
while ($row = $result->fetch_assoc()) {
    $new_requests[] = [
        'id' => $row['id'],
        'title' => $row['request_title'],
        'description' => $row['request_description'],
        'type' => $row['request_type'],
        'priority' => $row['priority'],
        'requester' => $row['requester_name'],
        'requester_role' => $row['requester_role'],
        'requester_module' => $row['requester_module'],
        'target_id' => $row['target_id'],
        'target_data' => $row['target_data'],
        'request_details' => $row['request_details'],
        'requestedAt' => date('M d, Y h:i A', strtotime($row['requested_at'])),
        'timestamp' => $row['requested_at']
    ];
}

// Get current counts
$counts_query = "SELECT 
    COUNT(*) as total_requests,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_requests,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_requests
    FROM owner_approval_requests";

$counts_result = $conn->query($counts_query);
$counts = $counts_result->fetch_assoc();

echo json_encode([
    'success' => true,
    'new_requests' => $new_requests,
    'counts' => $counts,
    'current_time' => date('Y-m-d H:i:s')
]);

$stmt->close();
$conn->close();
?>
