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
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? '';
$rfid_uid = $_POST['rfid_uid'] ?? '';

// Validate required fields
if (empty($employee_id) || empty($username) || empty($password) || empty($role)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// For teacher role, RFID is required
if ($role === 'teacher' && empty($rfid_uid)) {
    echo json_encode(['success' => false, 'message' => 'RFID number is required for teacher accounts']);
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
$valid_roles = ['registrar', 'cashier', 'guidance', 'attendance', 'teacher'];
if (!in_array($role, $valid_roles)) {
    echo json_encode(['success' => false, 'message' => 'Invalid role']);
    exit;
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Start transaction
$conn->begin_transaction();

try {
    // Insert into employee_accounts table
    $stmt = $conn->prepare("INSERT INTO employee_accounts (employee_id, username, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $employee_id, $username, $hashed_password, $role);
    $stmt->execute();
    
    // If teacher role, update RFID in employees table
    if ($role === 'teacher' && !empty($rfid_uid)) {
        $stmt = $conn->prepare("UPDATE employees SET rfid_uid = ? WHERE id_number = ?");
        $stmt->bind_param("ss", $rfid_uid, $employee_id);
        $stmt->execute();
    }
    
    // Commit transaction
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Account created successfully']);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error creating account: ' . $e->getMessage()]);
}
?>