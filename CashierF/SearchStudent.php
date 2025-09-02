<?php
session_start();
include("../StudentLogin/db_conn.php");

$query = $_GET['query'] ?? '';
if (!$query) {
    echo json_encode(['error' => 'Empty search']);
    exit;
}

$stmt = $conn->prepare(
    "SELECT id_number, CONCAT(first_name, ' ', last_name) as full_name, academic_track as program, grade_level as year_section, rfid_uid
     FROM student_account 
     WHERE CONCAT(first_name, ' ', last_name) LIKE ? OR id_number LIKE ? 
     LIMIT 10"
);
$searchTerm = "%$query%";
$stmt->bind_param("ss", $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

echo json_encode(['students' => $students]);
?>
