<?php
session_start();
include("../StudentLogin/db_conn.php");

// Simple session check
if (!isset($_SESSION['owner_id'])) {
    header("Location: ../emergency_access.php");
    exit;
}

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'];
    
    // Create maintenance file method (simple file-based approach)
    $maintenance_file = "../maintenance_mode.txt";
    
    if ($action === 'enable') {
        file_put_contents($maintenance_file, "enabled");
        $message = "⚠️ Maintenance mode ENABLED";
    } elseif ($action === 'disable') {
        if (file_exists($maintenance_file)) {
            unlink($maintenance_file);
        }
        $message = "✅ Maintenance mode DISABLED";
    }
}

// Check current status
$maintenance_file = "../maintenance_mode.txt";
$is_maintenance = file_exists($maintenance_file);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Control - Owner Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">

<div class="container mx-auto px-6 py-8">
    <div class="max-w-2xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">🔧 Maintenance Control</h1>
                    <p class="text-gray-600">System maintenance mode management</p>
                </div>
                <a href="Dashboard.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                    ← Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Current Status -->
        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
            <div class="flex items-center mb-4">
                <div class="mr-4">
                    <?php if ($is_maintenance): ?>
                        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                    <?php else: ?>
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    <?php endif; ?>
                </div>
                <div>
                    <h2 class="text-xl font-bold <?= $is_maintenance ? 'text-red-800' : 'text-green-800' ?>">
                        System Status: <?= $is_maintenance ? 'MAINTENANCE MODE' : 'ONLINE' ?>
                    </h2>
                    <p class="<?= $is_maintenance ? 'text-red-600' : 'text-green-600' ?>">
                        <?= $is_maintenance ? 'Users cannot access the system' : 'System is fully accessible' ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Message -->
        <?php if (!empty($message)): ?>
            <div class="bg-blue-100 border border-blue-300 text-blue-800 px-4 py-3 rounded-lg mb-6">
                <strong><?= $message ?></strong>
            </div>
        <?php endif; ?>

        <!-- Controls -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Maintenance Controls</h3>
            
            <form method="POST" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php if ($is_maintenance): ?>
                        <button type="submit" name="action" value="disable" 
                                class="bg-green-600 hover:bg-green-700 text-white py-4 px-6 rounded-lg font-bold text-lg">
                            ✅ DISABLE Maintenance Mode
                        </button>
                        <div class="flex items-center justify-center bg-gray-100 rounded-lg p-4">
                            <span class="text-gray-500">System is in maintenance</span>
                        </div>
                    <?php else: ?>
                        <div class="flex items-center justify-center bg-gray-100 rounded-lg p-4">
                            <span class="text-gray-500">System is online</span>
                        </div>
                        <button type="submit" name="action" value="enable" 
                                class="bg-red-600 hover:bg-red-700 text-white py-4 px-6 rounded-lg font-bold text-lg">
                            ⚠️ ENABLE Maintenance Mode
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Instructions -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 mt-6">
            <h4 class="font-bold text-yellow-800 mb-2">📋 Instructions:</h4>
            <ul class="text-sm text-yellow-700 space-y-1">
                <li>• <strong>Disable Maintenance:</strong> Allows all users to access the system normally</li>
                <li>• <strong>Enable Maintenance:</strong> Blocks all user access except admin/owner</li>
                <li>• <strong>Emergency Access:</strong> Use /emergency_access.php if you get locked out</li>
                <li>• <strong>Admin Access:</strong> Owner portal always accessible during maintenance</li>
            </ul>
        </div>
    </div>
</div>

</body>
</html>
