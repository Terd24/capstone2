<?php
/**
 * Setup Logout Time Tracking
 * Adds logout_time and last_activity columns to login tracking tables
 */

require_once '../StudentLogin/db_conn.php';

echo "<h2>Setting up Logout Time Tracking...</h2>";

// Check which login table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'login_activity'");
$tableName = ($tableCheck && $tableCheck->num_rows > 0) ? 'login_activity' : 'system_logs';

echo "<p>Using table: <strong>$tableName</strong></p>";

// Add columns to active login table
$alterQueries = [
    "ALTER TABLE $tableName ADD COLUMN IF NOT EXISTS logout_time DATETIME NULL",
    "ALTER TABLE $tableName ADD COLUMN IF NOT EXISTS last_activity DATETIME NULL",
    "ALTER TABLE $tableName ADD COLUMN IF NOT EXISTS session_duration INT NULL COMMENT 'Duration in seconds'",
    "ALTER TABLE $tableName ADD INDEX IF NOT EXISTS idx_logout_time (logout_time)",
    "ALTER TABLE $tableName ADD INDEX IF NOT EXISTS idx_last_activity (last_activity)"
];

foreach ($alterQueries as $query) {
    if ($conn->query($query)) {
        echo "✓ " . substr($query, 0, 80) . "...<br>";
    } else {
        echo "✗ Error: " . $conn->error . "<br>";
    }
}

// Add columns to archive table
echo "<br><h3>Updating Archive Table...</h3>";

$archiveQueries = [
    "ALTER TABLE login_logs_archive ADD COLUMN IF NOT EXISTS logout_time DATETIME NULL",
    "ALTER TABLE login_logs_archive ADD COLUMN IF NOT EXISTS last_activity DATETIME NULL",
    "ALTER TABLE login_logs_archive ADD COLUMN IF NOT EXISTS session_duration INT NULL"
];

foreach ($archiveQueries as $query) {
    if ($conn->query($query)) {
        echo "✓ " . substr($query, 0, 80) . "...<br>";
    } else {
        echo "✗ Error: " . $conn->error . "<br>";
    }
}

echo "<br><h3>✅ Setup Complete!</h3>";
echo "<p>Logout time tracking is now enabled.</p>";
echo "<p><a href='SuperAdminDashboard.php'>← Back to Dashboard</a></p>";

$conn->close();
?>
