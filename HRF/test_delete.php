<?php
session_start();
include '../StudentLogin/db_conn.php';

// Simple test to check if delete_employee.php is working
echo "<h2>Delete Employee Test</h2>";

// Check if tables exist
$tables = ['employees', 'employee_accounts', 'employee_attendance'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "<p>✅ Table '$table' exists</p>";
    } else {
        echo "<p>❌ Table '$table' does not exist</p>";
    }
}

// Check employees
$employees = $conn->query("SELECT id_number, first_name, last_name FROM employees LIMIT 5");
echo "<h3>Sample Employees:</h3>";
if ($employees && $employees->num_rows > 0) {
    while ($emp = $employees->fetch_assoc()) {
        echo "<p>ID: " . $emp['id_number'] . " - " . $emp['first_name'] . " " . $emp['last_name'] . "</p>";
    }
} else {
    echo "<p>No employees found</p>";
}

// Test delete functionality (without actually deleting)
if (isset($_POST['test_employee_id'])) {
    $test_id = $_POST['test_employee_id'];
    
    // Check if employee exists
    $check = $conn->prepare("SELECT id_number FROM employees WHERE id_number = ?");
    $check->bind_param("s", $test_id);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        echo "<p>✅ Employee $test_id exists and can be deleted</p>";
    } else {
        echo "<p>❌ Employee $test_id not found</p>";
    }
}
?>

<form method="POST">
    <input type="text" name="test_employee_id" placeholder="Enter Employee ID to test" required>
    <button type="submit">Test Delete (Check Only)</button>
</form>

<p><a href="Dashboard.php">Back to Dashboard</a></p>
