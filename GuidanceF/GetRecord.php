<?php
include '../StudentLogin/db_conn.php';

header('Content-Type: application/json');

if (!isset($_GET['rfid_uid'])) {
    echo json_encode(['error' => 'No rfid_uid provided']);
    exit;
}

$rfid_uid = strtoupper(trim($_GET['rfid_uid']));


$sql1 = "SELECT id_number, CONCAT(first_name, ' ', last_name) as full_name, academic_track as program, grade_level as year_section FROM student_account WHERE UPPER(rfid_uid) = ?";
$stmt1 = $conn->prepare($sql1);
$stmt1->bind_param("s", $rfid_uid);
$stmt1->execute();
$studentResult = $stmt1->get_result();

if ($studentResult->num_rows === 0) {
    echo json_encode(['error' => ' ']);
    exit;
}

$student = $studentResult->fetch_assoc();
$stmt1->close();

$sql2 = "SELECT remarks, record_date FROM guidance_records WHERE id_number = ? ORDER BY record_date DESC";
$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param("s", $student['id_number']);
$stmt2->execute();
$result = $stmt2->get_result();

$guidance_data = [];
while ($row = $result->fetch_assoc()) {
    $guidance_data[] = $row;
}
$stmt2->close();

echo json_encode([
    'student' => $student,
    'guidance_records' => $guidance_data
]);
?>
