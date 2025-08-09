<?php
include '../StudentLogin/db_conn.php';  // adjust path

header('Content-Type: application/json');

$query = trim($_GET['query'] ?? '');

if (!$query) {
    echo json_encode(['error' => 'No query provided']);
    exit;
}

$query_like = "%$query%";

// Search students by id_number or full_name (case-insensitive)
$sql = "SELECT * FROM student_account WHERE id_number LIKE ? OR full_name LIKE ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $query_like, $query_like);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($student = $result->fetch_assoc()) {
    // Get guidance records for this student
    $sql2 = "SELECT remarks, record_date FROM guidance_records WHERE id_number = ? ORDER BY record_date DESC";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("s", $student['id_number']);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    $guidance_records = [];
    while ($row = $res2->fetch_assoc()) {
        $guidance_records[] = $row;
    }

    $student['guidance_records'] = $guidance_records;
    $students[] = $student;
}

if (count($students) === 0) {
    echo json_encode(['students' => []]);
    exit;
}

echo json_encode(['students' => $students]);
