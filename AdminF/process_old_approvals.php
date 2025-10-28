<?php
// One-time script to process old approved student deletion requests
session_start();

require_once '../StudentLogin/db_conn.php';

echo "<h2>Processing Old Approved Student Deletion Requests</h2>";

// Find all approved student deletion requests
$query = "SELECT * FROM owner_approval_requests 
          WHERE status = 'approved'
          ORDER BY reviewed_at DESC";

$result = $conn->query($query);

if ($result->num_rows === 0) {
    echo "<p>No approved requests found.</p>";
    exit;
}

echo "<p>Found " . $result->num_rows . " approved requests. Processing...</p>";
echo "<ul>";

$processed = 0;

while ($request = $result->fetch_assoc()) {
    $target_data = json_decode($request['target_data'], true);
    $request_details = json_decode($request['request_details'], true);
    
    // Check if this is a student deletion
    $is_student = isset($target_data['student_id']) || isset($target_data['id_number']) || isset($target_data['student_name']);
    
    if (!$is_student) {
        continue; // Skip non-student requests
    }
    
    $student_id = $target_data['id_number'] ?? $target_data['student_id'] ?? $request['target_id'];
    $student_name = $target_data['student_name'] ?? 'Unknown';
    
    // Check if student still exists (not deleted)
    $check_stmt = $conn->prepare("SELECT id_number, deleted_at FROM student_account WHERE id_number = ?");
    $check_stmt->bind_param("s", $student_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        echo "<li>Student $student_id ($student_name) - Already removed from database</li>";
        $check_stmt->close();
        continue;
    }
    
    $student = $check_result->fetch_assoc();
    $check_stmt->close();
    
    if ($student['deleted_at'] !== null) {
        echo "<li>Student $student_id ($student_name) - Already marked as deleted</li>";
        continue;
    }
    
    // Student exists and not deleted - delete them now
    $deleted_by = $request['requester_name'] ?? 'Registrar';
    $deletion_reason = $request_details['deletion_reason'] ?? 'Approved by Owner';
    
    $delete_stmt = $conn->prepare("UPDATE student_account SET deleted_at = NOW(), deleted_by = ?, deleted_reason = ? WHERE id_number = ?");
    $delete_stmt->bind_param('sss', $deleted_by, $deletion_reason, $student_id);
    
    if ($delete_stmt->execute()) {
        echo "<li style='color: green;'><strong>✓ Student $student_id ($student_name) - Successfully deleted</strong></li>";
        $processed++;
    } else {
        echo "<li style='color: red;'>✗ Student $student_id ($student_name) - Failed to delete: " . $delete_stmt->error . "</li>";
    }
    
    $delete_stmt->close();
}

echo "</ul>";
echo "<p><strong>Processing complete! Processed $processed students.</strong></p>";
echo "<p><a href='SuperAdminDashboard.php'>Back to Dashboard</a> | <a href='../RegistrarF/AccountList.php'>View Student List</a></p>";

$conn->close();
?>
