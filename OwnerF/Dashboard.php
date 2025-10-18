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
    request_type ENUM('delete_account', 'restore_account', 'system_maintenance', 'data_modification', 'user_management', 'other') NOT NULL,
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
                            // Delete HR employee and their account
                            $del_acc_stmt = $conn->prepare("DELETE FROM employee_accounts WHERE employee_id = ?");
                            $del_acc_stmt->bind_param('s', $target_id);
                            $del_acc_stmt->execute();
                            
                            $del_emp_stmt = $conn->prepare("DELETE FROM employees WHERE id_number = ?");
                            $del_emp_stmt->bind_param('s', $target_id);
                            $del_emp_stmt->execute();
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
            
            $notif_stmt = $conn->prepare("INSERT INTO system_notifications (title, message, type, module, performed_by, user_role, action_type, target_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $notif_stmt->bind_param("ssssssss", $notif_title, $notif_message, $notif_type, 'Owner', $_SESSION['owner_name'], 'owner', 'request_' . $action, $request_id);
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
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <div class="text-gray-600 text-sm font-medium">Pending</div>
                            <div class="text-2xl font-bold text-gray-800"><?= $stats['pending_requests'] ?></div>
                        </div>
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <div class="text-gray-600 text-sm font-medium">Approved</div>
                            <div class="text-2xl font-bold text-gray-800"><?= $stats['approved_requests'] ?></div>
                        </div>
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <div class="text-gray-600 text-sm font-medium">Rejected</div>
                            <div class="text-2xl font-bold text-gray-800"><?= $stats['rejected_requests'] ?></div>
                        </div>
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <div class="text-gray-600 text-sm font-medium">Total</div>
                            <div class="text-2xl font-bold text-gray-800"><?= $stats['total_requests'] ?></div>
                        </div>
                    </div>

                    <!-- Pending Requests List -->
                    <div class="space-y-4 max-h-96 overflow-y-auto">
                        <?php 
                        // Reset the result pointer for pending requests
                        $pending_result = $conn->query($pending_query);
                        if ($pending_result && $pending_result->num_rows > 0): 
                        ?>
                            <?php while ($request = $pending_result->fetch_assoc()): ?>
                                <div class="border rounded-lg p-4 priority-<?= $request['priority'] ?>">
                                    <div class="flex justify-between items-start mb-3">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-2">
                                                <h3 class="font-semibold text-gray-900"><?= htmlspecialchars($request['request_title']) ?></h3>
                                                <span class="px-2 py-1 rounded-full text-xs font-medium
                                                    <?= $request['priority'] === 'critical' ? 'bg-red-100 text-red-800' : 
                                                       ($request['priority'] === 'high' ? 'bg-orange-100 text-orange-800' : 
                                                       ($request['priority'] === 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800')) ?>">
                                                    <?= strtoupper($request['priority']) ?>
                                                </span>
                                            </div>
                                            <p class="text-sm text-gray-600 mb-2">
                                                <strong>Requested by:</strong> <?= htmlspecialchars($request['requester_name']) ?> 
                                                (<?= ucfirst($request['requester_role']) ?>) • 
                                                <strong>Type:</strong> <?= ucwords(str_replace('_', ' ', $request['request_type'])) ?>
                                            </p>
                                            <p class="text-sm text-gray-700 mb-3"><?= htmlspecialchars($request['request_description']) ?></p>
                                            
                                            <?php if ($request['target_data']): ?>
                                                <div class="bg-gray-50 rounded p-2 mb-3">
                                                    <p class="text-xs font-medium text-gray-600 mb-1">Technical Details:</p>
                                                    <pre class="text-xs text-gray-700 whitespace-pre-wrap"><?= htmlspecialchars(json_encode(json_decode($request['target_data']), JSON_PRETTY_PRINT)) ?></pre>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <p class="text-xs text-gray-500">
                                                Submitted: <?= date('M j, Y g:i A', strtotime($request['requested_at'])) ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex space-x-2 pt-3 border-t border-gray-200">
                                        <button onclick="showApprovalModal(<?= $request['id'] ?>, 'approve', '<?= htmlspecialchars($request['request_title']) ?>')" 
                                                class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 transition font-medium">
                                            ✅ Approve
                                        </button>
                                        <button onclick="showApprovalModal(<?= $request['id'] ?>, 'reject', '<?= htmlspecialchars($request['request_title']) ?>')" 
                                                class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700 transition font-medium">
                                            ❌ Reject
                                        </button>
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


<!-- Approval Modal -->
<div id="approvalModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 id="modalTitle" class="text-lg font-bold text-gray-800"></h3>
            <button onclick="closeApprovalModal()" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form method="POST">
            <input type="hidden" id="modalRequestId" name="request_id">
            <input type="hidden" id="modalAction" name="action">
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Comments (Optional)</label>
                <textarea name="comments" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Add your comments or reasoning..."></textarea>
            </div>
            
            <div class="flex space-x-3">
                <button type="button" onclick="closeApprovalModal()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2 rounded-lg">
                    Cancel
                </button>
                <button type="submit" id="modalSubmitBtn" class="flex-1 py-2 rounded-lg text-white font-medium">
                    Confirm
                </button>
            </div>
        </form>
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

// Approval modal functions
function showApprovalModal(requestId, action, title) {
    document.getElementById('modalRequestId').value = requestId;
    document.getElementById('modalAction').value = action;
    document.getElementById('modalTitle').textContent = (action === 'approve' ? '✅ Approve' : '❌ Reject') + ' Request';
    
    const submitBtn = document.getElementById('modalSubmitBtn');
    if (action === 'approve') {
        submitBtn.className = 'flex-1 py-2 rounded-lg text-white bg-green-600 hover:bg-green-700 font-medium';
        submitBtn.textContent = '✅ Approve';
    } else {
        submitBtn.className = 'flex-1 py-2 rounded-lg text-white bg-red-600 hover:bg-red-700 font-medium';
        submitBtn.textContent = '❌ Reject';
    }
    
    document.getElementById('approvalModal').classList.remove('hidden');
}

function closeApprovalModal() {
    document.getElementById('approvalModal').classList.add('hidden');
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
    // Only refresh if no modal is open and we're on dashboard
    if (document.getElementById('approvalModal').classList.contains('hidden') && 
        !document.getElementById('dashboard-section').classList.contains('hidden')) {
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
