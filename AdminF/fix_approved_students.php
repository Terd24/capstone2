<?php
// Fix approved student deletions - mark them as deleted in the database
require_once '../StudentLogin/db_conn.php';

echo "<h2>Fixing Approved Student Deletions</h2>";

// Find all approved student deletion requests
$query = "SELECT oar.*, oar.target_id as student_id
          FROM owner_approval_requests oar
          WHERE oar.status = 'approved'
          AND (oar.request_type = 'student_deletion' OR oar.request_type = '')
          ORDER BY oar.reviewed_at DESC";

$result = $conn->query($query);

if (!$result) {
    die("Query failed: " . $conn->error);
}

echo "<p>Found " . $result->num_rows . " approved requests.</p>";
echo "<ul>";

$fixed = 0;

while ($request = $result->fetch_assoc()) {
    $target_data = json_decode($request['target_data'], true);
    
    // Check if this is a student deletion
    $is_student = isset($target_data['student_id']) || isset($target_data['id_number']) || isset($target_data['student_name']);
    
    if (!$is_student) {
        continue;
    }
    
    $student_id = $target_data['id_number'] ?? $target_data['student_id'] ?? $request['student_id'];
    $student_name = $target_data['student_name'] ?? 'Unknown';
    
    if (!$student_id) {
        echo "<li style='color: orange;'>⚠ Skipping - no student ID found</li>";
        continue;
    }
    
    // Check if student exists and is not deleted
    $check = $conn->prepare("SELECT id_number, deleted_at FROM student_account WHERE id_number = ?");
    $check->bind_param("s", $student_id);
    $check->execute();
    $check_result = $check->get_result();
    
    if ($check_result->num_rows === 0) {
        echo "<li>Student $student_id ($student_name) - Not found in database</li>";
        $check->close();
        continue;
    }
    
    $student = $check_result->fetch_assoc();
    $check->close();
    
    if ($student['deleted_at'] !== null) {
        echo "<li>Student $student_id ($student_name) - Already deleted</li>";
        continue;
    }
    
    // Delete the student
    $request_details = json_decode($request['request_details'], true);
    $deleted_by = $request['requester_name'] ?? 'Registrar';
    $deletion_reason = $request_details['deletion_reason'] ?? 'Approved by Owner';
    
    $delete = $conn->prepare("UPDATE student_account SET deleted_at = NOW(), deleted_by = ?, deleted_reason = ? WHERE id_number = ?");
    $delete->bind_param('sss', $deleted_by, $deletion_reason, $student_id);
    
    if ($delete->execute()) {
        echo "<li style='color: green;'><strong>✓ Fixed: Student $student_id ($student_name) - Marked as deleted</strong></li>";
        $fixed++;
    } else {
        echo "<li style='color: red;'>✗ Failed: Student $student_id ($student_name) - " . $delete->error . "</li>";
    }
    
    $delete->close();
}

echo "</ul>";
echo "<p><strong>Fixed $fixed students!</strong></p>";
echo "<p><a href='../RegistrarF/AccountList.php'>Go to Student List</a></p>";

$conn->close();
?>
