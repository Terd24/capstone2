<?php
// Script to fix the role in database
session_start();
include("../StudentLogin/db_conn.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $username = $_POST['username'];
    
    // Update the role to correct value
    $stmt = $conn->prepare("UPDATE employee_accounts SET role = 'department_head' WHERE username = ?");
    $stmt->bind_param("s", $username);
    
    if ($stmt->execute()) {
        echo "<h2 style='color: green;'>✅ Role Updated Successfully!</h2>";
        echo "<p>Username: <strong>" . htmlspecialchars($username) . "</strong></p>";
        echo "<p>New Role: <strong>department_head</strong></p>";
        echo "<hr>";
        echo "<p><a href='check_role.php?username=" . urlencode($username) . "'>Check Role Again</a></p>";
        echo "<p><a href='../admin_login.php'>Go to Login Page</a></p>";
    } else {
        echo "<h2 style='color: red;'>❌ Failed to Update Role</h2>";
        echo "<p>Error: " . $conn->error . "</p>";
    }
} else {
    echo "<h2>Invalid Request</h2>";
    echo "<p><a href='check_role.php'>Go Back</a></p>";
}
?>
