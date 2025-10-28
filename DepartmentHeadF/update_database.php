<?php
// Script to update the database to support department_head role
session_start();
include("../StudentLogin/db_conn.php");

echo "<h1>Database Update Script</h1>";
echo "<p>This script will update the employee_accounts table to support the 'department_head' role.</p>";
echo "<hr>";

try {
    // Check if employee_accounts table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'employee_accounts'");
    
    if ($table_check->num_rows == 0) {
        echo "<p style='color: orange;'>⚠️ employee_accounts table does not exist yet.</p>";
        echo "<p>It will be created automatically when you add the first employee.</p>";
    } else {
        echo "<p style='color: green;'>✅ employee_accounts table exists</p>";
        
        // Check current ENUM values
        $roleColumn = $conn->query("SHOW COLUMNS FROM employee_accounts LIKE 'role'")->fetch_assoc();
        
        if ($roleColumn) {
            echo "<h3>Current Role Column Definition:</h3>";
            echo "<pre>" . htmlspecialchars($roleColumn['Type']) . "</pre>";
            
            // Check if department_head is already in the ENUM
            if (strpos($roleColumn['Type'], "'department_head'") !== false) {
                echo "<p style='color: green;'>✅ 'department_head' role is already supported!</p>";
                echo "<p>No database update needed.</p>";
            } else {
                echo "<p style='color: orange;'>⚠️ 'department_head' role is NOT in the ENUM</p>";
                echo "<p>Updating database...</p>";
                
                // Update the ENUM to include department_head
                $update_sql = "ALTER TABLE employee_accounts MODIFY role ENUM('registrar','cashier','guidance','attendance','hr','teacher','department_head') NOT NULL";
                
                if ($conn->query($update_sql)) {
                    echo "<p style='color: green;'>✅ Database updated successfully!</p>";
                    echo "<p>The 'department_head' role is now supported.</p>";
                    
                    // Verify the update
                    $roleColumn = $conn->query("SHOW COLUMNS FROM employee_accounts LIKE 'role'")->fetch_assoc();
                    echo "<h3>Updated Role Column Definition:</h3>";
                    echo "<pre>" . htmlspecialchars($roleColumn['Type']) . "</pre>";
                } else {
                    echo "<p style='color: red;'>❌ Failed to update database</p>";
                    echo "<p>Error: " . $conn->error . "</p>";
                }
            }
        } else {
            echo "<p style='color: red;'>❌ Could not read role column definition</p>";
        }
    }
    
    echo "<hr>";
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>If you already created a Department Head account, check its role using <a href='check_role.php'>check_role.php</a></li>";
    echo "<li>If the role is wrong, fix it using <a href='fix_role.php'>fix_role.php</a></li>";
    echo "<li>Try logging in again at <a href='../admin_login.php'>admin_login.php</a></li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
