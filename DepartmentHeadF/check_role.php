<?php
// Diagnostic script to check Department Head role in database
session_start();
include("../StudentLogin/db_conn.php");

// Get the username from the session or prompt
$username = $_SESSION['username'] ?? '';

if (empty($username)) {
    echo "<h2>Please provide username to check</h2>";
    echo "<form method='GET'>";
    echo "<input type='text' name='username' placeholder='Enter username' required>";
    echo "<button type='submit'>Check Role</button>";
    echo "</form>";
    
    if (isset($_GET['username'])) {
        $username = $_GET['username'];
    } else {
        exit;
    }
}

// Query the database
$stmt = $conn->prepare("SELECT ea.*, e.first_name, e.last_name 
                        FROM employee_accounts ea 
                        JOIN employees e ON ea.employee_id = e.id_number 
                        WHERE ea.username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $account = $result->fetch_assoc();
    
    echo "<h2>Account Information</h2>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Field</th><th>Value</th><th>Details</th></tr>";
    
    echo "<tr><td>Username</td><td>" . htmlspecialchars($account['username']) . "</td><td>-</td></tr>";
    echo "<tr><td>Employee ID</td><td>" . htmlspecialchars($account['employee_id']) . "</td><td>-</td></tr>";
    echo "<tr><td>Full Name</td><td>" . htmlspecialchars($account['first_name'] . ' ' . $account['last_name']) . "</td><td>-</td></tr>";
    
    $role = $account['role'];
    $role_length = strlen($role);
    $role_lower = strtolower(trim($role));
    
    echo "<tr><td><strong>Role (Raw)</strong></td><td><strong>" . htmlspecialchars($role) . "</strong></td><td>Length: $role_length chars</td></tr>";
    echo "<tr><td>Role (Trimmed & Lowercase)</td><td>" . htmlspecialchars($role_lower) . "</td><td>Length: " . strlen($role_lower) . " chars</td></tr>";
    echo "<tr><td>Role Bytes</td><td>" . bin2hex($role) . "</td><td>Hex representation</td></tr>";
    
    echo "<tr><td colspan='3'><hr></td></tr>";
    
    // Check what it matches
    echo "<tr><td>Matches 'department_head'?</td><td>" . ($role_lower === 'department_head' ? '✅ YES' : '❌ NO') . "</td><td>-</td></tr>";
    echo "<tr><td>Matches 'Department Head'?</td><td>" . ($role === 'Department Head' ? '✅ YES' : '❌ NO') . "</td><td>-</td></tr>";
    echo "<tr><td>Matches 'teacher'?</td><td>" . ($role_lower === 'teacher' ? '✅ YES' : '❌ NO') . "</td><td>-</td></tr>";
    
    echo "</table>";
    
    echo "<h3>Recommended Fix:</h3>";
    if ($role_lower !== 'department_head') {
        echo "<p style='color: red;'>⚠️ The role is NOT 'department_head'!</p>";
        echo "<p>Current role: <strong>" . htmlspecialchars($role) . "</strong></p>";
        echo "<p>Expected role: <strong>department_head</strong> (with underscore, all lowercase)</p>";
        
        echo "<h4>SQL Fix:</h4>";
        echo "<pre style='background: #f5f5f5; padding: 10px;'>";
        echo "UPDATE employee_accounts \n";
        echo "SET role = 'department_head' \n";
        echo "WHERE username = '" . htmlspecialchars($username) . "';\n";
        echo "</pre>";
        
        echo "<form method='POST' action='fix_role.php'>";
        echo "<input type='hidden' name='username' value='" . htmlspecialchars($username) . "'>";
        echo "<button type='submit' style='background: green; color: white; padding: 10px 20px; border: none; cursor: pointer;'>Fix Role Now</button>";
        echo "</form>";
    } else {
        echo "<p style='color: green;'>✅ Role is correct!</p>";
        echo "<p>The issue might be elsewhere. Check admin_login.php routing.</p>";
    }
    
} else {
    echo "<p style='color: red;'>❌ No account found with username: " . htmlspecialchars($username) . "</p>";
}
?>
