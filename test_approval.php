<?php
// Simple test to check if student deletion is working
require_once 'StudentLogin/db_conn.php';

// Check if Gesterd Go exists and its status
$student_id = '02200000001';

echo "Checking student: $student_id\n\n";

$stmt = $conn->prepare("SELECT id_number, first_name, last_name, deleted_at, deleted_by, deleted_reason FROM student_account WHERE id_number = ?");
$stmt->bind_param('s', $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo "Student found:\n";
    echo "ID: " . $row['id_number'] . "\n";
    echo "Name: " . $row['first_name'] . " " . $row['last_name'] . "\n";
    echo "Deleted At: " . ($row['deleted_at'] ?? 'NULL') . "\n";
    echo "Deleted By: " . ($row['deleted_by'] ?? 'NULL') . "\n";
    echo "Deleted Reason: " . ($row['deleted_reason'] ?? 'NULL') . "\n";
} else {
    echo "Student not found in database\n";
}

echo "\n\nChecking approval requests for this student:\n\n";

$req_stmt = $conn->prepare("SELECT id, request_type, status, requested_at, reviewed_at, reviewed_by, owner_comments FROM owner_approval_requests WHERE target_id = ? ORDER BY requested_at DESC LIMIT 5");
$req_stmt->bind_param('s', $student_id);
$req_stmt->execute();
$req_result = $req_stmt->get_result();

while ($req = $req_result->fetch_assoc()) {
    echo "Request ID: " . $req['id'] . "\n";
    echo "Type: " . $req['request_type'] . "\n";
    echo "Status: " . $req['status'] . "\n";
    echo "Requested: " . $req['requested_at'] . "\n";
    echo "Reviewed: " . ($req['reviewed_at'] ?? 'NULL') . "\n";
    echo "Reviewed By: " . ($req['reviewed_by'] ?? 'NULL') . "\n";
    echo "Comments: " . ($req['owner_comments'] ?? 'NULL') . "\n";
    echo "---\n";
}
?>
