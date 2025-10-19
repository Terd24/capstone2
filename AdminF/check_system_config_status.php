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

$pending_maintenance = false;
$pending_backup = false;

// Check for pending maintenance mode toggle requests
$check_maintenance = $conn->query("
    SELECT id, request_type, status, target_data
    FROM owner_approval_requests 
    WHERE requester_role = 'superadmin' 
    AND request_type = 'maintenance_mode_toggle'
    AND status = 'pending'
    ORDER BY requested_at DESC
    LIMIT 1
");

if ($check_maintenance && $check_maintenance->num_rows > 0) {
    $pending_maintenance = true;
}

// Check for pending database backup requests
$check_backup = $conn->query("
    SELECT id, request_type, status
    FROM owner_approval_requests 
    WHERE requester_role = 'superadmin' 
    AND request_type = 'database_backup'
    AND status = 'pending'
    ORDER BY requested_at DESC
    LIMIT 1
");

if ($check_backup && $check_backup->num_rows > 0) {
    $pending_backup = true;
}

// Check for recently approved backup (within last 10 seconds) to trigger download
$approved_backup_filename = null;
$check_approved_backup = $conn->query("
    SELECT id, target_data, reviewed_at
    FROM owner_approval_requests 
    WHERE requester_role = 'superadmin' 
    AND request_type = 'database_backup'
    AND status = 'approved'
    AND reviewed_at > DATE_SUB(NOW(), INTERVAL 10 SECOND)
    ORDER BY reviewed_at DESC
    LIMIT 1
");

if ($check_approved_backup && $check_approved_backup->num_rows > 0) {
    $backup_row = $check_approved_backup->fetch_assoc();
    $backup_data = json_decode($backup_row['target_data'], true);
    if (isset($backup_data['backup_filename'])) {
        $approved_backup_filename = $backup_data['backup_filename'];
        error_log("Approved backup ready for download: $approved_backup_filename");
    }
}

// Get current maintenance mode status
$current_maintenance = '0';
$maintenance_result = $conn->query("SELECT config_value, updated_at FROM system_config WHERE config_key = 'maintenance_mode'");
if ($maintenance_result && $maintenance_result->num_rows > 0) {
    $row = $maintenance_result->fetch_assoc();
    $current_maintenance = $row['config_value'];
    error_log("Maintenance mode status: " . $current_maintenance . " (updated at: " . $row['updated_at'] . ")");
} else {
    error_log("No maintenance mode config found in database");
}

echo json_encode([
    'success' => true,
    'pending_maintenance' => $pending_maintenance,
    'pending_backup' => $pending_backup,
    'current_maintenance_mode' => $current_maintenance,
    'approved_backup_filename' => $approved_backup_filename,
    'debug' => [
        'has_config' => ($maintenance_result && $maintenance_result->num_rows > 0),
        'value' => $current_maintenance
    ]
]);

$conn->close();
?>
