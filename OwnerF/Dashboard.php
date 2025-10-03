<?php
session_start();
include("../StudentLogin/db_conn.php");

// Require owner login
if (!isset($_SESSION['owner_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: login.php");
    exit;
}

// Prevent caching (so back button after logout doesn't show dashboard)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Handle approval/rejection actions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $request_id = intval($_POST['request_id']);
    $action = $_POST['action']; // 'approve' or 'reject'
    $comments = trim($_POST['comments'] ?? '');
    
    if ($action === 'approve' || $action === 'reject') {
        $status = ($action === 'approve') ? 'approved' : 'rejected';
        $stmt = $conn->prepare("UPDATE owner_requests SET status = ?, reviewed_at = NOW(), reviewed_by = ?, owner_comments = ? WHERE id = ?");
        $stmt->bind_param("sssi", $status, $_SESSION['owner_username'], $comments, $request_id);
        
        if ($stmt->execute()) {
            // Log the action
            $log_stmt = $conn->prepare("INSERT INTO system_logs (action_type, performed_by, user_role, description, affected_record_id) VALUES (?, ?, ?, ?, ?)");
            $log_action = "request_" . $action;
            $log_desc = "Owner {$action}d request ID: {$request_id}";
            $log_stmt->bind_param("sssss", $log_action, $_SESSION['owner_username'], $_SESSION['role'], $log_desc, $request_id);
            $log_stmt->execute();
            
            $_SESSION['success_msg'] = "Request has been " . $action . "d successfully.";
        } else {
            $_SESSION['error_msg'] = "Error processing request: " . $conn->error;
        }
    }
    
    header("Location: Dashboard.php");
    exit;
}

// Handle success/error messages
$success_msg = $_SESSION['success_msg'] ?? '';
if ($success_msg) unset($_SESSION['success_msg']);

$error_msg = $_SESSION['error_msg'] ?? '';
if ($error_msg) unset($_SESSION['error_msg']);

// Get statistics - excluding superadmin requests
$stats_query = "SELECT 
    COUNT(*) as total_requests,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_requests,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_requests,
    SUM(CASE WHEN priority = 'critical' AND status = 'pending' THEN 1 ELSE 0 END) as critical_pending
FROM owner_requests WHERE requester_role != 'superadmin'";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Get pending requests (priority order) - excluding superadmin requests
$pending_query = "SELECT * FROM owner_requests WHERE status = 'pending' AND requester_role != 'superadmin' ORDER BY 
    CASE priority 
        WHEN 'critical' THEN 1 
        WHEN 'high' THEN 2 
        WHEN 'medium' THEN 3 
        WHEN 'low' THEN 4 
    END, requested_at ASC";
$pending_result = $conn->query($pending_query);

// Get recent activity - excluding superadmin requests
$recent_query = "SELECT * FROM owner_requests WHERE status IN ('approved', 'rejected') AND requester_role != 'superadmin' ORDER BY reviewed_at DESC LIMIT 5";
$recent_result = $conn->query($recent_query);

// Get soft-deleted students count
$deleted_students_query = "SELECT COUNT(*) as count FROM student_account WHERE deleted_at IS NOT NULL";
$deleted_students_result = $conn->query($deleted_students_query);
$deleted_students_count = $deleted_students_result ? $deleted_students_result->fetch_assoc()['count'] : 0;

// Get soft-deleted employees count  
$deleted_employees_query = "SELECT COUNT(*) as count FROM employees WHERE deleted_at IS NOT NULL";
$deleted_employees_result = $conn->query($deleted_employees_query);
$deleted_employees_count = $deleted_employees_result ? $deleted_employees_result->fetch_assoc()['count'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Owner Dashboard - Cornerstone College Inc.</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .school-gradient { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #1e40af 100%); }
        .card-shadow { box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .priority-critical { border-left: 4px solid #dc2626; background: #fef2f2; }
        .priority-high { border-left: 4px solid #ea580c; background: #fff7ed; }
        .priority-medium { border-left: 4px solid #d97706; background: #fffbeb; }
        .priority-low { border-left: 4px solid #65a30d; background: #f7fee7; }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">

<!-- Header -->
<header class="bg-[#0B2C62] text-white shadow-lg">
    <div class="container mx-auto px-6 py-4">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <div class="text-left">
                    <p class="text-sm text-blue-200">Welcome,</p>
                    <p class="font-semibold"><?= htmlspecialchars($_SESSION['owner_name'] ?? 'School Owner') ?></p>
                </div>
            </div>
            
            <div class="flex items-center space-x-4">
                <img src="../images/LogoCCI.png" alt="Cornerstone College Inc." class="h-12 w-12 rounded-full bg-white p-1">
                <div class="text-right">
                    <h1 class="text-xl font-bold">Cornerstone College Inc.</h1>
                    <p class="text-blue-200 text-sm">School Owner Portal</p>
                </div>
                <div class="relative">
                    <button id="menuBtn" class="bg-white bg-opacity-20 hover:bg-opacity-30 p-2 rounded-lg transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <div id="dropdownMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg z-50 text-gray-800">
                        <a href="SystemLogs.php" class="block px-4 py-3 hover:bg-gray-100 rounded-lg">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            System Logs
                        </a>
                        <a href="logout.php" class="block px-4 py-3 hover:bg-gray-100 rounded-lg">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="container mx-auto px-6 py-8">
    <!-- Alert Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Pending Approvals Alert -->
        <div class="bg-white rounded-xl card-shadow p-6 border-l-4 border-yellow-500">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Pending Approvals</p>
                    <p class="text-2xl font-bold text-yellow-600"><?= $stats['pending_requests'] ?></p>
                    <?php if ($stats['critical_pending'] > 0): ?>
                        <p class="text-xs text-red-600 font-medium"><?= $stats['critical_pending'] ?> Critical</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Deleted Students Alert -->
        <div class="bg-white rounded-xl card-shadow p-6 border-l-4 border-red-500">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-red-100 text-red-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Deleted Students</p>
                    <p class="text-2xl font-bold text-red-600"><?= $deleted_students_count ?></p>
                    <p class="text-xs text-gray-500">Awaiting permanent deletion</p>
                </div>
            </div>
        </div>

        <!-- Deleted Employees Alert -->
        <div class="bg-white rounded-xl card-shadow p-6 border-l-4 border-orange-500">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Deleted Employees</p>
                    <p class="text-2xl font-bold text-orange-600"><?= $deleted_employees_count ?></p>
                    <p class="text-xs text-gray-500">Awaiting permanent deletion</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Pending Approval Requests -->
        <div class="lg:col-span-2 bg-white rounded-xl card-shadow p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold text-gray-800">🔔 Approval Requests</h2>
                <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-medium">
                    <?= $stats['pending_requests'] ?> Pending
                </span>
            </div>

            <div class="space-y-4 max-h-96 overflow-y-auto">
                <?php if ($pending_result && $pending_result->num_rows > 0): ?>
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
                                        <strong>Requested by:</strong> <?= htmlspecialchars($request['requested_by']) ?> 
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

            <!-- System Status -->
            <div class="bg-white rounded-xl card-shadow p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">📊 System Overview</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Total Requests</span>
                        <span class="font-bold text-gray-900"><?= $stats['total_requests'] ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Approved</span>
                        <span class="font-bold text-green-600"><?= $stats['approved_requests'] ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Rejected</span>
                        <span class="font-bold text-red-600"><?= $stats['rejected_requests'] ?></span>
                    </div>
                </div>
            </div>
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
// Menu toggle
document.getElementById('menuBtn').addEventListener('click', function() {
    document.getElementById('dropdownMenu').classList.toggle('hidden');
});

// Close menu when clicking outside
document.addEventListener('click', function(event) {
    const menu = document.getElementById('dropdownMenu');
    const button = document.getElementById('menuBtn');
    if (!menu.contains(event.target) && !button.contains(event.target)) {
        menu.classList.add('hidden');
    }
});

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

// Auto-refresh every 30 seconds to check for new requests
setInterval(function() {
    // Only refresh if no modal is open
    if (document.getElementById('approvalModal').classList.contains('hidden')) {
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
