<?php
include("../StudentLogin/db_conn.php");

// Test if employees table exists
$result = $conn->query("SHOW TABLES LIKE 'employees'");
if ($result->num_rows > 0) {
    echo "Table 'employees' exists<br>";
    
    // Show table structure
    $structure = $conn->query("DESCRIBE employees");
    echo "<h3>Table Structure:</h3>";
    while ($row = $structure->fetch_assoc()) {
        echo $row['Field'] . " - " . $row['Type'] . "<br>";
    }
    
    // Test UPDATE query
    $test_stmt = $conn->prepare("UPDATE employees SET first_name = ? WHERE id_number = ?");
    if ($test_stmt) {
        echo "<br>UPDATE statement prepared successfully";
    } else {
        echo "<br>ERROR preparing UPDATE: " . $conn->error;
    }
} else {
    echo "Table 'employees' does NOT exist<br>";
    echo "Available tables:<br>";
    $tables = $conn->query("SHOW TABLES");
    while ($row = $tables->fetch_array()) {
        echo "- " . $row[0] . "<br>";
    }
}
?>
