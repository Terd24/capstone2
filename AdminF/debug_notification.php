<?php
// Simple debug script to test notification immediately
session_start();
$_SESSION['superadmin_name'] = 'Debug Admin';
$_SESSION['role'] = 'superadmin';

include("../StudentLogin/db_conn.php");
include("../includes/log_system_notification.php");

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug HR Notifications</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { color: blue; }
        pre { background: #f4f4f4; padding: 10px; border: 1px solid #ddd; }
        button { padding: 10px 20px; margin: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>HR Notification Debug Tool</h1>
    
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        echo "<h2>Testing: $action</h2>";
        
        try {
            if ($action === 'test_add') {
                $result = logSystemNotification(
                    $conn,
                    "New HR Employee Added",
                    "Added new employee: Test Employee (ID: TEST-ADD-001)",
                    'success',
                    'Super Admin',
                    'Debug Admin',
                    'Super Admin',
                    'employee_added',
                    'employees',
                    'TEST-ADD-001',
                    null,
                    ['name' => 'Test Employee', 'position' => 'Test Position']
                );
                
                if ($result) {
                    echo "<p class='success'>✓ ADD notification logged successfully!</p>";
                } else {
                    echo "<p class='error'>✗ Failed to log ADD notification</p>";
                }
            }
            
            if ($action === 'test_edit') {
                $result = logSystemNotification(
                    $conn,
                    "HR Employee Account Edited",
                    "Edited employee account: Test Employee (ID: TEST-EDIT-001)",
                    'info',
                    'Super Admin',
                    'Debug Admin',
                    'Super Admin',
                    'employee_edited',
                    'employees',
                    'TEST-EDIT-001',
                    null,
                    ['name' => 'Test Employee', 'position' => 'Updated Position']
                );
                
                if ($result) {
                    echo "<p class='success'>✓ EDIT notification logged successfully!</p>";
                } else {
                    echo "<p class='error'>✗ Failed to log EDIT notification</p>";
                }
            }
            
            if ($action === 'test_delete') {
                $result = logSystemNotification(
                    $conn,
                    "HR Employee Account Deleted",
                    "Deleted employee account: Test Employee (ID: TEST-DEL-001)",
                    'warning',
                    'Super Admin',
                    'Debug Admin',
                    'Super Admin',
                    'employee_deleted',
                    'employees',
                    'TEST-DEL-001',
                    ['name' => 'Test Employee', 'position' => 'Test Position'],
                    null
                );
                
                if ($result) {
                    echo "<p class='success'>✓ DELETE notification logged successfully!</p>";
                } else {
                    echo "<p class='error'>✗ Failed to log DELETE notification</p>";
                }
            }
            
            // Show the notification in database
            $check = $conn->query("SELECT * FROM system_notifications WHERE performed_by = 'Debug Admin' ORDER BY created_at DESC LIMIT 1");
            if ($check && $check->num_rows > 0) {
                $notif = $check->fetch_assoc();
                echo "<h3>Notification in Database:</h3>";
                echo "<pre>" . print_r($notif, true) . "</pre>";
            }
            
        } catch (Exception $e) {
            echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }
    }
    ?>
    
    <hr>
    
    <h2>Test Actions</h2>
    <form method="POST">
        <button type="submit" name="action" value="test_add">Test ADD Employee Notification</button>
    </form>
    
    <form method="POST">
        <button type="submit" name="action" value="test_edit">Test EDIT Employee Notification</button>
    </form>
    
    <form method="POST">
        <button type="submit" name="action" value="test_delete">Test DELETE Employee Notification</button>
    </form>
    
    <hr>
    
    <h2>Recent Notifications (Last 10)</h2>
    <?php
    $recent = $conn->query("SELECT id, title, message, type, module, performed_by, user_role, action_type, created_at, is_read 
                            FROM system_notifications 
                            ORDER BY created_at DESC 
                            LIMIT 10");
    
    if ($recent && $recent->num_rows > 0) {
        echo "<table border='1' cellpadding='5' cellspacing='0' style='width:100%; margin-top:10px;'>";
        echo "<tr style='background:#f0f0f0;'>";
        echo "<th>ID</th><th>Title</th><th>Message</th><th>Type</th><th>Module</th><th>Performed By</th><th>Role</th><th>Action</th><th>Created</th><th>Read</th>";
        echo "</tr>";
        
        while ($row = $recent->fetch_assoc()) {
            $read_status = $row['is_read'] ? 'Yes' : 'No';
            $row_color = $row['is_read'] ? '#f9f9f9' : '#ffffcc';
            echo "<tr style='background:$row_color;'>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['title']}</td>";
            echo "<td>{$row['message']}</td>";
            echo "<td>{$row['type']}</td>";
            echo "<td>{$row['module']}</td>";
            echo "<td>{$row['performed_by']}</td>";
            echo "<td>{$row['user_role']}</td>";
            echo "<td>{$row['action_type']}</td>";
            echo "<td>{$row['created_at']}</td>";
            echo "<td>$read_status</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='info'>No notifications found in database.</p>";
    }
    ?>
    
    <hr>
    <p><a href="SuperAdminDashboard.php">← Back to Dashboard</a> | <a href="../OwnerF/Dashboard.php">View Owner Notifications</a></p>
</body>
</html>
