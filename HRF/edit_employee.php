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
$middle_name = $_POST['middle_name'] ?? '';
$last_name = $_POST['last_name'] ?? '';
$position = $_POST['position'] ?? '';
$department = $_POST['department'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$address = $_POST['address'] ?? '';
$hire_date = $_POST['hire_date'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($employee_id) || empty($first_name) || empty($last_name) || empty($position) || empty($department) || empty($hire_date)) {
    echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
    exit;
}

// Prevent HR staff from editing HR department employees (only Super Admin can)
if ($_SESSION['role'] === 'hr' && $department === 'Human Resources') {
    echo json_encode(['success' => false, 'message' => 'HR staff cannot edit HR department employees. Only Super Admin can manage HR accounts.']);
    exit;
}

// Also check if the existing employee is from HR department
$check_dept = $conn->prepare("SELECT department FROM employees WHERE id_number = ?");
$check_dept->bind_param("s", $employee_id);
$check_dept->execute();
$dept_result = $check_dept->get_result();
if ($dept_result && $dept_result->num_rows > 0) {
    $existing_dept = $dept_result->fetch_assoc()['department'];
    if ($_SESSION['role'] === 'hr' && $existing_dept === 'Human Resources') {
        echo json_encode(['success' => false, 'message' => 'HR staff cannot edit HR department employees. Only Super Admin can manage HR accounts.']);
        exit;
    }
}

// Validate email format if provided
if (!empty($email)) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
        exit;
    }
    
    // Validate email domain - only allow trusted providers
    $allowedDomains = ['gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com', 'icloud.com', 'protonmail.com', 'aol.com', 'zoho.com', 'mail.com', 'yandex.com', 'gmx.com', 'tutanota.com'];
    $emailDomain = strtolower(substr(strrchr($email, "@"), 1));
    if (!in_array($emailDomain, $allowedDomains)) {
        echo json_encode(['success' => false, 'message' => 'Please use a valid email provider (Gmail, Yahoo, Outlook, etc.)']);
        exit;
    }
}

// Validate address
if (strlen($address) < 20) {
    echo json_encode(['success' => false, 'message' => 'Complete address must be at least 20 characters long.']);
    exit;
} elseif (strlen($address) > 500) {
    echo json_encode(['success' => false, 'message' => 'Complete address must not exceed 500 characters.']);
    exit;
} else {
    // Validate address has at least 4 components separated by commas
    $addressParts = array_filter(array_map('trim', explode(',', $address)), function($part) {
        return strlen($part) > 0;
    });
    if (count($addressParts) < 4) {
        echo json_encode(['success' => false, 'message' => 'Complete address must include at least 4 components separated by commas (e.g., Street, Barangay, City, Province)']);
        exit;
    }
}

// Update employee
$sql = "UPDATE employees SET first_name = ?, middle_name = ?, last_name = ?, position = ?, department = ?, email = ?, phone = ?, address = ?, hire_date = ? WHERE id_number = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log("SQL Error: " . $conn->error);
    error_log("SQL Query: " . $sql);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
}

$stmt->bind_param("ssssssssss", $first_name, $middle_name, $last_name, $position, $department, $email, $phone, $address, $hire_date, $employee_id);

if ($stmt->execute()) {
    // Update password if provided
    if (!empty($password)) {
        // Ensure must_change_password column exists in employee_accounts table
        $conn->query("ALTER TABLE employee_accounts ADD COLUMN IF NOT EXISTS must_change_password TINYINT(1) DEFAULT 0");
        
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $update_password = $conn->prepare("UPDATE employee_accounts SET password = ?, must_change_password = 1 WHERE employee_id = ?");
        
        if ($update_password) {
            $update_password->bind_param("ss", $hashed_password, $employee_id);
            
            if (!$update_password->execute()) {
                echo json_encode(['success' => false, 'message' => 'Error updating password: ' . $update_password->error]);
                exit;
            }
            $update_password->close();
        }
    }
    
    // Check if employee exists (affected_rows can be 0 if no changes were made)
    $check_stmt = $conn->prepare("SELECT id_number FROM employees WHERE id_number = ?");
    $check_stmt->bind_param("s", $employee_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Employee updated successfully', 'reload' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No employee found with ID: ' . $employee_id]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating employee: ' . $stmt->error]);
}
?>
