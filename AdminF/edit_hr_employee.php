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
$password = $_POST['password'] ?? '';

if (empty($employee_id) || empty($first_name) || empty($last_name) || empty($position) || empty($department) || empty($hire_date)) {
    echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
    exit;
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
if (empty($address)) {
    echo json_encode(['success' => false, 'message' => 'Complete Address is required']);
    exit;
}
if (strlen($address) < 20) {
    echo json_encode(['success' => false, 'message' => 'Complete address must be at least 20 characters long']);
    exit;
}
if (strlen($address) > 500) {
    echo json_encode(['success' => false, 'message' => 'Complete address must not exceed 500 characters']);
    exit;
}
// Validate address has at least 4 components separated by commas
$addressParts = array_filter(array_map('trim', explode(',', $address)), function($part) {
    return strlen($part) > 0;
});
if (count($addressParts) < 4) {
    echo json_encode(['success' => false, 'message' => 'Complete address must include at least 4 components separated by commas (e.g., Street, Barangay, City, Province)']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // First check if employee exists
    $check_stmt = $conn->prepare("SELECT id_number FROM employees WHERE id_number = ?");
    $check_stmt->bind_param("s", $employee_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        throw new Exception('No employee found with ID: ' . $employee_id);
    }

    // Update employee
    $stmt = $conn->prepare("UPDATE employees SET first_name = ?, middle_name = ?, last_name = ?, position = ?, department = ?, email = ?, phone = ?, hire_date = ?, address = ? WHERE id_number = ?");

    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param("ssssssssss", $first_name, $middle_name, $last_name, $position, $department, $email, $phone, $hire_date, $address, $employee_id);

    if (!$stmt->execute()) {
        throw new Exception('Error updating employee: ' . $stmt->error);
    }

    // Update password if provided
    if (!empty($password)) {
        // Ensure must_change_password column exists in employee_accounts table
        $conn->query("ALTER TABLE employee_accounts ADD COLUMN IF NOT EXISTS must_change_password TINYINT(1) DEFAULT 0");
        
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $update_password = $conn->prepare("UPDATE employee_accounts SET password = ?, must_change_password = 1 WHERE employee_id = ?");
        $update_password->bind_param("ss", $hashed_password, $employee_id);
        
        if (!$update_password->execute()) {
            throw new Exception('Error updating password: ' . $update_password->error);
        }
        $update_password->close();
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
