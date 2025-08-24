<?php
session_start();
include("../StudentLogin/db_conn.php");

header('Content-Type: application/json');

$query = $_GET['query'] ?? '';
if (!$query) {
    echo json_encode(["error" => "No search query provided."]);
    exit;
}

// ✅ Search by ID, Name, or RFID
$sql = "
    SELECT id_number, full_name, program, year_section, rfid_uid
    FROM student_account
    WHERE id_number LIKE ? 
       OR full_name LIKE ? 
       OR rfid_uid LIKE ?
    ORDER BY full_name ASC
";

$stmt = $conn->prepare($sql);
$searchTerm = "%" . $query . "%";
$stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

if (count($students) === 0) {
    echo json_encode(["error" => "No students found."]);
} else {
    echo json_encode(["students" => $students]);
}