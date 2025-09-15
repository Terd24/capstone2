<?php
session_start();
include("../StudentLogin/db_conn.php");

// Require HR login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'hr') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$employee_id = $_POST['employee_id'] ?? '';
$full_name = $_POST['full_name'] ?? '';
$position = $_POST['position'] ?? '';
$department = $_POST['department'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$hire_date = $_POST['hire_date'] ?? '';

if (empty($employee_id) || empty($full_name) || empty($position) || empty($department) || empty($hire_date)) {
    echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
    exit;
}

// Update employee
$stmt = $conn->prepare("UPDATE employees SET full_name = ?, position = ?, department = ?, email = ?, phone = ?, hire_date = ? WHERE id_number = ?");
$stmt->bind_param("sssssss", $full_name, $position, $department, $email, $phone, $hire_date, $employee_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Employee updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating employee: ' . $conn->error]);
}
?>
