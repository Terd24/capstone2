<?php
session_start();
include("../StudentLogin/db_conn.php");

// Require Super Admin login
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    $_SESSION['error_msg'] = 'Unauthorized access';
    header("Location: SuperAdminDashboard.php#hr-accounts");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_msg'] = 'Invalid request method';
    header("Location: SuperAdminDashboard.php#hr-accounts");
    exit;
}

try {
    $employee_id = $_POST['employee_id'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    if (empty($employee_id) || empty($username) || empty($password) || empty($role)) {
        $_SESSION['error_msg'] = 'All fields are required';
        header("Location: SuperAdminDashboard.php#hr-accounts");
        exit;
    }

    // Check if employee exists
    $stmt = $conn->prepare("SELECT id_number FROM employees WHERE id_number = ?");
    $stmt->bind_param("s", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['error_msg'] = 'Employee not found';
        header("Location: SuperAdminDashboard.php#hr-accounts");
        exit;
    }

    // Check if username is taken
    $stmt = $conn->prepare("SELECT username FROM employee_accounts WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error_msg'] = 'Username already taken';
        header("Location: SuperAdminDashboard.php#hr-accounts");
        exit;
    }

    // Check if employee already has an account
    $stmt = $conn->prepare("SELECT employee_id FROM employee_accounts WHERE employee_id = ?");
    $stmt->bind_param("s", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error_msg'] = 'Employee already has an account';
        header("Location: SuperAdminDashboard.php#hr-accounts");
        exit;
    }

    // Create account
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO employee_accounts (employee_id, username, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $employee_id, $username, $hashed_password, $role);

    if ($stmt->execute()) {
        $_SESSION['success_msg'] = 'System account created successfully for Employee ID: ' . $employee_id;
    } else {
        $_SESSION['error_msg'] = 'Failed to create account';
    }

} catch (Exception $e) {
    $_SESSION['error_msg'] = 'Database error: ' . $e->getMessage();
}

// Redirect back to HR accounts
header("Location: SuperAdminDashboard.php#hr-accounts");
exit;
?>
