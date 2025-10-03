<?php
session_start();
include("../StudentLogin/db_conn.php");

// Require HR login or Super Admin access
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'hr' && $_SESSION['role'] !== 'superadmin')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$employee_id = $_POST['employee_id'] ?? '';
$first_name = $_POST['first_name'] ?? '';
$last_name = $_POST['last_name'] ?? '';
$position = $_POST['position'] ?? '';
$department = $_POST['department'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$hire_date = $_POST['hire_date'] ?? '';

if (empty($employee_id) || empty($first_name) || empty($last_name) || empty($position) || empty($department) || empty($hire_date)) {
    echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
    exit;
}

// Update employee
$stmt = $conn->prepare("UPDATE employees SET first_name = ?, last_name = ?, position = ?, department = ?, email = ?, phone = ?, hire_date = ? WHERE id_number = ?");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param("ssssssss", $first_name, $last_name, $position, $department, $email, $phone, $hire_date, $employee_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $_SESSION['success_msg'] = 'Employee information updated successfully!';
        echo json_encode(['success' => true, 'message' => 'Employee updated successfully', 'reload' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No employee found with ID: ' . $employee_id]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating employee: ' . $stmt->error]);
}
?>
