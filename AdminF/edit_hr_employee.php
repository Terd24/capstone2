<?php
session_start();
include("../StudentLogin/db_conn.php");

// Require Super Admin access
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$employee_id = $_POST['employee_id'] ?? '';
$first_name = $_POST['first_name'] ?? '';
$middle_name = $_POST['middle_name'] ?? '';
$last_name = $_POST['last_name'] ?? '';
$position = $_POST['position'] ?? '';
$department = $_POST['department'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$hire_date = $_POST['hire_date'] ?? '';
$address = $_POST['address'] ?? '';

if (empty($employee_id) || empty($first_name) || empty($last_name) || empty($position) || empty($department) || empty($hire_date)) {
    echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Update employee
    $stmt = $conn->prepare("UPDATE employees SET first_name = ?, middle_name = ?, last_name = ?, position = ?, department = ?, email = ?, phone = ?, hire_date = ?, address = ? WHERE id_number = ?");

    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param("ssssssssss", $first_name, $middle_name, $last_name, $position, $department, $email, $phone, $hire_date, $address, $employee_id);

    if (!$stmt->execute()) {
        throw new Exception('Error updating employee: ' . $stmt->error);
    }

    if ($stmt->affected_rows === 0) {
        throw new Exception('No HR employee found with ID: ' . $employee_id);
    }

    // Commit transaction
    $conn->commit();

    $_SESSION['success_msg'] = 'HR Employee information updated successfully!';
    echo json_encode(['success' => true, 'message' => 'HR Employee updated successfully', 'reload' => true]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
