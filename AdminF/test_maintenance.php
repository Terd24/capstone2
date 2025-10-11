<?php
// Simple test file to check maintenance mode status
$conn = new mysqli('localhost', 'root', '', 'onecci_db');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'system_config'");
if ($tableCheck->num_rows == 0) {
    echo "<h2>❌ system_config table does not exist yet</h2>";
    echo "<p>Click 'Update Configuration' button first to create the table.</p>";
    exit;
}

// Get maintenance mode status
$result = $conn->query("SELECT * FROM system_config WHERE config_key = 'maintenance_mode'");

echo "<h1>Maintenance Mode Status</h1>";
echo "<hr>";

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $status = ($row['config_value'] == '1') ? '🔴 ENABLED' : '🟢 DISABLED';
    
    echo "<h2>Status: $status</h2>";
    echo "<p><strong>Config Value:</strong> " . $row['config_value'] . "</p>";
    echo "<p><strong>Updated By:</strong> " . ($row['updated_by'] ?? 'N/A') . "</p>";
    echo "<p><strong>Updated At:</strong> " . ($row['updated_at'] ?? 'N/A') . "</p>";
    
    if ($row['config_value'] == '1') {
        echo "<hr>";
        echo "<h3>⚠️ Maintenance Mode is ACTIVE</h3>";
        echo "<ul>";
        echo "<li>Students CANNOT login</li>";
        echo "<li>Students will see maintenance page</li>";
        echo "<li>Admins CAN still login via admin_login.php</li>";
        echo "</ul>";
    } else {
        echo "<hr>";
        echo "<h3>✅ System is NORMAL</h3>";
        echo "<ul>";
        echo "<li>All users can login normally</li>";
        echo "</ul>";
    }
} else {
    echo "<h2>⚠️ No maintenance mode configuration found</h2>";
    echo "<p>Click 'Update Configuration' button to set it up.</p>";
}

$conn->close();
?>
<style>
    body { font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
    h1 { color: #1e3a8a; }
    h2 { color: #3b82f6; }
    ul { background: #f0f9ff; padding: 20px; border-radius: 8px; }
    li { margin: 10px 0; }
</style>
