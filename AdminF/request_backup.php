<?php
ob_start();
session_start();
ob_clean();
header('Content-Type: application/json');

// Only SuperAdmin can request database backup
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    ob_end_flush();
    exit;
}

require_once '../StudentLogin/db_conn.php';

$data = json_decode(file_get_contents('php://input'), true);
$reason = $data['reason'] ?? '';

if (empty($reason)) {
    echo json_encode(['success' => false, 'message' => 'Reason is required']);
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
$request_title = "Database Backup Request";
$request_description = "Request to download complete database backup\n\nReason: $reason";
$request_type = 'database_backup';
$priority = 'high'; // High priority for security-sensitive operation
$requester_name = $_SESSION['superadmin_name'] ?? 'Super Admin';
$requester_role = 'superadmin';
$requester_module = 'System Configuration';
$target_id = 'backup_' . date('Y-m-d_H-i-s');
$target_data = json_encode(['reason' => $reason, 'requested_at' => date('Y-m-d H:i:s')]);

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
    error_log("Database backup request created with ID: $insert_id");
    echo json_encode([
        'success' => true,
        'message' => 'Database backup request submitted successfully! Waiting for Owner approval.',
        'requires_approval' => true,
        'request_id' => $insert_id
    ]);
} else {
    error_log("Failed to execute statement: " . $approval_stmt->error);
    echo json_encode(['success' => false, 'message' => 'Failed to create approval request: ' . $approval_stmt->error]);
}

$approval_stmt->close();
$conn->close();
ob_end_flush();
