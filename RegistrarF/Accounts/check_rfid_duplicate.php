<?php
session_start();
include(__DIR__ . "/../../StudentLogin/db_conn.php");

// Require registrar or HR login
if (!isset($_SESSION['registrar_id']) && !isset($_SESSION['hr_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$rfid_uid = trim($_POST['rfid_uid'] ?? '');
$current_id = trim($_POST['current_id'] ?? ''); // For edit mode - exclude current student

if (empty($rfid_uid)) {
    echo json_encode(['duplicate' => false]);
    exit;
}

// Check if RFID exists in student_account table (excluding current student if editing)
if (!empty($current_id)) {
    $check_student = $conn->prepare("SELECT id_number FROM student_account WHERE rfid_uid = ? AND id_number != ? AND deleted_at IS NULL");
    $check_student->bind_param("ss", $rfid_uid, $current_id);
} else {
    $check_student = $conn->prepare("SELECT id_number FROM student_account WHERE rfid_uid = ? AND deleted_at IS NULL");
    $check_student->bind_param("s", $rfid_uid);
}
$check_student->execute();
$check_student->store_result();

if ($check_student->num_rows > 0) {
    echo json_encode([
        'duplicate' => true,
        'message' => 'RFID already in use by another student!'
    ]);
    $check_student->close();
    exit;
}
$check_student->close();

// Check if RFID exists in employees table (excluding current employee if editing)
if (!empty($current_id)) {
    $check_employee = $conn->prepare("SELECT id_number FROM employees WHERE rfid_uid = ? AND id_number != ?");
    $check_employee->bind_param("ss", $rfid_uid, $current_id);
} else {
    $check_employee = $conn->prepare("SELECT id_number FROM employees WHERE rfid_uid = ?");
    $check_employee->bind_param("s", $rfid_uid);
}
$check_employee->execute();
$check_employee->store_result();

if ($check_employee->num_rows > 0) {
    echo json_encode([
        'duplicate' => true,
        'message' => 'RFID already in use by a teacher!'
    ]);
    $check_employee->close();
    exit;
}
$check_employee->close();

// No duplicate found
echo json_encode(['duplicate' => false]);
?>
