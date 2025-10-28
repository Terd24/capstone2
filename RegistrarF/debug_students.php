<?php
require_once __DIR__ . '/../StudentLogin/db_conn.php';

echo "Checking student records:\n\n";

$result = $conn->query("SELECT id_number, first_name, last_name, deleted_at, deleted_by FROM student_account ORDER BY id_number");

while($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id_number'] . "\n";
    echo "Name: " . $row['first_name'] . " " . $row['last_name'] . "\n";
    echo "Deleted At: " . ($row['deleted_at'] ?? 'NULL') . "\n";
    echo "Deleted By: " . ($row['deleted_by'] ?? 'NULL') . "\n";
    echo "---\n";
}

echo "\n\nChecking pending deletion requests:\n\n";

$requests = $conn->query("SELECT id, target_id, request_type, status, requested_at, reviewed_at FROM owner_approval_requests WHERE request_type = 'student_deletion' ORDER BY requested_at DESC LIMIT 10");

while($req = $requests->fetch_assoc()) {
    echo "Request ID: " . $req['id'] . "\n";
    echo "Student ID: " . $req['target_id'] . "\n";
    echo "Status: " . $req['status'] . "\n";
    echo "Requested: " . $req['requested_at'] . "\n";
    echo "Reviewed: " . ($req['reviewed_at'] ?? 'NULL') . "\n";
    echo "---\n";
}
?>
