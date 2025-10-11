<?php
// This script adds soft delete columns to student_account and employees tables
session_start();

// Require Super Admin login
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    die('Unauthorized access');
}

$conn = new mysqli('localhost', 'root', '', 'onecci_db');
if ($conn->connect_error) {
    die('DB connection failed: ' . $conn->connect_error);
}

$results = [];

// Add soft delete columns to student_account table
try {
    $conn->query("ALTER TABLE student_account 
                  ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL DEFAULT NULL,
                  ADD COLUMN IF NOT EXISTS deleted_by VARCHAR(100) NULL DEFAULT NULL,
                  ADD COLUMN IF NOT EXISTS deleted_reason TEXT NULL DEFAULT NULL");
    $results[] = "✓ Added soft delete columns to student_account table";
} catch (Exception $e) {
    $results[] = "⚠ student_account: " . $e->getMessage();
}

// Add soft delete columns to employees table
try {
    $conn->query("ALTER TABLE employees 
                  ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL DEFAULT NULL,
                  ADD COLUMN IF NOT EXISTS deleted_by VARCHAR(100) NULL DEFAULT NULL,
                  ADD COLUMN IF NOT EXISTS deleted_reason TEXT NULL DEFAULT NULL");
    $results[] = "✓ Added soft delete columns to employees table";
} catch (Exception $e) {
    $results[] = "⚠ employees: " . $e->getMessage();
}

// Create system_logs table if it doesn't exist
try {
    $conn->query("CREATE TABLE IF NOT EXISTS system_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        action VARCHAR(100) NOT NULL,
        details TEXT,
        performed_by VARCHAR(100),
        performed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_action (action),
        INDEX idx_performed_at (performed_at)
    )");
    $results[] = "✓ System logs table ready";
} catch (Exception $e) {
    $results[] = "⚠ system_logs: " . $e->getMessage();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Soft Delete - Cornerstone College</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg p-8 max-w-2xl w-full">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Soft Delete Setup Results</h1>
        <div class="space-y-2">
            <?php foreach ($results as $result): ?>
                <div class="p-3 bg-gray-50 rounded border border-gray-200">
                    <?= htmlspecialchars($result) ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-6">
            <a href="SuperAdminDashboard.php" class="inline-block bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>
