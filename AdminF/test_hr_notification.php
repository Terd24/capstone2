<?php
session_start();
include("../StudentLogin/db_conn.php");
include("../includes/log_system_notification.php");

// Set session for testing
$_SESSION['superadmin_name'] = 'Test Admin';
$_SESSION['role'] = 'superadmin';

echo "<h1>Testing HR Notification System</h1>";

// Test 1: Check if function exists
echo "<h2>Test 1: Function Exists</h2>";
if (function_exists('logSystemNotification')) {
    echo "✓ logSystemNotification function exists<br>";
} else {
    echo "✗ logSystemNotification function NOT found<br>";
}

if (function_exists('formatActionMessage')) {
    echo "✓ formatActionMessage function exists<br>";
} else {
    echo "✗ formatActionMessage function NOT found<br>";
}

// Test 2: Check database connection
echo "<h2>Test 2: Database Connection</h2>";
if ($conn && !$conn->connect_error) {
    echo "✓ Database connected<br>";
} else {
    echo "✗ Database connection failed: " . ($conn->connect_error ?? 'Unknown error') . "<br>";
}

// Test 3: Check if system_notifications table exists
echo "<h2>Test 3: System Notifications Table</h2>";
$table_check = $conn->query("SHOW TABLES LIKE 'system_notifications'");
if ($table_check && $table_check->num_rows > 0) {
    echo "✓ system_notifications table exists<br>";
    
    // Show table structure
    $structure = $conn->query("DESCRIBE system_notifications");
    echo "<table border='1' style='margin-top:10px;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while ($row = $structure->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "✗ system_notifications table NOT found<br>";
}

// Test 4: Try to insert a test notification
echo "<h2>Test 4: Insert Test Notification</h2>";
try {
    $test_title = "Test HR Employee Action";
    $test_message = "This is a test notification from HR Admin";
    $test_type = "info";
    $test_module = "HR Admin";
    $test_performed_by = "Test Admin";
    $test_role = "Super Admin";
    $test_action = "test_action";
    
    $result = logSystemNotification(
        $conn,
        $test_title,
        $test_message,
        $test_type,
        $test_module,
        $test_performed_by,
        $test_role,
        $test_action,
        'employees',
        'TEST-001',
        null,
        ['test' => 'data']
    );
    
    if ($result) {
        echo "✓ Test notification inserted successfully<br>";
        
        // Verify it was inserted
        $verify = $conn->query("SELECT * FROM system_notifications WHERE action_type = 'test_action' ORDER BY id DESC LIMIT 1");
        if ($verify && $verify->num_rows > 0) {
            $notif = $verify->fetch_assoc();
            echo "✓ Notification verified in database:<br>";
            echo "<pre>" . print_r($notif, true) . "</pre>";
        }
    } else {
        echo "✗ Failed to insert test notification<br>";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
}

// Test 5: Test formatActionMessage
echo "<h2>Test 5: Format Action Message</h2>";
try {
    $message = formatActionMessage('employee_edited', 'John Doe', 'EMP-001');
    echo "✓ Formatted message: " . $message . "<br>";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
}

// Test 6: Check recent notifications
echo "<h2>Test 6: Recent Notifications (Last 5)</h2>";
$recent = $conn->query("SELECT id, title, message, type, module, performed_by, created_at FROM system_notifications ORDER BY created_at DESC LIMIT 5");
if ($recent && $recent->num_rows > 0) {
    echo "<table border='1' style='margin-top:10px;'>";
    echo "<tr><th>ID</th><th>Title</th><th>Message</th><th>Type</th><th>Module</th><th>Performed By</th><th>Created At</th></tr>";
    while ($row = $recent->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['title']}</td>";
        echo "<td>{$row['message']}</td>";
        echo "<td>{$row['type']}</td>";
        echo "<td>{$row['module']}</td>";
        echo "<td>{$row['performed_by']}</td>";
        echo "<td>{$row['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No notifications found<br>";
}

echo "<hr>";
echo "<p><a href='SuperAdminDashboard.php'>Back to Dashboard</a></p>";
?>
