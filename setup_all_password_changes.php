<?php
// Complete setup script for first-time password change feature
// For both students and employees
require_once 'StudentLogin/db_conn.php';

echo "<h2>Setting up First-Time Password Change Feature...</h2>";
echo "<hr>";

// 1. Setup for Students
echo "<h3>1. Student Accounts</h3>";
$sql_student = "ALTER TABLE student_account 
                ADD COLUMN IF NOT EXISTS must_change_password TINYINT(1) DEFAULT 1 
                COMMENT 'Force password change on first login (1=yes, 0=no)'";

if ($conn->query($sql_student)) {
    echo "<p style='color:green;'>✅ Student table updated successfully!</p>";
} else {
    echo "<p style='color:red;'>❌ Error: " . $conn->error . "</p>";
}

// 2. Setup for Employees
echo "<h3>2. Employee Accounts</h3>";
$sql_employee = "ALTER TABLE employee_accounts 
                 ADD COLUMN IF NOT EXISTS must_change_password TINYINT(1) DEFAULT 1 
                 COMMENT 'Force password change on first login (1=yes, 0=no)'";

if ($conn->query($sql_employee)) {
    echo "<p style='color:green;'>✅ Employee table updated successfully!</p>";
} else {
    echo "<p style='color:red;'>❌ Error: " . $conn->error . "</p>";
}

echo "<hr>";

// 3. Options for existing accounts
echo "<h3>3. Existing Accounts Options</h3>";
echo "<p>Choose what to do with existing accounts:</p>";

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'keep_student':
            $conn->query("UPDATE student_account SET must_change_password = 0");
            echo "<p style='color:green;'>✅ Existing students can keep their passwords!</p>";
            break;
        case 'force_student':
            $conn->query("UPDATE student_account SET must_change_password = 1");
            echo "<p style='color:orange;'>⚠️ All students will be forced to change password!</p>";
            break;
        case 'keep_employee':
            $conn->query("UPDATE employee_accounts SET must_change_password = 0");
            echo "<p style='color:green;'>✅ Existing employees can keep their passwords!</p>";
            break;
        case 'force_employee':
            $conn->query("UPDATE employee_accounts SET must_change_password = 1");
            echo "<p style='color:orange;'>⚠️ All employees will be forced to change password!</p>";
            break;
    }
}

echo "<div style='margin: 20px 0;'>";
echo "<h4>Students:</h4>";
echo "<a href='?action=keep_student' style='padding:10px; background:#28a745; color:white; text-decoration:none; border-radius:5px; margin-right:10px;'>Keep Student Passwords</a>";
echo "<a href='?action=force_student' style='padding:10px; background:#dc3545; color:white; text-decoration:none; border-radius:5px;'>Force Students to Change</a>";
echo "</div>";

echo "<div style='margin: 20px 0;'>";
echo "<h4>Employees:</h4>";
echo "<a href='?action=keep_employee' style='padding:10px; background:#28a745; color:white; text-decoration:none; border-radius:5px; margin-right:10px;'>Keep Employee Passwords</a>";
echo "<a href='?action=force_employee' style='padding:10px; background:#dc3545; color:white; text-decoration:none; border-radius:5px;'>Force Employees to Change</a>";
echo "</div>";

echo "<hr>";
echo "<h3>✅ Setup Complete!</h3>";
echo "<p><strong>Features Enabled:</strong></p>";
echo "<ul>";
echo "<li>✅ Students created by Registrar will be forced to change password on first login</li>";
echo "<li>✅ Employees created by Admin will be forced to change password on first login</li>";
echo "<li>✅ Password requirements: 8+ chars, uppercase, lowercase, number, no spaces</li>";
echo "<li>✅ Real-time validation with visual feedback</li>";
echo "</ul>";

echo "<p style='margin-top:30px;'>";
echo "<a href='StudentLogin/login.php' style='padding:10px 20px; background:#007bff; color:white; text-decoration:none; border-radius:5px;'>Go to Login Page</a>";
echo "</p>";

$conn->close();
?>
