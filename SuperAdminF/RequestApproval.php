<?php
session_start();
include("../StudentLogin/db_conn.php");

// Require superadmin login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: ../StudentLogin/login.php");
    exit;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $request_type = $_POST['request_type'];
    $request_title = trim($_POST['request_title']);
    $request_description = trim($_POST['request_description']);
    $priority = $_POST['priority'];
    $target_data = $_POST['target_data'] ?? '';
    
    // Validate required fields
    if (empty($request_title) || empty($request_description)) {
        $_SESSION['error_msg'] = "Title and description are required.";
    } else {
        // Prepare target data as JSON
        $target_json = null;
        if (!empty($target_data)) {
            $target_json = json_encode(['additional_data' => $target_data]);
        }
        
        // Insert request
        $stmt = $conn->prepare("INSERT INTO owner_requests (request_type, requested_by, requester_role, request_title, request_description, target_data, priority) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $requested_by = $_SESSION['superadmin_name'] ?? $_SESSION['username'] ?? 'superadmin';
        $requester_role = 'superadmin';
        
        $stmt->bind_param("sssssss", $request_type, $requested_by, $requester_role, $request_title, $request_description, $target_json, $priority);
        
        if ($stmt->execute()) {
            $_SESSION['success_msg'] = "Request submitted successfully. Awaiting owner approval.";
        } else {
            $_SESSION['error_msg'] = "Error submitting request: " . $conn->error;
        }
    }
    
    header("Location: RequestApproval.php");
    exit;
}

// Handle messages
$success_msg = $_SESSION['success_msg'] ?? '';
if ($success_msg) unset($_SESSION['success_msg']);

$error_msg = $_SESSION['error_msg'] ?? '';
if ($error_msg) unset($_SESSION['error_msg']);

// Get user's requests
$user_requests_query = "SELECT * FROM owner_requests WHERE requested_by = ? ORDER BY requested_at DESC";
$stmt = $conn->prepare($user_requests_query);
$requested_by = $_SESSION['superadmin_name'] ?? $_SESSION['username'] ?? 'superadmin';
$stmt->bind_param("s", $requested_by);
$stmt->execute();
$user_requests = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Owner Approval - SuperAdmin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">

<div class="container mx-auto px-6 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Request Owner Approval</h1>
            <p class="text-gray-600">Submit requests that require school owner approval</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Request Form -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-6">Submit New Request</h2>
                
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Request Type *</label>
                        <select name="request_type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Select Type --</option>
                            <option value="delete_student">Permanent Student Deletion</option>
                            <option value="delete_employee">Permanent Employee Deletion</option>
                            <option value="system_maintenance">System Maintenance</option>
                            <option value="database_backup">Database Backup/Restore</option>
                            <option value="user_management">User Management Changes</option>
                            <option value="security_change">Security Configuration</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Priority *</label>
                        <select name="priority" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Request Title *</label>
                        <input type="text" name="request_title" required maxlength="255" 
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
                               placeholder="Brief title for your request">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                        <textarea name="request_description" required rows="4" 
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
                                  placeholder="Detailed description of what you need approval for..."></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Additional Data (Optional)</label>
                        <textarea name="target_data" rows="3" 
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
                                  placeholder="Any additional information, IDs, or technical details..."></textarea>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg font-medium">
                        Submit Request
                    </button>
                </form>
            </div>

            <!-- My Requests -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-6">My Requests</h2>
                
                <div class="space-y-4 max-h-96 overflow-y-auto">
                    <?php if ($user_requests && $user_requests->num_rows > 0): ?>
                        <?php while ($request = $user_requests->fetch_assoc()): ?>
                            <div class="border rounded-lg p-4 <?= 
                                $request['status'] === 'pending' ? 'border-yellow-300 bg-yellow-50' : 
                                ($request['status'] === 'approved' ? 'border-green-300 bg-green-50' : 'border-red-300 bg-red-50') 
                            ?>">
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="font-semibold text-gray-900"><?= htmlspecialchars($request['request_title']) ?></h3>
                                    <div class="flex items-center space-x-2">
                                        <span class="px-2 py-1 rounded-full text-xs font-medium <?= 
                                            $request['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                            ($request['status'] === 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') 
                                        ?>">
                                            <?= ucfirst($request['status']) ?>
                                        </span>
                                        <span class="px-2 py-1 rounded-full text-xs font-medium <?= 
                                            $request['priority'] === 'critical' ? 'bg-red-100 text-red-800' : 
                                            ($request['priority'] === 'high' ? 'bg-orange-100 text-orange-800' : 
                                            ($request['priority'] === 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800')) 
                                        ?>">
                                            <?= ucfirst($request['priority']) ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <p class="text-sm text-gray-700 mb-2"><?= htmlspecialchars($request['request_description']) ?></p>
                                
                                <div class="text-xs text-gray-500">
                                    <p>Submitted: <?= date('M j, Y g:i A', strtotime($request['requested_at'])) ?></p>
                                    <?php if ($request['reviewed_at']): ?>
                                        <p>Reviewed: <?= date('M j, Y g:i A', strtotime($request['reviewed_at'])) ?></p>
                                    <?php endif; ?>
                                    <?php if ($request['owner_comments']): ?>
                                        <p class="mt-1 italic">"<?= htmlspecialchars($request['owner_comments']) ?>"</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="text-gray-500">No requests submitted yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Success/Error Notifications -->
<?php if (!empty($success_msg)): ?>
<div id="successNotif" class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full opacity-0 transition-all duration-300">
    <?= htmlspecialchars($success_msg) ?>
</div>
<?php endif; ?>

<?php if (!empty($error_msg)): ?>
<div id="errorNotif" class="fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full opacity-0 transition-all duration-300">
    <?= htmlspecialchars($error_msg) ?>
</div>
<?php endif; ?>

<script>
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
</script>

</body>
</html>
