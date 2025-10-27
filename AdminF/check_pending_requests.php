<?php
session_start();

// Check if user is logged in as superadmin or HR
if (!isset($_SESSION['role']) || !in_array(strtolower($_SESSION['role']), ['superadmin', 'hr'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../StudentLogin/db_conn.php';

header('Content-Type: application/json');

// Get requester name based on role
$user_role = strtolower($_SESSION['role']);
if ($user_role === 'superadmin') {
    $requester_name = $_SESSION['superadmin_name'] ?? $_SESSION['username'] ?? 'Super Admin';
} else {
    $requester_name = $_SESSION['hr_name'] ?? $_SESSION['username'] ?? 'HR Staff';
}

// Get all pending requests for this user
$query = "SELECT * FROM owner_approval_requests 
    WHERE status = 'pending' 
    AND requester_name = ? 
    ORDER BY requested_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $requester_name);
$stmt->execute();
$result = $stmt->get_result();

$pending_requests = [];
while ($row = $result->fetch_assoc()) {
    $pending_requests[] = [
        'id' => $row['id'],
        'request_type' => $row['request_type'],
        'target_id' => $row['target_id'],
        'target_data' => $row['target_data'],
        'requested_at' => $row['requested_at']
    ];
}

echo json_encode([
    'success' => true,
    'pending_requests' => $pending_requests
]);

$stmt->close();
$conn->close();
?>
