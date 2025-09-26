<?php
// One-time seeder: creates super_admins table and inserts Super Admin user
// Username: superadmin, Password: superadmin123
// After success, DELETE this file for security.

session_start();
header('Content-Type: text/plain');

date_default_timezone_set('Asia/Manila');

$conn = new mysqli('localhost','root','', 'onecci_db');
if ($conn->connect_error) {
  http_response_code(500);
  die('DB connection failed: '.$conn->connect_error."\n");
}

// Create table if not exists
$sql = "CREATE TABLE IF NOT EXISTS super_admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  first_name VARCHAR(100) NULL,
  last_name VARCHAR(100) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
if(!$conn->query($sql)){
  http_response_code(500);
  die('Failed creating table super_admins: '.$conn->error."\n");
}

$username = 'superadmin';
$password = 'superadmin123';
$hash = password_hash($password, PASSWORD_DEFAULT);
$first = 'Super';
$last  = 'Admin';

// Upsert
$stmt = $conn->prepare("SELECT id FROM super_admins WHERE username=?");
$stmt->bind_param('s', $username);
$stmt->execute();
$res = $stmt->get_result();
$exists = $res && $res->num_rows > 0;
$stmt->close();

if ($exists) {
  $stmt = $conn->prepare("UPDATE super_admins SET password=?, first_name=?, last_name=? WHERE username=?");
  $stmt->bind_param('ssss', $hash, $first, $last, $username);
  $ok = $stmt->execute();
  $stmt->close();
  echo $ok ? "Updated existing Super Admin.\n" : "Failed to update Super Admin.\n";
} else {
  $stmt = $conn->prepare("INSERT INTO super_admins (username, password, first_name, last_name) VALUES (?,?,?,?)");
  $stmt->bind_param('ssss', $username, $hash, $first, $last);
  $ok = $stmt->execute();
  $stmt->close();
  echo $ok ? "Created Super Admin.\n" : "Failed to create Super Admin.\n";
}

echo "Username: $username\n";
echo "Password: $password\n";
echo "Login at: /onecci/StudentLogin/login.php (role: superadmin)\n";
echo "IMPORTANT: Delete AdminF/SeedSuperAdmin.php after success.\n";
