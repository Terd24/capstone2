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
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? '';

if (empty($employee_id) || empty($username) || empty($password) || empty($role)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Check if employee exists
$stmt = $conn->prepare("SELECT id_number FROM employees WHERE id_number = ?");
$stmt->bind_param("s", $employee_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Employee not found']);
    exit;
}

// Allow multiple accounts per employee. We intentionally do not block when one already exists.

// Check if username is taken
$stmt = $conn->prepare("SELECT username FROM employee_accounts WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Username already taken']);
    exit;
}

// Validate role
$valid_roles = ['registrar', 'cashier', 'guidance', 'attendance', 'hr'];
if (!in_array($role, $valid_roles)) {
    echo json_encode(['success' => false, 'message' => 'Invalid role']);
    exit;
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert into employee_accounts table only
$stmt = $conn->prepare("INSERT INTO employee_accounts (employee_id, username, password, role) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $employee_id, $username, $hashed_password, $role);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Account created successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error creating account: ' . $conn->error]);
}
?>