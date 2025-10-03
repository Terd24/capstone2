<?php
// Simple script to check and fix teacher_attendance table structure
include("StudentLogin/db_conn.php");

echo "<h2>Teacher Attendance Table Structure Check</h2>";

// Check if teacher_attendance table exists
$table_check = $conn->query("SHOW TABLES LIKE 'teacher_attendance'");
if ($table_check && $table_check->num_rows > 0) {
    echo "<p style='color: green;'>✓ teacher_attendance table exists</p>";
    
    // Show current structure
    echo "<h3>Current Table Structure:</h3>";
    $structure = $conn->query("DESCRIBE teacher_attendance");
    if ($structure) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $structure->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Check for teacher_id column specifically
    $teacher_id_check = $conn->query("SHOW COLUMNS FROM teacher_attendance LIKE 'teacher_id'");
    $employee_id_check = $conn->query("SHOW COLUMNS FROM teacher_attendance LIKE 'employee_id'");
    
    if ($teacher_id_check && $teacher_id_check->num_rows > 0) {
        echo "<p style='color: green;'>✓ teacher_id column exists</p>";
    } elseif ($employee_id_check && $employee_id_check->num_rows > 0) {
        echo "<p style='color: orange;'>⚠ employee_id column exists (should be teacher_id)</p>";
        echo "<p><strong>Recommendation:</strong> Run this SQL to rename the column:</p>";
        echo "<code>ALTER TABLE teacher_attendance CHANGE employee_id teacher_id VARCHAR(50) NOT NULL;</code>";
    } else {
        echo "<p style='color: red;'>✗ Neither teacher_id nor employee_id column found</p>";
    }
    
} else {
    echo "<p style='color: red;'>✗ teacher_attendance table does not exist</p>";
    
    // Check if employee_attendance exists
    $old_table_check = $conn->query("SHOW TABLES LIKE 'employee_attendance'");
    if ($old_table_check && $old_table_check->num_rows > 0) {
        echo "<p style='color: orange;'>⚠ employee_attendance table exists</p>";
        echo "<p><strong>Recommendation:</strong> Run this SQL to rename the table:</p>";
        echo "<code>RENAME TABLE employee_attendance TO teacher_attendance;</code>";
    } else {
        echo "<p><strong>Recommendation:</strong> Create the teacher_attendance table:</p>";
        echo "<pre>";
        echo "CREATE TABLE teacher_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id VARCHAR(50) NOT NULL,
    date DATE NOT NULL,
    day VARCHAR(20),
    time_in TIME NULL,
    time_out TIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_teacher_date (teacher_id, date),
    INDEX idx_date (date)
);";
        echo "</pre>";
    }
}

// Show sample data if table exists and has records
if ($table_check && $table_check->num_rows > 0) {
    $sample_data = $conn->query("SELECT * FROM teacher_attendance LIMIT 5");
    if ($sample_data && $sample_data->num_rows > 0) {
        echo "<h3>Sample Data (First 5 Records):</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        $first_row = true;
        while ($row = $sample_data->fetch_assoc()) {
            if ($first_row) {
                echo "<tr>";
                foreach (array_keys($row) as $header) {
                    echo "<th>" . htmlspecialchars($header) . "</th>";
                }
                echo "</tr>";
                $first_row = false;
            }
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Table exists but has no data.</p>";
    }
}

$conn->close();
?>
