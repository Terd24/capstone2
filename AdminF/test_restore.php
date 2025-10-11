<?php
// Test script to verify restore functionality
session_start();

// Simulate Super Admin session for testing
$_SESSION['role'] = 'superadmin';
$_SESSION['superadmin_name'] = 'Test Admin';

$conn = new mysqli('localhost', 'root', '', 'onecci_db');
if ($conn->connect_error) {
    die('DB connection failed: ' . $conn->connect_error);
}

echo "<h1>Restore Functionality Test</h1>";
echo "<hr>";

// Check if soft delete columns exist
echo "<h2>1. Checking Database Structure</h2>";

$tables = ['student_account', 'employees'];
foreach ($tables as $table) {
    echo "<h3>Table: $table</h3>";
    $result = $conn->query("SHOW COLUMNS FROM $table LIKE 'deleted_%'");
    if ($result && $result->num_rows > 0) {
        echo "<p style='color: green;'>✓ Soft delete columns exist:</p>";
        echo "<ul>";
        while ($row = $result->fetch_assoc()) {
            echo "<li>{$row['Field']} ({$row['Type']})</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>✗ Soft delete columns missing!</p>";
    }
}

// Check for deleted records
echo "<hr>";
echo "<h2>2. Checking Deleted Records</h2>";

echo "<h3>Deleted Students:</h3>";
$deleted_students = $conn->query("SELECT id_number, first_name, last_name, deleted_at, deleted_by FROM student_account WHERE deleted_at IS NOT NULL");
if ($deleted_students && $deleted_students->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Deleted At</th><th>Deleted By</th><th>Action</th></tr>";
    while ($row = $deleted_students->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id_number']}</td>";
        echo "<td>{$row['first_name']} {$row['last_name']}</td>";
        echo "<td>{$row['deleted_at']}</td>";
        echo "<td>{$row['deleted_by']}</td>";
        echo "<td><button onclick='testRestore(\"{$row['id_number']}\", \"student\")'>Test Restore</button></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No deleted students found</p>";
}

echo "<h3>Deleted Employees:</h3>";
$deleted_employees = $conn->query("SELECT id_number, first_name, last_name, deleted_at, deleted_by FROM employees WHERE deleted_at IS NOT NULL");
if ($deleted_employees && $deleted_employees->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Name</th><th>Deleted At</th><th>Deleted By</th><th>Action</th></tr>";
    while ($row = $deleted_employees->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id_number']}</td>";
        echo "<td>{$row['first_name']} {$row['last_name']}</td>";
        echo "<td>{$row['deleted_at']}</td>";
        echo "<td>{$row['deleted_by']}</td>";
        echo "<td><button onclick='testRestore(\"{$row['id_number']}\", \"employee\")'>Test Restore</button></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No deleted employees found</p>";
}

// Check system_logs table
echo "<hr>";
echo "<h2>3. Checking System Logs Table</h2>";
$logs_check = $conn->query("SHOW TABLES LIKE 'system_logs'");
if ($logs_check && $logs_check->num_rows > 0) {
    echo "<p style='color: green;'>✓ system_logs table exists</p>";
    $recent_logs = $conn->query("SELECT * FROM system_logs WHERE action LIKE 'RESTORE_%' ORDER BY performed_at DESC LIMIT 5");
    if ($recent_logs && $recent_logs->num_rows > 0) {
        echo "<h3>Recent Restore Logs:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Action</th><th>Details</th><th>Performed By</th><th>Time</th></tr>";
        while ($row = $recent_logs->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['action']}</td>";
            echo "<td>{$row['details']}</td>";
            echo "<td>{$row['performed_by']}</td>";
            echo "<td>{$row['performed_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No restore logs found yet</p>";
    }
} else {
    echo "<p style='color: orange;'>⚠ system_logs table doesn't exist (will be created automatically)</p>";
}

$conn->close();
?>

<script>
function testRestore(recordId, recordType) {
    if (confirm(`Test restore ${recordType} with ID: ${recordId}?`)) {
        fetch('restore_record.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'restore',
                record_type: recordType,
                record_id: recordId
            })
        })
        .then(response => response.json())
        .then(data => {
            alert(data.success ? 'Success: ' + data.message : 'Error: ' + data.message);
            if (data.success) {
                location.reload();
            }
        })
        .catch(error => {
            alert('Error: ' + error);
        });
    }
}
</script>

<style>
body { font-family: Arial, sans-serif; padding: 20px; }
table { border-collapse: collapse; margin: 10px 0; }
th { background-color: #0B2C62; color: white; }
button { background-color: #2F8D46; color: white; padding: 5px 10px; border: none; cursor: pointer; border-radius: 4px; }
button:hover { background-color: #256f37; }
</style>
