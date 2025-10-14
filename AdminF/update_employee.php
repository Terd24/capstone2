<?php
session_start();
include("../StudentLogin/db_conn.php");

// Require Super Admin login
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Set content type to JSON
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $employee_id = $_POST['employee_id'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $position = $_POST['position'] ?? '';
    $department = $_POST['department'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $hire_date = $_POST['hire_date'] ?? '';
    $username = $_POST['username'] ?? '';
    $role = $_POST['role'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($employee_id)) {
        echo json_encode(['success' => false, 'message' => 'Employee ID is required']);
        exit;
    }

    $conn->begin_transaction();

    // Update employee information
    $stmt = $conn->prepare("UPDATE employees SET first_name = ?, last_name = ?, position = ?, department = ?, email = ?, phone = ?, hire_date = ? WHERE id_number = ?");
    $stmt->bind_param("ssssssss", $first_name, $last_name, $position, $department, $email, $phone, $hire_date, $employee_id);
    $stmt->execute();

    // Handle account updates
    if (!empty($username)) {
        // Check if employee already has an account
        $stmt = $conn->prepare("SELECT username FROM employee_accounts WHERE employee_id = ?");
        $stmt->bind_param("s", $employee_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Ensure must_change_password column exists
            $conn->query("ALTER TABLE employee_accounts ADD COLUMN IF NOT EXISTS must_change_password TINYINT(1) DEFAULT 0");
            
            // Update existing account
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE employee_accounts SET username = ?, password = ?, role = ?, must_change_password = 1 WHERE employee_id = ?");
                $stmt->bind_param("ssss", $username, $hashed_password, $role, $employee_id);
            } else {
                $stmt = $conn->prepare("UPDATE employee_accounts SET username = ?, role = ? WHERE employee_id = ?");
                $stmt->bind_param("sss", $username, $role, $employee_id);
            }
            $stmt->execute();
        } else {
            // Ensure must_change_password column exists
            $conn->query("ALTER TABLE employee_accounts ADD COLUMN IF NOT EXISTS must_change_password TINYINT(1) DEFAULT 0");
            
            // Create new account
            if (empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Password is required for new accounts']);
                $conn->rollback();
                exit;
            }
            
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO employee_accounts (employee_id, username, password, role, must_change_password) VALUES (?, ?, ?, ?, 1)");
            $stmt->bind_param("ssss", $employee_id, $username, $hashed_password, $role);
            $stmt->execute();
        }
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Employee updated successfully']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
