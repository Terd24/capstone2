<?php
session_start();
require_once '../StudentLogin/db_conn.php';

// Check if user is Super Admin
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    die('Unauthorized access');
}

echo "<h2>Setting up Archive System...</h2>";

// Create archived_students table matching student_account structure
$create_archived_students = "CREATE TABLE IF NOT EXISTS archived_students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_id INT NOT NULL,
    lrn VARCHAR(20),
    password VARCHAR(255),
    academic_track VARCHAR(100),
    enrollment_status ENUM('OLD', 'NEW'),
    school_type ENUM('PUBLIC', 'PRIVATE'),
    last_name VARCHAR(50),
    first_name VARCHAR(50),
    middle_name VARCHAR(50),
    school_year VARCHAR(20),
    grade_level VARCHAR(20),
    semester VARCHAR(10),
    dob DATE,
    birthplace VARCHAR(100),
    gender ENUM('Male', 'Female'),
    religion VARCHAR(50),
    credentials TEXT,
    payment_mode ENUM('Cash', 'Installment'),
    address TEXT,
    father_name VARCHAR(100),
    father_occupation VARCHAR(100),
    father_contact VARCHAR(20),
    mother_name VARCHAR(100),
    mother_occupation VARCHAR(100),
    mother_contact VARCHAR(20),
    guardian_name VARCHAR(100),
    guardian_occupation VARCHAR(100),
    guardian_contact VARCHAR(20),
    last_school VARCHAR(100),
    last_school_year VARCHAR(20),
    id_number VARCHAR(20),
    username VARCHAR(50),
    rfid_uid VARCHAR(10),
    created_at TIMESTAMP NULL,
    class_schedule VARCHAR(255),
    deleted_at TIMESTAMP NULL,
    deleted_by VARCHAR(255),
    deleted_reason TEXT,
    must_change_password TINYINT(1) DEFAULT 0,
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archived_by VARCHAR(255),
    archive_reason TEXT,
    INDEX idx_original_id (original_id),
    INDEX idx_lrn (lrn),
    INDEX idx_archived_at (archived_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($create_archived_students)) {
    echo "<p style='color: green;'>✓ archived_students table created successfully</p>";
} else {
    echo "<p style='color: red;'>✗ Error creating archived_students table: " . $conn->error . "</p>";
}

// Create archived_employees table matching employee structure
$create_archived_employees = "CREATE TABLE IF NOT EXISTS archived_employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_id INT NOT NULL,
    id_number VARCHAR(20),
    first_name VARCHAR(50),
    middle_name VARCHAR(50),
    last_name VARCHAR(50),
    position VARCHAR(100),
    department VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP NULL,
    hire_date DATE,
    rfid_uid VARCHAR(20),
    deleted_at TIMESTAMP NULL,
    deleted_by VARCHAR(255),
    deleted_reason TEXT,
    archive_scheduled TINYINT(1) DEFAULT 0,
    archive_scheduled_by VARCHAR(100),
    archive_scheduled_at TIMESTAMP NULL,
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archived_by VARCHAR(255),
    archive_reason TEXT,
    INDEX idx_original_id (original_id),
    INDEX idx_id_number (id_number),
    INDEX idx_archived_at (archived_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($create_archived_employees)) {
    echo "<p style='color: green;'>✓ archived_employees table created successfully</p>";
} else {
    echo "<p style='color: red;'>✗ Error creating archived_employees table: " . $conn->error . "</p>";
}

// Create archive_log table for tracking archive actions
$create_archive_log = "CREATE TABLE IF NOT EXISTS archive_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_type ENUM('student', 'employee') NOT NULL,
    record_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    performed_by VARCHAR(255) NOT NULL,
    performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    details TEXT,
    ip_address VARCHAR(45),
    INDEX idx_record (record_type, record_id),
    INDEX idx_performed_at (performed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($create_archive_log)) {
    echo "<p style='color: green;'>✓ archive_log table created successfully</p>";
} else {
    echo "<p style='color: red;'>✗ Error creating archive_log table: " . $conn->error . "</p>";
}

echo "<h3>Archive System Setup Complete!</h3>";
echo "<p><a href='SuperAdminDashboard.php'>Return to Dashboard</a></p>";

$conn->close();
?>
