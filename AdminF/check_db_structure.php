<?php
// Check database structure for soft delete system
$conn = new mysqli('localhost', 'root', '', 'onecci_db');
if ($conn->connect_error) {
    die('DB connection failed: ' . $conn->connect_error);
}

echo "<h2>Database Structure Check</h2>";

$tables = ['student_account', 'employees'];
foreach ($tables as $table) {
    echo "<h3>Table: $table</h3>";
    
    // Check if soft delete columns exist
    $result = $conn->query("SHOW COLUMNS FROM $table LIKE 'deleted_at'");
    if ($result->num_rows > 0) {
        echo "<p style='color: green;'>✅ deleted_at column exists</p>";
        
        // Check for deleted records
        $count_result = $conn->query("SELECT COUNT(*) as count FROM $table WHERE deleted_at IS NOT NULL");
        $count = $count_result->fetch_assoc()['count'];
        echo "<p>Deleted records: <strong>$count</strong></p>";
        
        if ($count > 0) {
            echo "<h4>Sample deleted records:</h4>";
            $sample = $conn->query("SELECT id_number, first_name, last_name, deleted_at, deleted_by, deleted_reason FROM $table WHERE deleted_at IS NOT NULL LIMIT 5");
            echo "<ul>";
            while ($row = $sample->fetch_assoc()) {
                echo "<li>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . " (ID: " . htmlspecialchars($row['id_number']) . ") deleted on " . $row['deleted_at'] . "</li>";
            }
            echo "</ul>";
        }
    } else {
        echo "<p style='color: red;'>❌ deleted_at column missing</p>";
        echo "<p>Run this SQL to add the column:</p>";
        echo "<pre>ALTER TABLE $table ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL;</pre>";
        echo "<pre>ALTER TABLE $table ADD COLUMN deleted_by VARCHAR(255) NULL;</pre>";
        echo "<pre>ALTER TABLE $table ADD COLUMN deleted_reason TEXT NULL;</pre>";
    }
}
?>
