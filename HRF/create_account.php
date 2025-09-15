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

// Check if account already exists
$stmt = $conn->prepare("SELECT username FROM employee_accounts WHERE employee_id = ?");
$stmt->bind_param("s", $employee_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Account already exists for this employee']);
    exit;
}

// Check if username is taken
$stmt = $conn->prepare("SELECT username FROM employee_accounts WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Username already taken']);
    exit;
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Create account based on role
$table_map = [
    'registrar' => 'registrar_account',
    'cashier' => 'cashier_account',
    'guidance' => 'guidance_account',
    'hr' => 'hr_account'
];

if (!isset($table_map[$role])) {
    echo json_encode(['success' => false, 'message' => 'Invalid role']);
    exit;
}

$table = $table_map[$role];

// Get employee details for account creation
$stmt = $conn->prepare("SELECT full_name FROM employees WHERE id_number = ?");
$stmt->bind_param("s", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();

// Split full name
$name_parts = explode(' ', $employee['full_name'], 2);
$first_name = $name_parts[0];
$last_name = isset($name_parts[1]) ? $name_parts[1] : '';

// Insert into appropriate role table
$stmt = $conn->prepare("INSERT INTO $table (id_number, username, password, first_name, last_name) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $employee_id, $username, $hashed_password, $first_name, $last_name);

if ($stmt->execute()) {
    // Also insert into employee_accounts for tracking
    $stmt = $conn->prepare("INSERT INTO employee_accounts (employee_id, username, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $employee_id, $username, $role);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Account created successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error creating account: ' . $conn->error]);
}
?>
