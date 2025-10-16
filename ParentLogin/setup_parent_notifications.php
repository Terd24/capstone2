<?php
// Setup script for parent notifications system
// Run this once to create the parent_notifications table

include('../StudentLogin/db_conn.php');

echo "<h2>Parent Notifications System Setup</h2>";
echo "<p>Setting up parent_notifications table...</p>";

// Create parent_notifications table
$sql = "CREATE TABLE IF NOT EXISTS parent_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_id VARCHAR(50) NOT NULL,
    child_id VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    date_sent DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_read TINYINT(1) DEFAULT 0,
    INDEX idx_parent_child (parent_id, child_id),
    INDEX idx_is_read (is_read),
    INDEX idx_date_sent (date_sent)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color: green;'>✓ Table 'parent_notifications' created successfully or already exists.</p>";
    
    // Check if table has any records
    $count_result = $conn->query("SELECT COUNT(*) as count FROM parent_notifications");
    $count = $count_result->fetch_assoc()['count'];
    echo "<p>Current notification count: <strong>$count</strong></p>";
    
    echo "<h3>Setup Complete!</h3>";
    echo "<p>The parent notification system is ready to use.</p>";
    echo "<ul>";
    echo "<li>Parents of Kinder students will see a notification bell in their dashboard</li>";
    echo "<li>Notifications are sent when document requests are submitted or updated</li>";
    echo "<li>Use the helper function in send_parent_notification.php to send custom notifications</li>";
    echo "</ul>";
    
    echo "<h3>Test Notification</h3>";
    echo "<p>To test the system, you can insert a sample notification:</p>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
    echo "INSERT INTO parent_notifications (parent_id, child_id, message)\n";
    echo "VALUES ('YOUR_PARENT_ID', 'YOUR_CHILD_ID', '🔔 Test: Welcome to the notification system!');\n";
    echo "</pre>";
    
} else {
    echo "<p style='color: red;'>✗ Error creating table: " . $conn->error . "</p>";
}

$conn->close();

echo "<hr>";
echo "<p><a href='ParentDashboard.php'>← Back to Parent Dashboard</a></p>";
?>
