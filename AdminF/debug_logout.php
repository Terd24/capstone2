<?php
session_start();
require_once '../StudentLogin/db_conn.php';

echo "<h2>Logout Debugging</h2>";

// Check if columns exist
echo "<h3>1. Checking table structure:</h3>";
$columns = $conn->query("SHOW COLUMNS FROM login_activity");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
while ($col = $columns->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$col['Field']}</td>";
    echo "<td>{$col['Type']}</td>";
    echo "<td>{$col['Null']}</td>";
    echo "<td>{$col['Default']}</td>";
    echo "</tr>";
}
echo "</table>";

// Check recent login records
echo "<h3>2. Recent login records (last 5):</h3>";
$recent = $conn->query("SELECT * FROM login_activity ORDER BY login_time DESC LIMIT 5");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Username</th><th>ID Number</th><th>Login Time</th><th>Logout Time</th><th>Duration</th></tr>";
while ($row = $recent->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>{$row['username']}</td>";
    echo "<td>" . ($row['id_number'] ?? 'N/A') . "</td>";
    echo "<td>{$row['login_time']}</td>";
    echo "<td>" . ($row['logout_time'] ?? '<span style="color:red">NULL</span>') . "</td>";
    echo "<td>" . ($row['session_duration'] ?? '<span style="color:red">NULL</span>') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check session info
echo "<h3>3. Current session info:</h3>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "\n";
echo "ID Number: " . ($_SESSION['id_number'] ?? 'Not set') . "\n";
echo "Username: " . ($_SESSION['username'] ?? 'Not set') . "\n";
echo "Role: " . ($_SESSION['role'] ?? 'Not set') . "\n";
echo "</pre>";

// Test update query
if (isset($_SESSION['id_number'])) {
    $id_number = $_SESSION['id_number'];
    echo "<h3>4. Testing update query:</h3>";
    
    // Show what would be updated
    $test = $conn->prepare("SELECT id, username, id_number, login_time, logout_time FROM login_activity WHERE id_number = ? AND (logout_time IS NULL OR logout_time = '0000-00-00 00:00:00') ORDER BY login_time DESC LIMIT 1");
    $test->bind_param('s', $id_number);
    $test->execute();
    $result = $test->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo "<p style='color:green'>✓ Found record to update:</p>";
        echo "<pre>";
        print_r($row);
        echo "</pre>";
    } else {
        echo "<p style='color:red'>✗ No matching record found for id_number: $id_number</p>";
    }
    $test->close();
}

echo "<p><a href='SuperAdminDashboard.php'>← Back to Dashboard</a></p>";

$conn->close();
?>
