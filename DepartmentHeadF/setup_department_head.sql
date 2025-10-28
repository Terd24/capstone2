-- SQL Script to Create a Department Head Account
-- Run this in your phpMyAdmin or MySQL client

-- Step 1: Create a test employee record (if not exists)
-- Replace with actual employee data if you have an existing employee
INSERT INTO employees (id_number, first_name, last_name, middle_name, position, department, hire_date, phone, email, address)
VALUES 
('CCI2025-DH1', 'John', 'Doe', 'M', 'Department Head', 'Academic Affairs', '2025-01-01', '+63 912-345-6789', 'depthead@gmail.com', 'Sample Address, City, Province')
ON DUPLICATE KEY UPDATE id_number = id_number;

-- Step 2: Create employee account with department_head role
-- Default password: DeptHead123 (hashed with bcrypt)
INSERT INTO employee_accounts (employee_id, username, password, role, must_change_password)
VALUES 
('CCI2025-DH1', 'depthead', '$2y$10$YourHashedPasswordHere', 'department_head', 0)
ON DUPLICATE KEY UPDATE role = 'department_head';

-- Note: To generate the password hash, use PHP:
-- <?php echo password_hash('DeptHead123', PASSWORD_DEFAULT); ?>
-- Or use this pre-generated hash for password "DeptHead123":
-- $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi

-- Step 3: Verify the account was created
SELECT 
    e.id_number,
    e.first_name,
    e.last_name,
    e.position,
    e.department,
    ea.username,
    ea.role
FROM employees e
JOIN employee_accounts ea ON e.id_number = ea.employee_id
WHERE ea.role = 'department_head';

-- Login Credentials:
-- Username: depthead
-- Password: DeptHead123
-- URL: http://localhost/onecci/admin_login.php
