<?php
ob_start();
session_start();
ob_clean();
header('Content-Type: application/json');

// Only SuperAdmin can request to clear attendance
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    ob_end_flush();
    exit;
}

require_once '../StudentLogin/db_conn.php';

$data = json_decode(file_get_contents('php://input'), true);
$start = $data['start_date'] ?? '';
$end = $data['end_date'] ?? '';
$reason = $data['reason'] ?? '';

if (empty($start) || empty($end) || empty($reason)) {
    echo json_encode(['success' => false, 'message' => 'Start date, end date, and reason are required']);
    ob_end_flush();
    exit;
}

// Ensure approval requests table exists
$conn->query("CREATE TABLE IF NOT EXISTS owner_approval_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_title VARCHAR(255) NOT NULL,
    request_description TEXT NOT NULL,
    request_type ENUM('delete_account', 'restore_account', 'system_maintenance', 'data_modification', 'user_management', 'add_hr_employee', 'delete_hr_employee', 'restore_student', 'restore_employee', 'archive_student', 'archive_employee', 'archive_login_logs', 'archive_attendance', 'other') NOT NULL,
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
$request_title = "Archive Attendance Records";
$request_description = "Request to archive attendance records from $start to $end\n\nReason: $reason";
$request_type = 'archive_attendance';
$priority = 'medium';
$requester_name = $_SESSION['superadmin_name'] ?? 'Super Admin';
$requester_role = 'superadmin';
$requester_module = 'System Maintenance';
$target_id = $start . '_to_' . $end;
$target_data = json_encode(['start_date' => $start, 'end_date' => $end, 'reason' => $reason]);

$approval_stmt = $conn->prepare("INSERT INTO owner_approval_requests (request_title, request_description, request_type, priority, requester_name, requester_role, requester_module, target_id, target_data, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
$approval_stmt->bind_param("sssssssss", $request_title, $request_description, $request_type, $priority, $requester_name, $requester_role, $requester_module, $target_id, $target_data);

if ($approval_stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Archive request submitted successfully! Waiting for Owner approval.',
        'requires_approval' => true
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to create approval request: ' . $approval_stmt->error]);
}

$approval_stmt->close();
$conn->close();
ob_end_flush();