<?php
// This file handles adding new employees and optionally creating accounts
session_start();
include '../StudentLogin/db_conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Employee information
    $id_number = trim($_POST['id_number']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $position = trim($_POST['position']);
    $department = trim($_POST['department']);
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $hire_date = $_POST['hire_date'];
    $create_account = isset($_POST['create_account']);
    $rfid_uid = trim($_POST['rfid_uid'] ?? ''); // required when role=teacher
    
    // Account information (if creating account)
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    try {
        // Start transaction
        $conn->begin_transaction();

        // Check if employees table exists, create if not
        $table_check = $conn->query("SHOW TABLES LIKE 'employees'");
        if ($table_check->num_rows == 0) {
            $create_employees_table = "CREATE TABLE employees (
                id INT AUTO_INCREMENT PRIMARY KEY,
                id_number VARCHAR(20) UNIQUE NOT NULL,
                first_name VARCHAR(50) NOT NULL,
                last_name VARCHAR(50) NOT NULL,
                position VARCHAR(100) NOT NULL,
                department VARCHAR(100) NOT NULL,
                email VARCHAR(100),
                phone VARCHAR(20),
                rfid_uid VARCHAR(20),
                hire_date DATE NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            $conn->query($create_employees_table);
        } else {
            // Check if hire_date column exists, add if not
            $column_check = $conn->query("SHOW COLUMNS FROM employees LIKE 'hire_date'");
            if ($column_check->num_rows == 0) {
                $conn->query("ALTER TABLE employees ADD COLUMN hire_date DATE NOT NULL DEFAULT '2024-01-01'");
            }
            // Ensure rfid_uid column exists
            $rfid_col_check = $conn->query("SHOW COLUMNS FROM employees LIKE 'rfid_uid'");
            if ($rfid_col_check->num_rows == 0) {
                $conn->query("ALTER TABLE employees ADD COLUMN rfid_uid VARCHAR(20) NULL");
            }
        }

        // Basic validations
        if (!empty($phone) && (!preg_match('/^[0-9]+$/', $phone) || strlen($phone) !== 11)) {
            throw new Exception("Phone must be exactly 11 digits (numbers only).");
        }
        if ($create_account && $role === 'teacher') {
            if (!preg_match('/^[0-9]+$/', $rfid_uid) || strlen($rfid_uid) !== 10) {
                throw new Exception("RFID is required for Teacher and must be exactly 10 digits.");
            }
        } else {
            // If not teacher, do not store accidental RFID
            if ($rfid_uid === '') { $rfid_uid = null; }
        }

        // Insert employee
        $stmt = $conn->prepare("INSERT INTO employees (id_number, first_name, last_name, position, department, email, phone, rfid_uid, hire_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssss", $id_number, $first_name, $last_name, $position, $department, $email, $phone, $rfid_uid, $hire_date);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to add employee: " . $stmt->error);
        }

        // If creating account, handle account creation
        if ($create_account && $username && $password && $role) {
            if ($role === 'hr') {
                throw new Exception("Creating HR accounts is not allowed.");
            }
            // Check if employee_accounts table exists, create if not
            $account_table_check = $conn->query("SHOW TABLES LIKE 'employee_accounts'");
            if ($account_table_check->num_rows == 0) {
                $create_accounts_table = "CREATE TABLE employee_accounts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    employee_id VARCHAR(20) NOT NULL,
                    username VARCHAR(50) UNIQUE NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    role ENUM('registrar', 'cashier', 'guidance', 'attendance', 'teacher') NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (employee_id) REFERENCES employees(id_number) ON DELETE CASCADE
                )";
                $conn->query($create_accounts_table);
            }
            // Ensure 'teacher' is allowed in enum
            $roleColumn = $conn->query("SHOW COLUMNS FROM employee_accounts LIKE 'role'")->fetch_assoc();
            if ($roleColumn && isset($roleColumn['Type']) && strpos($roleColumn['Type'], "'teacher'") === false) {
                // Keep existing roles and add teacher; do not remove existing like 'hr' to avoid breaking records
                $conn->query("ALTER TABLE employee_accounts MODIFY role ENUM('registrar','cashier','guidance','attendance','hr','teacher') NOT NULL");
            }

            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert into employee_accounts
            $account_stmt = $conn->prepare("INSERT INTO employee_accounts (employee_id, username, password, role) VALUES (?, ?, ?, ?)");
            $account_stmt->bind_param("ssss", $id_number, $username, $hashed_password, $role);
            
            if (!$account_stmt->execute()) {
                throw new Exception("Failed to create account: " . $account_stmt->error);
            }

            // No longer need to insert into separate role tables - unified in employee_accounts
        }

        // Commit transaction
        $conn->commit();
        
        $success_message = "Employee added successfully";
        if ($create_account) {
            $success_message .= " with system account";
        }
        $_SESSION['success_msg'] = $success_message;
        
    } catch (Exception $e) {
        // Rollback transaction
        $conn->rollback();
        $_SESSION['error_msg'] = "Error: " . $e->getMessage();
        
        // Log detailed error for debugging
        error_log("Employee creation error: " . $e->getMessage());
        error_log("POST data: " . print_r($_POST, true));
    }
    
    // Redirect back to dashboard
    header("Location: Dashboard.php");
    exit;
}
?>