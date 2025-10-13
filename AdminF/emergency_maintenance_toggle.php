<?php
session_start();

// Emergency maintenance toggle - requires Super Admin login
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    die('⛔ Access Denied: Super Admin only');
}

require_once '../StudentLogin/db_conn.php';

// Create table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS system_config (
    config_key VARCHAR(50) PRIMARY KEY,
    config_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by VARCHAR(50)
)");

// Get current status
$result = $conn->query("SELECT config_value FROM system_config WHERE config_key = 'maintenance_mode'");
$currentStatus = '0';
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $currentStatus = $row['config_value'];
}

// Handle toggle action
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $newValue = ($action === 'enable') ? '1' : '0';
    $updatedBy = $_SESSION['superadmin_name'] ?? 'Super Admin';
    
    $stmt = $conn->prepare("INSERT INTO system_config (config_key, config_value, updated_by) 
                            VALUES ('maintenance_mode', ?, ?) 
                            ON DUPLICATE KEY UPDATE config_value = ?, updated_by = ?");
    $stmt->bind_param('ssss', $newValue, $updatedBy, $newValue, $updatedBy);
    
    if ($stmt->execute()) {
        $currentStatus = $newValue;
        $message = ($newValue == '1') ? '✅ Maintenance Mode ENABLED' : '✅ Maintenance Mode DISABLED';
    } else {
        $message = '❌ Failed to update maintenance mode';
    }
}

$statusText = ($currentStatus == '1') ? '🔴 ENABLED' : '🟢 DISABLED';
$statusColor = ($currentStatus == '1') ? '#dc2626' : '#16a34a';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Maintenance Toggle</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen p-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-2xl shadow-lg p-8">
            <div class="flex items-center gap-3 mb-6">
                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <h1 class="text-2xl font-bold text-gray-900">Emergency Maintenance Toggle</h1>
            </div>

            <?php if (isset($message)): ?>
            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-blue-800 font-medium"><?= $message ?></p>
            </div>
            <?php endif; ?>

            <div class="mb-8 p-6 bg-gray-50 rounded-xl">
                <h2 class="text-lg font-semibold text-gray-900 mb-2">Current Status</h2>
                <p class="text-3xl font-bold" style="color: <?= $statusColor ?>"><?= $statusText ?></p>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6">
                <a href="?action=enable" 
                   class="bg-red-600 hover:bg-red-700 text-white px-6 py-4 rounded-xl font-semibold text-center transition-colors">
                    🔴 Enable Maintenance
                </a>
                <a href="?action=disable" 
                   class="bg-green-600 hover:bg-green-700 text-white px-6 py-4 rounded-xl font-semibold text-center transition-colors">
                    🟢 Disable Maintenance
                </a>
            </div>

            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <h3 class="font-semibold text-yellow-900 mb-2">⚠️ Important Notes:</h3>
                <ul class="text-sm text-yellow-800 space-y-1">
                    <li>• When ENABLED: Students cannot login</li>
                    <li>• When ENABLED: Admins can still login via admin_login.php</li>
                    <li>• Changes take effect immediately</li>
                    <li>• All actions are logged</li>
                </ul>
            </div>

            <div class="flex gap-4">
                <a href="SuperAdminDashboard.php#system-maintenance" 
                   class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-lg font-medium text-center transition-colors">
                    ← Back to Dashboard
                </a>
                <a href="test_maintenance.php" 
                   class="flex-1 bg-gray-600 hover:bg-gray-700 text-white px-4 py-3 rounded-lg font-medium text-center transition-colors">
                    Test Status
                </a>
            </div>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
