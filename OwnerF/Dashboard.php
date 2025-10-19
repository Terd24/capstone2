<?php
session_start();
include("../StudentLogin/db_conn.php");

// Require owner login
if (!isset($_SESSION['owner_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: ../admin_login.php");
    exit;
}

// Prevent caching (so back button after logout doesn't show dashboard)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Ensure soft delete columns exist in employees and employee_accounts tables
$conn->query("ALTER TABLE employees 
              ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL DEFAULT NULL,
              ADD COLUMN IF NOT EXISTS deleted_by VARCHAR(100) NULL DEFAULT NULL,
              ADD COLUMN IF NOT EXISTS deletion_reason TEXT NULL DEFAULT NULL");

$conn->query("ALTER TABLE employee_accounts 
              ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL DEFAULT NULL,
              ADD COLUMN IF NOT EXISTS deleted_by VARCHAR(100) NULL DEFAULT NULL,
              ADD COLUMN IF NOT EXISTS deletion_reason TEXT NULL DEFAULT NULL");

// Create necessary tables if they don't exist
$conn->query("CREATE TABLE IF NOT EXISTS system_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'success', 'error', 'critical') DEFAULT 'info',
    module VARCHAR(50) NOT NULL,
    performed_by VARCHAR(100) NOT NULL,
    user_role VARCHAR(50) NOT NULL,
    target_table VARCHAR(50),
    target_id VARCHAR(50),
    action_type VARCHAR(50) NOT NULL,
    old_data JSON,
    new_data JSON,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS owner_approval_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_title VARCHAR(255) NOT NULL,
    request_description TEXT NOT NULL,
    request_type ENUM('delete_account', 'restore_account', 'system_maintenance', 'data_modification', 'user_management', 'add_hr_employee', 'delete_hr_employee', 'other') NOT NULL,
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

// Alter existing table to add new enum values if they don't exist
$conn->query("ALTER TABLE owner_approval_requests MODIFY request_type ENUM('delete_account', 'restore_account', 'system_maintenance', 'data_modification', 'user_management', 'add_hr_employee', 'delete_hr_employee', 'restore_student', 'restore_employee', 'archive_student', 'archive_employee', 'other') NOT NULL");

// Handle approval/rejection actions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $request_id = intval($_POST['request_id']);
    $action = $_POST['action']; // 'approve' or 'reject'
    $comments = trim($_POST['comments'] ?? '');
    
    if ($action === 'approve' || $action === 'reject') {
        $status = ($action === 'approve') ? 'approved' : 'rejected';
        $stmt = $conn->prepare("UPDATE owner_approval_requests SET status = ?, reviewed_at = NOW(), reviewed_by = ?, owner_comments = ? WHERE id = ?");
        $stmt->bind_param("sssi", $status, $_SESSION['owner_name'], $comments, $request_id);
        
        if ($stmt->execute()) {
            // Get request details for notification
            $req_stmt = $conn->prepare("SELECT * FROM owner_approval_requests WHERE id = ?");
            $req_stmt->bind_param("i", $request_id);
            $req_stmt->execute();
            $request_data = $req_stmt->get_result()->fetch_assoc();
            
            // If approved, execute the action
            if ($action === 'approve') {
                $request_type = $request_data['request_type'];
                $target_id = $request_data['target_id'];
                $target_data = json_decode($request_data['target_data'], true);
                
                try {
                    switch ($request_type) {
                        case 'add_hr_employee':
                            // Add the HR employee
                            if ($target_data) {
                                $conn->begin_transaction();
                                
                                // Extract variables for bind_param
                                $emp_id_number = $target_data['id_number'];
                                $emp_first_name = $target_data['first_name'];
                                $emp_middle_name = $target_data['middle_name'];
                                $emp_last_name = $target_data['last_name'];
                                $emp_position = $target_data['position'];
                                $emp_department = $target_data['department'];
                                $emp_email = $target_data['email'];
                                $emp_phone = $target_data['phone'];
                                $emp_address = $target_data['address'];
                                $emp_hire_date = $target_data['hire_date'];
                                
                                // Insert employee
                                $emp_stmt = $conn->prepare("INSERT INTO employees (id_number, first_name, middle_name, last_name, position, department, email, phone, address, hire_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                                $emp_stmt->bind_param("ssssssssss", 
                                    $emp_id_number,
                                    $emp_first_name,
                                    $emp_middle_name,
                                    $emp_last_name,
                                    $emp_position,
                                    $emp_department,
                                    $emp_email,
                                    $emp_phone,
                                    $emp_address,
                                    $emp_hire_date
                                );
                                $emp_stmt->execute();
                                
                                // Create account
                                $hashed_password = password_hash($target_data['password'], PASSWORD_DEFAULT);
                                $emp_username = $target_data['username'];
                                $emp_role = $target_data['role'];
                                
                                $acc_stmt = $conn->prepare("INSERT INTO employee_accounts (employee_id, username, password, role) VALUES (?, ?, ?, ?)");
                                $acc_stmt->bind_param("ssss", 
                                    $emp_id_number,
                                    $emp_username,
                                    $hashed_password,
                                    $emp_role
                                );
                                $acc_stmt->execute();
                                
                                $conn->commit();
                            }
                            break;
                            
                        case 'delete_hr_employee':
                            // Soft delete HR employee and their account
                            $conn->begin_transaction();
                            
                            // Get deletion reason from target_data
                            $deletion_reason = $target_data['deletion_reason'] ?? 'Approved by Owner';
                            $deleted_by = $_SESSION['owner_name'] ?? 'Owner';
                            
                            // Soft delete employee account first
                            $del_acc_stmt = $conn->prepare("UPDATE employee_accounts SET deleted_at = NOW(), deleted_by = ?, deletion_reason = ? WHERE employee_id = ?");
                            if ($del_acc_stmt) {
                                $del_acc_stmt->bind_param('sss', $deleted_by, $deletion_reason, $target_id);
                                if (!$del_acc_stmt->execute()) {
                                    error_log("Failed to soft delete employee account: " . $del_acc_stmt->error);
                                }
                                $del_acc_stmt->close();
                            }
                            
                            // Soft delete employee record
                            $del_emp_stmt = $conn->prepare("UPDATE employees SET deleted_at = NOW(), deleted_by = ?, deletion_reason = ? WHERE id_number = ?");
                            if ($del_emp_stmt) {
                                $del_emp_stmt->bind_param('sss', $deleted_by, $deletion_reason, $target_id);
                                if (!$del_emp_stmt->execute()) {
                                    error_log("Failed to soft delete employee: " . $del_emp_stmt->error);
                                    throw new Exception("Failed to delete employee");
                                }
                                $affected = $del_emp_stmt->affected_rows;
                                error_log("Soft deleted employee $target_id - Affected rows: $affected");
                                $del_emp_stmt->close();
                            }
                            
                            $conn->commit();
                            break;
                            
                        case 'restore_student':
                            // Restore student from soft delete
                            error_log("Owner approving restore for student: $target_id");
                            $conn->begin_transaction();
                            
                            $restore_stmt = $conn->prepare("UPDATE student_account SET deleted_at = NULL, deleted_by = NULL, deleted_reason = NULL WHERE id_number = ?");
                            if ($restore_stmt) {
                                $restore_stmt->bind_param('s', $target_id);
                                if ($restore_stmt->execute()) {
                                    $affected = $restore_stmt->affected_rows;
                                    error_log("Restored student $target_id - Affected rows: $affected");
                                    if ($affected === 0) {
                                        error_log("WARNING: No student found with id_number: $target_id");
                                    }
                                } else {
                                    error_log("Failed to restore student: " . $restore_stmt->error);
                                    throw new Exception("Failed to restore student");
                                }
                                $restore_stmt->close();
                            }
                            
                            $conn->commit();
                            break;
                            
                        case 'restore_employee':
                            // Restore employee from soft delete
                            error_log("Owner approving restore for employee: $target_id");
                            $conn->begin_transaction();
                            
                            $restore_stmt = $conn->prepare("UPDATE employees SET deleted_at = NULL, deleted_by = NULL, deleted_reason = NULL WHERE id_number = ?");
                            if ($restore_stmt) {
                                $restore_stmt->bind_param('s', $target_id);
                                if ($restore_stmt->execute()) {
                                    $affected = $restore_stmt->affected_rows;
                                    error_log("Restored employee $target_id - Affected rows: $affected");
                                    if ($affected === 0) {
                                        error_log("WARNING: No employee found with id_number: $target_id");
                                    }
                                } else {
                                    error_log("Failed to restore employee: " . $restore_stmt->error);
                                    throw new Exception("Failed to restore employee");
                                }
                                $restore_stmt->close();
                            }
                            
                            $conn->commit();
                            break;
                            
                        case 'archive_student':
                            // Permanently archive student
                            error_log("Owner approving archive for student: $target_id");
                            $conn->begin_transaction();
                            
                            // Ensure archived_students table exists
                            $conn->query("CREATE TABLE IF NOT EXISTS archived_students (
                                archive_id INT AUTO_INCREMENT PRIMARY KEY,
                                original_id INT,
                                lrn VARCHAR(50),
                                password VARCHAR(255),
                                academic_track VARCHAR(100),
                                enrollment_status VARCHAR(50),
                                school_type VARCHAR(50),
                                last_name VARCHAR(100),
                                first_name VARCHAR(100),
                                middle_name VARCHAR(100),
                                school_year VARCHAR(20),
                                grade_level VARCHAR(50),
                                semester VARCHAR(20),
                                dob DATE,
                                birthplace VARCHAR(255),
                                gender VARCHAR(20),
                                religion VARCHAR(100),
                                credentials TEXT,
                                payment_mode VARCHAR(50),
                                address TEXT,
                                father_name VARCHAR(100),
                                father_occupation VARCHAR(100),
                                father_contact VARCHAR(50),
                                mother_name VARCHAR(100),
                                mother_occupation VARCHAR(100),
                                mother_contact VARCHAR(50),
                                guardian_name VARCHAR(100),
                                guardian_occupation VARCHAR(100),
                                guardian_contact VARCHAR(50),
                                last_school VARCHAR(255),
                                last_school_year VARCHAR(20),
                                id_number VARCHAR(50),
                                username VARCHAR(100),
                                rfid_uid VARCHAR(50),
                                created_at DATETIME,
                                class_schedule TEXT,
                                deleted_at DATETIME,
                                deleted_by VARCHAR(100),
                                deleted_reason TEXT,
                                must_change_password TINYINT(1) DEFAULT 0,
                                archived_at DATETIME,
                                archived_by VARCHAR(100),
                                archive_reason TEXT,
                                INDEX(id_number),
                                INDEX(archived_at)
                            )");
                            
                            // Get full student data
                            $get_stmt = $conn->prepare("SELECT * FROM student_account WHERE id_number = ?");
                            $get_stmt->bind_param('s', $target_id);
                            $get_stmt->execute();
                            $student_result = $get_stmt->get_result();
                            
                            if ($student_result->num_rows > 0) {
                                $student = $student_result->fetch_assoc();
                                error_log("Found student to archive: " . $student['first_name'] . " " . $student['last_name']);
                                
                                // Insert into archive table
                                $archive_stmt = $conn->prepare("INSERT INTO archived_students (
                                    original_id, lrn, password, academic_track, enrollment_status, school_type,
                                    last_name, first_name, middle_name, school_year, grade_level, semester,
                                    dob, birthplace, gender, religion, credentials, payment_mode, address,
                                    father_name, father_occupation, father_contact,
                                    mother_name, mother_occupation, mother_contact,
                                    guardian_name, guardian_occupation, guardian_contact,
                                    last_school, last_school_year, id_number, username, rfid_uid,
                                    created_at, class_schedule, deleted_at, deleted_by, deleted_reason,
                                    must_change_password, archived_at, archived_by, archive_reason
                                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)");
                                
                                $archived_by = $_SESSION['owner_name'] ?? 'Owner';
                                $archive_reason = 'Approved by Owner: ' . $comments;
                                
                                // Count: 41 parameters total (original_id=int, must_change_password=int, rest=strings)
                                // Type string: i + 37s + i + 2s = 41 total
                                $archive_stmt->bind_param("isssssssssssssssssssssssssssssssssssssiss",
                                    $student['id'], 
                                    $student['lrn'], 
                                    $student['password'], 
                                    $student['academic_track'],
                                    $student['enrollment_status'], 
                                    $student['school_type'], 
                                    $student['last_name'],
                                    $student['first_name'], 
                                    $student['middle_name'], 
                                    $student['school_year'],
                                    $student['grade_level'], 
                                    $student['semester'], 
                                    $student['dob'], 
                                    $student['birthplace'],
                                    $student['gender'], 
                                    $student['religion'], 
                                    $student['credentials'], 
                                    $student['payment_mode'],
                                    $student['address'], 
                                    $student['father_name'], 
                                    $student['father_occupation'],
                                    $student['father_contact'], 
                                    $student['mother_name'], 
                                    $student['mother_occupation'],
                                    $student['mother_contact'], 
                                    $student['guardian_name'], 
                                    $student['guardian_occupation'],
                                    $student['guardian_contact'], 
                                    $student['last_school'], 
                                    $student['last_school_year'],
                                    $student['id_number'], 
                                    $student['username'], 
                                    $student['rfid_uid'], 
                                    $student['created_at'],
                                    $student['class_schedule'], 
                                    $student['deleted_at'], 
                                    $student['deleted_by'],
                                    $student['deleted_reason'], 
                                    $student['must_change_password'], 
                                    $archived_by, 
                                    $archive_reason
                                );
                                
                                if ($archive_stmt->execute()) {
                                    $archive_id = $conn->insert_id;
                                    error_log("Successfully inserted into archived_students with ID: $archive_id");
                                    
                                    // Delete from main table
                                    $delete_stmt = $conn->prepare("DELETE FROM student_account WHERE id_number = ?");
                                    $delete_stmt->bind_param('s', $target_id);
                                    if ($delete_stmt->execute()) {
                                        error_log("Successfully deleted student $target_id from student_account");
                                    } else {
                                        error_log("Failed to delete student from student_account: " . $delete_stmt->error);
                                    }
                                    error_log("Archived and deleted student $target_id");
                                } else {
                                    error_log("Failed to execute archive_stmt: " . $archive_stmt->error);
                                    throw new Exception("Failed to archive student: " . $archive_stmt->error);
                                }
                            } else {
                                error_log("WARNING: No student found to archive with id_number: $target_id");
                            }
                            
                            $conn->commit();
                            break;
                            
                        case 'archive_employee':
                            // Permanently archive employee
                            error_log("Owner approving archive for employee: $target_id");
                            $conn->begin_transaction();
                            
                            // Get full employee data
                            $get_stmt = $conn->prepare("SELECT * FROM employees WHERE id_number = ?");
                            $get_stmt->bind_param('s', $target_id);
                            $get_stmt->execute();
                            $employee_result = $get_stmt->get_result();
                            
                            if ($employee_result->num_rows > 0) {
                                $employee = $employee_result->fetch_assoc();
                                
                                // Insert into archive table
                                $archive_stmt = $conn->prepare("INSERT INTO archived_employees (
                                    original_id, id_number, first_name, middle_name, last_name,
                                    position, department, email, phone, address, created_at, hire_date,
                                    rfid_uid, deleted_at, deleted_by, deleted_reason,
                                    archive_scheduled, archive_scheduled_by, archive_scheduled_at,
                                    archived_at, archived_by, archive_reason
                                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)");
                                
                                $archived_by = $_SESSION['owner_name'] ?? 'Owner';
                                $archive_reason = 'Approved by Owner: ' . $comments;
                                
                                $archive_stmt->bind_param("isssssssssssssssissss",
                                    $employee['id'], $employee['id_number'], $employee['first_name'],
                                    $employee['middle_name'], $employee['last_name'], $employee['position'],
                                    $employee['department'], $employee['email'], $employee['phone'],
                                    $employee['address'], $employee['created_at'], $employee['hire_date'],
                                    $employee['rfid_uid'], $employee['deleted_at'], $employee['deleted_by'],
                                    $employee['deleted_reason'], $employee['archive_scheduled'],
                                    $employee['archive_scheduled_by'], $employee['archive_scheduled_at'],
                                    $archived_by, $archive_reason
                                );
                                
                                if ($archive_stmt->execute()) {
                                    // Delete from main table
                                    $delete_stmt = $conn->prepare("DELETE FROM employees WHERE id_number = ?");
                                    $delete_stmt->bind_param('s', $target_id);
                                    $delete_stmt->execute();
                                    error_log("Archived and deleted employee $target_id");
                                } else {
                                    throw new Exception("Failed to archive employee: " . $archive_stmt->error);
                                }
                            } else {
                                error_log("WARNING: No employee found to archive with id_number: $target_id");
                            }
                            
                            $conn->commit();
                            break;
                    }
                } catch (Exception $e) {
                    if (isset($conn)) {
                        $conn->rollback();
                    }
                    error_log("Error executing approved action: " . $e->getMessage());
                }
            }
            
            // Create notification
            $notif_title = "Request " . ucfirst($action) . "d";
            $notif_message = "Owner has {$action}d request: {$request_data['request_title']}";
            $notif_type = ($action === 'approve') ? 'success' : 'warning';
            $notif_module = 'Owner';
            $notif_role = 'owner';
            $notif_action_type = 'request_' . $action;
            
            $notif_stmt = $conn->prepare("INSERT INTO system_notifications (title, message, type, module, performed_by, user_role, action_type, target_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $notif_stmt->bind_param("sssssssi", $notif_title, $notif_message, $notif_type, $notif_module, $_SESSION['owner_name'], $notif_role, $notif_action_type, $request_id);
            $notif_stmt->execute();
            
            $_SESSION['success_msg'] = "Request has been " . $action . "d successfully." . ($action === 'approve' ? " Action has been executed." : "");
        } else {
            $_SESSION['error_msg'] = "Error processing request: " . $conn->error;
        }
    }
    
    header("Location: Dashboard.php");
    exit;
}

// Handle notification mark as read
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['mark_read'])) {
    $notif_id = intval($_POST['notification_id']);
    $stmt = $conn->prepare("UPDATE system_notifications SET is_read = TRUE WHERE id = ?");
    $stmt->bind_param("i", $notif_id);
    $stmt->execute();
    
    header("Location: Dashboard.php");
    exit;
}

// Handle mark all notifications as read
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['mark_all_read'])) {
    $conn->query("UPDATE system_notifications SET is_read = TRUE WHERE is_read = FALSE");
    header("Location: Dashboard.php");
    exit;
}

// Handle success/error messages
$success_msg = $_SESSION['success_msg'] ?? '';
if ($success_msg) unset($_SESSION['success_msg']);

$error_msg = $_SESSION['error_msg'] ?? '';
if ($error_msg) unset($_SESSION['error_msg']);

// Get approval request statistics
$stats_query = "SELECT 
    COUNT(*) as total_requests,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_requests,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_requests,
    SUM(CASE WHEN priority = 'critical' AND status = 'pending' THEN 1 ELSE 0 END) as critical_pending
FROM owner_approval_requests";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Get pending approval requests (priority order)
$pending_query = "SELECT * FROM owner_approval_requests WHERE status = 'pending' ORDER BY 
    CASE priority 
        WHEN 'critical' THEN 1 
        WHEN 'high' THEN 2 
        WHEN 'medium' THEN 3 
        WHEN 'low' THEN 4 
    END, requested_at ASC";
$pending_result = $conn->query($pending_query);

// Get recent approval activity
$recent_query = "SELECT * FROM owner_approval_requests WHERE status IN ('approved', 'rejected') ORDER BY reviewed_at DESC LIMIT 5";
$recent_result = $conn->query($recent_query);

// Get system notifications (unread first)
$notifications_query = "SELECT * FROM system_notifications ORDER BY is_read ASC, created_at DESC LIMIT 20";
$notifications_result = $conn->query($notifications_query);

// Get notification statistics
$notif_stats_query = "SELECT 
    COUNT(*) as total_notifications,
    SUM(CASE WHEN is_read = FALSE THEN 1 ELSE 0 END) as unread_notifications,
    SUM(CASE WHEN type = 'critical' AND is_read = FALSE THEN 1 ELSE 0 END) as critical_unread
FROM system_notifications";
$notif_stats_result = $conn->query($notif_stats_query);
$notif_stats = $notif_stats_result->fetch_assoc();

// Get soft-deleted accounts count (for dashboard overview only)
$deleted_students_query = "SELECT COUNT(*) as count FROM student_account WHERE deleted_at IS NOT NULL";
$deleted_students_result = $conn->query($deleted_students_query);
$deleted_students_count = $deleted_students_result ? $deleted_students_result->fetch_assoc()['count'] : 0;

$deleted_employees_query = "SELECT COUNT(*) as count FROM employees WHERE deleted_at IS NOT NULL";
$deleted_employees_result = $conn->query($deleted_employees_query);
$deleted_employees_count = $deleted_employees_result ? $deleted_employees_result->fetch_assoc()['count'] : 0;

$total_deleted_accounts = $deleted_students_count + $deleted_employees_count;

// Get module activity statistics
$module_stats_query = "SELECT 
    module,
    user_role,
    COUNT(*) as activity_count,
    MAX(created_at) as last_activity
FROM system_notifications 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY module, user_role
ORDER BY activity_count DESC";
$module_stats_result = $conn->query($module_stats_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard - Cornerstone College Inc.</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .nav-item.active { background: rgba(255,255,255,0.1); }
        .card-shadow { box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .priority-critical { border-left: 4px solid #dc2626; background: #fef2f2; }
        .priority-high { border-left: 4px solid #ea580c; background: #fff7ed; }
        .priority-medium { border-left: 4px solid #d97706; background: #fffbeb; }
        .priority-low { border-left: 4px solid #65a30d; background: #f7fee7; }
    </style>
</head>
<body class="min-h-screen bg-gray-50 flex">

    <!-- Sidebar -->
    <div id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-gradient-to-b from-[#0B2C62] to-[#153e86] text-white transform -translate-x-full transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0">
        <div class="flex items-center justify-between h-16 px-6 border-b border-white/10">
            <div class="flex items-center gap-3">
                <img src="../images/LogoCCI.png" class="h-8 w-8 rounded-full bg-white p-1" alt="Logo">
                <div class="leading-tight">
                    <div class="font-bold text-sm">Cornerstone College</div>
                    <div class="text-xs text-blue-200">Owner Portal</div>
                </div>
            </div>
        </div>
        
        <nav class="mt-8 px-4">
            <div class="space-y-2">
                <!-- Dashboard -->
                <a href="#dashboard" onclick="showSection('dashboard', event)" class="nav-item active flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                    </svg>
                    <span>Dashboard</span>
                </a>
                
                <!-- Management Tools -->
                <div class="pt-4">
                    <div class="text-xs font-semibold text-blue-200 uppercase tracking-wider px-4 mb-2">Management</div>
                    <a href="#notifications" onclick="showSection('notifications', event)" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4 19h6v-2H4v2zM4 15h8v-2H4v2zM4 11h8V9H4v2z"/>
                        </svg>
                        <span>System Notifications</span>
                        <?php if ($notif_stats['unread_notifications'] > 0): ?>
                            <span class="bg-red-500 text-white text-xs rounded-full px-2 py-1 ml-auto"><?= $notif_stats['unread_notifications'] ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="#approval-requests" onclick="showSection('approval-requests', event)" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Approval Requests</span>
                        <?php if ($stats['pending_requests'] > 0): ?>
                            <span class="bg-yellow-500 text-white text-xs rounded-full px-2 py-1 ml-auto"><?= $stats['pending_requests'] ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="#module-activity" onclick="showSection('module-activity', event)" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        <span>Module Activity</span>
                    </a>
                    
                    <!-- User Info & Logout -->
                    <div class="mt-6 pt-4 border-t border-white/10">
                        <div class="flex items-center gap-3 mb-3 px-4">
                            <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center">
                                <span class="text-sm font-semibold"><?= substr($_SESSION['owner_name'] ?? 'OW', 0, 2) ?></span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium truncate"><?= htmlspecialchars($_SESSION['owner_name'] ?? 'School Owner') ?></div>
                                <div class="text-xs text-blue-200">Owner</div>
                            </div>
                        </div>
                        <a href="../StudentLogin/logout.php" class="flex items-center gap-2 w-full px-4 py-2 text-sm hover:bg-white/10 rounded-lg transition mx-4">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="flex-1 lg:ml-0">
        <!-- Top Header -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="flex items-center justify-between px-6 py-4">
                <div class="flex items-center gap-4">
                    <button onclick="toggleSidebar()" class="lg:hidden p-2 rounded-md hover:bg-gray-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <h1 id="page-title" class="text-2xl font-bold text-gray-900">Dashboard</h1>
                </div>
                <div class="flex items-center gap-4">
                    <button onclick="location.reload()" class="p-2 rounded-md hover:bg-gray-100 text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <main class="p-6">
            <!-- Dashboard Section -->
            <div id="dashboard-section" class="section-content">
    <!-- Top Overview Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Deleted Accounts Overview -->
        <div class="bg-white rounded-xl card-shadow p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="p-3 rounded-lg bg-gray-100 text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M12 7a3 3 0 110-6 3 3 0 010 6z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Deleted Accounts</p>
                    <p class="text-3xl font-bold text-gray-800"><?= $total_deleted_accounts ?></p>
                    <p class="text-xs text-gray-500">Students: <?= $deleted_students_count ?> • Employees: <?= $deleted_employees_count ?></p>
                </div>
            </div>
        </div>

        <!-- System Overview -->
        <div class="bg-white rounded-xl card-shadow p-6 border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">System Overview</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Unread Notifications</span>
                    <span class="font-bold text-gray-800"><?= $notif_stats['unread_notifications'] ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">System Status</span>
                    <span class="font-medium text-gray-800">🟢 Online</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Recent Activity & Quick Stats -->
        <div class="space-y-6">
            <!-- Quick Actions -->
            <div class="bg-white rounded-xl card-shadow p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">🚀 Quick Actions</h3>
                <div class="space-y-3">
                    <a href="SystemLogs.php" class="block w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg text-center font-medium transition">
                        📋 View System Logs
                    </a>
                    <button onclick="window.location.reload()" class="block w-full bg-gray-600 hover:bg-gray-700 text-white py-2 px-4 rounded-lg text-center font-medium transition">
                        🔄 Refresh Dashboard
                    </button>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white rounded-xl card-shadow p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">📈 Recent Activity</h3>
                
                <div class="space-y-3">
                    <?php if ($recent_result && $recent_result->num_rows > 0): ?>
                        <?php while ($activity = $recent_result->fetch_assoc()): ?>
                            <div class="border-l-4 <?= $activity['status'] === 'approved' ? 'border-green-500 bg-green-50' : 'border-red-500 bg-red-50' ?> pl-4 py-2 rounded-r">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <h4 class="font-medium text-gray-900 text-sm"><?= htmlspecialchars($activity['request_title']) ?></h4>
                                        <p class="text-xs text-gray-600">
                                            <?= $activity['status'] === 'approved' ? '✅ Approved' : '❌ Rejected' ?> • 
                                            <?= date('M j, g:i A', strtotime($activity['reviewed_at'])) ?>
                                        </p>
                                        <?php if ($activity['owner_comments']): ?>
                                            <p class="text-xs text-gray-700 mt-1 italic">"<?= htmlspecialchars($activity['owner_comments']) ?>"</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-6">
                            <p class="text-gray-500 text-sm">No recent activity</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</div>

            <!-- System Notifications Section -->
            <div id="notifications-section" class="section-content hidden">
                <div class="bg-white rounded-xl card-shadow p-6 mb-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800">🔔 System Notifications</h2>
                        <div class="flex gap-3">
                            <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm font-medium">
                                <?= $notif_stats['unread_notifications'] ?> Unread
                            </span>
                            <form method="POST" class="inline">
                                <button type="submit" name="mark_all_read" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                                    Mark All Read
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="space-y-4 max-h-96 overflow-y-auto">
                        <?php if ($notifications_result && $notifications_result->num_rows > 0): ?>
                            <?php while ($notification = $notifications_result->fetch_assoc()): ?>
                                <div class="border rounded-lg p-4 <?= $notification['is_read'] ? 'bg-gray-50' : 'bg-white border-l-4 border-blue-500' ?> cursor-pointer hover:shadow-md transition-all duration-200" onclick="toggleNotificationDetails(<?= $notification['id'] ?>)">
                                    <div class="flex justify-between items-start mb-3">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-2">
                                                <h3 class="font-semibold text-gray-900"><?= htmlspecialchars($notification['title']) ?></h3>
                                                <span class="px-2 py-1 rounded-full text-xs font-medium
                                                    <?= $notification['type'] === 'critical' ? 'bg-red-100 text-red-800' : 
                                                       ($notification['type'] === 'error' ? 'bg-red-100 text-red-800' : 
                                                       ($notification['type'] === 'warning' ? 'bg-yellow-100 text-yellow-800' : 
                                                       ($notification['type'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'))) ?>">
                                                    <?= strtoupper($notification['type']) ?>
                                                </span>
                                                <?php if (!$notification['is_read']): ?>
                                                    <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="text-sm text-gray-600 mb-2">
                                                <strong>From:</strong> <?= htmlspecialchars($notification['performed_by']) ?> 
                                                (<?= ucfirst($notification['user_role']) ?>) • 
                                                <strong>Module:</strong> <?= htmlspecialchars($notification['module']) ?>
                                            </p>
                                            <p class="text-sm text-gray-700 mb-3"><?= htmlspecialchars($notification['message']) ?></p>
                                            <p class="text-xs text-gray-500">
                                                <?= date('M j, Y g:i A', strtotime($notification['created_at'])) ?>
                                            </p>
                                        </div>
                                        <?php if (!$notification['is_read']): ?>
                                            <form method="POST" class="ml-4" onclick="event.stopPropagation()">
                                                <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                                                <button type="submit" name="mark_read" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">
                                                    Mark Read
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-12">
                                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4 19h6v-2H4v2zM4 15h8v-2H4v2zM4 11h8V9H4v2z"></path>
                                </svg>
                                <p class="text-gray-500 text-lg font-medium">No notifications</p>
                                <p class="text-gray-400 text-sm">All system notifications will appear here</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Approval Requests Section -->
            <div id="approval-requests-section" class="section-content hidden">
                <div class="bg-white rounded-xl card-shadow p-6 mb-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800">✅ Approval Requests</h2>
                        <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm font-medium">
                            <?= $stats['pending_requests'] ?> Pending
                        </span>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <!-- Pending Card -->
                        <div class="bg-yellow-50 border border-gray-200 rounded-lg p-5">
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-10 h-10 bg-yellow-500 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <?php if ($stats['pending_requests'] > 0): ?>
                                    <span class="bg-yellow-500 text-white text-xs font-bold px-2 py-0.5 rounded">NEW</span>
                                <?php endif; ?>
                            </div>
                            <div class="text-gray-700 text-sm font-medium mb-1">Pending</div>
                            <div class="text-3xl font-bold text-gray-900"><?= $stats['pending_requests'] ?></div>
                        </div>

                        <!-- Approved Card -->
                        <div class="bg-green-50 border border-gray-200 rounded-lg p-5">
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="text-gray-700 text-sm font-medium mb-1">Approved</div>
                            <div class="text-3xl font-bold text-gray-900"><?= $stats['approved_requests'] ?></div>
                        </div>

                        <!-- Rejected Card -->
                        <div class="bg-red-50 border border-gray-200 rounded-lg p-5">
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-10 h-10 bg-red-500 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="text-gray-700 text-sm font-medium mb-1">Rejected</div>
                            <div class="text-3xl font-bold text-gray-900"><?= $stats['rejected_requests'] ?></div>
                        </div>

                        <!-- Total Card -->
                        <div class="bg-blue-50 border border-gray-200 rounded-lg p-5">
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="text-gray-700 text-sm font-medium mb-1">Total</div>
                            <div class="text-3xl font-bold text-gray-900"><?= $stats['total_requests'] ?></div>
                        </div>
                    </div>

                    <!-- Pending Requests List -->
                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        <?php 
                        // Reset the result pointer for pending requests
                        $pending_result = $conn->query($pending_query);
                        if ($pending_result && $pending_result->num_rows > 0): 
                        ?>
                            <?php while ($request = $pending_result->fetch_assoc()): 
                                $priorityColors = [
                                    'critical' => ['bg' => 'bg-red-50', 'border' => 'border-red-200', 'badge' => 'bg-red-100 text-red-800', 'icon' => 'text-red-600'],
                                    'high' => ['bg' => 'bg-orange-50', 'border' => 'border-orange-200', 'badge' => 'bg-orange-100 text-orange-800', 'icon' => 'text-orange-600'],
                                    'medium' => ['bg' => 'bg-yellow-50', 'border' => 'border-yellow-200', 'badge' => 'bg-yellow-100 text-yellow-800', 'icon' => 'text-yellow-600'],
                                    'low' => ['bg' => 'bg-green-50', 'border' => 'border-green-200', 'badge' => 'bg-green-100 text-green-800', 'icon' => 'text-green-600']
                                ];
                                $colors = $priorityColors[$request['priority']] ?? $priorityColors['medium'];
                            ?>
                                <div class="border-2 <?= $colors['border'] ?> rounded-xl overflow-hidden hover:shadow-lg transition-all duration-200">
                                    <!-- Summary Row (Always Visible) -->
                                    <div class="<?= $colors['bg'] ?> p-5 cursor-pointer hover:opacity-90 transition-opacity" onclick="openRequestModal(<?= $request['id'] ?>)">
                                        <div class="flex items-start justify-between gap-4">
                                            <div class="flex items-start gap-4 flex-1">
                                                <!-- Icon -->
                                                <div class="w-12 h-12 rounded-full bg-white shadow-sm flex items-center justify-center flex-shrink-0">
                                                    <svg class="w-6 h-6 <?= $colors['icon'] ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                                    </svg>
                                                </div>
                                                
                                                <!-- Content -->
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center gap-2 mb-2">
                                                        <span class="px-3 py-1 rounded-full text-xs font-bold <?= $colors['badge'] ?> shadow-sm">
                                                            <?= strtoupper($request['priority']) ?>
                                                        </span>
                                                        <span class="px-2 py-1 rounded bg-white text-xs font-medium text-gray-600 shadow-sm">
                                                            <?= ucwords(str_replace('_', ' ', $request['request_type'])) ?>
                                                        </span>
                                                    </div>
                                                    <h3 class="font-bold text-gray-900 text-base mb-1"><?= htmlspecialchars($request['request_title']) ?></h3>
                                                    <div class="flex items-center gap-2 text-xs text-gray-600">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                        </svg>
                                                        <span class="font-medium"><?= htmlspecialchars($request['requester_name']) ?></span>
                                                        <span class="text-gray-400">•</span>
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                        </svg>
                                                        <span><?= date('M j, Y g:i A', strtotime($request['requested_at'])) ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- View Details Button -->
                                            <div class="flex-shrink-0">
                                                <button onclick="event.stopPropagation();" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-sm transition-colors text-sm">
                                                    View Details
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Detailed View (Hidden by Default) -->
                                    <div id="details-<?= $request['id'] ?>" class="hidden border-t-2 <?= $colors['border'] ?> bg-white p-6">
                                        <div class="space-y-4">
                                            <?php 
                                            // Extract description and reason separately
                                            $description = $request['request_description'];
                                            $reason = '';
                                            
                                            if (strpos($description, 'Reason:') !== false) {
                                                $parts = explode('Reason:', $description);
                                                $description = trim($parts[0]); // Description without reason
                                                $reason = trim($parts[1]); // Just the reason
                                            }
                                            ?>
                                            
                                            <!-- Description (without reason) -->
                                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                                <div class="flex items-start gap-2 mb-2">
                                                    <svg class="w-5 h-5 text-gray-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                    </svg>
                                                    <div class="flex-1">
                                                        <p class="text-sm font-bold text-gray-700 mb-1">Description</p>
                                                        <p class="text-sm text-gray-600 leading-relaxed"><?= htmlspecialchars($description) ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Reason (if exists) -->
                                            <?php if (!empty($reason)): ?>
                                            <div class="bg-yellow-50 rounded-lg p-4 border-2 border-yellow-300">
                                                <div class="flex items-start gap-2">
                                                    <svg class="w-5 h-5 text-yellow-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                    <div class="flex-1">
                                                        <p class="text-sm font-bold text-yellow-800 mb-2">Reason for Request</p>
                                                        <p class="text-sm text-yellow-900 leading-relaxed bg-white px-3 py-2 rounded border border-yellow-200"><?= htmlspecialchars($reason) ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <!-- Requester Info -->
                                            <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                                                <div class="flex items-start gap-2">
                                                    <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                    </svg>
                                                    <div class="flex-1">
                                                        <p class="text-sm font-bold text-gray-700 mb-1">Requested by</p>
                                                        <p class="text-sm text-gray-600">
                                                            <span class="font-semibold"><?= htmlspecialchars($request['requester_name']) ?></span>
                                                            <span class="text-gray-400 mx-1">•</span>
                                                            <span class="text-blue-600"><?= ucfirst($request['requester_role']) ?></span>
                                                            <span class="text-gray-400 mx-1">•</span>
                                                            <span><?= htmlspecialchars($request['requester_module']) ?></span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Student/Employee Details -->
                                            <?php if ($request['target_data']): 
                                                $target_data = json_decode($request['target_data'], true);
                                                // Determine if it's a student or employee based on request type
                                                $isStudent = (strpos($request['request_type'], 'student') !== false);
                                                $detailsTitle = $isStudent ? 'Student Details' : 'Employee Details';
                                                $idLabel = $isStudent ? 'Student ID' : 'Employee ID';
                                            ?>
                                                <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-lg p-4 border border-gray-200">
                                                    <div class="flex items-center gap-2 mb-3">
                                                        <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <?php if ($isStudent): ?>
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/>
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0v6"/>
                                                            <?php else: ?>
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                                            <?php endif; ?>
                                                        </svg>
                                                        <p class="text-sm font-bold text-gray-800"><?= $detailsTitle ?></p>
                                                    </div>
                                                    <div class="bg-white rounded-lg p-3 space-y-2.5 shadow-sm">
                                                        <?php if (isset($target_data['id_number'])): ?>
                                                            <div class="text-sm p-3 bg-gray-50 rounded-lg border border-gray-200">
                                                                <span class="text-gray-600 font-medium block mb-1.5"><?= $idLabel ?></span>
                                                                <span class="font-bold text-gray-900 bg-white px-3 py-1.5 rounded border border-gray-300 inline-block"><?= htmlspecialchars($target_data['id_number']) ?></span>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if (isset($target_data['first_name']) || isset($target_data['last_name'])): ?>
                                                            <div class="text-sm p-3 bg-gray-50 rounded-lg border border-gray-200">
                                                                <span class="text-gray-600 font-medium block mb-1.5">Full Name</span>
                                                                <span class="font-bold text-gray-900 text-base">
                                                                    <?= htmlspecialchars(trim(($target_data['first_name'] ?? '') . ' ' . ($target_data['middle_name'] ?? '') . ' ' . ($target_data['last_name'] ?? ''))) ?>
                                                                </span>
                                                            </div>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($isStudent): ?>
                                                            <?php if (isset($target_data['grade_level'])): ?>
                                                                <div class="text-sm p-3 bg-gray-50 rounded-lg border border-gray-200">
                                                                    <span class="text-gray-600 font-medium block mb-1.5">Grade Level</span>
                                                                    <span class="font-semibold text-gray-900"><?= htmlspecialchars($target_data['grade_level']) ?></span>
                                                                </div>
                                                            <?php endif; ?>
                                                            <?php if (isset($target_data['academic_track'])): ?>
                                                                <div class="text-sm p-3 bg-gray-50 rounded-lg border border-gray-200">
                                                                    <span class="text-gray-600 font-medium block mb-1.5">Academic Track</span>
                                                                    <span class="font-semibold text-gray-900"><?= htmlspecialchars($target_data['academic_track']) ?></span>
                                                                </div>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <?php if (isset($target_data['position'])): ?>
                                                                <div class="text-sm p-3 bg-gray-50 rounded-lg border border-gray-200">
                                                                    <span class="text-gray-600 font-medium block mb-1.5">Position</span>
                                                                    <span class="font-semibold text-gray-900"><?= htmlspecialchars($target_data['position']) ?></span>
                                                                </div>
                                                            <?php endif; ?>
                                                            <?php if (isset($target_data['department'])): ?>
                                                                <div class="text-sm p-3 bg-gray-50 rounded-lg border border-gray-200">
                                                                    <span class="text-gray-600 font-medium block mb-1.5">Department</span>
                                                                    <span class="font-semibold text-gray-900"><?= htmlspecialchars($target_data['department']) ?></span>
                                                                </div>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                        <?php if (isset($target_data['deletion_reason'])): ?>
                                                            <div class="p-3 bg-red-50 rounded-lg border-2 border-red-200">
                                                                <div class="flex items-center gap-2 mb-2">
                                                                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                                                    </svg>
                                                                    <span class="text-red-800 font-bold">Deletion Reason</span>
                                                                </div>
                                                                <p class="font-medium text-gray-900 bg-white p-3 rounded-lg border border-red-300 leading-relaxed"><?= htmlspecialchars($target_data['deletion_reason']) ?></p>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Action Buttons -->
                                        <div class="flex gap-3 pt-5 mt-5 border-t-2 border-gray-200">
                                            <button onclick="event.stopPropagation(); confirmAndSubmit(<?= $request['id'] ?>, 'approve')" 
                                                    class="flex-1 px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-xl text-sm font-bold hover:from-green-700 hover:to-green-800 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                Approve Request
                                            </button>
                                            <button onclick="event.stopPropagation(); confirmAndSubmit(<?= $request['id'] ?>, 'reject')" 
                                                    class="flex-1 px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-xl text-sm font-bold hover:from-red-700 hover:to-red-800 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                Reject Request
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-12">
                                <svg class="w-16 h-16 mx-auto text-green-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="text-gray-500 text-lg font-medium">All caught up!</p>
                                <p class="text-gray-400 text-sm">No pending approval requests</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Module Activity Section -->
            <div id="module-activity-section" class="section-content hidden">
                <div class="bg-white rounded-xl card-shadow p-6 mb-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800">📊 Module Activity</h2>
                        <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm font-medium">
                            Last 7 Days
                        </span>
                    </div>

                    <div class="space-y-4">
                        <?php if ($module_stats_result && $module_stats_result->num_rows > 0): ?>
                            <?php while ($module = $module_stats_result->fetch_assoc()): ?>
                                <div class="border rounded-lg p-4 hover:bg-gray-50 transition">
                                    <div class="flex justify-between items-center">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <h3 class="font-semibold text-gray-900"><?= htmlspecialchars($module['module']) ?></h3>
                                                <p class="text-sm text-gray-600">
                                                    Role: <?= ucfirst($module['user_role']) ?> • 
                                                    Last Activity: <?= date('M j, Y g:i A', strtotime($module['last_activity'])) ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-2xl font-bold text-gray-900"><?= $module['activity_count'] ?></div>
                                            <div class="text-sm text-gray-500">Activities</div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-12">
                                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                                <p class="text-gray-500 text-lg font-medium">No recent activity</p>
                                <p class="text-gray-400 text-sm">Module activity from the last 7 days will appear here</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>




<!-- Success/Error Notifications -->
<?php if (!empty($success_msg)): ?>
<div id="successNotif" class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full opacity-0 transition-all duration-300">
    ✅ <?= htmlspecialchars($success_msg) ?>
</div>
<?php endif; ?>

<?php if (!empty($error_msg)): ?>
<div id="errorNotif" class="fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full opacity-0 transition-all duration-300">
    ❌ <?= htmlspecialchars($error_msg) ?>
</div>
<?php endif; ?>

<script>
// Sidebar toggle for mobile
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('-translate-x-full');
}

// Section navigation
function showSection(sectionId, event) {
    if (event) {
        event.preventDefault();
    }
    
    // Store current section in sessionStorage
    sessionStorage.setItem('ownerCurrentSection', sectionId);
    
    // Hide all sections
    document.querySelectorAll('.section-content').forEach(section => {
        section.classList.add('hidden');
    });
    
    // Remove active class from all nav items
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // Show selected section
    const targetSection = document.getElementById(sectionId + '-section');
    if (targetSection) {
        targetSection.classList.remove('hidden');
    }
    
    // Add active class to clicked nav item or find by href
    if (event && event.target) {
        const navItem = event.target.closest('.nav-item');
        if (navItem) {
            navItem.classList.add('active');
        }
    } else {
        // If no click event, find the nav item by href
        const navItem = document.querySelector(`a[href="#${sectionId}"]`);
        if (navItem) {
            navItem.classList.add('active');
        }
    }
    
    // Update page title
    const titles = {
        'dashboard': 'Dashboard',
        'notifications': 'System Notifications',
        'approval-requests': 'Approval Requests',
        'module-activity': 'Module Activity'
    };
    document.getElementById('page-title').textContent = titles[sectionId] || 'Dashboard';
}

// Toggle notification details (expand/collapse)
function toggleNotificationDetails(notificationId) {
    const notification = event.currentTarget;
    const isExpanded = notification.classList.contains('expanded');
    
    if (isExpanded) {
        notification.classList.remove('expanded');
    } else {
        // Collapse all other notifications
        document.querySelectorAll('.border.rounded-lg.p-4.expanded').forEach(n => {
            n.classList.remove('expanded');
        });
        notification.classList.add('expanded');
    }
}

// Open request details in modal
function openRequestModal(requestId) {
    const detailsDiv = document.getElementById('details-' + requestId);
    
    // Create modal backdrop
    const modal = document.createElement('div');
    modal.id = 'requestModal-' + requestId;
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4';
    modal.onclick = function(e) {
        if (e.target === modal) closeRequestModal(requestId);
    };
    
    // Create modal content wrapper
    const modalContent = document.createElement('div');
    modalContent.className = 'bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col';
    modalContent.onclick = function(e) {
        e.stopPropagation();
    };
    
    // Create modal header with close button
    const modalHeader = document.createElement('div');
    modalHeader.className = 'flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50 flex-shrink-0';
    modalHeader.innerHTML = `
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-900">Request Details</h3>
                <p class="text-xs text-gray-500">Review and take action</p>
            </div>
        </div>
        <button onclick="closeRequestModal(${requestId})" class="group flex items-center justify-center w-10 h-10 rounded-lg hover:bg-red-50 transition-colors">
            <svg class="w-6 h-6 text-gray-400 group-hover:text-red-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    `;
    
    // Create scrollable content area
    const modalBody = document.createElement('div');
    modalBody.className = 'overflow-y-auto p-6';
    
    // Clone the details content
    const content = detailsDiv.cloneNode(true);
    content.classList.remove('hidden', 'border-t-2', 'p-6');
    
    modalBody.appendChild(content);
    modalContent.appendChild(modalHeader);
    modalContent.appendChild(modalBody);
    modal.appendChild(modalContent);
    document.body.appendChild(modal);
}

function closeRequestModal(requestId) {
    const modal = document.getElementById('requestModal-' + requestId);
    if (modal) {
        modal.remove();
    }
}

// Show custom confirmation modal
function confirmAndSubmit(requestId, action) {
    // Store data for later use
    window.pendingApproval = { requestId, action };
    
    // Create modal
    const modal = document.createElement('div');
    modal.id = 'confirmModal';
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    
    const isApprove = action === 'approve';
    const iconColor = isApprove ? 'text-green-600' : 'text-red-600';
    const iconBg = isApprove ? 'bg-green-50' : 'bg-red-50';
    const btnColor = isApprove ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700';
    const title = isApprove ? 'Approve Request' : 'Reject Request';
    const message = isApprove 
        ? 'Are you sure you want to approve this request? This action will execute the requested changes and cannot be undone.'
        : 'Are you sure you want to reject this request? The requester will be notified of the rejection.';
    const actionText = isApprove ? 'Approve' : 'Reject';
    
    modal.innerHTML = `
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 overflow-hidden animate-scale-in">
            <div class="p-8 text-center">
                <div class="mx-auto w-20 h-20 ${iconBg} rounded-full flex items-center justify-center mb-6">
                    <svg class="w-10 h-10 ${iconColor}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-3">${title}</h3>
                <p class="text-gray-600 leading-relaxed mb-6">${message}</p>
            </div>
            <div class="px-8 pb-8 flex gap-3">
                <button onclick="closeConfirmModal()" class="flex-1 px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-xl font-semibold transition-all">
                    Cancel
                </button>
                <button onclick="submitApproval()" class="flex-1 px-6 py-3 ${btnColor} text-white rounded-xl font-semibold transition-all">
                    ${actionText}
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Add animation style if not present
    if (!document.getElementById('modal-animations')) {
        const style = document.createElement('style');
        style.id = 'modal-animations';
        style.textContent = `
            @keyframes scale-in {
                from { opacity: 0; transform: scale(0.9); }
                to { opacity: 1; transform: scale(1); }
            }
            .animate-scale-in { animation: scale-in 0.2s ease-out; }
        `;
        document.head.appendChild(style);
    }
}

function closeConfirmModal() {
    const modal = document.getElementById('confirmModal');
    if (modal) {
        modal.remove();
    }
    window.pendingApproval = null;
}

function submitApproval() {
    if (!window.pendingApproval) return;
    
    const { requestId, action } = window.pendingApproval;
    
    // Create and submit form
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '';
    
    const requestIdInput = document.createElement('input');
    requestIdInput.type = 'hidden';
    requestIdInput.name = 'request_id';
    requestIdInput.value = requestId;
    
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = action;
    
    const commentsInput = document.createElement('input');
    commentsInput.type = 'hidden';
    commentsInput.name = 'comments';
    commentsInput.value = '';
    
    form.appendChild(requestIdInput);
    form.appendChild(actionInput);
    form.appendChild(commentsInput);
    
    document.body.appendChild(form);
    form.submit();
}

// Restore section on page load
document.addEventListener('DOMContentLoaded', function() {
    // Restore the saved section or show dashboard
    const savedSection = sessionStorage.getItem('ownerCurrentSection');
    const sectionToShow = savedSection || 'dashboard';
    
    // Always call showSection to properly set active classes
    showSection(sectionToShow);
    
    const successNotif = document.getElementById('successNotif');
    const errorNotif = document.getElementById('errorNotif');
    
    if (successNotif) {
        setTimeout(() => {
            successNotif.classList.remove('translate-x-full', 'opacity-0');
        }, 100);
        setTimeout(() => {
            successNotif.classList.add('translate-x-full', 'opacity-0');
        }, 4000);
    }
    
    if (errorNotif) {
        setTimeout(() => {
            errorNotif.classList.remove('translate-x-full', 'opacity-0');
        }, 100);
        setTimeout(() => {
            errorNotif.classList.add('translate-x-full', 'opacity-0');
        }, 4000);
    }
});

// Real-time polling for new approval requests
let lastRequestCheck = '<?= date('Y-m-d H:i:s') ?>';
let requestCheckInterval;

// Start polling when on approval-requests section
document.addEventListener('DOMContentLoaded', function() {
    // Check every 1 second for new requests (instant updates)
    requestCheckInterval = setInterval(checkForNewRequests, 1000);
});

async function checkForNewRequests() {
    // Only check if we're on the approval-requests section
    const approvalSection = document.getElementById('approval-requests-section');
    if (!approvalSection || approvalSection.classList.contains('hidden')) {
        return;
    }
    
    try {
        const response = await fetch(`check_new_requests.php?last_check=${encodeURIComponent(lastRequestCheck)}`);
        if (!response.ok) return;
        
        const data = await response.json();
        
        if (data.success) {
            // Update counts
            if (data.counts) {
                updateRequestCounts(data.counts);
            }
            
            // Show notification and add new requests dynamically
            if (data.new_requests && data.new_requests.length > 0) {
                data.new_requests.forEach(request => {
                    showNewRequestNotification(request);
                    addRequestToList(request);
                });
            }
            
            // Update last check time
            lastRequestCheck = data.current_time;
        }
    } catch (error) {
        console.error('Error checking for new requests:', error);
    }
}

function updateRequestCounts(counts) {
    // Update pending count
    const pendingCount = document.querySelector('.text-yellow-600')?.closest('.bg-yellow-50')?.querySelector('.text-3xl');
    if (pendingCount) {
        pendingCount.textContent = counts.pending_requests || 0;
    }
    
    // Update approved count
    const approvedCount = document.querySelector('.text-green-600')?.closest('.bg-green-50')?.querySelector('.text-3xl');
    if (approvedCount) {
        approvedCount.textContent = counts.approved_requests || 0;
    }
    
    // Update rejected count
    const rejectedCount = document.querySelector('.text-red-600')?.closest('.bg-red-50')?.querySelector('.text-3xl');
    if (rejectedCount) {
        rejectedCount.textContent = counts.rejected_requests || 0;
    }
    
    // Update total count
    const totalCount = document.querySelector('.text-blue-600')?.closest('.bg-blue-50')?.querySelector('.text-3xl');
    if (totalCount) {
        totalCount.textContent = counts.total_requests || 0;
    }
    
    // Update badge in sidebar
    const badge = document.querySelector('.nav-item [onclick*="approval-requests"] .bg-yellow-500');
    if (badge && counts.pending_requests > 0) {
        badge.textContent = counts.pending_requests;
    }
}

function showNewRequestNotification(request) {
    // Create notification container if it doesn't exist
    let notificationContainer = document.getElementById('request-notification-container');
    if (!notificationContainer) {
        notificationContainer = document.createElement('div');
        notificationContainer.id = 'request-notification-container';
        notificationContainer.className = 'fixed top-4 right-4 z-50 flex flex-col gap-3';
        document.body.appendChild(notificationContainer);
    }
    
    // Priority colors
    const priorityColors = {
        'critical': 'from-red-500 to-red-600',
        'high': 'from-orange-500 to-orange-600',
        'medium': 'from-yellow-500 to-yellow-600',
        'low': 'from-blue-500 to-blue-600'
    };
    
    const bgColor = priorityColors[request.priority] || 'from-blue-500 to-blue-600';
    
    // Create notification
    const notification = document.createElement('div');
    notification.className = `bg-gradient-to-r ${bgColor} text-white rounded-lg shadow-2xl p-5 max-w-md transform translate-x-full transition-all duration-500 ease-out`;
    
    notification.innerHTML = `
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0 bg-white bg-opacity-20 rounded-full p-2">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between mb-1">
                    <h4 class="text-base font-bold text-white">🔔 New Request!</h4>
                    <button onclick="this.closest('.bg-gradient-to-r').remove()" class="text-white hover:text-gray-200 transition-colors ml-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="space-y-1">
                    <p class="text-sm text-white font-medium">
                        ${request.title}
                    </p>
                    <p class="text-xs text-white opacity-90">
                        <strong>From:</strong> ${request.requester}
                    </p>
                    <p class="text-xs text-white opacity-90">
                        <strong>Priority:</strong> ${request.priority.toUpperCase()}
                    </p>
                    <p class="text-xs text-white opacity-90">
                        <strong>Time:</strong> ${request.requestedAt}
                    </p>
                </div>
            </div>
        </div>
    `;
    
    notificationContainer.appendChild(notification);
    
    // Slide in animation
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);
    
    // Play notification sound
    playNotificationSound();
    
    // Auto remove after 10 seconds
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 500);
    }, 10000);
}

function addRequestToList(request) {
    // Find the pending requests container
    const requestsContainer = document.querySelector('#approval-requests-section .space-y-3');
    if (!requestsContainer) {
        console.error('Could not find requests container');
        return;
    }
    
    // Check if "All caught up!" message is showing and remove it
    const caughtUpMessage = requestsContainer.querySelector('.text-center.py-12');
    if (caughtUpMessage) {
        caughtUpMessage.remove();
    }
    
    // Priority colors matching PHP
    const priorityColors = {
        'critical': { bg: 'bg-red-50', border: 'border-red-200', badge: 'bg-red-100 text-red-800', icon: 'text-red-600' },
        'high': { bg: 'bg-orange-50', border: 'border-orange-200', badge: 'bg-orange-100 text-orange-800', icon: 'text-orange-600' },
        'medium': { bg: 'bg-yellow-50', border: 'border-yellow-200', badge: 'bg-yellow-100 text-yellow-800', icon: 'text-yellow-600' },
        'low': { bg: 'bg-green-50', border: 'border-green-200', badge: 'bg-green-100 text-green-800', icon: 'text-green-600' }
    };
    
    const colors = priorityColors[request.priority] || priorityColors['medium'];
    
    // Parse description and reason
    let description = request.description || '';
    let reason = '';
    if (description.includes('Reason:')) {
        const parts = description.split('Reason:');
        description = parts[0].trim();
        reason = parts[1].trim();
    }
    
    // Parse target data
    let targetData = {};
    try {
        targetData = request.target_data ? JSON.parse(request.target_data) : {};
    } catch (e) {
        console.error('Error parsing target_data:', e);
    }
    
    const isStudent = request.type.includes('student');
    const detailsTitle = isStudent ? 'Student Details' : 'Employee Details';
    const idLabel = isStudent ? 'Student ID' : 'Employee ID';
    
    // Build target details HTML
    let targetDetailsHTML = '';
    if (Object.keys(targetData).length > 0) {
        let detailsContent = '';
        
        if (targetData.id_number) {
            detailsContent += `
                <div class="text-sm p-3 bg-gray-50 rounded-lg border border-gray-200">
                    <span class="text-gray-600 font-medium block mb-1.5">${idLabel}</span>
                    <span class="font-bold text-gray-900 bg-white px-3 py-1.5 rounded border border-gray-300 inline-block">${targetData.id_number}</span>
                </div>`;
        }
        
        if (targetData.first_name || targetData.last_name) {
            const fullName = [targetData.first_name, targetData.middle_name, targetData.last_name].filter(Boolean).join(' ');
            detailsContent += `
                <div class="text-sm p-3 bg-gray-50 rounded-lg border border-gray-200">
                    <span class="text-gray-600 font-medium block mb-1.5">Full Name</span>
                    <span class="font-bold text-gray-900 text-base">${fullName}</span>
                </div>`;
        }
        
        if (isStudent) {
            if (targetData.grade_level) {
                detailsContent += `
                    <div class="text-sm p-3 bg-gray-50 rounded-lg border border-gray-200">
                        <span class="text-gray-600 font-medium block mb-1.5">Grade Level</span>
                        <span class="font-semibold text-gray-900">${targetData.grade_level}</span>
                    </div>`;
            }
            if (targetData.academic_track) {
                detailsContent += `
                    <div class="text-sm p-3 bg-gray-50 rounded-lg border border-gray-200">
                        <span class="text-gray-600 font-medium block mb-1.5">Academic Track</span>
                        <span class="font-semibold text-gray-900">${targetData.academic_track}</span>
                    </div>`;
            }
        } else {
            if (targetData.position) {
                detailsContent += `
                    <div class="text-sm p-3 bg-gray-50 rounded-lg border border-gray-200">
                        <span class="text-gray-600 font-medium block mb-1.5">Position</span>
                        <span class="font-semibold text-gray-900">${targetData.position}</span>
                    </div>`;
            }
            if (targetData.department) {
                detailsContent += `
                    <div class="text-sm p-3 bg-gray-50 rounded-lg border border-gray-200">
                        <span class="text-gray-600 font-medium block mb-1.5">Department</span>
                        <span class="font-semibold text-gray-900">${targetData.department}</span>
                    </div>`;
            }
        }
        
        if (targetData.deletion_reason) {
            detailsContent += `
                <div class="p-3 bg-red-50 rounded-lg border-2 border-red-200">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span class="text-red-800 font-bold">Deletion Reason</span>
                    </div>
                    <p class="font-medium text-gray-900 bg-white p-3 rounded-lg border border-red-300 leading-relaxed">${targetData.deletion_reason}</p>
                </div>`;
        }
        
        targetDetailsHTML = `
            <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-lg p-4 border border-gray-200">
                <div class="flex items-center gap-2 mb-3">
                    <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        ${isStudent ? 
                            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0v6"/>' :
                            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>'
                        }
                    </svg>
                    <p class="text-sm font-bold text-gray-800">${detailsTitle}</p>
                </div>
                <div class="bg-white rounded-lg p-3 space-y-2.5 shadow-sm">
                    ${detailsContent}
                </div>
            </div>`;
    }
    
    // Create card HTML matching existing PHP structure
    const cardHTML = `
        <div class="border-2 ${colors.border} rounded-xl overflow-hidden hover:shadow-lg transition-all duration-200" style="opacity: 0; transform: scale(0.95); transition: all 0.5s;">
            <div class="${colors.bg} p-5 cursor-pointer hover:opacity-90 transition-opacity" onclick="openRequestModal(${request.id})">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-4 flex-1">
                        <div class="w-12 h-12 rounded-full bg-white shadow-sm flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 ${colors.icon}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="px-3 py-1 rounded-full text-xs font-bold ${colors.badge} shadow-sm">${request.priority.toUpperCase()}</span>
                                <span class="px-2 py-1 rounded bg-white text-xs font-medium text-gray-600 shadow-sm">${request.type.replace(/_/g, ' ')}</span>
                                <span class="px-2 py-1 rounded bg-green-500 text-white text-xs font-bold shadow-sm new-badge-${request.id}">NEW</span>
                            </div>
                            <h3 class="font-bold text-gray-900 text-base mb-1">${request.title}</h3>
                            <div class="flex items-center gap-2 text-xs text-gray-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <span class="font-medium">${request.requester}</span>
                                <span class="text-gray-400">•</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>${request.requestedAt}</span>
                            </div>
                        </div>
                    </div>
                    <button onclick="event.stopPropagation(); openRequestModal(${request.id});" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold transition-colors shadow-sm">
                        View Details
                    </button>
                </div>
            </div>
            <div id="details-${request.id}" class="hidden border-t-2 ${colors.border} bg-white p-6">
                <div class="space-y-4">
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                        <div class="flex items-start gap-2 mb-2">
                            <svg class="w-5 h-5 text-gray-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <div class="flex-1">
                                <p class="text-sm font-bold text-gray-700 mb-1">Description</p>
                                <p class="text-sm text-gray-600 leading-relaxed">${description}</p>
                            </div>
                        </div>
                    </div>
                    ${reason ? `
                    <div class="bg-yellow-50 rounded-lg p-4 border-2 border-yellow-300">
                        <div class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-yellow-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="flex-1">
                                <p class="text-sm font-bold text-yellow-800 mb-2">Reason for Request</p>
                                <p class="text-sm text-yellow-900 leading-relaxed bg-white px-3 py-2 rounded border border-yellow-200">${reason}</p>
                            </div>
                        </div>
                    </div>` : ''}
                    <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                        <div class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <div class="flex-1">
                                <p class="text-sm font-bold text-gray-700 mb-1">Requested by</p>
                                <p class="text-sm text-gray-600">
                                    <span class="font-semibold">${request.requester}</span>
                                    <span class="text-gray-400 mx-1">•</span>
                                    <span class="text-blue-600">${request.requester_role || ''}</span>
                                    <span class="text-gray-400 mx-1">•</span>
                                    <span>${request.requester_module || ''}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                    ${targetDetailsHTML}
                </div>
                
                <!-- Action Buttons -->
                <div class="flex gap-3 pt-5 mt-5 border-t-2 border-gray-200">
                    <button onclick="event.stopPropagation(); confirmAndSubmit(${request.id}, 'approve')" 
                            class="flex-1 px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-xl text-sm font-bold hover:from-green-700 hover:to-green-800 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Approve Request
                    </button>
                    <button onclick="event.stopPropagation(); confirmAndSubmit(${request.id}, 'reject')" 
                            class="flex-1 px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-xl text-sm font-bold hover:from-red-700 hover:to-red-800 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Reject Request
                    </button>
                </div>
            </div>
        </div>
    `;
    
    // Insert at the beginning
    requestsContainer.insertAdjacentHTML('afterbegin', cardHTML);
    
    // Get the newly added card and animate it
    const newCard = requestsContainer.firstElementChild;
    setTimeout(() => {
        newCard.style.opacity = '1';
        newCard.style.transform = 'scale(1)';
    }, 100);
    
    // Remove NEW badge after 8 seconds
    setTimeout(() => {
        const badge = newCard.querySelector(`.new-badge-${request.id}`);
        if (badge) {
            badge.style.transition = 'opacity 0.5s';
            badge.style.opacity = '0';
            setTimeout(() => badge.remove(), 500);
        }
    }, 8000);
}

function playNotificationSound() {
    try {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.frequency.value = 800;
        oscillator.type = 'sine';
        
        gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.1);
    } catch (e) {
        // Silently fail if audio not supported
    }
}

// Clean up interval when page is hidden/closed
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        clearInterval(requestCheckInterval);
    } else {
        requestCheckInterval = setInterval(checkForNewRequests, 2000);
    }
});

// ===== PREVENT BACK BUTTON AFTER LOGOUT =====
window.addEventListener("pageshow", function(event) {
  if (event.persisted || (performance.navigation.type === 2)) window.location.reload();
});
</script>

</body>
</html>
