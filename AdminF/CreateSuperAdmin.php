<?php
// One-time script to create Super Admin account: username=superadmin password=superadmin123
// After running successfully once, DELETE this file for security.

iheader:
session_start();

date_default_timezone_set('Asia/Manila');

$conn = new mysqli('localhost','root','', 'onecci_db');
if ($conn->connect_error) {
    die('DB connection failed: '.$conn->connect_error);
}

function run($conn, $sql){ return $conn->query($sql); }
function esc($conn,$s){ return $conn->real_escape_string($s); }

// 1) Ensure employees base record exists
$empId = 'SA001';
$first = 'System';
$last  = 'Administrator';
$position = 'Super Admin';
$department = 'IT';

run($conn, "CREATE TABLE IF NOT EXISTS employees (
  id_number VARCHAR(50) PRIMARY KEY,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  position VARCHAR(100) NULL,
  department VARCHAR(100) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$stmt = $conn->prepare("SELECT COUNT(*) FROM employees WHERE id_number=?");
$stmt->bind_param('s', $empId);
$stmt->execute(); $cnt = $stmt->get_result()->fetch_row()[0] ?? 0; $stmt->close();
if (!$cnt) {
    $stmt = $conn->prepare("INSERT INTO employees (id_number, first_name, last_name, position, department) VALUES (?,?,?,?,?)");
    $stmt->bind_param('sssss', $empId, $first, $last, $position, $department);
    $stmt->execute();
    $stmt->close();
}

// 2) Ensure employee_accounts table exists and role column supports 'superadmin'
run($conn, "CREATE TABLE IF NOT EXISTS employee_accounts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id VARCHAR(50) NOT NULL,
  username VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(50) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_emp (employee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// If role is ENUM, convert to VARCHAR to support any future roles safely
$colType = null;
$res = $conn->query("SHOW COLUMNS FROM employee_accounts LIKE 'role'");
if ($res && $row = $res->fetch_assoc()) { $colType = strtolower($row['Type']); }
if ($colType && strpos($colType,'enum(') === 0) {
    run($conn, "ALTER TABLE employee_accounts MODIFY role VARCHAR(50) NOT NULL");
}

// 3) Upsert superadmin account
$username = 'superadmin';
$pwdHash = password_hash('superadmin123', PASSWORD_DEFAULT);
$role    = 'superadmin';

$stmt = $conn->prepare("SELECT id FROM employee_accounts WHERE username = ?");
$stmt->bind_param('s', $username);
$stmt->execute(); $rs = $stmt->get_result(); $exists = $rs && $rs->num_rows > 0; $stmt->close();

if ($exists) {
    $stmt = $conn->prepare("UPDATE employee_accounts SET employee_id=?, password=?, role=? WHERE username=?");
    $stmt->bind_param('ssss', $empId, $pwdHash, $role, $username);
    $ok = $stmt->execute();
    $stmt->close();
    $msg = $ok ? 'Updated existing Super Admin account.' : 'Failed to update Super Admin account.';
} else {
    $stmt = $conn->prepare("INSERT INTO employee_accounts (employee_id, username, password, role, created_at) VALUES (?,?,?,?,NOW())");
    $stmt->bind_param('ssss', $empId, $username, $pwdHash, $role);
    $ok = $stmt->execute();
    $stmt->close();
    $msg = $ok ? 'Created Super Admin account.' : 'Failed to create Super Admin account.';
}

// 4) Output result
header('Content-Type: text/plain');
echo $msg."\n";
echo "Username: superadmin\n";
echo "Password: superadmin123\n";
echo "Employee ID: SA001\n";
echo "Go to: /onecci/StudentLogin/login.php and sign in as Super Admin.\n";
echo "IMPORTANT: Delete this file (AdminF/CreateSuperAdmin.php) after success.\n";
