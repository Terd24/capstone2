<?php
session_start();
require_once '../StudentLogin/db_conn.php';

header('Content-Type: text/html; charset=utf-8');

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    die('Unauthorized access');
}

echo "<h1>Student Account Table Structure</h1>";

$columns = $conn->query("DESCRIBE student_account");
echo "<ul>";
while ($col = $columns->fetch_assoc()) {
    echo "<li><strong>{$col['Field']}</strong> - {$col['Type']}</li>";
}
echo "</ul>";

// Get a sample deleted student
$result = $conn->query("SELECT * FROM student_account WHERE deleted_at IS NOT NULL LIMIT 1");
if ($result->num_rows > 0) {
    echo "<h2>Sample Deleted Student Data:</h2>";
    $student = $result->fetch_assoc();
    echo "<pre>";
    print_r(array_keys($student));
    echo "</pre>";
}
?>
