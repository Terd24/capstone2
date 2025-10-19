<?php
session_start();
header('Content-Type: application/json');

// Check if user is Super Admin
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../StudentLogin/db_conn.php';

// Check for pending OR recently approved (within last 5 minutes) archive requests
// This prevents re-submitting while the approved request is being processed
$pending_login_logs = false;
$pending_attendance = false;
$recently_reviewed = [];

// Debug: Get all archive requests to see what's in the database
$debug_query = $conn->query("
    SELECT id, request_type, status, requested_at, reviewed_at 
    FROM owner_approval_requests 
    WHERE requester_role = 'superadmin' 
    AND (request_type = 'archive_login_logs' OR request_type = 'archive_attendance')
    ORDER BY requested_at DESC
    LIMIT 5
");

$all_requests = [];
if ($debug_query) {
    while ($row = $debug_query->fetch_assoc()) {
        $all_requests[] = $row;
    }
}

// Only check for PENDING requests (not approved/rejected)
$check_pending = $conn->query("
    SELECT id, request_type, status 
    FROM owner_approval_requests 
    WHERE requester_role = 'superadmin' 
    AND (request_type = 'archive_login_logs' OR request_type = 'archive_attendance')
    AND status = 'pending'
");

if ($check_pending && $check_pending->num_rows > 0) {
    while ($row = $check_pending->fetch_assoc()) {
        if ($row['request_type'] === 'archive_login_logs') {
            $pending_login_logs = true;
        }
        if ($row['request_type'] === 'archive_attendance') {
            $pending_attendance = true;
        }
    }
}

// Check for recently reviewed requests (approved or rejected within last 10 seconds)
// This will trigger notifications
$check_reviewed = $conn->query("
    SELECT id, request_type, status, reviewed_at, reviewed_by, owner_comments 
    FROM owner_approval_requests 
    WHERE requester_role = 'superadmin' 
    AND (request_type = 'archive_login_logs' OR request_type = 'archive_attendance')
    AND status IN ('approved', 'rejected')
    AND reviewed_at > DATE_SUB(NOW(), INTERVAL 10 SECOND)
    ORDER BY reviewed_at DESC
");

if ($check_reviewed && $check_reviewed->num_rows > 0) {
    while ($row = $check_reviewed->fetch_assoc()) {
        $recently_reviewed[] = [
            'id' => $row['id'],
            'type' => $row['request_type'],
            'status' => $row['status'],
            'reviewed_by' => $row['reviewed_by'],
            'comments' => $row['owner_comments'],
            'reviewed_at' => $row['reviewed_at']
        ];
    }
}

echo json_encode([
    'success' => true,
    'pending_login_logs' => $pending_login_logs,
    'pending_attendance' => $pending_attendance,
    'recently_reviewed' => $recently_reviewed,
    'debug_all_requests' => $all_requests  // Add debug info
]);

$conn->close();
?>
