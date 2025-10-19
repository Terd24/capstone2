<?php
ob_start();
session_start();
ob_clean();
header('Content-Type: application/json');

// Only SuperAdmin can request maintenance mode toggle
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    ob_end_flush();
    exit;
}

require_once '../StudentLogin/db_conn.php';

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? ''; // 'enable' or 'disable'
$reason = $data['reason'] ?? '';

if (empty($action) || empty($reason)) {
    echo json_encode(['success' => false, 'message' => 'Action and reason are required']);
    ob_end_flush();
    exit;
}

if (!in_array($action, ['enable', 'disable'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    ob_end_flush();
    exit;
}

// Ensure approval requests table exists with all enum values
$conn->query("CREATE TABLE IF NOT EXISTS owner_approval_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_title VARCHAR(255) NOT NULL,
    request_description TEXT NOT NULL,
    request_type ENUM('delete_account', 'restore_account', 'system_maintenance', 'data_modification', 'user_management', 'add_hr_employee', 'delete_hr_employee', 'restore_student', 'restore_employee', 'archive_student', 'archive_employee', 'archive_login_logs', 'archive_attendance', 'database_backup', 'maintenance_mode_toggle', 'other') NOT NULL,
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    requester_name VARCHAR(100) NOT NULL,
    requester_role VARCHAR(50) NOT NULL,
    requester_module VARCHAR(50) NOT NULL,
    target_table VARCHAR(50),
    target_id VARCHAR(50),
    target_data JSON,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    owner_comments TEXT,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    reviewed_by VARCHAR(100)
)");

// Create approval request for Owner
$actionText = ucfirst($action);
$request_title = "$actionText Maintenance Mode";
$request_description = "Request to $action system maintenance mode\n\nReason: $reason\n\nImpact: " . 
    ($action === 'enable' ? 'All users except admins will be blocked from accessing the system' : 'System will return to normal operation');
$request_type = 'maintenance_mode_toggle';
$priority = 'critical'; // Critical priority for system-wide impact
$requester_name = $_SESSION['superadmin_name'] ?? 'Super Admin';
$requester_role = 'superadmin';
$requester_module = 'System Configuration';
$target_id = 'maintenance_' . $action;
$target_data = json_encode(['action' => $action, 'reason' => $reason, 'requested_at' => date('Y-m-d H:i:s')]);

$approval_stmt = $conn->prepare("INSERT INTO owner_approval_requests (request_title, request_description, request_type, priority, requester_name, requester_role, requester_module, target_id, target_data, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");

if (!$approval_stmt) {
    error_log("Failed to prepare statement: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    ob_end_flush();
    exit;
}

$approval_stmt->bind_param("sssssssss", $request_title, $request_description, $request_type, $priority, $requester_name, $requester_role, $requester_module, $target_id, $target_data);

if ($approval_stmt->execute()) {
    $insert_id = $approval_stmt->insert_id;
    error_log("Maintenance mode toggle request created with ID: $insert_id");
    echo json_encode([
        'success' => true,
        'message' => "Request to $action maintenance mode submitted successfully! Waiting for Owner approval.",
        'requires_approval' => true,
        'request_id' => $insert_id,
        'action' => $action
    ]);
} else {
    error_log("Failed to execute statement: " . $approval_stmt->error);
    echo json_encode(['success' => false, 'message' => 'Failed to create approval request: ' . $approval_stmt->error]);
}

$approval_stmt->close();
$conn->close();
ob_end_flush();
