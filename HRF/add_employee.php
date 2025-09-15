<?php
// This file handles adding new employees and optionally creating accounts

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Employee information
    $id_number = $_POST['id_number'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $position = $_POST['position'];
    $department = $_POST['department'];
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $hire_date = $_POST['hire_date'];
    $create_account = isset($_POST['create_account']);
    
    // Account information (if creating account)
    $username = $_POST['username'] ?? '';
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
                hire_date DATE NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )";
            $conn->query($create_employees_table);
        }

        // Insert employee
        $stmt = $conn->prepare("INSERT INTO employees (id_number, first_name, last_name, position, department, email, phone, hire_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $id_number, $first_name, $last_name, $position, $department, $email, $phone, $hire_date);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to add employee: " . $stmt->error);
        }

        // If creating account, handle account creation
        if ($create_account && $username && $password && $role) {
            // Check if employee_accounts table exists, create if not
            $account_table_check = $conn->query("SHOW TABLES LIKE 'employee_accounts'");
            if ($account_table_check->num_rows == 0) {
                $create_accounts_table = "CREATE TABLE employee_accounts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    employee_id VARCHAR(20) NOT NULL,
                    username VARCHAR(50) UNIQUE NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    role ENUM('registrar', 'cashier', 'guidance', 'hr') NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (employee_id) REFERENCES employees(id_number) ON DELETE CASCADE
                )";
                $conn->query($create_accounts_table);
            }

            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert into employee_accounts
            $account_stmt = $conn->prepare("INSERT INTO employee_accounts (employee_id, username, password, role) VALUES (?, ?, ?, ?)");
            $account_stmt->bind_param("ssss", $id_number, $username, $hashed_password, $role);
            
            if (!$account_stmt->execute()) {
                throw new Exception("Failed to create account: " . $account_stmt->error);
            }

            // Also insert into the specific role table for compatibility
            switch ($role) {
                case 'registrar':
                    $role_stmt = $conn->prepare("INSERT INTO registrar_account (id_number, first_name, last_name, username, password) VALUES (?, ?, ?, ?, ?)");
                    $role_stmt->bind_param("sssss", $id_number, $first_name, $last_name, $username, $hashed_password);
                    break;
                case 'cashier':
                    $role_stmt = $conn->prepare("INSERT INTO cashier_account (id_number, first_name, last_name, username, password) VALUES (?, ?, ?, ?, ?)");
                    $role_stmt->bind_param("sssss", $id_number, $first_name, $last_name, $username, $hashed_password);
                    break;
                case 'guidance':
                    $role_stmt = $conn->prepare("INSERT INTO guidance_account (id_number, first_name, last_name, username, password) VALUES (?, ?, ?, ?, ?)");
                    $role_stmt->bind_param("sssss", $id_number, $first_name, $last_name, $username, $hashed_password);
                    break;
                case 'hr':
                    // Check if hr_account table exists, create if not
                    $hr_table_check = $conn->query("SHOW TABLES LIKE 'hr_account'");
                    if ($hr_table_check->num_rows == 0) {
                        $create_hr_table = "CREATE TABLE hr_account (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            id_number VARCHAR(20) UNIQUE NOT NULL,
                            first_name VARCHAR(50) NOT NULL,
                            last_name VARCHAR(50) NOT NULL,
                            username VARCHAR(50) UNIQUE NOT NULL,
                            password VARCHAR(255) NOT NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        )";
                        $conn->query($create_hr_table);
                    }
                    $role_stmt = $conn->prepare("INSERT INTO hr_account (id_number, first_name, last_name, username, password) VALUES (?, ?, ?, ?, ?)");
                    $role_stmt->bind_param("sssss", $id_number, $first_name, $last_name, $username, $hashed_password);
                    break;
            }
            
            if (isset($role_stmt) && !$role_stmt->execute()) {
                throw new Exception("Failed to create role-specific account: " . $role_stmt->error);
            }
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
        $_SESSION['error_msg'] = $e->getMessage();
    }
    
    // Redirect back to dashboard
    header("Location: Dashboard.php");
    exit;
}
?>
