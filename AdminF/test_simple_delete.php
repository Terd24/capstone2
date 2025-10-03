<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'onecci_db');
if ($conn->connect_error) {
    die('DB connection failed: ' . $conn->connect_error);
}

echo "<h2>Simple Delete Test</h2>";

// Show current students
echo "<h3>Current Students:</h3>";
$students = $conn->query("SELECT id_number, first_name, last_name, deleted_at FROM student_account ORDER BY id_number LIMIT 10");
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>ID</th><th>Name</th><th>Status</th><th>Action</th></tr>";

while ($row = $students->fetch_assoc()) {
    $status = $row['deleted_at'] ? "🔴 DELETED" : "✅ ACTIVE";
    $bg_color = $row['deleted_at'] ? "background-color: #ffebee;" : "";
    
    echo "<tr style='$bg_color'>";
    echo "<td>" . htmlspecialchars($row['id_number']) . "</td>";
    echo "<td>" . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . "</td>";
    echo "<td>$status</td>";
    
    if (!$row['deleted_at']) {
        echo "<td>";
        echo "<form method='post' style='display: inline;'>";
        echo "<input type='hidden' name='test_delete_id' value='" . htmlspecialchars($row['id_number']) . "'>";
        echo "<button type='submit' style='background: red; color: white; padding: 5px 10px; border: none; border-radius: 3px;'>Test Delete</button>";
        echo "</form>";
        echo "</td>";
    } else {
        echo "<td>Already Deleted</td>";
    }
    echo "</tr>";
}
echo "</table>";

// Handle test delete
if ($_POST['test_delete_id'] ?? false) {
    $student_id = $_POST['test_delete_id'];
    echo "<h3>Testing Delete for Student ID: $student_id</h3>";
    
    // First ensure columns exist
    echo "<p>1. Creating soft delete columns...</p>";
    $result1 = $conn->query("ALTER TABLE student_account ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL DEFAULT NULL");
    $result2 = $conn->query("ALTER TABLE student_account ADD COLUMN IF NOT EXISTS deleted_by VARCHAR(255) NULL");
    $result3 = $conn->query("ALTER TABLE student_account ADD COLUMN IF NOT EXISTS deleted_reason TEXT NULL");
    
    if ($result1 && $result2 && $result3) {
        echo "<p style='color: green;'>✅ Columns created/verified successfully</p>";
    } else {
        echo "<p style='color: red;'>❌ Error creating columns: " . $conn->error . "</p>";
    }
    
    // Test the soft delete
    echo "<p>2. Performing soft delete...</p>";
    $delete_stmt = $conn->prepare("UPDATE student_account SET deleted_at = NOW(), deleted_by = ?, deleted_reason = ? WHERE id_number = ?");
    $deleted_by = 'Test Script';
    $deleted_reason = 'Test deletion';
    $delete_stmt->bind_param("sss", $deleted_by, $deleted_reason, $student_id);
    
    if ($delete_stmt->execute()) {
        $affected_rows = $delete_stmt->affected_rows;
        echo "<p style='color: green;'>✅ Delete executed successfully! Affected rows: $affected_rows</p>";
        
        // Verify the delete
        $verify = $conn->query("SELECT deleted_at, deleted_by FROM student_account WHERE id_number = '$student_id'");
        $row = $verify->fetch_assoc();
        echo "<p><strong>Verification:</strong></p>";
        echo "<ul>";
        echo "<li>deleted_at: " . ($row['deleted_at'] ?: 'NULL') . "</li>";
        echo "<li>deleted_by: " . ($row['deleted_by'] ?: 'NULL') . "</li>";
        echo "</ul>";
        
        echo "<p><a href='?' style='background: blue; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>Refresh to See Results</a></p>";
    } else {
        echo "<p style='color: red;'>❌ Delete failed: " . $delete_stmt->error . "</p>";
    }
    $delete_stmt->close();
}

// Show counts
echo "<h3>Summary:</h3>";
$total = $conn->query("SELECT COUNT(*) as count FROM student_account")->fetch_assoc()['count'];
$active = $conn->query("SELECT COUNT(*) as count FROM student_account WHERE deleted_at IS NULL")->fetch_assoc()['count'];
$deleted = $conn->query("SELECT COUNT(*) as count FROM student_account WHERE deleted_at IS NOT NULL")->fetch_assoc()['count'];

echo "<ul>";
echo "<li><strong>Total Students:</strong> $total</li>";
echo "<li><strong>Active Students:</strong> $active</li>";
echo "<li><strong>Deleted Students:</strong> $deleted</li>";
echo "</ul>";

$conn->close();
?>
