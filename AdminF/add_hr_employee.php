<?php
// This file handles adding new HR employees for Super Admin
// Based on HRF/add_employee.php but specifically for HR accounts
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include '../StudentLogin/db_conn.php';

// Require Super Admin login
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    header("Location: ../StudentLogin/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Employee information
    $id_number = trim($_POST['id_number']);
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name = trim($_POST['last_name']);
    $position = trim($_POST['position']);
    $department = trim($_POST['department']); // Will always be "Human Resources"
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $hire_date = $_POST['hire_date'];
    $create_account = isset($_POST['create_account']);
    
    // Account information (if creating account)
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = 'hr'; // Always HR for Super Admin

    try {
        // Start transaction
        $conn->begin_transaction();

        // Ensure employees table exists (and required columns)
        $table_check = $conn->query("SHOW TABLES LIKE 'employees'");
        if ($table_check->num_rows == 0) {
            $create_employees_table = "CREATE TABLE employees (
                id INT AUTO_INCREMENT PRIMARY KEY,
                id_number VARCHAR(20) UNIQUE NOT NULL,
                first_name VARCHAR(50) NOT NULL,
                middle_name VARCHAR(50),
                last_name VARCHAR(50) NOT NULL,
                position VARCHAR(100) NOT NULL,
                department VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL,
                phone VARCHAR(20) NOT NULL,
                address TEXT NOT NULL,
                hire_date DATE NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            $conn->query($create_employees_table);
        } else {
            // Update id_number column to support longer IDs (CCI2025-0001 format)
            $id_column = $conn->query("SHOW COLUMNS FROM employees LIKE 'id_number'")->fetch_assoc();
            if ($id_column && strpos($id_column['Type'], 'varchar(11)') !== false) {
                $conn->query("ALTER TABLE employees MODIFY COLUMN id_number VARCHAR(20) UNIQUE NOT NULL");
            }
            
            // Add missing columns if needed
            $column_check = $conn->query("SHOW COLUMNS FROM employees LIKE 'hire_date'");
            if ($column_check->num_rows == 0) {
                $conn->query("ALTER TABLE employees ADD COLUMN hire_date DATE NOT NULL DEFAULT '2024-01-01'");
            }
            $middle_name_check = $conn->query("SHOW COLUMNS FROM employees LIKE 'middle_name'");
            if ($middle_name_check->num_rows == 0) {
                $conn->query("ALTER TABLE employees ADD COLUMN middle_name VARCHAR(50) NULL AFTER first_name");
            }
            $address_check = $conn->query("SHOW COLUMNS FROM employees LIKE 'address'");
            if ($address_check->num_rows == 0) {
                $conn->query("ALTER TABLE employees ADD COLUMN address TEXT NULL");
            }
        }

        // Validations
        if (!preg_match('/^[0-9A-Za-z\-]+$/', $id_number) || strlen($id_number) > 20) {
            throw new Exception("Employee ID must contain only letters, numbers, and dashes and be maximum 20 characters.");
        }
        if (empty($email)) {
            throw new Exception("Email is required.");
        }
        if (empty($phone) || !preg_match('/^[0-9]{11}$/', $phone)) {
            throw new Exception("Phone must be exactly 11 digits (numbers only).");
        }
        if (empty($address)) {
            throw new Exception("Complete Address is required.");
        }

        // Check if employee ID already exists
        $check_stmt = $conn->prepare("SELECT id_number FROM employees WHERE id_number = ?");
        $check_stmt->bind_param("s", $id_number);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            throw new Exception("Employee ID already exists.");
        }

        // Insert employee
        $stmt = $conn->prepare("INSERT INTO employees (id_number, first_name, middle_name, last_name, position, department, email, phone, address, hire_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssss", $id_number, $first_name, $middle_name, $last_name, $position, $department, $email, $phone, $address, $hire_date);
        if (!$stmt->execute()) {
            throw new Exception("Failed to add employee: " . $stmt->error);
        }

        // Optionally create a system account (HR accounts are allowed for Super Admin)
        if ($create_account && $username && $password) {
            // Ensure employee_accounts table exists
            $account_table_check = $conn->query("SHOW TABLES LIKE 'employee_accounts'");
            if ($account_table_check->num_rows == 0) {
                $create_accounts_table = "CREATE TABLE employee_accounts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    employee_id VARCHAR(20) NOT NULL,
                    username VARCHAR(50) UNIQUE NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    role ENUM('registrar','cashier','guidance','attendance','hr','teacher') NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (employee_id) REFERENCES employees(id_number) ON DELETE CASCADE
                )";
                $conn->query($create_accounts_table);
            }
            // Ensure ENUM includes hr
            $roleColumn = $conn->query("SHOW COLUMNS FROM employee_accounts LIKE 'role'")->fetch_assoc();
            if ($roleColumn && isset($roleColumn['Type'])) {
                if (strpos($roleColumn['Type'], "'hr'") === false) {
                    $conn->query("ALTER TABLE employee_accounts MODIFY role ENUM('registrar','cashier','guidance','attendance','hr','teacher') NOT NULL");
                }
            }

            // Check if username already exists
            $username_check = $conn->prepare("SELECT username FROM employee_accounts WHERE username = ?");
            $username_check->bind_param("s", $username);
            $username_check->execute();
            if ($username_check->get_result()->num_rows > 0) {
                throw new Exception("Username already exists.");
            }

            // Hash password and insert
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $account_stmt = $conn->prepare("INSERT INTO employee_accounts (employee_id, username, password, role) VALUES (?, ?, ?, ?)");
            $account_stmt->bind_param("ssss", $id_number, $username, $hashed_password, $role);
            if (!$account_stmt->execute()) {
                throw new Exception("Failed to create account: " . $account_stmt->error);
            }
        }

        // Commit transaction
        $conn->commit();

        $success_message = "HR Employee added successfully";
        if ($create_account) {
            $success_message .= " with system account";
        }
        $_SESSION['success_msg'] = $success_message;

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_msg'] = "Error: " . $e->getMessage();
        error_log("HR Employee creation error: " . $e->getMessage());
        error_log("POST data: " . print_r($_POST, true));
    }

    // Redirect back to dashboard
    header("Location: SuperAdminDashboard.php#hr-accounts");
    exit;
}
?>
