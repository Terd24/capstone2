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
$conn->query("ALTER TABLE owner_approval_requests MODIFY request_type ENUM('delete_account', 'restore_account', 'system_maintenance', 'data_modification', 'user_management', 'add_hr_employee', 'delete_hr_employee', 'other') NOT NULL");

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
                                
                                // Insert employee
                                $emp_stmt = $conn->prepare("INSERT INTO employees (id_number, first_name, middle_name, last_name, position, department, email, phone, address, hire_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                                $emp_stmt->bind_param("ssssssssss", 
                                    $target_data['id_number'],
                                    $target_data['first_name'],
                                    $target_data['middle_name'],
                                    $target_data['last_name'],
                                    $target_data['position'],
                                    $target_data['department'],
                                    $target_data['email'],
                                    $target_data['phone'],
                                    $target_data['address'],
                                    $target_data['hire_date']
                                );
                                $emp_stmt->execute();
                                
                                // Create account
                                $hashed_password = password_hash($target_data['password'], PASSWORD_DEFAULT);
                                $acc_stmt = $conn->prepare("INSERT INTO employee_accounts (employee_id, username, password, role) VALUES (?, ?, ?, ?)");
                                $acc_stmt->bind_param("ssss", 
                                    $target_data['id_number'],
                                    $target_data['username'],
                                    $hashed_password,
                                    $target_data['role']
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
                                <div class="border rounded-lg p-4 <?= $notification['is_read'] ? 'bg-gray-50' : 'bg-white border-l-4 border-blue-500' ?>">
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
                                            <form method="POST" class="ml-4">
                                                <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                                                <button type="submit" name="mark_read" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
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
                                    <div class="<?= $colors['bg'] ?> p-5 cursor-pointer hover:opacity-90 transition-opacity" onclick="toggleRequestDetails(<?= $request['id'] ?>)">
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
                                            
                                            <!-- Arrow -->
                                            <div class="flex-shrink-0">
                                                <svg id="arrow-<?= $request['id'] ?>" class="w-6 h-6 text-gray-600 transform transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Detailed View (Hidden by Default) -->
                                    <div id="details-<?= $request['id'] ?>" class="hidden border-t-2 <?= $colors['border'] ?> bg-white p-6">
                                        <div class="space-y-4">
                                            <!-- Description -->
                                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                                <div class="flex items-start gap-2 mb-2">
                                                    <svg class="w-5 h-5 text-gray-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                    </svg>
                                                    <div class="flex-1">
                                                        <p class="text-sm font-bold text-gray-700 mb-1">Description</p>
                                                        <p class="text-sm text-gray-600 leading-relaxed"><?= htmlspecialchars($request['request_description']) ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                            
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
                                            
                                            <!-- Employee Details -->
                                            <?php if ($request['target_data']): 
                                                $target_data = json_decode($request['target_data'], true);
                                            ?>
                                                <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-lg p-4 border border-gray-200">
                                                    <div class="flex items-center gap-2 mb-3">
                                                        <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                                        </svg>
                                                        <p class="text-sm font-bold text-gray-800">Employee Details</p>
                                                    </div>
                                                    <div class="bg-white rounded-lg p-3 space-y-2.5 shadow-sm">
                                                        <?php if (isset($target_data['id_number'])): ?>
                                                            <div class="text-sm p-3 bg-gray-50 rounded-lg border border-gray-200">
                                                                <span class="text-gray-600 font-medium block mb-1.5">Employee ID</span>
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
    
    // Add active class to clicked nav item
    if (event) {
        event.target.closest('.nav-item').classList.add('active');
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

// Toggle request details
function toggleRequestDetails(requestId) {
    const detailsDiv = document.getElementById('details-' + requestId);
    const arrow = document.getElementById('arrow-' + requestId);
    
    if (detailsDiv.classList.contains('hidden')) {
        detailsDiv.classList.remove('hidden');
        arrow.classList.add('rotate-180');
    } else {
        detailsDiv.classList.add('hidden');
        arrow.classList.remove('rotate-180');
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

// Show notifications
document.addEventListener('DOMContentLoaded', function() {
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

// Auto-refresh every 30 seconds to check for new requests (only on dashboard)
setInterval(function() {
    // Only refresh if we're on dashboard
    if (!document.getElementById('dashboard-section').classList.contains('hidden')) {
        window.location.reload();
    }
}, 30000);

// ===== PREVENT BACK BUTTON AFTER LOGOUT =====
window.addEventListener("pageshow", function(event) {
  if (event.persisted || (performance.navigation.type === 2)) window.location.reload();
});
</script>

</body>
</html>
