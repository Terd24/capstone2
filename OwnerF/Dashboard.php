<?php
session_start();
include("../StudentLogin/db_conn.php");

// Require owner login
if (!isset($_SESSION['owner_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: ../admin_login.php");
    exit;
}

// Prevent caching (so back button after logout doesn't show dashboard)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Ensure soft delete columns exist in employees and employee_accounts tables
$conn->query("ALTER TABLE employees 
              ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL DEFAULT NULL,
              ADD COLUMN IF NOT EXISTS deleted_by VARCHAR(100) NULL DEFAULT NULL,
              ADD COLUMN IF NOT EXISTS deletion_reason TEXT NULL DEFAULT NULL");

$conn->query("ALTER TABLE employee_accounts 
              ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL DEFAULT NULL,
              ADD COLUMN IF NOT EXISTS deleted_by VARCHAR(100) NULL DEFAULT NULL,
              ADD COLUMN IF NOT EXISTS deletion_reason TEXT NULL DEFAULT NULL");

// Create necessary tables if they don't exist
$conn->query("CREATE TABLE IF NOT EXISTS system_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'success', 'error', 'critical') DEFAULT 'info',
    module VARCHAR(50) NOT NULL,
    performed_by VARCHAR(100) NOT NULL,
    user_role VARCHAR(50) NOT NULL,
    target_table VARCHAR(50),
    target_id VARCHAR(50),
    action_type VARCHAR(50) NOT NULL,
    old_data JSON,
    new_data JSON,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS owner_approval_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_title VARCHAR(255) NOT NULL,
    request_description TEXT NOT NULL,
    request_type ENUM('delete_account', 'restore_account', 'system_maintenance', 'data_modification', 'user_management', 'add_hr_employee', 'delete_hr_employee', 'other') NOT NULL,
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    requester_name VARCHAR(100) NOT NULL,
    requester_role VARCHAR(50) NOT NULL,
    requester_module VARCHAR(50) NOT NULL,
    target_table VARCHAR(50),
    target_id VARCHAR(50),
    target_data JSON,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    owner_comments TEXT,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    reviewed_by VARCHAR(100)
)");

// Alter existing table to add new enum values if they don't exist
$conn->query("ALTER TABLE owner_approval_requests MODIFY request_type ENUM('delete_account', 'restore_account', 'system_maintenance', 'data_modification', 'user_management', 'add_hr_employee', 'delete_hr_employee', 'restore_student', 'restore_employee', 'archive_student', 'archive_employee', 'archive_login_logs', 'archive_attendance', 'database_backup', 'maintenance_mode_toggle', 'other') NOT NULL");

// Handle approval/rejection actions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $request_id = intval($_POST['request_id']);
    $action = $_POST['action']; // 'approve' or 'reject'
    $comments = trim($_POST['comments'] ?? '');
    
    if ($action === 'approve' || $action === 'reject') {
        $status = ($action === 'approve') ? 'approved' : 'rejected';
        $stmt = $conn->prepare("UPDATE owner_approval_requests SET status = ?, reviewed_at = NOW(), reviewed_by = ?, owner_comments = ? WHERE id = ?");
        $stmt->bind_param("sssi", $status, $_SESSION['owner_name'], $comments, $request_id);
        
        if ($stmt->execute()) {
            // Get request details for notification
            $req_stmt = $conn->prepare("SELECT * FROM owner_approval_requests WHERE id = ?");
            $req_stmt->bind_param("i", $request_id);
            $req_stmt->execute();
            $request_data = $req_stmt->get_result()->fetch_assoc();
            
            // If approved, execute the action
            if ($action === 'approve') {
                $request_type = $request_data['request_type'];
                $target_id = $request_data['target_id'];
                $target_data = json_decode($request_data['target_data'], true);
                
                error_log("=== OWNER APPROVAL EXECUTION START ===");
                error_log("Request ID: $request_id");
                error_log("Request Type: $request_type");
                error_log("Target ID: $target_id");
                error_log("Target Data: " . json_encode($target_data));
                
                try {
                    switch ($request_type) {
                        case 'add_hr_employee':
                            // Add the HR employee
                            if ($target_data) {
                                $conn->begin_transaction();
                                
                                // Extract variables for bind_param
                                $emp_id_number = $target_data['id_number'];
                                $emp_first_name = $target_data['first_name'];
                                $emp_middle_name = $target_data['middle_name'];
                                $emp_last_name = $target_data['last_name'];
                                $emp_position = $target_data['position'];
                                $emp_department = $target_data['department'];
                                $emp_email = $target_data['email'];
                                $emp_phone = $target_data['phone'];
                                $emp_address = $target_data['address'];
                                $emp_hire_date = $target_data['hire_date'];
                                
                                // Insert employee
                                $emp_stmt = $conn->prepare("INSERT INTO employees (id_number, first_name, middle_name, last_name, position, department, email, phone, address, hire_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                                $emp_stmt->bind_param("ssssssssss", 
                                    $emp_id_number,
                                    $emp_first_name,
                                    $emp_middle_name,
                                    $emp_last_name,
                                    $emp_position,
                                    $emp_department,
                                    $emp_email,
                                    $emp_phone,
                                    $emp_address,
                                    $emp_hire_date
                                );
                                $emp_stmt->execute();
                                
                                // Create account
                                $hashed_password = password_hash($target_data['password'], PASSWORD_DEFAULT);
                                $emp_username = $target_data['username'];
                                $emp_role = $target_data['role'];
                                
                                $acc_stmt = $conn->prepare("INSERT INTO employee_accounts (employee_id, username, password, role) VALUES (?, ?, ?, ?)");
                                $acc_stmt->bind_param("ssss", 
                                    $emp_id_number,
                                    $emp_username,
                                    $hashed_password,
                                    $emp_role
                                );
                                $acc_stmt->execute();
                                
                                $conn->commit();
                            }
                            break;
                            
                        case 'delete_hr_employee':
                            // Soft delete HR employee and their account
                            $conn->begin_transaction();
                            
                            // Get deletion reason from target_data
                            $deletion_reason = $target_data['deletion_reason'] ?? 'Approved by Owner';
                            $deleted_by = $_SESSION['owner_name'] ?? 'Owner';
                            
                            // Soft delete employee account first
                            $del_acc_stmt = $conn->prepare("UPDATE employee_accounts SET deleted_at = NOW(), deleted_by = ?, deletion_reason = ? WHERE employee_id = ?");
                            if ($del_acc_stmt) {
                                $del_acc_stmt->bind_param('sss', $deleted_by, $deletion_reason, $target_id);
                                if (!$del_acc_stmt->execute()) {
                                    error_log("Failed to soft delete employee account: " . $del_acc_stmt->error);
                                }
                                $del_acc_stmt->close();
                            }
                            
                            // Soft delete employee record
                            $del_emp_stmt = $conn->prepare("UPDATE employees SET deleted_at = NOW(), deleted_by = ?, deletion_reason = ? WHERE id_number = ?");
                            if ($del_emp_stmt) {
                                $del_emp_stmt->bind_param('sss', $deleted_by, $deletion_reason, $target_id);
                                if (!$del_emp_stmt->execute()) {
                                    error_log("Failed to soft delete employee: " . $del_emp_stmt->error);
                                    throw new Exception("Failed to delete employee");
                                }
                                $affected = $del_emp_stmt->affected_rows;
                                error_log("Soft deleted employee $target_id - Affected rows: $affected");
                                $del_emp_stmt->close();
                            }
                            
                            $conn->commit();
                            break;
                            
                        case 'restore_student':
                            // Restore student from soft delete
                            error_log("Owner approving restore for student: $target_id");
                            $conn->begin_transaction();
                            
                            $restore_stmt = $conn->prepare("UPDATE student_account SET deleted_at = NULL, deleted_by = NULL, deleted_reason = NULL WHERE id_number = ?");
                            if ($restore_stmt) {
                                $restore_stmt->bind_param('s', $target_id);
                                if ($restore_stmt->execute()) {
                                    $affected = $restore_stmt->affected_rows;
                                    error_log("Restored student $target_id - Affected rows: $affected");
                                    if ($affected === 0) {
                                        error_log("WARNING: No student found with id_number: $target_id");
                                    }
                                } else {
                                    error_log("Failed to restore student: " . $restore_stmt->error);
                                    throw new Exception("Failed to restore student");
                                }
                                $restore_stmt->close();
                            }
                            
                            $conn->commit();
                            break;
                            
                        case 'restore_employee':
                            // Restore employee from soft delete
                            error_log("Owner approving restore for employee: $target_id");
                            $conn->begin_transaction();
                            
                            $restore_stmt = $conn->prepare("UPDATE employees SET deleted_at = NULL, deleted_by = NULL, deleted_reason = NULL WHERE id_number = ?");
                            if ($restore_stmt) {
                                $restore_stmt->bind_param('s', $target_id);
                                if ($restore_stmt->execute()) {
                                    $affected = $restore_stmt->affected_rows;
                                    error_log("Restored employee $target_id - Affected rows: $affected");
                                    if ($affected === 0) {
                                        error_log("WARNING: No employee found with id_number: $target_id");
                                    }
                                } else {
                                    error_log("Failed to restore employee: " . $restore_stmt->error);
                                    throw new Exception("Failed to restore employee");
                                }
                                $restore_stmt->close();
                            }
                            
                            $conn->commit();
                            break;
                            
                        case 'archive_student':
                            // Permanently archive student
                            error_log("Owner approving archive for student: $target_id");
                            $conn->begin_transaction();
                            
                            // Ensure archived_students table exists
                            $conn->query("CREATE TABLE IF NOT EXISTS archived_students (
                                archive_id INT AUTO_INCREMENT PRIMARY KEY,
                                original_id INT,
                                lrn VARCHAR(50),
                                password VARCHAR(255),
                                academic_track VARCHAR(100),
                                enrollment_status VARCHAR(50),
                                school_type VARCHAR(50),
                                last_name VARCHAR(100),
                                first_name VARCHAR(100),
                                middle_name VARCHAR(100),
                                school_year VARCHAR(20),
                                grade_level VARCHAR(50),
                                semester VARCHAR(20),
                                dob DATE,
                                birthplace VARCHAR(255),
                                gender VARCHAR(20),
                                religion VARCHAR(100),
                                credentials TEXT,
                                payment_mode VARCHAR(50),
                                address TEXT,
                                father_name VARCHAR(100),
                                father_occupation VARCHAR(100),
                                father_contact VARCHAR(50),
                                mother_name VARCHAR(100),
                                mother_occupation VARCHAR(100),
                                mother_contact VARCHAR(50),
                                guardian_name VARCHAR(100),
                                guardian_occupation VARCHAR(100),
                                guardian_contact VARCHAR(50),
                                last_school VARCHAR(255),
                                last_school_year VARCHAR(20),
                                id_number VARCHAR(50),
                                username VARCHAR(100),
                                rfid_uid VARCHAR(50),
                                created_at DATETIME,
                                class_schedule TEXT,
                                deleted_at DATETIME,
                                deleted_by VARCHAR(100),
                                deleted_reason TEXT,
                                must_change_password TINYINT(1) DEFAULT 0,
                                archived_at DATETIME,
                                archived_by VARCHAR(100),
                                archive_reason TEXT,
                                INDEX(id_number),
                                INDEX(archived_at)
                            )");
                            
                            // Get full student data
                            $get_stmt = $conn->prepare("SELECT * FROM student_account WHERE id_number = ?");
                            $get_stmt->bind_param('s', $target_id);
                            $get_stmt->execute();
                            $student_result = $get_stmt->get_result();
                            
                            if ($student_result->num_rows > 0) {
                                $student = $student_result->fetch_assoc();
                                error_log("Found student to archive: " . $student['first_name'] . " " . $student['last_name']);
                                
                                // Insert into archive table
                                $archive_stmt = $conn->prepare("INSERT INTO archived_students (
                                    original_id, lrn, password, academic_track, enrollment_status, school_type,
                                    last_name, first_name, middle_name, school_year, grade_level, semester,
                                    dob, birthplace, gender, religion, credentials, payment_mode, address,
                                    father_name, father_occupation, father_contact,
                                    mother_name, mother_occupation, mother_contact,
                                    guardian_name, guardian_occupation, guardian_contact,
                                    last_school, last_school_year, id_number, username, rfid_uid,
                                    created_at, class_schedule, deleted_at, deleted_by, deleted_reason,
                                    must_change_password, archived_at, archived_by, archive_reason
                                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)");
                                
                                $archived_by = $_SESSION['owner_name'] ?? 'Owner';
                                $archive_reason = 'Approved by Owner: ' . $comments;
                                
                                // Count: 41 parameters total (original_id=int, must_change_password=int, rest=strings)
                                // Type string: i + 37s + i + 2s = 41 total
                                $archive_stmt->bind_param("isssssssssssssssssssssssssssssssssssssiss",
                                    $student['id'], 
                                    $student['lrn'], 
                                    $student['password'], 
                                    $student['academic_track'],
                                    $student['enrollment_status'], 
                                    $student['school_type'], 
                                    $student['last_name'],
                                    $student['first_name'], 
                                    $student['middle_name'], 
                                    $student['school_year'],
                                    $student['grade_level'], 
                                    $student['semester'], 
                                    $student['dob'], 
                                    $student['birthplace'],
                                    $student['gender'], 
                                    $student['religion'], 
                                    $student['credentials'], 
                                    $student['payment_mode'],
                                    $student['address'], 
                                    $student['father_name'], 
                                    $student['father_occupation'],
                                    $student['father_contact'], 
                                    $student['mother_name'], 
                                    $student['mother_occupation'],
                                    $student['mother_contact'], 
                                    $student['guardian_name'], 
                                    $student['guardian_occupation'],
                                    $student['guardian_contact'], 
                                    $student['last_school'], 
                                    $student['last_school_year'],
                                    $student['id_number'], 
                                    $student['username'], 
                                    $student['rfid_uid'], 
                                    $student['created_at'],
                                    $student['class_schedule'], 
                                    $student['deleted_at'], 
                                    $student['deleted_by'],
                                    $student['deleted_reason'], 
                                    $student['must_change_password'], 
                                    $archived_by, 
                                    $archive_reason
                                );
                                
                                if ($archive_stmt->execute()) {
                                    $archive_id = $conn->insert_id;
                                    error_log("Successfully inserted into archived_students with ID: $archive_id");
                                    
                                    // Delete from main table
                                    $delete_stmt = $conn->prepare("DELETE FROM student_account WHERE id_number = ?");
                                    $delete_stmt->bind_param('s', $target_id);
                                    if ($delete_stmt->execute()) {
                                        error_log("Successfully deleted student $target_id from student_account");
                                    } else {
                                        error_log("Failed to delete student from student_account: " . $delete_stmt->error);
                                    }
                                    error_log("Archived and deleted student $target_id");
                                } else {
                                    error_log("Failed to execute archive_stmt: " . $archive_stmt->error);
                                    throw new Exception("Failed to archive student: " . $archive_stmt->error);
                                }
                            } else {
                                error_log("WARNING: No student found to archive with id_number: $target_id");
                            }
                            
                            $conn->commit();
                            break;
                            
                        case 'archive_employee':
                            // Permanently archive employee
                            error_log("Owner approving archive for employee: $target_id");
                            $conn->begin_transaction();
                            
                            // Get full employee data
                            $get_stmt = $conn->prepare("SELECT * FROM employees WHERE id_number = ?");
                            $get_stmt->bind_param('s', $target_id);
                            $get_stmt->execute();
                            $employee_result = $get_stmt->get_result();
                            
                            if ($employee_result->num_rows > 0) {
                                $employee = $employee_result->fetch_assoc();
                                
                                // Insert into archive table
                                $archive_stmt = $conn->prepare("INSERT INTO archived_employees (
                                    original_id, id_number, first_name, middle_name, last_name,
                                    position, department, email, phone, address, created_at, hire_date,
                                    rfid_uid, deleted_at, deleted_by, deleted_reason,
                                    archive_scheduled, archive_scheduled_by, archive_scheduled_at,
                                    archived_at, archived_by, archive_reason
                                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)");
                                
                                $archived_by = $_SESSION['owner_name'] ?? 'Owner';
                                $archive_reason = 'Approved by Owner: ' . $comments;
                                
                                $archive_stmt->bind_param("isssssssssssssssissss",
                                    $employee['id'], $employee['id_number'], $employee['first_name'],
                                    $employee['middle_name'], $employee['last_name'], $employee['position'],
                                    $employee['department'], $employee['email'], $employee['phone'],
                                    $employee['address'], $employee['created_at'], $employee['hire_date'],
                                    $employee['rfid_uid'], $employee['deleted_at'], $employee['deleted_by'],
                                    $employee['deleted_reason'], $employee['archive_scheduled'],
                                    $employee['archive_scheduled_by'], $employee['archive_scheduled_at'],
                                    $archived_by, $archive_reason
                                );
                                
                                if ($archive_stmt->execute()) {
                                    // Delete from main table
                                    $delete_stmt = $conn->prepare("DELETE FROM employees WHERE id_number = ?");
                                    $delete_stmt->bind_param('s', $target_id);
                                    $delete_stmt->execute();
                                    error_log("Archived and deleted employee $target_id");
                                } else {
                                    throw new Exception("Failed to archive employee: " . $archive_stmt->error);
                                }
                            } else {
                                error_log("WARNING: No employee found to archive with id_number: $target_id");
                            }
                            
                            $conn->commit();
                            break;
                            
                        case 'archive_login_logs':
                            // Archive login logs - copied from clear_login_logs.php
                            error_log("=== ARCHIVE LOGIN LOGS START ===");
                            error_log("Owner approving archive login logs from {$target_data['start_date']} to {$target_data['end_date']}");
                            
                            try {
                                $conn->begin_transaction();
                                
                                $start = $target_data['start_date'];
                                $end = $target_data['end_date'];
                                error_log("Archiving from $start to $end");
                            
                            // Ensure archive table exists
                            $conn->query("CREATE TABLE IF NOT EXISTS login_logs_archive (
                                id INT AUTO_INCREMENT PRIMARY KEY,
                                original_id INT,
                                username VARCHAR(100),
                                login_time DATETIME,
                                logout_time DATETIME,
                                last_activity DATETIME,
                                session_duration INT,
                                ip_address VARCHAR(45),
                                user_agent TEXT,
                                user_type VARCHAR(50),
                                status VARCHAR(20),
                                archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                archived_by VARCHAR(100),
                                archived_reason VARCHAR(255),
                                INDEX idx_username (username),
                                INDEX idx_login_time (login_time),
                                INDEX idx_archived_at (archived_at)
                            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                            
                            $tableCheck = $conn->query("SHOW TABLES LIKE 'login_activity'");
                            $tableName = ($tableCheck && $tableCheck->num_rows > 0) ? 'login_activity' : 'system_logs';
                            $dateCol = ($tableName === 'login_activity') ? 'login_time' : 'timestamp';
                            
                            // Get records to archive
                            $selectQuery = "SELECT * FROM $tableName WHERE DATE($dateCol) BETWEEN ? AND ?";
                            if ($tableName === 'system_logs') {
                                $selectQuery .= " AND action LIKE '%login%'";
                            }
                            
                            $selectStmt = $conn->prepare($selectQuery);
                            $selectStmt->bind_param('ss', $start, $end);
                            $selectStmt->execute();
                            $result = $selectStmt->get_result();
                            
                            $count = 0;
                            $archived_by = $_SESSION['owner_name'] ?? 'Owner';
                            $archived_reason = "Archived login logs from $start to $end";
                            
                            // Archive each record
                            while ($row = $result->fetch_assoc()) {
                                $archiveStmt = $conn->prepare("INSERT INTO login_logs_archive 
                                    (original_id, username, login_time, logout_time, last_activity, session_duration, ip_address, user_agent, user_type, status, archived_by, archived_reason) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                                
                                $original_id = $row['id'];
                                $username = $row['username'] ?? $row['user_id'] ?? 'Unknown';
                                $login_time = $row[$dateCol];
                                $logout_time = $row['logout_time'] ?? null;
                                $last_activity = $row['last_activity'] ?? null;
                                $session_duration = $row['session_duration'] ?? null;
                                $ip_address = $row['ip_address'] ?? 'N/A';
                                $user_agent = $row['user_agent'] ?? 'N/A';
                                $user_type = $row['user_type'] ?? $row['role'] ?? 'Unknown';
                                $status = $row['status'] ?? 'success';
                                
                                $archiveStmt->bind_param('isssssisssss', 
                                    $original_id, $username, $login_time, $logout_time, $last_activity, 
                                    $session_duration, $ip_address, $user_agent, $user_type, $status, 
                                    $archived_by, $archived_reason
                                );
                                
                                if ($archiveStmt->execute()) {
                                    $count++;
                                }
                                $archiveStmt->close();
                            }
                            
                            // Delete archived records from original table
                            if ($count > 0) {
                                $deleteQuery = "DELETE FROM $tableName WHERE DATE($dateCol) BETWEEN ? AND ?";
                                if ($tableName === 'system_logs') {
                                    $deleteQuery .= " AND action LIKE '%login%'";
                                }
                                
                                $deleteStmt = $conn->prepare($deleteQuery);
                                $deleteStmt->bind_param('ss', $start, $end);
                                $deleteStmt->execute();
                                $deleteStmt->close();
                            }
                            
                                $selectStmt->close();
                                $conn->commit();
                                error_log("Successfully archived $count login records from $tableName");
                            } catch (Exception $archive_ex) {
                                $conn->rollback();
                                error_log("ERROR in archive_login_logs: " . $archive_ex->getMessage());
                                error_log("Stack: " . $archive_ex->getTraceAsString());
                                // Don't re-throw, just log
                            }
                            error_log("=== ARCHIVE LOGIN LOGS END ===");
                            break;
                            
                        case 'archive_attendance':
                            // Archive attendance records
                            error_log("=== ARCHIVE ATTENDANCE START ===");
                            error_log("Owner approving archive attendance from {$target_data['start_date']} to {$target_data['end_date']}");
                            
                            try {
                                $conn->begin_transaction();
                                
                                $start = $target_data['start_date'];
                                $end = $target_data['end_date'];
                                error_log("Archiving attendance from $start to $end");
                            
                                // Ensure archive table exists
                                $conn->query("CREATE TABLE IF NOT EXISTS attendance_archive (
                                    id INT AUTO_INCREMENT PRIMARY KEY,
                                    original_id INT,
                                    employee_id VARCHAR(50),
                                    employee_name VARCHAR(255),
                                    date DATE,
                                    time_in TIME,
                                    time_out TIME,
                                    status VARCHAR(50),
                                    hours_worked DECIMAL(5,2),
                                    notes TEXT,
                                    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                    archived_by VARCHAR(100),
                                    archived_reason VARCHAR(255),
                                    INDEX idx_employee_id (employee_id),
                                    INDEX idx_date (date),
                                    INDEX idx_archived_at (archived_at)
                                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                                
                                // Get records to archive from attendance table
                                $selectQuery = "SELECT * FROM attendance WHERE date BETWEEN ? AND ?";
                                $selectStmt = $conn->prepare($selectQuery);
                                $selectStmt->bind_param('ss', $start, $end);
                                $selectStmt->execute();
                                $result = $selectStmt->get_result();
                                
                                $count = 0;
                                $archived_by = $_SESSION['owner_name'] ?? 'Owner';
                                $archived_reason = "Archived attendance records from $start to $end";
                                
                                // Archive each record
                                while ($row = $result->fetch_assoc()) {
                                    $archiveStmt = $conn->prepare("INSERT INTO attendance_archive 
                                        (original_id, employee_id, employee_name, date, time_in, time_out, status, hours_worked, notes, archived_by, archived_reason) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                                    
                                    $original_id = $row['id'];
                                    $employee_id = $row['employee_id'] ?? 'Unknown';
                                    $employee_name = $row['employee_name'] ?? 'Unknown';
                                    $date = $row['date'];
                                    $time_in = $row['time_in'] ?? null;
                                    $time_out = $row['time_out'] ?? null;
                                    $status = $row['status'] ?? 'present';
                                    $hours_worked = $row['hours_worked'] ?? null;
                                    $notes = $row['notes'] ?? null;
                                    
                                    $archiveStmt->bind_param('issssssdsss', 
                                        $original_id, $employee_id, $employee_name, $date, $time_in, $time_out, 
                                        $status, $hours_worked, $notes, $archived_by, $archived_reason
                                    );
                                    
                                    if ($archiveStmt->execute()) {
                                        $count++;
                                    }
                                    $archiveStmt->close();
                                }
                                
                                // Delete archived records from original table
                                if ($count > 0) {
                                    $deleteQuery = "DELETE FROM attendance WHERE date BETWEEN ? AND ?";
                                    $deleteStmt = $conn->prepare($deleteQuery);
                                    $deleteStmt->bind_param('ss', $start, $end);
                                    $deleteStmt->execute();
                                    $deleteStmt->close();
                                }
                                
                                $selectStmt->close();
                                $conn->commit();
                                error_log("Successfully archived $count attendance records");
                            } catch (Exception $archive_ex) {
                                $conn->rollback();
                                error_log("ERROR in archive_attendance: " . $archive_ex->getMessage());
                                error_log("Stack: " . $archive_ex->getTraceAsString());
                                // Don't re-throw, just log
                            }
                            error_log("=== ARCHIVE ATTENDANCE END ===");
                            break;
                            
                        case 'database_backup':
                            // Execute database backup
                            error_log("=== DATABASE BACKUP START ===");
                            error_log("Owner approving database backup request");
                            
                            try {
                                // Generate backup filename with timestamp
                                $timestamp = date('Y-m-d_H-i-s');
                                $filename = 'onecci_db_backup_' . $timestamp . '.sql';
                                
                                // Get all tables
                                $tables = [];
                                $result = $conn->query("SHOW TABLES");
                                while ($row = $result->fetch_array()) {
                                    $tables[] = $row[0];
                                }
                                
                                // Start building SQL dump
                                $sqlDump = "-- OneCCI Database Backup\n";
                                $sqlDump .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
                                $sqlDump .= "-- Approved by: " . ($_SESSION['owner_name'] ?? 'Owner') . "\n";
                                $sqlDump .= "-- Database: onecci_db\n\n";
                                $sqlDump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
                                
                                foreach ($tables as $table) {
                                    // Get CREATE TABLE statement
                                    $createTableResult = $conn->query("SHOW CREATE TABLE `$table`");
                                    $createTableRow = $createTableResult->fetch_array();
                                    $sqlDump .= "\n-- Table: $table\n";
                                    $sqlDump .= "DROP TABLE IF EXISTS `$table`;\n";
                                    $sqlDump .= $createTableRow[1] . ";\n\n";
                                    
                                    // Get table data
                                    $dataResult = $conn->query("SELECT * FROM `$table`");
                                    if ($dataResult->num_rows > 0) {
                                        $sqlDump .= "-- Data for table: $table\n";
                                        while ($row = $dataResult->fetch_assoc()) {
                                            $sqlDump .= "INSERT INTO `$table` VALUES (";
                                            $values = [];
                                            foreach ($row as $value) {
                                                if ($value === null) {
                                                    $values[] = 'NULL';
                                                } else {
                                                    $values[] = "'" . $conn->real_escape_string($value) . "'";
                                                }
                                            }
                                            $sqlDump .= implode(', ', $values) . ");\n";
                                        }
                                        $sqlDump .= "\n";
                                    }
                                }
                                
                                $sqlDump .= "SET FOREIGN_KEY_CHECKS=1;\n";
                                
                                // Save backup file to backups directory
                                $backupDir = '../backups';
                                if (!file_exists($backupDir)) {
                                    mkdir($backupDir, 0755, true);
                                }
                                $filepath = $backupDir . '/' . $filename;
                                file_put_contents($filepath, $sqlDump);
                                
                                error_log("Database backup created: $filename");
                                
                                // Update the approval request with the backup filename
                                $updateStmt = $conn->prepare("UPDATE owner_approval_requests SET target_data = ? WHERE id = ?");
                                $updatedTargetData = json_encode(['reason' => $target_data['reason'], 'requested_at' => $target_data['requested_at'], 'backup_filename' => $filename]);
                                $updateStmt->bind_param('si', $updatedTargetData, $request_id);
                                $updateStmt->execute();
                                $updateStmt->close();
                                
                                error_log("Backup filename stored in approval request: $filename");
                                
                            } catch (Exception $backup_ex) {
                                error_log("ERROR in database_backup: " . $backup_ex->getMessage());
                                error_log("Stack: " . $backup_ex->getTraceAsString());
                            }
                            error_log("=== DATABASE BACKUP END ===");
                            break;
                            
                        case 'maintenance_mode_toggle':
                            // Toggle maintenance mode
                            error_log("=== MAINTENANCE MODE TOGGLE START ===");
                            error_log("Target data: " . json_encode($target_data));
                            $action = $target_data['action'] ?? '';
                            error_log("Owner approving maintenance mode action: '$action'");
                            
                            if (empty($action)) {
                                error_log("ERROR: Action is empty!");
                                break;
                            }
                            
                            try {
                                // Create system_config table if not exists
                                $conn->query("CREATE TABLE IF NOT EXISTS system_config (
                                    config_key VARCHAR(50) PRIMARY KEY,
                                    config_value TEXT,
                                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                                )");
                                
                                $newValue = ($action === 'enable') ? '1' : '0';
                                
                                error_log("Setting maintenance_mode to: $newValue (action was: $action)");
                                
                                $stmt = $conn->prepare("INSERT INTO system_config (config_key, config_value) 
                                                        VALUES ('maintenance_mode', ?) 
                                                        ON DUPLICATE KEY UPDATE config_value = ?");
                                $stmt->bind_param('ss', $newValue, $newValue);
                                
                                if ($stmt->execute()) {
                                    $affected = $stmt->affected_rows;
                                    error_log("Maintenance mode $action successful - Affected rows: $affected");
                                    
                                    // Verify it was set
                                    $verify = $conn->query("SELECT config_value FROM system_config WHERE config_key = 'maintenance_mode'");
                                    if ($verify && $verify->num_rows > 0) {
                                        $verifyRow = $verify->fetch_assoc();
                                        error_log("Verified maintenance_mode value in DB: " . $verifyRow['config_value']);
                                    }
                                } else {
                                    error_log("Failed to toggle maintenance mode: " . $stmt->error);
                                }
                                $stmt->close();
                                
                            } catch (Exception $maint_ex) {
                                error_log("ERROR in maintenance_mode_toggle: " . $maint_ex->getMessage());
                                error_log("Stack: " . $maint_ex->getTraceAsString());
                            }
                            error_log("=== MAINTENANCE MODE TOGGLE END ===");
                            break;
                    }
                } catch (Exception $e) {
                    error_log("=== EXCEPTION CAUGHT IN APPROVAL EXECUTION ===");
                    error_log("Error: " . $e->getMessage());
                    error_log("File: " . $e->getFile());
                    error_log("Line: " . $e->getLine());
                    error_log("Trace: " . $e->getTraceAsString());
                    if (isset($conn)) {
                        $conn->rollback();
                    }
                    error_log("Error executing approved action: " . $e->getMessage());
                }
                
                error_log("=== OWNER APPROVAL EXECUTION END ===");
            }
            
            // Create notification
            $notif_title = "Request " . ucfirst($action) . "d";
            $notif_message = "Owner has {$action}d request: {$request_data['request_title']}";
            $notif_type = ($action === 'approve') ? 'success' : 'warning';
            $notif_module = 'Owner';
            $notif_role = 'owner';
            $notif_action_type = 'request_' . $action;
            
            $notif_stmt = $conn->prepare("INSERT INTO system_notifications (title, message, type, module, performed_by, user_role, action_type, target_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $notif_stmt->bind_param("sssssssi", $notif_title, $notif_message, $notif_type, $notif_module, $_SESSION['owner_name'], $notif_role, $notif_action_type, $request_id);
            $notif_stmt->execute();
            
            $_SESSION['success_msg'] = "Request has been " . $action . "d successfully." . ($action === 'approve' ? " Action has been executed." : "");
        } else {
            $_SESSION['error_msg'] = "Error processing request: " . $conn->error;
        }
    }
    
    header("Location: Dashboard.php");
    exit;
}

// Handle notification mark as read
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['mark_read'])) {
    $notif_id = intval($_POST['notification_id']);
    $stmt = $conn->prepare("UPDATE system_notifications SET is_read = TRUE WHERE id = ?");
    $stmt->bind_param("i", $notif_id);
    $stmt->execute();
    
    header("Location: Dashboard.php");
    exit;
}

// Handle mark all notifications as read
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['mark_all_read'])) {
    $conn->query("UPDATE system_notifications SET is_read = TRUE WHERE is_read = FALSE");
    header("Location: Dashboard.php");
    exit;
}

// Handle success/error messages
$success_msg = $_SESSION['success_msg'] ?? '';
if ($success_msg) unset($_SESSION['success_msg']);

$error_msg = $_SESSION['error_msg'] ?? '';
if ($error_msg) unset($_SESSION['error_msg']);

// Get approval request statistics
$stats_query = "SELECT 
    COUNT(*) as total_requests,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_requests,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_requests,
    SUM(CASE WHEN priority = 'critical' AND status = 'pending' THEN 1 ELSE 0 END) as critical_pending
FROM owner_approval_requests";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Get pending approval requests (priority order)
$pending_query = "SELECT * FROM owner_approval_requests WHERE status = 'pending' ORDER BY 
    CASE priority 
        WHEN 'critical' THEN 1 
        WHEN 'high' THEN 2 
        WHEN 'medium' THEN 3 
        WHEN 'low' THEN 4 
    END, requested_at ASC";
$pending_result = $conn->query($pending_query);

// Get recent approval activity
$recent_query = "SELECT * FROM owner_approval_requests WHERE status IN ('approved', 'rejected') ORDER BY reviewed_at DESC LIMIT 5";
$recent_result = $conn->query($recent_query);

// Get system notifications (unread first) - Initial load of 5
$notifications_query = "SELECT * FROM system_notifications ORDER BY is_read ASC, created_at DESC LIMIT 5";
$notifications_result = $conn->query($notifications_query);

// Get notification statistics
$notif_stats_query = "SELECT 
    COUNT(*) as total_notifications,
    SUM(CASE WHEN is_read = FALSE THEN 1 ELSE 0 END) as unread_notifications,
    SUM(CASE WHEN type = 'critical' AND is_read = FALSE THEN 1 ELSE 0 END) as critical_unread
FROM system_notifications";
$notif_stats_result = $conn->query($notif_stats_query);
$notif_stats = $notif_stats_result->fetch_assoc();

// Get soft-deleted accounts count (for dashboard overview only)
$deleted_students_query = "SELECT COUNT(*) as count FROM student_account WHERE deleted_at IS NOT NULL";
$deleted_students_result = $conn->query($deleted_students_query);
$deleted_students_count = $deleted_students_result ? $deleted_students_result->fetch_assoc()['count'] : 0;

$deleted_employees_query = "SELECT COUNT(*) as count FROM employees WHERE deleted_at IS NOT NULL";
$deleted_employees_result = $conn->query($deleted_employees_query);
$deleted_employees_count = $deleted_employees_result ? $deleted_employees_result->fetch_assoc()['count'] : 0;

$total_deleted_accounts = $deleted_students_count + $deleted_employees_count;

// Get today's login activity
$today = date('Y-m-d');
$today_logins = [];

// Check if login_activity table exists
$table_check = $conn->query("SHOW TABLES LIKE 'login_activity'");
if ($table_check && $table_check->num_rows > 0) {
    try {
        // Query to get login activity with names from appropriate tables
        $login_query = "
            SELECT 
                la.user_type, 
                la.id_number, 
                la.username, 
                la.role, 
                la.login_time,
                la.logout_time,
                la.session_duration,
                CASE 
                    WHEN la.user_type = 'student' THEN CONCAT(s.first_name, ' ', s.last_name)
                    WHEN la.user_type = 'employee' THEN CONCAT(e.first_name, ' ', e.last_name)
                    WHEN la.user_type = 'parent' THEN CONCAT('Parent of ', sc.first_name, ' ', sc.last_name)
                    ELSE la.username
                END as full_name
            FROM login_activity la
            LEFT JOIN student_account s ON la.id_number = s.id_number AND la.user_type = 'student'
            LEFT JOIN employees e ON la.id_number = e.id_number AND la.user_type = 'employee'
            LEFT JOIN student_account sc ON la.id_number = sc.id_number AND la.user_type = 'parent'
            WHERE DATE(la.login_time) = ?
            ORDER BY la.login_time DESC 
            LIMIT 50
        ";
        
        if ($stmt = $conn->prepare($login_query)) {
            $stmt->bind_param('s', $today);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $today_logins[] = $row;
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        error_log("Login activity error: " . $e->getMessage());
    }
}

// Initial load - get first page of request history (will be replaced by AJAX)
$history_page = 1;
$history_filter = 'all';
$history_per_page = 10;
$history_offset = 0;

// Get total count for initial display
$history_count_query = "SELECT COUNT(*) as total FROM owner_approval_requests WHERE status IN ('approved', 'rejected')";
$history_count_result = $conn->query($history_count_query);
$history_total = $history_count_result->fetch_assoc()['total'];
$history_total_pages = ceil($history_total / $history_per_page);

// Get first page results
$history_query = "SELECT * FROM owner_approval_requests WHERE status IN ('approved', 'rejected') ORDER BY reviewed_at DESC LIMIT $history_per_page";
$history_result = $conn->query($history_query);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard - Cornerstone College Inc.</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .nav-item.active { background: rgba(255,255,255,0.1); }
        .card-shadow { box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .priority-critical { border-left: 4px solid #dc2626; background: #fef2f2; }
        .priority-high { border-left: 4px solid #ea580c; background: #fff7ed; }
        .priority-medium { border-left: 4px solid #d97706; background: #fffbeb; }
        .priority-low { border-left: 4px solid #65a30d; background: #f7fee7; }
        
        /* Custom notification animation */
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        .animate-slide-in {
            animation: slideIn 0.3s ease-out;
        }
        #customNotification {
            transition: all 0.3s ease-out;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50 flex">

    <!-- Sidebar -->
    <div id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-gradient-to-b from-[#0B2C62] to-[#153e86] text-white transform -translate-x-full transition-transform duration-300 ease-in-out lg:translate-x-0 overflow-y-auto flex flex-col">
        <!-- Header with Logo -->
        <div class="flex items-center gap-3 h-16 px-6 border-b border-white/10 flex-shrink-0">
            <img src="../images/LogoCCI.png" class="h-8 w-8 rounded-full bg-white p-1" alt="Logo">
            <div class="leading-tight">
                <div class="font-bold text-sm">Cornerstone College</div>
                <div class="text-xs text-blue-200">Owner Portal</div>
            </div>
        </div>
        
        <!-- Navigation - Flex grow to fill space -->
        <nav class="flex-1 px-4 py-6">
            <div class="space-y-1">
                <!-- Dashboard -->
                <a href="#dashboard" onclick="showSection('dashboard', event)" class="nav-item active flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-white/10 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                    </svg>
                    <span>Dashboard</span>
                </a>
                
                <!-- Management Tools -->
                <div class="pt-4">
                    <div class="text-xs font-semibold text-blue-200 uppercase tracking-wider px-4 mb-2">Management</div>
                    <a href="#notifications" onclick="showSection('notifications', event)" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-white/10 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4 19h6v-2H4v2zM4 15h8v-2H4v2zM4 11h8V9H4v2z"/>
                        </svg>
                        <span>System Notifications</span>
                        <?php if ($notif_stats['unread_notifications'] > 0): ?>
                            <span class="bg-red-500 text-white text-xs rounded-full px-2 py-1 ml-auto"><?= $notif_stats['unread_notifications'] ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="#approval-requests" onclick="showSection('approval-requests', event)" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-white/10 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Approval Requests</span>
                        <?php if ($stats['pending_requests'] > 0): ?>
                            <span class="bg-yellow-500 text-white text-xs rounded-full px-2 py-1 ml-auto"><?= $stats['pending_requests'] ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="#request-history" onclick="showSection('request-history', event)" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-white/10 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Request History</span>
                    </a>
                    <a href="#tuition-fees" onclick="showSection('tuition-fees', event)" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-white/10 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Tuition Fees</span>
                    </a>
                    <a href="#fee-types" onclick="showSection('fee-types', event)" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-white/10 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                        <span>Fee Types</span>
                    </a>
                    <a href="#document-fees" onclick="showSection('document-fees', event)" class="nav-item flex items-center gap-3 px-4 py-2.5 rounded-lg hover:bg-white/10 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Document Fees</span>
                    </a>
                </div>
            </div>
        </nav>
        
        <!-- User Info & Logout - Pinned to bottom -->
        <div class="border-t border-white/10 p-4 flex-shrink-0">
            <div class="flex items-center gap-3 px-2 mb-3">
                <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center flex-shrink-0">
                    <span class="text-sm font-semibold"><?= substr($_SESSION['owner_name'] ?? 'OW', 0, 2) ?></span>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium truncate"><?= htmlspecialchars($_SESSION['owner_name'] ?? 'School Owner') ?></div>
                    <div class="text-xs text-blue-200">Owner</div>
                </div>
            </div>
            <a href="../StudentLogin/logout.php" class="flex items-center gap-2 w-full px-4 py-2 text-sm hover:bg-white/10 rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                Logout
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 lg:ml-64">
        <!-- Top Header -->
        <header class="bg-white shadow-sm border-b border-gray-200">
            <div class="flex items-center justify-between px-6 py-4">
                <div class="flex items-center gap-4">
                    <button onclick="toggleSidebar()" class="lg:hidden p-2 rounded-md hover:bg-gray-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    <h1 id="page-title" class="text-2xl font-bold text-gray-900">Dashboard</h1>
                </div>
                <div class="flex items-center gap-4">
                    <button onclick="location.reload()" class="p-2 rounded-md hover:bg-gray-100 text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <main class="p-6">
            <!-- Dashboard Section -->
            <div id="dashboard-section" class="section-content">
    <!-- Top Overview Cards -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Deleted Accounts Overview -->
        <div class="bg-white rounded-xl card-shadow p-6 border border-gray-200">
            <div class="flex items-center">
                <div class="p-3 rounded-lg bg-gray-100 text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M12 7a3 3 0 110-6 3 3 0 010 6z"/>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Deleted Accounts</p>
                    <p class="text-3xl font-bold text-gray-800"><?= $total_deleted_accounts ?></p>
                    <p class="text-xs text-gray-500">Students: <?= $deleted_students_count ?> • Employees: <?= $deleted_employees_count ?></p>
                </div>
            </div>
        </div>

        <!-- System Overview -->
        <div class="bg-white rounded-xl card-shadow p-6 border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">System Overview</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Unread Notifications</span>
                    <span class="font-bold text-gray-800"><?= $notif_stats['unread_notifications'] ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">System Status</span>
                    <span class="font-medium text-gray-800">🟢 Online</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Recent Activity & Quick Stats -->
        <div class="space-y-6">
            <!-- Quick Actions -->
            <div class="bg-white rounded-xl card-shadow p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">🚀 Quick Actions</h3>
                <div class="space-y-3">
                    <a href="SystemLogs.php" class="block w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg text-center font-medium transition">
                        📋 View System Logs
                    </a>
                    <button onclick="window.location.reload()" class="block w-full bg-gray-600 hover:bg-gray-700 text-white py-2 px-4 rounded-lg text-center font-medium transition">
                        🔄 Refresh Dashboard
                    </button>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white rounded-xl card-shadow p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">📈 Recent Activity</h3>
                
                <div class="space-y-3">
                    <?php if ($recent_result && $recent_result->num_rows > 0): ?>
                        <?php while ($activity = $recent_result->fetch_assoc()): ?>
                            <div class="border-l-4 <?= $activity['status'] === 'approved' ? 'border-green-500 bg-green-50' : 'border-red-500 bg-red-50' ?> pl-4 py-2 rounded-r">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <h4 class="font-medium text-gray-900 text-sm"><?= htmlspecialchars($activity['request_title']) ?></h4>
                                        <p class="text-xs text-gray-600">
                                            <?= $activity['status'] === 'approved' ? '✅ Approved' : '❌ Rejected' ?> • 
                                            <?= date('M j, g:i A', strtotime($activity['reviewed_at'])) ?>
                                        </p>
                                        <?php if ($activity['owner_comments']): ?>
                                            <p class="text-xs text-gray-700 mt-1 italic">"<?= htmlspecialchars($activity['owner_comments']) ?>"</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-6">
                            <p class="text-gray-500 text-sm">No recent activity</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <!-- Today's Logins Section -->
    <div class="mb-6">
        <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
            <div class="bg-blue-600 px-6 py-4">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-white/20 p-2 rounded-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Today's Logins</h3>
                            <p class="text-blue-100 text-sm">Recent system access activity</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="openLoginHistory()" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Login History
                        </button>
                        <button onclick="location.reload()" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Refresh
                        </button>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="flex flex-wrap gap-3 items-end">
                    <div class="flex items-center gap-2">
                        <label class="text-white text-sm font-medium whitespace-nowrap">User Type:</label>
                        <select id="filter-user-type" onchange="updateRoleOptions(); filterLogins();" class="px-3 py-2 bg-white text-gray-900 border border-white/30 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-white shadow-sm">
                            <option value="all">All</option>
                            <option value="student">Student</option>
                            <option value="employee">Employee</option>
                            <option value="parent">Parent</option>
                        </select>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <label class="text-white text-sm font-medium whitespace-nowrap">Role:</label>
                        <select id="filter-role" onchange="filterLogins()" class="px-3 py-2 bg-white text-gray-900 border border-white/30 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-white shadow-sm">
                            <option value="all">All</option>
                        </select>
                    </div>
                    
                    <div class="flex items-center gap-2 flex-1 min-w-[250px] max-w-md">
                        <label class="text-white text-sm font-medium whitespace-nowrap">Search:</label>
                        <div class="relative flex-1">
                            <input type="text" id="filter-search" oninput="filterLogins()" placeholder="Search by ID or Name..." class="w-full pl-9 pr-3 py-2 bg-white text-gray-900 placeholder-gray-400 border border-white/30 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-white shadow-sm">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                    </div>
                    
                    <button onclick="clearFilters()" class="px-4 py-2 bg-white/20 hover:bg-white/30 text-white border border-white/30 rounded-lg text-sm font-medium transition-all shadow-sm whitespace-nowrap">
                        Clear Filters
                    </button>
                </div>
            </div>
            
            <!-- Login Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm" id="logins-table">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">User Type</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">ID</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Name</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Role</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Login Time</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Logout Time</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Duration</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100" id="logins-tbody">
                        <?php if (!empty($today_logins)): ?>
                            <?php foreach ($today_logins as $login): 
                                $userTypeColors = [
                                    'employee' => 'bg-purple-100 text-purple-700',
                                    'student' => 'bg-blue-100 text-blue-700',
                                    'parent' => 'bg-cyan-100 text-cyan-700'
                                ];
                                $userTypeColor = $userTypeColors[$login['user_type']] ?? 'bg-gray-100 text-gray-700';
                                
                                $roleColors = [
                                    'hr' => 'bg-orange-100 text-orange-700',
                                    'teacher' => 'bg-green-100 text-green-700',
                                    'registrar' => 'bg-indigo-100 text-indigo-700',
                                    'cashier' => 'bg-yellow-100 text-yellow-700',
                                    'guidance' => 'bg-pink-100 text-pink-700',
                                    'attendance' => 'bg-teal-100 text-teal-700',
                                    'student' => 'bg-blue-100 text-blue-700',
                                    'parent' => 'bg-cyan-100 text-cyan-700'
                                ];
                                $roleColor = $roleColors[$login['role']] ?? 'bg-gray-100 text-gray-700';
                            ?>
                            <tr class="hover:bg-blue-50 transition-colors login-row" data-user-type="<?= strtolower(htmlspecialchars($login['user_type'])) ?>" data-role="<?= strtolower(htmlspecialchars($login['role'])) ?>" data-id="<?= htmlspecialchars($login['id_number']) ?>" data-name="<?= htmlspecialchars($login['full_name'] ?: $login['username']) ?>">
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium <?= $userTypeColor ?>">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <circle cx="10" cy="10" r="3"/>
                                        </svg>
                                        <?= ucfirst(htmlspecialchars($login['user_type'])) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-mono text-gray-600"><?= htmlspecialchars($login['id_number']) ?></td>
                                <td class="px-4 py-3 font-medium text-gray-900"><?= htmlspecialchars($login['full_name'] ?: $login['username']) ?></td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?= $roleColor ?>">
                                        <?= ucfirst(htmlspecialchars($login['role'])) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <?= date('M j, Y g:i A', strtotime($login['login_time'])) ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    <?php if (!empty($login['logout_time'])): ?>
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                            </svg>
                                            <?= date('M j, Y g:i A', strtotime($login['logout_time'])) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-green-600 font-medium flex items-center gap-1">
                                            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                                            Active
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    <?php if (!empty($login['session_duration'])): ?>
                                        <?php 
                                            $hours = floor($login['session_duration'] / 3600);
                                            $minutes = floor(($login['session_duration'] % 3600) / 60);
                                            if ($hours > 0) {
                                                echo $hours . 'h ' . $minutes . 'm';
                                            } else {
                                                echo $minutes . ' min';
                                            }
                                        ?>
                                    <?php else: ?>
                                        <span class="text-gray-400">---</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr class="login-row">
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                    <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                    </svg>
                                    No logins recorded today
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination for Today's Logins -->
            <?php if (!empty($today_logins) && count($today_logins) > 10): ?>
            <div class="flex items-center justify-between px-6 py-4 bg-gray-50 border-t border-gray-200">
                <div class="text-sm text-gray-600">
                    Showing <span id="logins-start" class="font-semibold text-gray-900">1</span> to <span id="logins-end" class="font-semibold text-gray-900">10</span> of <span id="logins-total" class="font-semibold text-gray-900"><?= count($today_logins) ?></span> logins
                </div>
                <div class="flex gap-2">
                    <button id="logins-prev" onclick="changeLoginsPage(-1)" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Previous
                    </button>
                    <button id="logins-next" onclick="changeLoginsPage(1)" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                        Next
                        <svg class="w-4 h-4 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Not Logged In Today Sections -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Not Logged In Today (Employees) -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
            <div class="bg-orange-500 px-6 py-4">
                <div class="flex items-center gap-3 mb-4">
                    <div class="bg-white/20 p-2 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">Not Logged In Today</h3>
                        <p class="text-orange-100 text-sm">Employees</p>
                    </div>
                </div>
                <!-- Employee Filters -->
                <div class="flex flex-wrap gap-2">
                    <div class="relative flex-1 min-w-[200px]">
                        <input type="text" id="employee-search" oninput="filterEmployees()" placeholder="Search by name or ID..." class="w-full pl-9 pr-3 py-2 bg-white text-gray-900 placeholder-gray-400 border border-white/30 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-white shadow-sm">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <select id="employee-role-filter" onchange="filterEmployees()" class="px-3 py-2 bg-white text-gray-900 border border-white/30 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-white shadow-sm">
                        <option value="all">All Roles</option>
                        <option value="teacher">Teacher</option>
                        <option value="registrar">Registrar</option>
                        <option value="hr">HR</option>
                        <option value="attendance">Attendance</option>
                        <option value="cashier">Cashier</option>
                        <option value="guidance">Guidance</option>
                    </select>
                    <button onclick="clearEmployeeFilters()" class="px-3 py-2 bg-white/20 hover:bg-white/30 text-white border border-white/30 rounded-lg text-sm font-medium transition-all shadow-sm whitespace-nowrap">
                        Clear
                    </button>
                </div>
            </div>
            <div class="p-6">
                <div class="min-h-[280px]">
                    <ul class="space-y-2" id="employees-list">
                        <!-- Items will be loaded here -->
                    </ul>
                    <div id="employees-loading" class="text-center py-8">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-orange-200 border-t-orange-600"></div>
                        <p class="text-gray-500 text-sm mt-2">Loading employees...</p>
                    </div>
                </div>
                <!-- Pagination for Employees -->
                <div id="employees-pagination" class="flex items-center justify-between mt-4 pt-4 border-t border-gray-200 hidden">
                    <div class="text-sm text-gray-600">
                        Showing <span id="employees-start" class="font-semibold text-gray-900">1</span> to <span id="employees-end" class="font-semibold text-gray-900">10</span> of <span id="employees-total" class="font-semibold text-gray-900">0</span> employees
                    </div>
                    <div class="flex gap-2">
                        <button id="employees-prev" onclick="changeEmployeesPage(-1)" class="px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                            Prev
                        </button>
                        <button id="employees-next" onclick="changeEmployeesPage(1)" class="px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            Next
                            <svg class="w-4 h-4 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Not Logged In Today (Students) -->
        <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden">
            <div class="bg-blue-500 px-6 py-4">
                <div class="flex items-center gap-3 mb-4">
                    <div class="bg-white/20 p-2 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M12 14l9-5-9-5-9 5 9 5z"></path>
                            <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">Not Logged In Today</h3>
                        <p class="text-blue-100 text-sm">Students & Parents</p>
                    </div>
                </div>
                <!-- Student Filters -->
                <div class="flex flex-wrap gap-2">
                    <div class="relative flex-1 min-w-[200px]">
                        <input type="text" id="student-search" oninput="filterStudents()" placeholder="Search by name or ID..." class="w-full pl-9 pr-3 py-2 bg-white text-gray-900 placeholder-gray-400 border border-white/30 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-white shadow-sm">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <select id="student-type-filter" onchange="filterStudents()" class="px-3 py-2 bg-white text-gray-900 border border-white/30 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-white shadow-sm">
                        <option value="all">All Types</option>
                        <option value="student">Students</option>
                        <option value="parent">Parents</option>
                    </select>
                    <button onclick="clearStudentFilters()" class="px-3 py-2 bg-white/20 hover:bg-white/30 text-white border border-white/30 rounded-lg text-sm font-medium transition-all shadow-sm whitespace-nowrap">
                        Clear
                    </button>
                </div>
            </div>
            <div class="p-6">
                <div class="min-h-[280px]">
                    <ul class="space-y-2" id="students-list">
                        <!-- Items will be loaded here -->
                    </ul>
                    <div id="students-loading" class="text-center py-8">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-200 border-t-blue-600"></div>
                        <p class="text-gray-500 text-sm mt-2">Loading students & parents...</p>
                    </div>
                </div>
                <!-- Pagination for Students & Parents -->
                <div id="students-pagination" class="flex items-center justify-between mt-4 pt-4 border-t border-gray-200 hidden">
                    <div class="text-sm text-gray-600">
                        Showing <span id="students-start" class="font-semibold text-gray-900">1</span> to <span id="students-end" class="font-semibold text-gray-900">10</span> of <span id="students-total" class="font-semibold text-gray-900">0</span> users
                    </div>
                    <div class="flex gap-2">
                        <button id="students-prev" onclick="changeStudentsPage(-1)" class="px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                            Prev
                        </button>
                        <button id="students-next" onclick="changeStudentsPage(1)" class="px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            Next
                            <svg class="w-4 h-4 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

            <!-- System Notifications Section -->
            <div id="notifications-section" class="section-content hidden">
                <div class="bg-white rounded-xl card-shadow p-6 mb-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800">🔔 System Notifications</h2>
                        <div class="flex gap-3">
                            <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm font-medium">
                                <?= $notif_stats['unread_notifications'] ?> Unread
                            </span>
                            <form method="POST" class="inline">
                                <button type="submit" name="mark_all_read" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                                    Mark All Read
                                </button>
                            </form>
                        </div>
                    </div>

                    <div id="notifications-container" class="space-y-4 max-h-[600px] overflow-y-auto">
                        <?php if ($notifications_result && $notifications_result->num_rows > 0): ?>
                            <?php while ($notification = $notifications_result->fetch_assoc()): ?>
                                <div class="border rounded-lg p-4 <?= $notification['is_read'] ? 'bg-gray-50' : 'bg-white border-l-4 border-blue-500' ?> cursor-pointer hover:shadow-md transition-all duration-200" onclick="toggleNotificationDetails(<?= $notification['id'] ?>)">
                                    <div class="flex justify-between items-start mb-3">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2 mb-2">
                                                <h3 class="font-semibold text-gray-900"><?= htmlspecialchars($notification['title']) ?></h3>
                                                <span class="px-2 py-1 rounded-full text-xs font-medium
                                                    <?= $notification['type'] === 'critical' ? 'bg-red-100 text-red-800' : 
                                                       ($notification['type'] === 'error' ? 'bg-red-100 text-red-800' : 
                                                       ($notification['type'] === 'warning' ? 'bg-yellow-100 text-yellow-800' : 
                                                       ($notification['type'] === 'success' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'))) ?>">
                                                    <?= strtoupper($notification['type']) ?>
                                                </span>
                                                <?php if (!$notification['is_read']): ?>
                                                    <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="text-sm text-gray-600 mb-2">
                                                <strong>From:</strong> <?= htmlspecialchars($notification['performed_by']) ?> 
                                                (<?= ucfirst($notification['user_role']) ?>) • 
                                                <strong>Module:</strong> <?= htmlspecialchars($notification['module']) ?>
                                            </p>
                                            <p class="text-sm text-gray-700 mb-3"><?= htmlspecialchars($notification['message']) ?></p>
                                            <p class="text-xs text-gray-500">
                                                <?= date('M j, Y g:i A', strtotime($notification['created_at'])) ?>
                                            </p>
                                        </div>
                                        <?php if (!$notification['is_read']): ?>
                                            <form method="POST" class="ml-4" onclick="event.stopPropagation()">
                                                <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                                                <button type="submit" name="mark_read" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">
                                                    Mark Read
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-12">
                                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4 19h6v-2H4v2zM4 15h8v-2H4v2zM4 11h8V9H4v2z"></path>
                                </svg>
                                <p class="text-gray-500 text-lg font-medium">No notifications</p>
                                <p class="text-gray-400 text-sm">All system notifications will appear here</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Approval Requests Section -->
            <div id="approval-requests-section" class="section-content hidden">
                <div class="bg-white rounded-xl card-shadow p-6 mb-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800">✅ Approval Requests</h2>
                        <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm font-medium">
                            <?= $stats['pending_requests'] ?> Pending
                        </span>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <!-- Pending Card -->
                        <div class="bg-yellow-50 border border-gray-200 rounded-lg p-5">
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-10 h-10 bg-yellow-500 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <?php if ($stats['pending_requests'] > 0): ?>
                                    <span class="bg-yellow-500 text-white text-xs font-bold px-2 py-0.5 rounded">NEW</span>
                                <?php endif; ?>
                            </div>
                            <div class="text-gray-700 text-sm font-medium mb-1">Pending</div>
                            <div class="text-3xl font-bold text-gray-900"><?= $stats['pending_requests'] ?></div>
                        </div>

                        <!-- Approved Card -->
                        <div class="bg-green-50 border border-gray-200 rounded-lg p-5">
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="text-gray-700 text-sm font-medium mb-1">Approved</div>
                            <div class="text-3xl font-bold text-gray-900"><?= $stats['approved_requests'] ?></div>
                        </div>

                        <!-- Rejected Card -->
                        <div class="bg-red-50 border border-gray-200 rounded-lg p-5">
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-10 h-10 bg-red-500 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="text-gray-700 text-sm font-medium mb-1">Rejected</div>
                            <div class="text-3xl font-bold text-gray-900"><?= $stats['rejected_requests'] ?></div>
                        </div>

                        <!-- Total Card -->
                        <div class="bg-blue-50 border border-gray-200 rounded-lg p-5">
                            <div class="flex items-center justify-between mb-3">
                                <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="text-gray-700 text-sm font-medium mb-1">Total</div>
                            <div class="text-3xl font-bold text-gray-900"><?= $stats['total_requests'] ?></div>
                        </div>
                    </div>

                    <!-- Pending Requests List -->
                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        <?php 
                        // Reset the result pointer for pending requests
                        $pending_result = $conn->query($pending_query);
                        if ($pending_result && $pending_result->num_rows > 0): 
                        ?>
                            <?php while ($request = $pending_result->fetch_assoc()): 
                                $priorityColors = [
                                    'critical' => ['bg' => 'bg-red-50', 'border' => 'border-red-200', 'badge' => 'bg-red-100 text-red-800', 'icon' => 'text-red-600'],
                                    'high' => ['bg' => 'bg-orange-50', 'border' => 'border-orange-200', 'badge' => 'bg-orange-100 text-orange-800', 'icon' => 'text-orange-600'],
                                    'medium' => ['bg' => 'bg-yellow-50', 'border' => 'border-yellow-200', 'badge' => 'bg-yellow-100 text-yellow-800', 'icon' => 'text-yellow-600'],
                                    'low' => ['bg' => 'bg-green-50', 'border' => 'border-green-200', 'badge' => 'bg-green-100 text-green-800', 'icon' => 'text-green-600']
                                ];
                                $colors = $priorityColors[$request['priority']] ?? $priorityColors['medium'];
                            ?>
                                <div class="border-2 <?= $colors['border'] ?> rounded-xl overflow-hidden hover:shadow-lg transition-all duration-200">
                                    <!-- Summary Row (Always Visible) -->
                                    <div class="<?= $colors['bg'] ?> p-5 cursor-pointer hover:opacity-90 transition-opacity" onclick="openRequestModal(<?= $request['id'] ?>)">
                                        <div class="flex items-start justify-between gap-4">
                                            <div class="flex items-start gap-4 flex-1">
                                                <!-- Icon -->
                                                <div class="w-12 h-12 rounded-full bg-white shadow-sm flex items-center justify-center flex-shrink-0">
                                                    <svg class="w-6 h-6 <?= $colors['icon'] ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                                    </svg>
                                                </div>
                                                
                                                <!-- Content -->
                                                <div class="flex-1 min-w-0">
                                                    <div class="flex items-center gap-2 mb-2">
                                                        <span class="px-3 py-1 rounded-full text-xs font-bold <?= $colors['badge'] ?> shadow-sm">
                                                            <?= strtoupper($request['priority']) ?>
                                                        </span>
                                                        <span class="px-2 py-1 rounded bg-white text-xs font-medium text-gray-600 shadow-sm">
                                                            <?= ucwords(str_replace('_', ' ', $request['request_type'])) ?>
                                                        </span>
                                                    </div>
                                                    <h3 class="font-bold text-gray-900 text-base mb-1"><?= htmlspecialchars($request['request_title']) ?></h3>
                                                    <div class="flex items-center gap-2 text-xs text-gray-600">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                        </svg>
                                                        <span class="font-medium"><?= htmlspecialchars($request['requester_name']) ?></span>
                                                        <span class="text-gray-400">•</span>
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                        </svg>
                                                        <span><?= date('M j, Y g:i A', strtotime($request['requested_at'])) ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- View Details Button -->
                                            <div class="flex-shrink-0">
                                                <button onclick="event.stopPropagation(); openRequestModal(<?= $request['id'] ?>);" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-sm transition-colors text-sm">
                                                    View Details
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Detailed View (Hidden by Default) -->
                                    <div id="details-<?= $request['id'] ?>" class="hidden border-t-2 <?= $colors['border'] ?> bg-white p-6">
                                        <div class="space-y-4">
                                            <?php 
                                            // Extract description and reason separately
                                            $description = $request['request_description'];
                                            $reason = '';
                                            
                                            if (strpos($description, 'Reason:') !== false) {
                                                $parts = explode('Reason:', $description);
                                                $description = trim($parts[0]); // Description without reason
                                                $reason = trim($parts[1]); // Just the reason
                                            }
                                            ?>
                                            
                                            <!-- Description (without reason) -->
                                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                                <div class="flex items-start gap-2 mb-2">
                                                    <svg class="w-5 h-5 text-gray-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                    </svg>
                                                    <div class="flex-1">
                                                        <p class="text-sm font-bold text-gray-700 mb-1">Description</p>
                                                        <p class="text-sm text-gray-600 leading-relaxed"><?= htmlspecialchars($description) ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Reason (if exists) -->
                                            <?php if (!empty($reason)): ?>
                                            <div class="bg-yellow-50 rounded-lg p-4 border-2 border-yellow-300">
                                                <div class="flex items-start gap-2">
                                                    <svg class="w-5 h-5 text-yellow-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                    <div class="flex-1">
                                                        <p class="text-sm font-bold text-yellow-800 mb-2">Reason for Request</p>
                                                        <p class="text-sm text-yellow-900 leading-relaxed bg-white px-3 py-2 rounded border border-yellow-200"><?= htmlspecialchars($reason) ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <!-- Requester Info -->
                                            <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                                                <div class="flex items-start gap-2">
                                                    <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                    </svg>
                                                    <div class="flex-1">
                                                        <p class="text-sm font-bold text-gray-700 mb-1">Requested by</p>
                                                        <p class="text-sm text-gray-600">
                                                            <span class="font-semibold"><?= htmlspecialchars($request['requester_name']) ?></span>
                                                            <span class="text-gray-400 mx-1">•</span>
                                                            <span class="text-blue-600"><?= ucfirst($request['requester_role']) ?></span>
                                                            <span class="text-gray-400 mx-1">•</span>
                                                            <span><?= htmlspecialchars($request['requester_module']) ?></span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Student/Employee Details -->
                                            <?php 
                                            $isArchiveRequest = (strpos($request['request_type'], 'archive') !== false);
                                            if ($request['target_data'] && !$isArchiveRequest): 
                                                $target_data = json_decode($request['target_data'], true);
                                                // Only show if we have actual person data (id_number, first_name, or last_name)
                                                $hasPersonData = isset($target_data['id_number']) || isset($target_data['first_name']) || isset($target_data['last_name']);
                                                if ($hasPersonData):
                                                    // Determine if it's a student or employee based on request type
                                                    $isStudent = (strpos($request['request_type'], 'student') !== false);
                                                    $detailsTitle = $isStudent ? 'Student Details' : 'Employee Details';
                                                    $idLabel = $isStudent ? 'Student ID' : 'Employee ID';
                                            ?>
                                                <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-lg p-4 border border-gray-200">
                                                    <div class="flex items-center gap-2 mb-3">
                                                        <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <?php if ($isStudent): ?>
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/>
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0v6"/>
                                                            <?php else: ?>
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                                            <?php endif; ?>
                                                        </svg>
                                                        <p class="text-sm font-bold text-gray-800"><?= $detailsTitle ?></p>
                                                    </div>
                                                    <div class="bg-white rounded-lg p-3 space-y-2.5 shadow-sm">
                                                        <?php if (isset($target_data['id_number'])): ?>
                                                            <div class="text-sm p-3 bg-gray-50 rounded-lg border border-gray-200">
                                                                <span class="text-gray-600 font-medium block mb-1.5"><?= $idLabel ?></span>
                                                                <span class="font-bold text-gray-900 bg-white px-3 py-1.5 rounded border border-gray-300 inline-block"><?= htmlspecialchars($target_data['id_number']) ?></span>
                                                            </div>
                                                        <?php endif; ?>
                                                        <?php if (isset($target_data['first_name']) || isset($target_data['last_name'])): ?>
                                                            <div class="text-sm p-3 bg-gray-50 rounded-lg border border-gray-200">
                                                                <span class="text-gray-600 font-medium block mb-1.5">Full Name</span>
                                                                <span class="font-bold text-gray-900 text-base">
                                                                    <?= htmlspecialchars(trim(($target_data['first_name'] ?? '') . ' ' . ($target_data['middle_name'] ?? '') . ' ' . ($target_data['last_name'] ?? ''))) ?>
                                                                </span>
                                                            </div>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($isStudent): ?>
                                                            <?php if (isset($target_data['grade_level'])): ?>
                                                                <div class="text-sm p-3 bg-gray-50 rounded-lg border border-gray-200">
                                                                    <span class="text-gray-600 font-medium block mb-1.5">Grade Level</span>
                                                                    <span class="font-semibold text-gray-900"><?= htmlspecialchars($target_data['grade_level']) ?></span>
                                                                </div>
                                                            <?php endif; ?>
                                                            <?php if (isset($target_data['academic_track'])): ?>
                                                                <div class="text-sm p-3 bg-gray-50 rounded-lg border border-gray-200">
                                                                    <span class="text-gray-600 font-medium block mb-1.5">Academic Track</span>
                                                                    <span class="font-semibold text-gray-900"><?= htmlspecialchars($target_data['academic_track']) ?></span>
                                                                </div>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <?php if (isset($target_data['position'])): ?>
                                                                <div class="text-sm p-3 bg-gray-50 rounded-lg border border-gray-200">
                                                                    <span class="text-gray-600 font-medium block mb-1.5">Position</span>
                                                                    <span class="font-semibold text-gray-900"><?= htmlspecialchars($target_data['position']) ?></span>
                                                                </div>
                                                            <?php endif; ?>
                                                            <?php if (isset($target_data['department'])): ?>
                                                                <div class="text-sm p-3 bg-gray-50 rounded-lg border border-gray-200">
                                                                    <span class="text-gray-600 font-medium block mb-1.5">Department</span>
                                                                    <span class="font-semibold text-gray-900"><?= htmlspecialchars($target_data['department']) ?></span>
                                                                </div>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                        <?php 
                                                        // Only show deletion reason for delete/archive requests, not restore requests
                                                        $isRestoreRequest = strpos($request['request_type'], 'restore') !== false;
                                                        if (isset($target_data['deletion_reason']) && !$isRestoreRequest): 
                                                        ?>
                                                            <div class="p-3 bg-red-50 rounded-lg border-2 border-red-200">
                                                                <div class="flex items-center gap-2 mb-2">
                                                                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                                                    </svg>
                                                                    <span class="text-red-800 font-bold">Deletion Reason</span>
                                                                </div>
                                                                <p class="font-medium text-gray-900 bg-white p-3 rounded-lg border border-red-300 leading-relaxed"><?= htmlspecialchars($target_data['deletion_reason']) ?></p>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php 
                                                endif; // End of hasPersonData check
                                            endif; // End of target_data && !isArchiveRequest check 
                                            ?>
                                        </div>
                                        
                                        <!-- Action Buttons -->
                                        <div class="flex gap-3 pt-5 mt-5 border-t-2 border-gray-200">
                                            <button onclick="event.stopPropagation(); confirmAndSubmit(<?= $request['id'] ?>, 'approve')" 
                                                    class="flex-1 px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-xl text-sm font-bold hover:from-green-700 hover:to-green-800 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                Approve Request
                                            </button>
                                            <button onclick="event.stopPropagation(); confirmAndSubmit(<?= $request['id'] ?>, 'reject')" 
                                                    class="flex-1 px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-xl text-sm font-bold hover:from-red-700 hover:to-red-800 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                Reject Request
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-12">
                                <svg class="w-16 h-16 mx-auto text-green-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="text-gray-500 text-lg font-medium">All caught up!</p>
                                <p class="text-gray-400 text-sm">No pending approval requests</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Tuition Fees Section -->
            <div id="tuition-fees-section" class="section-content hidden">
                <div class="bg-white rounded-xl card-shadow p-6 mb-6">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                        <h2 class="text-xl font-bold text-gray-800">💰 Tuition Fee Management</h2>
                        <button onclick="showAddSchoolYearModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition">
                            + Add School Year
                        </button>
                    </div>

                    <div id="tuition-fees-list" class="space-y-4">
                        <div class="text-center py-8">
                            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                            <p class="text-gray-600 mt-2">Loading tuition fees...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fee Types Section -->
            <div id="fee-types-section" class="section-content hidden">
                <div class="bg-white rounded-xl card-shadow p-6 mb-6">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">📋 Fee Types Management</h2>
                            <p class="text-sm text-gray-600 mt-1">Manage additional fee types and their default amounts</p>
                        </div>
                        <button onclick="showAddFeeTypeModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition">
                            + Add Fee Type
                        </button>
                    </div>

                    <div id="fee-types-list" class="space-y-4">
                        <div class="text-center py-8">
                            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                            <p class="text-gray-600 mt-2">Loading fee types...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Document Fees Section -->
            <div id="document-fees-section" class="section-content hidden">
                <div class="bg-white rounded-xl card-shadow p-6 mb-6">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                        <div>
                            <h2 class="text-xl font-bold text-gray-800">📄 Document Request Fees</h2>
                            <p class="text-sm text-gray-600 mt-1">Set fees for document requests. If fee is ₱0, no balance will be created in Cashier.</p>
                        </div>
                    </div>

                    <div id="document-fees-list" class="space-y-4">
                        <div class="text-center py-8">
                            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                            <p class="text-gray-600 mt-2">Loading document fees...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Request History Section -->
            <div id="request-history-section" class="section-content hidden">
                <div class="bg-white rounded-xl card-shadow p-6 mb-6">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
                        <h2 class="text-xl font-bold text-gray-800">📜 Request History</h2>
                        <div class="flex flex-wrap items-center gap-3">
                            <!-- Filter Dropdown -->
                            <div class="flex items-center gap-2">
                                <label for="history-filter" class="text-sm font-medium text-gray-700">Filter:</label>
                                <select id="history-filter" onchange="filterHistory(this.value)" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                                    <option value="all">All (<?= $stats['approved_requests'] + $stats['rejected_requests'] ?>)</option>
                                    <option value="approved">Approved (<?= $stats['approved_requests'] ?>)</option>
                                    <option value="rejected">Rejected (<?= $stats['rejected_requests'] ?>)</option>
                                </select>
                            </div>
                            
                            <!-- Summary Badges -->
                            <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm font-medium">
                                <?= $stats['approved_requests'] ?> Approved
                            </span>
                            <span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-sm font-medium">
                                <?= $stats['rejected_requests'] ?> Rejected
                            </span>
                        </div>
                    </div>

                    <!-- Results Info -->
                    <div id="history-results-info" class="mb-4 text-sm text-gray-600">
                        Showing <?= $history_result->num_rows > 0 ? $history_offset + 1 : 0 ?> - <?= min($history_offset + $history_result->num_rows, $history_total) ?> of <?= $history_total ?> requests
                    </div>

                    <!-- Loading Indicator -->
                    <div id="history-loading" class="hidden text-center py-8">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                        <p class="text-gray-600 mt-2">Loading...</p>
                    </div>

                    <div id="history-container" class="space-y-4">
                        <!-- Initial content will be replaced by AJAX -->
                    </div>

                    <!-- Pagination Controls (Dynamic) -->
                    <div id="history-pagination" class="mt-6"></div>
                </div>
            </div>

<!-- Success/Error Notifications -->
<?php if (!empty($success_msg)): ?>
<div id="successNotif" class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full opacity-0 transition-all duration-300">
    ✅ <?= htmlspecialchars($success_msg) ?>
</div>
<?php endif; ?>

<?php if (!empty($error_msg)): ?>
<div id="errorNotif" class="fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 transform translate-x-full opacity-0 transition-all duration-300">
    ❌ <?= htmlspecialchars($error_msg) ?>
</div>
<?php endif; ?>

<script>
// Sidebar toggle for mobile
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('-translate-x-full');
}

// Section navigation
function showSection(sectionId, event) {
    if (event) {
        event.preventDefault();
    }
    
    // Store current section in sessionStorage
    sessionStorage.setItem('ownerCurrentSection', sectionId);
    
    // Hide all sections
    document.querySelectorAll('.section-content').forEach(section => {
        section.classList.add('hidden');
    });
    
    // Remove active class from all nav items
    document.querySelectorAll('.nav-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // Show selected section
    const targetSection = document.getElementById(sectionId + '-section');
    if (targetSection) {
        targetSection.classList.remove('hidden');
    }
    
    // Add active class to clicked nav item or find by href
    if (event && event.target) {
        const navItem = event.target.closest('.nav-item');
        if (navItem) {
            navItem.classList.add('active');
        }
    } else {
        // If no click event, find the nav item by href
        const navItem = document.querySelector(`a[href="#${sectionId}"]`);
        if (navItem) {
            navItem.classList.add('active');
        }
    }
    
    // Update page title
    const titles = {
        'dashboard': 'Dashboard',
        'notifications': 'System Notifications',
        'approval-requests': 'Approval Requests',
        'request-history': 'Request History',
        'tuition-fees': 'Tuition Fee Management',
        'fee-types': 'Fee Types Management',
        'document-fees': 'Document Request Fees'
    };
    document.getElementById('page-title').textContent = titles[sectionId] || 'Dashboard';
    
    // Load tuition fees when section is shown
    if (sectionId === 'tuition-fees') {
        loadTuitionFees();
    }
    
    // Load fee types when section is shown
    if (sectionId === 'fee-types') {
        loadFeeTypes();
    }
    
    // Load document fees when section is shown
    if (sectionId === 'document-fees') {
        loadDocumentFees();
    }
}

// Toggle notification details (expand/collapse)
function toggleNotificationDetails(notificationId) {
    const notification = event.currentTarget;
    const isExpanded = notification.classList.contains('expanded');
    
    if (isExpanded) {
        notification.classList.remove('expanded');
    } else {
        // Collapse all other notifications
        document.querySelectorAll('.border.rounded-lg.p-4.expanded').forEach(n => {
            n.classList.remove('expanded');
        });
        notification.classList.add('expanded');
    }
}

// Open request details in modal
function openRequestModal(requestId) {
    const detailsDiv = document.getElementById('details-' + requestId);
    
    // Create modal backdrop
    const modal = document.createElement('div');
    modal.id = 'requestModal-' + requestId;
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4';
    modal.onclick = function(e) {
        if (e.target === modal) closeRequestModal(requestId);
    };
    
    // Create modal content wrapper
    const modalContent = document.createElement('div');
    modalContent.className = 'bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col';
    modalContent.onclick = function(e) {
        e.stopPropagation();
    };
    
    // Create modal header with close button
    const modalHeader = document.createElement('div');
    modalHeader.className = 'flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50 flex-shrink-0';
    modalHeader.innerHTML = `
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-900">Request Details</h3>
                <p class="text-xs text-gray-500">Review and take action</p>
            </div>
        </div>
        <button onclick="closeRequestModal(${requestId})" class="group flex items-center justify-center w-10 h-10 rounded-lg hover:bg-red-50 transition-colors">
            <svg class="w-6 h-6 text-gray-400 group-hover:text-red-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    `;
    
    // Create scrollable content area
    const modalBody = document.createElement('div');
    modalBody.className = 'overflow-y-auto p-6';
    
    // Clone the details content
    const content = detailsDiv.cloneNode(true);
    content.classList.remove('hidden', 'border-t-2', 'p-6');
    
    modalBody.appendChild(content);
    modalContent.appendChild(modalHeader);
    modalContent.appendChild(modalBody);
    modal.appendChild(modalContent);
    document.body.appendChild(modal);
}

function closeRequestModal(requestId) {
    const modal = document.getElementById('requestModal-' + requestId);
    if (modal) {
        modal.remove();
    }
}

// Show custom confirmation modal
function confirmAndSubmit(requestId, action) {
    // Store data for later use
    window.pendingApproval = { requestId, action };
    
    // Create modal
    const modal = document.createElement('div');
    modal.id = 'confirmModal';
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    
    const isApprove = action === 'approve';
    const iconColor = isApprove ? 'text-green-600' : 'text-red-600';
    const iconBg = isApprove ? 'bg-green-50' : 'bg-red-50';
    const btnColor = isApprove ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700';
    const title = isApprove ? 'Approve Request' : 'Reject Request';
    const message = isApprove 
        ? 'Are you sure you want to approve this request? This action will execute the requested changes and cannot be undone.'
        : 'Are you sure you want to reject this request? The requester will be notified of the rejection.';
    const actionText = isApprove ? 'Approve' : 'Reject';
    
    modal.innerHTML = `
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 overflow-hidden animate-scale-in">
            <div class="p-8 text-center">
                <div class="mx-auto w-20 h-20 ${iconBg} rounded-full flex items-center justify-center mb-6">
                    <svg class="w-10 h-10 ${iconColor}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-3">${title}</h3>
                <p class="text-gray-600 leading-relaxed mb-6">${message}</p>
            </div>
            <div class="px-8 pb-8 flex gap-3">
                <button onclick="closeConfirmModal()" class="flex-1 px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-xl font-semibold transition-all">
                    Cancel
                </button>
                <button onclick="submitApproval()" class="flex-1 px-6 py-3 ${btnColor} text-white rounded-xl font-semibold transition-all">
                    ${actionText}
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Add animation style if not present
    if (!document.getElementById('modal-animations')) {
        const style = document.createElement('style');
        style.id = 'modal-animations';
        style.textContent = `
            @keyframes scale-in {
                from { opacity: 0; transform: scale(0.9); }
                to { opacity: 1; transform: scale(1); }
            }
            .animate-scale-in { animation: scale-in 0.2s ease-out; }
        `;
        document.head.appendChild(style);
    }
}

function closeConfirmModal() {
    const modal = document.getElementById('confirmModal');
    if (modal) {
        modal.remove();
    }
    window.pendingApproval = null;
}

function submitApproval() {
    if (!window.pendingApproval) return;
    
    const { requestId, action } = window.pendingApproval;
    
    // Create and submit form
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '';
    
    const requestIdInput = document.createElement('input');
    requestIdInput.type = 'hidden';
    requestIdInput.name = 'request_id';
    requestIdInput.value = requestId;
    
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = action;
    
    const commentsInput = document.createElement('input');
    commentsInput.type = 'hidden';
    commentsInput.name = 'comments';
    commentsInput.value = '';
    
    form.appendChild(requestIdInput);
    form.appendChild(actionInput);
    form.appendChild(commentsInput);
    
    document.body.appendChild(form);
    form.submit();
}

// Request History AJAX Functions
let currentHistoryPage = 1;
let currentHistoryFilter = 'all';
let historyTotalPages = <?= $history_total_pages ?>;
let requestsData = {};

// Load request history via AJAX
async function loadRequestHistory(page = 1, filter = 'all') {
    const container = document.getElementById('history-container');
    const loading = document.getElementById('history-loading');
    const resultsInfo = document.getElementById('history-results-info');
    const pagination = document.getElementById('history-pagination');
    
    // Show loading
    container.classList.add('hidden');
    loading.classList.remove('hidden');
    
    try {
        const formData = new FormData();
        formData.append('page', page);
        formData.append('filter', filter);
        
        const response = await fetch('get_request_history.php', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) throw new Error('Failed to load history');
        
        const data = await response.json();
        
        if (data.success) {
            // Store requests data for modal access
            requestsData = {};
            data.requests.forEach(request => {
                requestsData[request.id] = request;
            });
            
            // Update state
            currentHistoryPage = data.pagination.current_page;
            currentHistoryFilter = filter;
            historyTotalPages = data.pagination.total_pages;
            
            // Update results info
            const start = data.pagination.offset + 1;
            const end = Math.min(data.pagination.offset + data.requests.length, data.pagination.total_records);
            resultsInfo.textContent = `Showing ${data.requests.length > 0 ? start : 0} - ${end} of ${data.pagination.total_records} requests`;
            
            // Render requests
            container.innerHTML = renderRequests(data.requests);
            
            // Render pagination
            pagination.innerHTML = renderPagination(data.pagination);
            
            // Show container
            container.classList.remove('hidden');
            loading.classList.add('hidden');
        }
    } catch (error) {
        console.error('Error loading history:', error);
        container.innerHTML = '<div class="text-center py-12 text-red-600">Error loading request history. Please try again.</div>';
        container.classList.remove('hidden');
        loading.classList.add('hidden');
    }
}

// Render requests HTML
function renderRequests(requests) {
    if (requests.length === 0) {
        return `
            <div class="text-center py-12">
                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-gray-500 text-lg font-medium">No request history</p>
                <p class="text-gray-400 text-sm">Approved and rejected requests will appear here</p>
            </div>
        `;
    }
    
    return requests.map(request => {
        const isApproved = request.status === 'approved';
        const statusColor = isApproved ? 'green' : 'red';
        const statusIcon = isApproved ? '✅' : '❌';
        const statusText = isApproved ? 'Approved' : 'Rejected';
        
        // Calculate time difference
        const requestedTime = new Date(request.requested_at).getTime();
        const reviewedTime = new Date(request.reviewed_at).getTime();
        const timeDiff = (reviewedTime - requestedTime) / 1000; // seconds
        
        const hours = Math.floor(timeDiff / 3600);
        const minutes = Math.floor((timeDiff % 3600) / 60);
        const timeToReview = hours > 0 ? `${hours}h ${minutes}m` : `${minutes}m`;
        
        const requestedAt = new Date(request.requested_at).toLocaleString('en-US', { 
            month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true 
        });
        const reviewedAt = new Date(request.reviewed_at).toLocaleString('en-US', { 
            month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true 
        });
        
        return `
            <div class="border-2 border-${statusColor}-200 rounded-xl overflow-hidden hover:shadow-lg transition-all duration-200">
                <div class="bg-${statusColor}-50 p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-4 flex-1">
                            <div class="w-12 h-12 rounded-full bg-white shadow-sm flex items-center justify-center flex-shrink-0">
                                <span class="text-2xl">${statusIcon}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-${statusColor}-100 text-${statusColor}-800 shadow-sm">
                                        ${statusText.toUpperCase()}
                                    </span>
                                    <span class="px-2 py-1 rounded bg-white text-xs font-medium text-gray-600 shadow-sm">
                                        ${request.request_type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}
                                    </span>
                                </div>
                                <h3 class="font-bold text-gray-900 text-base mb-2">${escapeHtml(request.request_title)}</h3>
                                <div class="space-y-2 text-sm">
                                    <div class="flex items-center gap-2 text-gray-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        <span class="font-medium">Requested by:</span>
                                        <span>${escapeHtml(request.requester_name)}</span>
                                        <span class="text-gray-400">•</span>
                                        <span class="text-blue-600">${request.requester_role.charAt(0).toUpperCase() + request.requester_role.slice(1)}</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-gray-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span class="font-medium">Requested:</span>
                                        <span>${requestedAt}</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-${statusColor}-700 font-medium">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span class="font-medium">${statusText}:</span>
                                        <span>${reviewedAt}</span>
                                        <span class="text-gray-400">•</span>
                                        <span class="bg-white px-2 py-0.5 rounded text-xs">${timeToReview} to review</span>
                                    </div>
                                    ${request.reviewed_by ? `
                                    <div class="flex items-center gap-2 text-gray-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span class="font-medium">Reviewed by:</span>
                                        <span>${escapeHtml(request.reviewed_by)}</span>
                                    </div>
                                    ` : ''}
                                </div>
                                ${request.owner_comments ? `
                                <div class="mt-3 bg-white rounded-lg p-3 border border-${statusColor}-200">
                                    <div class="flex items-start gap-2">
                                        <svg class="w-4 h-4 text-gray-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                        </svg>
                                        <div class="flex-1">
                                            <p class="text-xs font-semibold text-gray-700 mb-1">Owner Comments:</p>
                                            <p class="text-sm text-gray-600 italic">"${escapeHtml(request.owner_comments)}"</p>
                                        </div>
                                    </div>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            <button onclick="alert('View Details - Request ID: ${request.id}')" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-sm transition-colors text-sm">
                                View Details
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

// Render pagination HTML
function renderPagination(pagination) {
    if (pagination.total_pages <= 1) return '';
    
    const currentPage = pagination.current_page;
    const totalPages = pagination.total_pages;
    
    let html = `
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 border-t border-gray-200">
            <div class="text-sm text-gray-600">
                Page ${currentPage} of ${totalPages}
            </div>
            <div class="flex items-center gap-2">
    `;
    
    // First page button
    if (currentPage > 1) {
        html += `
            <button onclick="goToHistoryPage(1)" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition text-sm font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                </svg>
            </button>
        `;
    }
    
    // Previous button
    if (currentPage > 1) {
        html += `
            <button onclick="goToHistoryPage(${currentPage - 1})" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition text-sm font-medium">
                Previous
            </button>
        `;
    }
    
    // Page numbers
    html += '<div class="flex gap-1">';
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        const activeClass = i === currentPage ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50';
        html += `
            <button onclick="goToHistoryPage(${i})" class="px-4 py-2 border border-gray-300 rounded-lg transition text-sm font-medium ${activeClass}">
                ${i}
            </button>
        `;
    }
    html += '</div>';
    
    // Next button
    if (currentPage < totalPages) {
        html += `
            <button onclick="goToHistoryPage(${currentPage + 1})" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition text-sm font-medium">
                Next
            </button>
        `;
    }
    
    // Last page button
    if (currentPage < totalPages) {
        html += `
            <button onclick="goToHistoryPage(${totalPages})" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition text-sm font-medium">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/>
                </svg>
            </button>
        `;
    }
    
    html += `
            </div>
            <div class="flex items-center gap-2">
                <label for="jump-to-page" class="text-sm text-gray-600">Go to:</label>
                <input type="number" id="jump-to-page" min="1" max="${totalPages}" value="${currentPage}" 
                       class="w-20 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                       onkeypress="if(event.key === 'Enter') goToHistoryPage(parseInt(this.value))">
                <button onclick="goToHistoryPage(parseInt(document.getElementById('jump-to-page').value))" 
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition">
                    Go
                </button>
            </div>
        </div>
    `;
    
    return html;
}

// Filter history by status
function filterHistory(filter) {
    currentHistoryFilter = filter;
    loadRequestHistory(1, filter); // Reset to page 1 when filtering
}

// Go to specific history page
function goToHistoryPage(page) {
    // Validate page number
    if (page < 1) page = 1;
    if (page > historyTotalPages) page = historyTotalPages;
    
    loadRequestHistory(page, currentHistoryFilter);
}

// Helper function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Restore section on page load
document.addEventListener('DOMContentLoaded', function() {
    // Restore the saved section or show dashboard
    const savedSection = sessionStorage.getItem('ownerCurrentSection');
    const sectionToShow = savedSection || 'dashboard';
    
    // Always call showSection to properly set active classes
    showSection(sectionToShow);
    
    // Load initial history data
    loadRequestHistory(1, 'all');
    
    const successNotif = document.getElementById('successNotif');
    const errorNotif = document.getElementById('errorNotif');
    
    if (successNotif) {
        setTimeout(() => {
            successNotif.classList.remove('translate-x-full', 'opacity-0');
        }, 100);
        setTimeout(() => {
            successNotif.classList.add('translate-x-full', 'opacity-0');
        }, 4000);
    }
    
    if (errorNotif) {
        setTimeout(() => {
            errorNotif.classList.remove('translate-x-full', 'opacity-0');
        }, 100);
        setTimeout(() => {
            errorNotif.classList.add('translate-x-full', 'opacity-0');
        }, 4000);
    }
});

// Real-time polling for new approval requests
let lastRequestCheck = '<?= date('Y-m-d H:i:s') ?>';
let requestCheckInterval;

// Start polling when on approval-requests section
document.addEventListener('DOMContentLoaded', function() {
    // Check every 1 second for new requests (instant updates)
    requestCheckInterval = setInterval(checkForNewRequests, 1000);
});

async function checkForNewRequests() {
    // Only check if we're on the approval-requests section
    const approvalSection = document.getElementById('approval-requests-section');
    if (!approvalSection || approvalSection.classList.contains('hidden')) {
        return;
    }
    
    try {
        const response = await fetch(`check_new_requests.php?last_check=${encodeURIComponent(lastRequestCheck)}`);
        if (!response.ok) return;
        
        const data = await response.json();
        
        if (data.success) {
            // Update counts
            if (data.counts) {
                updateRequestCounts(data.counts);
            }
            
            // Show notification and add new requests dynamically
            if (data.new_requests && data.new_requests.length > 0) {
                data.new_requests.forEach(request => {
                    showNewRequestNotification(request);
                    addRequestToList(request);
                });
            }
            
            // Update last check time
            lastRequestCheck = data.current_time;
        }
    } catch (error) {
        console.error('Error checking for new requests:', error);
    }
}

function updateRequestCounts(counts) {
    // Update pending count
    const pendingCount = document.querySelector('.bg-yellow-50 .text-3xl');
    if (pendingCount) {
        pendingCount.textContent = counts.pending_requests || 0;
    }
    
    // Update approved count
    const approvedCount = document.querySelector('.bg-green-50 .text-3xl');
    if (approvedCount) {
        approvedCount.textContent = counts.approved_requests || 0;
    }
    
    // Update rejected count
    const rejectedCount = document.querySelector('.bg-red-50 .text-3xl');
    if (rejectedCount) {
        rejectedCount.textContent = counts.rejected_requests || 0;
    }
    
    // Update total count
    const totalCount = document.querySelector('.bg-blue-50 .text-3xl');
    if (totalCount) {
        totalCount.textContent = counts.total_requests || 0;
    }
    
    // Update badge in sidebar
    const badge = document.querySelector('.nav-item [onclick*="approval-requests"] .bg-yellow-500');
    if (badge && counts.pending_requests > 0) {
        badge.textContent = counts.pending_requests;
    } else if (badge && counts.pending_requests === 0) {
        badge.style.display = 'none';
    }
    
    // Update header pending count
    const headerPending = document.querySelector('#approval-requests-section .bg-gray-100');
    if (headerPending) {
        headerPending.textContent = `${counts.pending_requests || 0} Pending`;
    }
}

function showNewRequestNotification(request) {
    // Create notification container if it doesn't exist
    let notificationContainer = document.getElementById('request-notification-container');
    if (!notificationContainer) {
        notificationContainer = document.createElement('div');
        notificationContainer.id = 'request-notification-container';
        notificationContainer.className = 'fixed top-4 right-4 z-50 flex flex-col gap-3';
        document.body.appendChild(notificationContainer);
    }
    
    // Priority colors
    const priorityColors = {
        'critical': 'from-red-500 to-red-600',
        'high': 'from-orange-500 to-orange-600',
        'medium': 'from-yellow-500 to-yellow-600',
        'low': 'from-blue-500 to-blue-600'
    };
    
    const bgColor = priorityColors[request.priority] || 'from-blue-500 to-blue-600';
    
    // Create notification
    const notification = document.createElement('div');
    notification.className = `bg-gradient-to-r ${bgColor} text-white rounded-lg shadow-2xl p-5 max-w-md transform translate-x-full transition-all duration-500 ease-out`;
    
    notification.innerHTML = `
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0 bg-white bg-opacity-20 rounded-full p-2">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between mb-1">
                    <h4 class="text-base font-bold text-white">🔔 New Request!</h4>
                    <button onclick="this.closest('.bg-gradient-to-r').remove()" class="text-white hover:text-gray-200 transition-colors ml-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="space-y-1">
                    <p class="text-sm text-white font-medium">
                        ${request.title}
                    </p>
                    <p class="text-xs text-white opacity-90">
                        <strong>From:</strong> ${request.requester}
                    </p>
                    <p class="text-xs text-white opacity-90">
                        <strong>Priority:</strong> ${request.priority.toUpperCase()}
                    </p>
                    <p class="text-xs text-white opacity-90">
                        <strong>Time:</strong> ${request.requestedAt}
                    </p>
                </div>
            </div>
        </div>
    `;
    
    notificationContainer.appendChild(notification);
    
    // Slide in animation
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);
    
    // Play notification sound
    playNotificationSound();
    
    // Auto remove after 10 seconds
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 500);
    }, 10000);
}

function addRequestToList(request) {
    // Find the pending requests container
    const requestsContainer = document.querySelector('#approval-requests-section .space-y-3');
    if (!requestsContainer) {
        console.error('Could not find requests container');
        return;
    }
    
    // Check if "All caught up!" message is showing and remove it
    const caughtUpMessage = requestsContainer.querySelector('.text-center.py-12');
    if (caughtUpMessage) {
        caughtUpMessage.remove();
    }
    
    // Priority colors matching PHP
    const priorityColors = {
        'critical': { bg: 'bg-red-50', border: 'border-red-200', badge: 'bg-red-100 text-red-800', icon: 'text-red-600' },
        'high': { bg: 'bg-orange-50', border: 'border-orange-200', badge: 'bg-orange-100 text-orange-800', icon: 'text-orange-600' },
        'medium': { bg: 'bg-yellow-50', border: 'border-yellow-200', badge: 'bg-yellow-100 text-yellow-800', icon: 'text-yellow-600' },
        'low': { bg: 'bg-green-50', border: 'border-green-200', badge: 'bg-green-100 text-green-800', icon: 'text-green-600' }
    };
    
    const colors = priorityColors[request.priority] || priorityColors['medium'];
    
    // Parse description and reason
    let description = request.description || '';
    let reason = '';
    if (description.includes('Reason:')) {
        const parts = description.split('Reason:');
        description = parts[0].trim();
        reason = parts[1].trim();
    }
    
    // Parse target data
    let targetData = {};
    try {
        targetData = request.target_data ? JSON.parse(request.target_data) : {};
    } catch (e) {
        console.error('Error parsing target_data:', e);
    }
    
    const isStudent = request.type.includes('student');
    const isArchiveRequest = request.type.includes('archive');
    const detailsTitle = isStudent ? 'Student Details' : 'Employee Details';
    const idLabel = isStudent ? 'Student ID' : 'Employee ID';
    
    // Build target details HTML (skip for archive requests)
    let targetDetailsHTML = '';
    if (Object.keys(targetData).length > 0 && !isArchiveRequest) {
        let detailsContent = '';
        
        if (targetData.id_number) {
            detailsContent += `
                <div class="text-sm p-3 bg-gray-50 rounded-lg border border-gray-200">
                    <span class="text-gray-600 font-medium block mb-1.5">${idLabel}</span>
                    <span class="font-bold text-gray-900 bg-white px-3 py-1.5 rounded border border-gray-300 inline-block">${targetData.id_number}</span>
                </div>`;
        }
        
        if (targetData.first_name || targetData.last_name) {
            const fullName = [targetData.first_name, targetData.middle_name, targetData.last_name].filter(Boolean).join(' ');
            detailsContent += `
                <div class="text-sm p-3 bg-gray-50 rounded-lg border border-gray-200">
                    <span class="text-gray-600 font-medium block mb-1.5">Full Name</span>
                    <span class="font-bold text-gray-900 text-base">${fullName}</span>
                </div>`;
        }
        
        if (isStudent) {
            if (targetData.grade_level) {
                detailsContent += `
                    <div class="text-sm p-3 bg-gray-50 rounded-lg border border-gray-200">
                        <span class="text-gray-600 font-medium block mb-1.5">Grade Level</span>
                        <span class="font-semibold text-gray-900">${targetData.grade_level}</span>
                    </div>`;
            }
            if (targetData.academic_track) {
                detailsContent += `
                    <div class="text-sm p-3 bg-gray-50 rounded-lg border border-gray-200">
                        <span class="text-gray-600 font-medium block mb-1.5">Academic Track</span>
                        <span class="font-semibold text-gray-900">${targetData.academic_track}</span>
                    </div>`;
            }
        } else {
            if (targetData.position) {
                detailsContent += `
                    <div class="text-sm p-3 bg-gray-50 rounded-lg border border-gray-200">
                        <span class="text-gray-600 font-medium block mb-1.5">Position</span>
                        <span class="font-semibold text-gray-900">${targetData.position}</span>
                    </div>`;
            }
            if (targetData.department) {
                detailsContent += `
                    <div class="text-sm p-3 bg-gray-50 rounded-lg border border-gray-200">
                        <span class="text-gray-600 font-medium block mb-1.5">Department</span>
                        <span class="font-semibold text-gray-900">${targetData.department}</span>
                    </div>`;
            }
        }
        
        // Only show deletion reason for delete/archive requests, not restore requests
        const isRestoreRequest = request.type.includes('restore');
        if (targetData.deletion_reason && !isRestoreRequest) {
            detailsContent += `
                <div class="p-3 bg-red-50 rounded-lg border-2 border-red-200">
                    <div class="flex items-center gap-2 mb-2">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span class="text-red-800 font-bold">Deletion Reason</span>
                    </div>
                    <p class="font-medium text-gray-900 bg-white p-3 rounded-lg border border-red-300 leading-relaxed">${targetData.deletion_reason}</p>
                </div>`;
        }
        
        targetDetailsHTML = `
            <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-lg p-4 border border-gray-200">
                <div class="flex items-center gap-2 mb-3">
                    <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        ${isStudent ? 
                            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0v6"/>' :
                            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>'
                        }
                    </svg>
                    <p class="text-sm font-bold text-gray-800">${detailsTitle}</p>
                </div>
                <div class="bg-white rounded-lg p-3 space-y-2.5 shadow-sm">
                    ${detailsContent}
                </div>
            </div>`;
    }
    
    // Create card HTML matching existing PHP structure
    const cardHTML = `
        <div class="border-2 ${colors.border} rounded-xl overflow-hidden hover:shadow-lg transition-all duration-200" style="opacity: 0; transform: scale(0.95); transition: all 0.5s;">
            <div class="${colors.bg} p-5 cursor-pointer hover:opacity-90 transition-opacity" onclick="openRequestModal(${request.id})">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-4 flex-1">
                        <div class="w-12 h-12 rounded-full bg-white shadow-sm flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6 ${colors.icon}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="px-3 py-1 rounded-full text-xs font-bold ${colors.badge} shadow-sm">${request.priority.toUpperCase()}</span>
                                <span class="px-2 py-1 rounded bg-white text-xs font-medium text-gray-600 shadow-sm">${request.type.replace(/_/g, ' ')}</span>
                                <span class="px-2 py-1 rounded bg-green-500 text-white text-xs font-bold shadow-sm new-badge-${request.id}">NEW</span>
                            </div>
                            <h3 class="font-bold text-gray-900 text-base mb-1">${request.title}</h3>
                            <div class="flex items-center gap-2 text-xs text-gray-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <span class="font-medium">${request.requester}</span>
                                <span class="text-gray-400">•</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>${request.requestedAt}</span>
                            </div>
                        </div>
                    </div>
                    <button onclick="event.stopPropagation(); openRequestModal(${request.id});" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold transition-colors shadow-sm">
                        View Details
                    </button>
                </div>
            </div>
            <div id="details-${request.id}" class="hidden border-t-2 ${colors.border} bg-white p-6">
                <div class="space-y-4">
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                        <div class="flex items-start gap-2 mb-2">
                            <svg class="w-5 h-5 text-gray-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <div class="flex-1">
                                <p class="text-sm font-bold text-gray-700 mb-1">Description</p>
                                <p class="text-sm text-gray-600 leading-relaxed">${description}</p>
                            </div>
                        </div>
                    </div>
                    ${reason ? `
                    <div class="bg-yellow-50 rounded-lg p-4 border-2 border-yellow-300">
                        <div class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-yellow-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <div class="flex-1">
                                <p class="text-sm font-bold text-yellow-800 mb-2">Reason for Request</p>
                                <p class="text-sm text-yellow-900 leading-relaxed bg-white px-3 py-2 rounded border border-yellow-200">${reason}</p>
                            </div>
                        </div>
                    </div>` : ''}
                    <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                        <div class="flex items-start gap-2">
                            <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <div class="flex-1">
                                <p class="text-sm font-bold text-gray-700 mb-1">Requested by</p>
                                <p class="text-sm text-gray-600">
                                    <span class="font-semibold">${request.requester}</span>
                                    <span class="text-gray-400 mx-1">•</span>
                                    <span class="text-blue-600">${request.requester_role || ''}</span>
                                    <span class="text-gray-400 mx-1">•</span>
                                    <span>${request.requester_module || ''}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                    ${targetDetailsHTML}
                </div>
                
                <!-- Action Buttons -->
                <div class="flex gap-3 pt-5 mt-5 border-t-2 border-gray-200">
                    <button onclick="event.stopPropagation(); confirmAndSubmit(${request.id}, 'approve')" 
                            class="flex-1 px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-xl text-sm font-bold hover:from-green-700 hover:to-green-800 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Approve Request
                    </button>
                    <button onclick="event.stopPropagation(); confirmAndSubmit(${request.id}, 'reject')" 
                            class="flex-1 px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-xl text-sm font-bold hover:from-red-700 hover:to-red-800 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Reject Request
                    </button>
                </div>
            </div>
        </div>
    `;
    
    // Insert at the beginning
    requestsContainer.insertAdjacentHTML('afterbegin', cardHTML);
    
    // Get the newly added card and animate it
    const newCard = requestsContainer.firstElementChild;
    setTimeout(() => {
        newCard.style.opacity = '1';
        newCard.style.transform = 'scale(1)';
    }, 100);
    
    // Remove NEW badge after 8 seconds
    setTimeout(() => {
        const badge = newCard.querySelector(`.new-badge-${request.id}`);
        if (badge) {
            badge.style.transition = 'opacity 0.5s';
            badge.style.opacity = '0';
            setTimeout(() => badge.remove(), 500);
        }
    }, 8000);
}

function playNotificationSound() {
    try {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.frequency.value = 800;
        oscillator.type = 'sine';
        
        gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.1);
    } catch (e) {
        // Silently fail if audio not supported
    }
}

// Clean up interval when page is hidden/closed
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        clearInterval(requestCheckInterval);
    } else {
        requestCheckInterval = setInterval(checkForNewRequests, 2000);
    }
});

// ===== PREVENT BACK BUTTON AFTER LOGOUT =====
window.addEventListener("pageshow", function(event) {
  if (event.persisted || (performance.navigation.type === 2)) window.location.reload();
});

// ===== CUSTOM NOTIFICATION SYSTEM =====
function showNotification(message, type = 'success') {
    // Remove any existing notifications
    const existing = document.getElementById('customNotification');
    if (existing) existing.remove();
    
    // Create notification element
    const notification = document.createElement('div');
    notification.id = 'customNotification';
    notification.className = 'fixed top-4 right-4 z-50 max-w-md animate-slide-in';
    
    const bgColor = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500';
    const icon = type === 'success' ? '✓' : type === 'error' ? '✗' : 'ℹ';
    
    notification.innerHTML = `
        <div class="${bgColor} text-white rounded-lg shadow-lg p-4 flex items-start gap-3">
            <div class="flex-shrink-0 w-6 h-6 rounded-full bg-white bg-opacity-30 flex items-center justify-center font-bold">
                ${icon}
            </div>
            <div class="flex-1">
                <p class="text-sm font-medium">${message}</p>
            </div>
            <button onclick="this.parentElement.parentElement.remove()" class="flex-shrink-0 text-white hover:text-gray-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

// ===== TUITION FEE MANAGEMENT =====
let allTuitionFees = []; // Store all fees
let yearFeesData = {}; // Store fees grouped by year for filtering

function loadTuitionFees() {
    fetch('ManageTuitionFees.php?action=get_fees')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                allTuitionFees = data.fees;
                displayTuitionFees(data.fees);
            }
        })
        .catch(error => console.error('Error loading tuition fees:', error));
}

function updateAcademicTrackOptions(yearId) {
    const level = document.getElementById(`filterLevel-${yearId}`).value;
    const trackSelect = document.getElementById(`filterTrack-${yearId}`);
    
    if (!trackSelect) return;
    
    // Clear current options
    trackSelect.innerHTML = '<option value="">All Tracks</option>';
    
    if (level === 'shs') {
        // Show only Senior High School tracks (matching database values from add_account.php)
        trackSelect.disabled = false;
        trackSelect.innerHTML = `
            <option value="">All Tracks</option>
            <option value="ABM">ABM (Accountancy, Business & Management)</option>
            <option value="GAS">GAS (General Academic Strand)</option>
            <option value="HE">HE (Home Economics)</option>
            <option value="HUMSS">HUMSS (Humanities & Social Sciences)</option>
            <option value="ICT">ICT (Information and Communications Technology)</option>
            <option value="SPORTS">SPORTS</option>
            <option value="STEM">STEM (Science, Technology, Engineering & Mathematics)</option>
        `;
    } else if (level === 'college') {
        // Show only College courses (supporting both database formats)
        trackSelect.disabled = false;
        trackSelect.innerHTML = `
            <option value="">All Tracks</option>
            <option value="BPEd">BPEd (Bachelor of Physical Education)</option>
            <option value="BECEd">BECEd (Bachelor of Early Childhood Education)</option>
        `;
    } else if (level === '' || level === 'kinder' || level === 'elementary' || level === 'jhs') {
        // For lower grades or "All Levels", disable the track dropdown
        trackSelect.disabled = true;
        trackSelect.innerHTML = '<option value="">Not Applicable</option>';
    }
}

function filterYearFees(yearId) {
    const searchBar = document.getElementById(`searchBar-${yearId}`)?.value.toLowerCase() || '';
    const term = document.getElementById(`filterTerm-${yearId}`).value;
    const level = document.getElementById(`filterLevel-${yearId}`).value;
    const track = document.getElementById(`filterTrack-${yearId}`).value;
    
    // Get original fees for this year
    let filtered = yearFeesData[yearId] || [];
    
    // Filter by search bar
    if (searchBar) {
        filtered = filtered.filter(f => {
            const searchText = `${f.grade_level} ${f.term} ${f.academic_track || ''}`.toLowerCase();
            return searchText.includes(searchBar);
        });
    }
    
    // Filter by term
    if (term) {
        filtered = filtered.filter(f => f.term === term);
    }
    
    // Filter by education level
    if (level) {
        filtered = filtered.filter(f => {
            const gradeText = f.grade_level.toLowerCase().trim();
            switch(level) {
                case 'kinder': return gradeText.includes('kinder');
                case 'elementary': return gradeText.match(/\bgrade\s*[1-6]\b/);
                case 'jhs': return gradeText.match(/\bgrade\s*(7|8|9|10)\b/);
                case 'shs': return gradeText.match(/\bgrade\s*(11|12)\b/);
                case 'college': return gradeText.includes('year');
                default: return true;
            }
        });
    }
    
    // Filter by academic track
    if (track) {
        filtered = filtered.filter(f => {
            const academicTrack = f.academic_track || '';
            // Handle both formats: "BPEd" matches both "BPEd (Bachelor...)" and "Bachelor... (BPEd)"
            if (track === 'BPEd') {
                return academicTrack.includes('BPEd') || academicTrack.includes('Bachelor of Physical Education');
            } else if (track === 'BECEd') {
                return academicTrack.includes('BECEd') || academicTrack.includes('Bachelor of Early Childhood Education');
            }
            return academicTrack === track;
        });
    }
    
    // Update count
    document.getElementById(`filterCount-${yearId}`).textContent = filtered.length;
    
    // Re-render cards for this year (reset to page 1)
    renderYearCards(yearId, filtered, 1);
}

function clearYearFilters(yearId) {
    if (document.getElementById(`searchBar-${yearId}`)) {
        document.getElementById(`searchBar-${yearId}`).value = '';
    }
    document.getElementById(`filterTerm-${yearId}`).value = '';
    document.getElementById(`filterLevel-${yearId}`).value = '';
    document.getElementById(`filterTrack-${yearId}`).value = '';
    
    // Reset Academic Track dropdown to disabled state
    updateAcademicTrackOptions(yearId);
    
    filterYearFees(yearId);
}

// Store current page for each year
let yearCurrentPage = {};

function renderYearCards(yearId, fees, page = 1) {
    const container = document.getElementById(`feeCards-${yearId}`);
    const paginationContainer = document.getElementById(`pagination-${yearId}`);
    if (!container) return;
    
    const itemsPerPage = 6;
    yearCurrentPage[yearId] = page;
    
    if (fees.length === 0) {
        container.innerHTML = '<div class="col-span-full text-center py-8 text-gray-500">No fee structures match the selected filters</div>';
        if (paginationContainer) paginationContainer.innerHTML = '';
        return;
    }
    
    // Sort fees
    const sortedFees = fees.sort((a, b) => {
        const getOrder = (gradeLevel) => {
            const grade = gradeLevel.toLowerCase().trim();
            // Kinder 1 and Kinder 2
            if (grade === 'kinder 1') return 0;
            if (grade === 'kinder 2') return 0.5;
            if (grade.includes('kinder')) return 0; // Fallback for old "Kinder" entries
            // Elementary (Grade 1-6) - use word boundaries to avoid matching Grade 10, 11, 12
            if (grade.match(/\bgrade\s*1\b/)) return 1;
            if (grade.match(/\bgrade\s*2\b/)) return 2;
            if (grade.match(/\bgrade\s*3\b/)) return 3;
            if (grade.match(/\bgrade\s*4\b/)) return 4;
            if (grade.match(/\bgrade\s*5\b/)) return 5;
            if (grade.match(/\bgrade\s*6\b/)) return 6;
            // Junior High (Grade 7-10)
            if (grade.match(/\bgrade\s*7\b/)) return 7;
            if (grade.match(/\bgrade\s*8\b/)) return 8;
            if (grade.match(/\bgrade\s*9\b/)) return 9;
            if (grade.match(/\bgrade\s*10\b/)) return 10;
            // Senior High (Grade 11-12)
            if (grade.match(/\bgrade\s*11\b/)) return 11;
            if (grade.match(/\bgrade\s*12\b/)) return 12;
            // College (1st-4th Year)
            if (grade.includes('1st year')) return 13;
            if (grade.includes('2nd year')) return 14;
            if (grade.includes('3rd year')) return 15;
            if (grade.includes('4th year')) return 16;
            return 999;
        };
        
        const orderA = getOrder(a.grade_level);
        const orderB = getOrder(b.grade_level);
        
        if (orderA !== orderB) return orderA - orderB;
        return a.academic_track.localeCompare(b.academic_track);
    });
    
    // Calculate pagination
    const totalPages = Math.ceil(sortedFees.length / itemsPerPage);
    const startIndex = (page - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const paginatedFees = sortedFees.slice(startIndex, endIndex);
    
    // Render cards
    let html = '';
    paginatedFees.forEach(fee => {
        html += `
            <div class="group border border-gray-200 rounded-xl p-5 hover:shadow-lg hover:border-blue-300 transition-all duration-200">
                <div class="flex justify-between items-start mb-4">
                    <div class="flex-1">
                        <h4 class="font-bold text-gray-900 text-base mb-1">${fee.grade_level}</h4>
                        <p class="text-sm text-gray-500 flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                            ${fee.academic_track}
                        </p>
                    </div>
                    <button onclick="editTuitionFee(${fee.id})" class="opacity-0 group-hover:opacity-100 transition-opacity p-2 hover:bg-blue-50 rounded-lg text-blue-600 hover:text-blue-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                </div>
                
                <div class="space-y-2.5">
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-sm text-gray-600">Tuition Fee</span>
                        <span class="font-semibold text-gray-900">₱${parseFloat(fee.tuition_fee).toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-sm text-gray-600">Other Fees</span>
                        <span class="font-semibold text-gray-900">₱${parseFloat(fee.other_fees).toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
                    </div>
                    <div class="flex justify-between items-center pt-3 mt-2 border-t-2 border-blue-100">
                        <span class="text-base font-bold text-gray-800">Total Amount</span>
                        <span class="text-lg font-bold text-blue-600">₱${parseFloat(fee.total_fee).toLocaleString('en-PH', {minimumFractionDigits: 2})}</span>
                    </div>
                    <div class="mt-3 pt-3 border-t border-gray-200">
                        <div class="flex items-center justify-center gap-2 text-xs">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span class="font-semibold text-indigo-600">${fee.term || '1st Semester'}</span>
                        </div>
                    </div>
                </div>
            </div>`;
    });
    
    container.innerHTML = html;
    
    // Render pagination if needed
    if (paginationContainer && totalPages > 1) {
        let paginationHtml = '<div class="flex items-center justify-center gap-2 mt-6">';
        
        // Previous button
        paginationHtml += `
            <button onclick="changeYearPage('${yearId}', ${page - 1})" 
                    ${page === 1 ? 'disabled' : ''}
                    class="px-3 py-2 rounded-lg border ${page === 1 ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white hover:bg-gray-50 text-gray-700'} transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>`;
        
        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= page - 1 && i <= page + 1)) {
                paginationHtml += `
                    <button onclick="changeYearPage('${yearId}', ${i})" 
                            class="px-4 py-2 rounded-lg ${i === page ? 'bg-blue-600 text-white' : 'bg-white hover:bg-gray-50 text-gray-700'} border transition">
                        ${i}
                    </button>`;
            } else if (i === page - 2 || i === page + 2) {
                paginationHtml += '<span class="px-2 text-gray-400">...</span>';
            }
        }
        
        // Next button
        paginationHtml += `
            <button onclick="changeYearPage('${yearId}', ${page + 1})" 
                    ${page === totalPages ? 'disabled' : ''}
                    class="px-3 py-2 rounded-lg border ${page === totalPages ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white hover:bg-gray-50 text-gray-700'} transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>`;
        
        paginationHtml += '</div>';
        paginationContainer.innerHTML = paginationHtml;
    } else if (paginationContainer) {
        paginationContainer.innerHTML = '';
    }
}

function changeYearPage(yearId, page) {
    const term = document.getElementById(`filterTerm-${yearId}`).value;
    const level = document.getElementById(`filterLevel-${yearId}`).value;
    
    // Get filtered fees
    let filtered = yearFeesData[yearId] || [];
    
    if (term) {
        filtered = filtered.filter(f => f.term === term);
    }
    
    if (level) {
        filtered = filtered.filter(f => {
            const grade = f.grade_level.toLowerCase().trim();
            switch(level) {
                case 'kinder': return grade.includes('kinder');
                case 'elementary': return grade.match(/\bgrade\s*[1-6]\b/);
                case 'jhs': return grade.match(/\bgrade\s*(7|8|9|10)\b/);
                case 'shs': return grade.match(/\bgrade\s*(11|12)\b/);
                case 'college': return grade.includes('year');
                default: return true;
            }
        });
    }
    
    renderYearCards(yearId, filtered, page);
}

function displayTuitionFees(fees) {
    const container = document.getElementById('tuition-fees-list');
    
    if (fees.length === 0) {
        container.innerHTML = '<div class="text-center py-12 text-gray-500">No tuition fee structures found</div>';
        return;
    }
    
    // Group by school year
    const grouped = {};
    fees.forEach(fee => {
        if (!grouped[fee.school_year]) grouped[fee.school_year] = [];
        grouped[fee.school_year].push(fee);
    });
    
    let html = '';
    const years = Object.keys(grouped).sort().reverse();
    
    years.forEach((year, index) => {
        const isExpanded = index === 0; // Only first year expanded by default
        const yearId = year.replace(/[^a-zA-Z0-9]/g, '');
        
        html += `
            <div class="mb-4 border rounded-lg overflow-hidden">
                <button onclick="toggleYearSection('${yearId}')" class="w-full bg-gradient-to-r from-blue-50 to-indigo-50 hover:from-blue-100 hover:to-indigo-100 px-6 py-4 flex items-center justify-between transition">
                    <div class="flex items-center gap-3">
                        <svg id="icon-${yearId}" class="w-5 h-5 text-blue-600 transition-transform ${isExpanded ? 'rotate-90' : ''}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        <h3 class="text-lg font-bold text-gray-800">School Year: ${year}</h3>
                    </div>
                    <span class="text-sm text-gray-600 bg-white px-3 py-1 rounded-full">${grouped[year].length} fee structures</span>
                </button>
                
                <div id="year-${yearId}" class="${isExpanded ? '' : 'hidden'} p-6 bg-white">
                    <!-- Filters inside school year section -->
                    <div class="bg-gray-50 rounded-lg p-4 mb-4 border border-gray-200">
                        <!-- Search Bar -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                            <input type="text" id="searchBar-${yearId}" oninput="filterYearFees('${yearId}')" placeholder="Search by grade, level, or term..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Term/Semester</label>
                                <select id="filterTerm-${yearId}" onchange="filterYearFees('${yearId}')" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="">All Terms</option>
                                    <option value="1st Semester">1st Semester</option>
                                    <option value="2nd Semester">2nd Semester</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Education Level</label>
                                <select id="filterLevel-${yearId}" onchange="updateAcademicTrackOptions('${yearId}'); filterYearFees('${yearId}')" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <option value="">All Levels</option>
                                    <option value="kinder">Pre-Elementary (Kinder)</option>
                                    <option value="elementary">Elementary (Grade 1-6)</option>
                                    <option value="jhs">Junior High School (Grade 7-10)</option>
                                    <option value="shs">Senior High School (Grade 11-12)</option>
                                    <option value="college">College (1st-4th Year)</option>
                                </select>
                            </div>
                            <div id="trackWrapper-${yearId}">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Academic Track</label>
                                <select id="filterTrack-${yearId}" onchange="filterYearFees('${yearId}')" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent" disabled>
                                    <option value="">All Tracks</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center justify-between">
                            <p class="text-sm text-gray-600">
                                <span id="filterCount-${yearId}" class="font-semibold">${grouped[year].length}</span> fee structures
                            </p>
                            <button onclick="clearYearFilters('${yearId}')" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                Clear Filters
                            </button>
                        </div>
                    </div>
                    
                    <div id="feeCards-${yearId}" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4"></div>
                    <div id="pagination-${yearId}"></div>
                </div>
            </div>`;
        
        // Store fees for this year for filtering
        yearFeesData[yearId] = grouped[year];
    });
    
    container.innerHTML = html;
    
    // Render cards for each year
    Object.keys(yearFeesData).forEach(yearId => {
        renderYearCards(yearId, yearFeesData[yearId]);
    });
}

function toggleYearSection(yearId) {
    const section = document.getElementById('year-' + yearId);
    const icon = document.getElementById('icon-' + yearId);
    
    if (section.classList.contains('hidden')) {
        section.classList.remove('hidden');
        icon.classList.add('rotate-90');
    } else {
        section.classList.add('hidden');
        icon.classList.remove('rotate-90');
    }
}

function showAddSchoolYearModal() {
    // Get current year and calculate school year
    const now = new Date();
    const currentYear = now.getFullYear();
    const currentMonth = now.getMonth() + 1; // 0-11, so add 1
    
    // If current month is June-December, school year is current-next
    // If current month is January-May, school year is previous-current
    let startYear, endYear;
    if (currentMonth >= 6) {
        startYear = currentYear;
        endYear = currentYear + 1;
    } else {
        startYear = currentYear - 1;
        endYear = currentYear;
    }
    
    const currentSchoolYear = `${startYear}-${endYear}`;
    
    // Fetch existing school years for copy option
    fetch('ManageTuitionFees.php?action=get_fees')
        .then(response => response.json())
        .then(data => {
            const existingYears = [...new Set(data.fees.map(f => f.school_year))].sort().reverse();
            
            let yearOptions = '<option value="">Start with ₱0.00 (blank)</option>';
            existingYears.forEach(year => {
                yearOptions += `<option value="${year}">Copy from ${year}</option>`;
            });
            
            const modal = document.createElement('div');
            modal.id = 'addSchoolYearModal';
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            modal.innerHTML = `
                <div class="bg-white rounded-xl p-6 max-w-md w-full mx-4">
                    <h3 class="text-xl font-bold mb-4">Add School Year</h3>
                    <p class="text-sm text-gray-600 mb-4">Create all grade levels, tracks, and terms for a new school year.</p>
                    <form onsubmit="submitAddSchoolYear(event)">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium mb-2">School Year</label>
                                <input type="text" name="school_year" value="${currentSchoolYear}" required 
                                       pattern="[0-9]{4}-[0-9]{4}"
                                       class="w-full border rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500">
                                <p class="text-xs text-gray-500 mt-1">Current school year auto-filled</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">Copy Prices From</label>
                                <select name="copy_from_year" id="copyFromYear" onchange="toggleCopyInfo()" class="w-full border rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500">
                                    ${yearOptions}
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Optional: Copy prices from previous year</p>
                            </div>
                            <div id="copyInfo" class="hidden bg-green-50 border border-green-200 rounded-lg p-3">
                                <p class="text-sm text-green-900">✓ Prices will be copied from the selected year</p>
                            </div>
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                <p class="text-sm text-blue-900 font-medium mb-2">What will be created:</p>
                                <ul class="text-xs text-blue-800 space-y-1">
                                    <li>✓ All grade levels (Kinder to 4th Year)</li>
                                    <li>✓ All tracks/strands (Elementary, JHS, SHS, College)</li>
                                    <li>✓ Both 1st and 2nd Semester</li>
                                    <li>✓ Total: 66 fee structures</li>
                                </ul>
                            </div>
                        </div>
                        <div class="flex gap-3 mt-6">
                            <button type="button" onclick="closeAddSchoolYearModal()" class="flex-1 bg-gray-300 hover:bg-gray-400 px-4 py-2 rounded-lg">Cancel</button>
                            <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">Create School Year</button>
                        </div>
                    </form>
                </div>`;
            document.body.appendChild(modal);
        });
}

function toggleCopyInfo() {
    const copySelect = document.getElementById('copyFromYear');
    const copyInfo = document.getElementById('copyInfo');
    if (copySelect && copyInfo) {
        if (copySelect.value) {
            copyInfo.classList.remove('hidden');
        } else {
            copyInfo.classList.add('hidden');
        }
    }
}

function closeAddSchoolYearModal() {
    const modal = document.getElementById('addSchoolYearModal');
    if (modal) modal.remove();
}

function submitAddSchoolYear(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('action', 'add_school_year');
    
    const submitBtn = event.target.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Creating...';
    
    fetch('ManageTuitionFees.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeAddSchoolYearModal();
            loadTuitionFees();
            showNotification(`Success! ${data.inserted} fee structures created for school year ${formData.get('school_year')}. You can now edit the prices.`, 'success');
        } else {
            showNotification('Error: ' + data.message, 'error');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Create School Year';
        }
    })
    .catch(error => {
        showNotification('Error: ' + error.message, 'error');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Create School Year';
    });
}

function editTuitionFee(id) {
    // Get current fee data
    fetch(`ManageTuitionFees.php?action=get_fees`)
        .then(response => response.json())
        .then(data => {
            const fee = data.fees.find(f => f.id == id);
            if (!fee) return;
            
            const modal = document.createElement('div');
            modal.id = 'editFeeModal';
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            modal.innerHTML = `
                <div class="bg-white rounded-xl p-6 max-w-md w-full mx-4">
                    <h3 class="text-xl font-bold mb-4">Edit Tuition Fee</h3>
                    <form onsubmit="submitEditFee(event, ${id})">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Grade Level</label>
                                <input type="text" value="${fee.grade_level}" readonly class="w-full border rounded-lg px-3 py-2 bg-gray-100">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Academic Track</label>
                                <input type="text" value="${fee.academic_track}" readonly class="w-full border rounded-lg px-3 py-2 bg-gray-100">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Tuition Fee</label>
                                <input type="number" step="0.01" name="tuition_fee" value="${fee.tuition_fee}" required class="w-full border rounded-lg px-3 py-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Other Fees</label>
                                <input type="number" step="0.01" name="other_fees" value="${fee.other_fees}" required class="w-full border rounded-lg px-3 py-2">
                            </div>
                        </div>
                        <div class="flex gap-3 mt-6">
                            <button type="button" onclick="closeEditFeeModal()" class="flex-1 bg-gray-300 hover:bg-gray-400 px-4 py-2 rounded-lg">Cancel</button>
                            <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">Update</button>
                        </div>
                    </form>
                </div>`;
            document.body.appendChild(modal);
        });
}

function closeEditFeeModal() {
    const modal = document.getElementById('editFeeModal');
    if (modal) modal.remove();
}

function submitEditFee(event, id) {
    event.preventDefault();
    const formData = new FormData(event.target);
    formData.append('action', 'update_fee');
    formData.append('id', id);
    
    fetch('ManageTuitionFees.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeEditFeeModal();
            // Smooth update: only refresh the specific fee data without losing state
            updateSingleFee(id, formData);
            showNotification('Tuition fee updated successfully!', 'success');
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    });
}

function updateSingleFee(feeId, formData) {
    // Find and update the fee in allTuitionFees array
    const feeIndex = allTuitionFees.findIndex(f => f.id == feeId);
    if (feeIndex === -1) return;
    
    const fee = allTuitionFees[feeIndex];
    
    // Update the fee object with new values
    fee.tuition_fee = formData.get('tuition_fee');
    fee.other_fees = formData.get('other_fees');
    fee.total_fee = parseFloat(fee.tuition_fee) + parseFloat(fee.other_fees);
    
    // Get the sanitized yearId (same format used in displayTuitionFees)
    const yearId = fee.school_year.replace(/[^a-zA-Z0-9]/g, '');
    
    // Update in yearFeesData as well (using sanitized yearId)
    if (yearFeesData[yearId]) {
        const yearFeeIndex = yearFeesData[yearId].findIndex(f => f.id == feeId);
        if (yearFeeIndex !== -1) {
            yearFeesData[yearId][yearFeeIndex] = fee;
        }
    }
    
    // Re-render only the affected year section with current filters and pagination
    const currentPage = yearCurrentPage[yearId] || 1;
    
    // Apply current filters
    const searchBar = document.getElementById(`searchBar-${yearId}`)?.value.toLowerCase() || '';
    const term = document.getElementById(`filterTerm-${yearId}`)?.value || '';
    const level = document.getElementById(`filterLevel-${yearId}`)?.value || '';
    const track = document.getElementById(`filterTrack-${yearId}`)?.value || '';
    
    let filtered = yearFeesData[yearId] || [];
    
    // Apply search filter
    if (searchBar) {
        filtered = filtered.filter(f => {
            const searchText = `${f.grade_level} ${f.term} ${f.academic_track || ''}`.toLowerCase();
            return searchText.includes(searchBar);
        });
    }
    
    // Apply term filter
    if (term) {
        filtered = filtered.filter(f => f.term === term);
    }
    
    // Apply level filter
    if (level) {
        filtered = filtered.filter(f => {
            const gradeText = f.grade_level.toLowerCase().trim();
            switch(level) {
                case 'kinder': return gradeText.includes('kinder');
                case 'elementary': return gradeText.match(/\bgrade\s*[1-6]\b/);
                case 'jhs': return gradeText.match(/\bgrade\s*(7|8|9|10)\b/);
                case 'shs': return gradeText.match(/\bgrade\s*(11|12)\b/);
                case 'college': return gradeText.includes('year');
                default: return true;
            }
        });
    }
    
    // Apply track filter
    if (track) {
        filtered = filtered.filter(f => {
            const academicTrack = f.academic_track || '';
            if (track === 'BPEd') {
                return academicTrack.includes('BPEd') || academicTrack.includes('Bachelor of Physical Education');
            } else if (track === 'BECEd') {
                return academicTrack.includes('BECEd') || academicTrack.includes('Bachelor of Early Childhood Education');
            }
            return academicTrack === track;
        });
    }
    
    // Update filter count
    const filterCountEl = document.getElementById(`filterCount-${yearId}`);
    if (filterCountEl) {
        filterCountEl.textContent = filtered.length;
    }
    
    // Re-render with current page
    renderYearCards(yearId, filtered, currentPage);
}

// ==================== FEE TYPES MANAGEMENT ====================

function loadFeeTypes() {
    fetch('ManageFeeTypes.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayFeeTypes(data.data);
            } else {
                document.getElementById('fee-types-list').innerHTML = `
                    <div class="text-center py-8 text-red-600">
                        <p>Error loading fee types: ${data.message}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading fee types:', error);
            document.getElementById('fee-types-list').innerHTML = `
                <div class="text-center py-8 text-red-600">
                    <p>Error loading fee types</p>
                </div>
            `;
        });
}

function displayFeeTypes(feeTypes) {
    const container = document.getElementById('fee-types-list');
    
    if (feeTypes.length === 0) {
        container.innerHTML = `
            <div class="text-center py-12">
                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p class="text-gray-500 text-lg">No fee types found</p>
                <p class="text-gray-400 text-sm mt-2">Click "Add Fee Type" to create your first fee type</p>
            </div>
        `;
        return;
    }
    
    let html = `
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b-2 border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fee Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Default Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Updated</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
    `;
    
    feeTypes.forEach(fee => {
        const createdDate = new Date(fee.created_at).toLocaleDateString();
        const updatedDate = new Date(fee.updated_at).toLocaleDateString();
        
        html += `
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">${fee.fee_name}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">₱${parseFloat(fee.default_amount).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-500">${createdDate}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-500">${updatedDate}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                    <button onclick='editFeeType(${JSON.stringify(fee)})' class="text-blue-600 hover:text-blue-800 mr-3">
                        <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                    <button onclick="deleteFeeType(${fee.id}, '${fee.fee_name}')" class="text-red-600 hover:text-red-800">
                        <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
            </table>
        </div>
    `;
    
    container.innerHTML = html;
}

function showAddFeeTypeModal() {
    const modal = document.createElement('div');
    modal.id = 'fee-type-modal';
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    modal.innerHTML = `
        <div class="bg-white rounded-xl p-6 max-w-md w-full mx-4">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Add New Fee Type</h3>
            <form id="fee-type-form" onsubmit="saveFeeType(event)">
                <input type="hidden" id="fee-type-id" name="id" value="">
                <input type="hidden" name="action" value="add">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fee Name *</label>
                    <input type="text" name="fee_name" id="fee-name" required 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="e.g., Library Fee, Laboratory Fee">
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Default Amount (₱) *</label>
                    <input type="number" name="default_amount" id="fee-amount" required min="0" step="0.01"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="0.00">
                </div>
                
                <div class="flex gap-3">
                    <button type="button" onclick="closeFeeTypeModal()" 
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        Save Fee Type
                    </button>
                </div>
            </form>
        </div>
    `;
    document.body.appendChild(modal);
}

function editFeeType(fee) {
    const modal = document.createElement('div');
    modal.id = 'fee-type-modal';
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    modal.innerHTML = `
        <div class="bg-white rounded-xl p-6 max-w-md w-full mx-4">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Edit Fee Type</h3>
            <form id="fee-type-form" onsubmit="saveFeeType(event)">
                <input type="hidden" id="fee-type-id" name="id" value="${fee.id}">
                <input type="hidden" name="action" value="edit">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fee Name *</label>
                    <input type="text" name="fee_name" id="fee-name" required value="${fee.fee_name}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Default Amount (₱) *</label>
                    <input type="number" name="default_amount" id="fee-amount" required min="0" step="0.01" value="${fee.default_amount}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div class="flex gap-3">
                    <button type="button" onclick="closeFeeTypeModal()" 
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        Update Fee Type
                    </button>
                </div>
            </form>
        </div>
    `;
    document.body.appendChild(modal);
}

function closeFeeTypeModal() {
    const modal = document.getElementById('fee-type-modal');
    if (modal) {
        modal.remove();
    }
}

function saveFeeType(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    fetch('ManageFeeTypes.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeFeeTypeModal();
            loadFeeTypes();
            showNotification(data.message, 'success');
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error saving fee type:', error);
        showNotification('Error saving fee type', 'error');
    });
}

function deleteFeeType(id, name) {
    if (!confirm(`Are you sure you want to delete "${name}"?\n\nThis will remove the fee type from the system.`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);
    
    fetch('ManageFeeTypes.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadFeeTypes();
            showNotification(data.message, 'success');
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error deleting fee type:', error);
        showNotification('Error deleting fee type', 'error');
    });
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 
        'bg-blue-500'
    } text-white`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// ==================== DOCUMENT FEES MANAGEMENT ====================

function loadDocumentFees() {
    fetch('ManageDocumentFees.php?action=list')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayDocumentFees(data.data);
            } else {
                document.getElementById('document-fees-list').innerHTML = `
                    <div class="text-center py-8 text-red-600">
                        <p>Error loading document fees: ${data.message}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading document fees:', error);
            document.getElementById('document-fees-list').innerHTML = `
                <div class="text-center py-8 text-red-600">
                    <p>Error loading document fees</p>
                </div>
            `;
        });
}

function displayDocumentFees(documentFees) {
    const container = document.getElementById('document-fees-list');
    
    if (documentFees.length === 0) {
        container.innerHTML = `
            <div class="text-center py-12">
                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-gray-500 text-lg">No document types found</p>
                <p class="text-gray-400 text-sm mt-2">Add document types in the Registrar Dashboard first</p>
            </div>
        `;
        return;
    }
    
    let html = `
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b-2 border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Document Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fee Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
    `;
    
    documentFees.forEach(doc => {
        const feeAmount = parseFloat(doc.fee_amount);
        const statusBadge = feeAmount > 0 
            ? '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Creates Balance</span>'
            : '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Free</span>';
        
        html += `
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">${doc.document_name}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">₱${feeAmount.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    ${statusBadge}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                    <button onclick='editDocumentFee("${doc.document_name}", ${feeAmount})' 
                            class="text-blue-600 hover:text-blue-800 font-medium">
                        Edit Fee
                    </button>
                </td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
            </table>
        </div>
    `;
    
    container.innerHTML = html;
}

function editDocumentFee(documentName, currentFee) {
    const modal = document.createElement('div');
    modal.id = 'document-fee-modal';
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    modal.innerHTML = `
        <div class="bg-white rounded-xl p-6 max-w-md w-full mx-4">
            <h3 class="text-xl font-bold text-gray-800 mb-4">Set Document Fee</h3>
            <form id="document-fee-form" onsubmit="saveDocumentFee(event)">
                <input type="hidden" name="action" value="update_fee">
                <input type="hidden" name="document_name" value="${documentName}">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Document Name</label>
                    <input type="text" value="${documentName}" disabled
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fee Amount (₱) *</label>
                    <input type="number" name="fee_amount" id="doc-fee-amount" required min="0" step="0.01" value="${currentFee}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="0.00">
                    <p class="text-xs text-gray-500 mt-1">Set to ₱0 if this document should be free (no balance created)</p>
                </div>
                
                <div class="flex gap-3">
                    <button type="button" onclick="closeDocumentFeeModal()" 
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        Save Fee
                    </button>
                </div>
            </form>
        </div>
    `;
    document.body.appendChild(modal);
}

function closeDocumentFeeModal() {
    const modal = document.getElementById('document-fee-modal');
    if (modal) {
        modal.remove();
    }
}

function saveDocumentFee(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    fetch('ManageDocumentFees.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeDocumentFeeModal();
            loadDocumentFees();
            showNotification(data.message, 'success');
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error saving document fee:', error);
        showNotification('Error saving document fee', 'error');
    });
}

// ==================== INFINITE SCROLL FOR NOTIFICATIONS ====================
let notificationsOffset = 5; // Start from 5 since we loaded 5 initially
let isLoadingNotifications = false;
let hasMoreNotifications = true;

const notificationsContainer = document.getElementById('notifications-container');

if (notificationsContainer) {
    notificationsContainer.addEventListener('scroll', function() {
        // Check if scrolled to bottom (with 50px threshold)
        if (notificationsContainer.scrollTop + notificationsContainer.clientHeight >= notificationsContainer.scrollHeight - 50) {
            if (!isLoadingNotifications && hasMoreNotifications) {
                loadMoreNotifications();
            }
        }
    });
}

function loadMoreNotifications() {
    isLoadingNotifications = true;
    
    // Show loading indicator
    const loadingDiv = document.createElement('div');
    loadingDiv.id = 'notifications-loading';
    loadingDiv.className = 'text-center py-4';
    loadingDiv.innerHTML = '<div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>';
    notificationsContainer.appendChild(loadingDiv);
    
    fetch(`load_more_notifications.php?offset=${notificationsOffset}`)
        .then(response => response.json())
        .then(data => {
            // Remove loading indicator
            const loading = document.getElementById('notifications-loading');
            if (loading) loading.remove();
            
            if (data.success && data.notifications.length > 0) {
                data.notifications.forEach(notification => {
                    const notifElement = createNotificationElement(notification);
                    notificationsContainer.appendChild(notifElement);
                });
                
                notificationsOffset += data.notifications.length;
                hasMoreNotifications = data.has_more;
                
                // Show "No more notifications" message if we've reached the end
                if (!data.has_more) {
                    const endDiv = document.createElement('div');
                    endDiv.className = 'text-center py-4 text-gray-500 text-sm';
                    endDiv.innerHTML = '— End of notifications —';
                    notificationsContainer.appendChild(endDiv);
                }
            } else {
                hasMoreNotifications = false;
            }
            
            isLoadingNotifications = false;
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
            const loading = document.getElementById('notifications-loading');
            if (loading) loading.remove();
            isLoadingNotifications = false;
        });
}

function createNotificationElement(notification) {
    const div = document.createElement('div');
    const isUnread = !notification.is_read;
    
    // Determine badge color based on type
    let badgeClass = 'bg-blue-100 text-blue-800';
    if (notification.type === 'critical' || notification.type === 'error') {
        badgeClass = 'bg-red-100 text-red-800';
    } else if (notification.type === 'warning') {
        badgeClass = 'bg-yellow-100 text-yellow-800';
    } else if (notification.type === 'success') {
        badgeClass = 'bg-green-100 text-green-800';
    }
    
    div.className = `border rounded-lg p-4 ${isUnread ? 'bg-white border-l-4 border-blue-500' : 'bg-gray-50'} cursor-pointer hover:shadow-md transition-all duration-200`;
    div.onclick = () => toggleNotificationDetails(notification.id);
    
    // Format date
    const date = new Date(notification.created_at);
    const formattedDate = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) + ' ' + 
                          date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
    
    div.innerHTML = `
        <div class="flex justify-between items-start mb-3">
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                    <h3 class="font-semibold text-gray-900">${escapeHtml(notification.title)}</h3>
                    <span class="px-2 py-1 rounded-full text-xs font-medium ${badgeClass}">
                        ${notification.type.toUpperCase()}
                    </span>
                    ${isUnread ? '<span class="w-2 h-2 bg-blue-500 rounded-full"></span>' : ''}
                </div>
                <p class="text-sm text-gray-600 mb-2">
                    <strong>From:</strong> ${escapeHtml(notification.performed_by)} 
                    (${notification.user_role.charAt(0).toUpperCase() + notification.user_role.slice(1)}) • 
                    <strong>Module:</strong> ${escapeHtml(notification.module)}
                </p>
                <p class="text-sm text-gray-700 mb-3">${escapeHtml(notification.message)}</p>
                <p class="text-xs text-gray-500">${formattedDate}</p>
            </div>
            ${isUnread ? `
                <form method="POST" class="ml-4" onclick="event.stopPropagation()">
                    <input type="hidden" name="notification_id" value="${notification.id}">
                    <button type="submit" name="mark_read" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">
                        Mark Read
                    </button>
                </form>
            ` : ''}
        </div>
    `;
    
    return div;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ===== TODAY'S LOGINS FUNCTIONS =====

// Pagination for Today's Logins
let loginsPage = 1;
const loginsPerPage = 10;

function changeLoginsPage(direction) {
    const rows = document.querySelectorAll('#logins-tbody .login-row');
    const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
    const totalPages = Math.ceil(visibleRows.length / loginsPerPage);
    
    loginsPage += direction;
    if (loginsPage < 1) loginsPage = 1;
    if (loginsPage > totalPages) loginsPage = totalPages;
    
    updateLoginsDisplay();
}

function updateLoginsDisplay() {
    const rows = document.querySelectorAll('#logins-tbody .login-row');
    const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
    const totalVisible = visibleRows.length;
    const totalPages = Math.ceil(totalVisible / loginsPerPage);
    
    // Hide all rows first
    visibleRows.forEach(row => row.classList.add('hidden'));
    
    // Show only current page rows
    const start = (loginsPage - 1) * loginsPerPage;
    const end = start + loginsPerPage;
    visibleRows.slice(start, end).forEach(row => row.classList.remove('hidden'));
    
    // Update pagination info
    const startNum = totalVisible > 0 ? start + 1 : 0;
    const endNum = Math.min(end, totalVisible);
    
    const startSpan = document.getElementById('logins-start');
    const endSpan = document.getElementById('logins-end');
    const totalSpan = document.getElementById('logins-total');
    
    if (startSpan) startSpan.textContent = startNum;
    if (endSpan) endSpan.textContent = endNum;
    if (totalSpan) totalSpan.textContent = totalVisible;
    
    // Update button states
    const prevBtn = document.getElementById('logins-prev');
    const nextBtn = document.getElementById('logins-next');
    
    if (prevBtn) prevBtn.disabled = loginsPage === 1;
    if (nextBtn) nextBtn.disabled = loginsPage >= totalPages;
}

// Filter functions for Today's Logins
function updateRoleOptions() {
    const userTypeFilter = document.getElementById('filter-user-type').value.toLowerCase();
    const roleFilter = document.getElementById('filter-role');
    
    // Clear existing options except "All"
    roleFilter.innerHTML = '<option value="all">All</option>';
    
    // Define role options based on user type
    const roleOptions = {
        'student': ['Student'],
        'parent': ['Parent'],
        'employee': ['HR', 'Teacher', 'Registrar', 'Cashier', 'Guidance', 'Attendance'],
        'all': ['Student', 'Parent', 'HR', 'Teacher', 'Registrar', 'Cashier', 'Guidance', 'Attendance']
    };
    
    const roles = roleOptions[userTypeFilter] || roleOptions['all'];
    roles.forEach(role => {
        const option = document.createElement('option');
        option.value = role.toLowerCase();
        option.textContent = role;
        roleFilter.appendChild(option);
    });
}

function filterLogins() {
    const userTypeFilter = document.getElementById('filter-user-type').value.toLowerCase();
    const roleFilter = document.getElementById('filter-role').value.toLowerCase();
    const searchFilter = document.getElementById('filter-search').value.toLowerCase();
    
    const rows = document.querySelectorAll('#logins-tbody .login-row');
    
    rows.forEach(row => {
        const userType = row.dataset.userType;
        const role = row.dataset.role;
        const id = row.dataset.id.toLowerCase();
        const name = row.dataset.name.toLowerCase();
        
        const userTypeMatch = userTypeFilter === 'all' || userType === userTypeFilter;
        const roleMatch = roleFilter === 'all' || role === roleFilter;
        const searchMatch = searchFilter === '' || id.includes(searchFilter) || name.includes(searchFilter);
        
        if (userTypeMatch && roleMatch && searchMatch) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
    
    // Reset to page 1 after filtering
    loginsPage = 1;
    updateLoginsDisplay();
}

function clearFilters() {
    document.getElementById('filter-user-type').value = 'all';
    document.getElementById('filter-role').value = 'all';
    document.getElementById('filter-search').value = '';
    updateRoleOptions();
    filterLogins();
}

// Initialize Today's Logins pagination on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize role options
    updateRoleOptions();
    
    // Initialize Today's Logins pagination only if pagination elements exist
    const loginsTable = document.getElementById('logins-table');
    const loginsPagination = document.getElementById('logins-start');
    
    if (loginsTable && loginsPagination) {
        updateLoginsDisplay();
    }
});

// ===== LOGIN HISTORY MODAL FUNCTIONS =====

let historyPage = 1;
const historyPerPage = 10;
let historyData = [];
let searchTimeout;
let isSearching = false;

function updateHistoryRoleOptions() {
    const userTypeFilter = document.getElementById('history-user-type').value.toLowerCase();
    const roleSelect = document.getElementById('history-role');
    
    const roleOptions = {
        'all': [
            { value: 'all', label: 'All' },
            { value: 'student', label: 'Student' },
            { value: 'parent', label: 'Parent' },
            { value: 'teacher', label: 'Teacher' },
            { value: 'registrar', label: 'Registrar' },
            { value: 'cashier', label: 'Cashier' },
            { value: 'guidance', label: 'Guidance' },
            { value: 'hr', label: 'HR' },
            { value: 'attendance', label: 'Attendance' }
        ],
        'student': [
            { value: 'all', label: 'All' },
            { value: 'student', label: 'Student' }
        ],
        'parent': [
            { value: 'all', label: 'All' },
            { value: 'parent', label: 'Parent' }
        ],
        'employee': [
            { value: 'all', label: 'All' },
            { value: 'teacher', label: 'Teacher' },
            { value: 'registrar', label: 'Registrar' },
            { value: 'cashier', label: 'Cashier' },
            { value: 'guidance', label: 'Guidance' },
            { value: 'hr', label: 'HR' },
            { value: 'attendance', label: 'Attendance' }
        ]
    };
    
    const currentRole = roleSelect.value;
    roleSelect.innerHTML = '';
    
    const options = roleOptions[userTypeFilter] || roleOptions['all'];
    options.forEach(option => {
        const optionElement = document.createElement('option');
        optionElement.value = option.value;
        optionElement.textContent = option.label;
        roleSelect.appendChild(optionElement);
    });
    
    const optionExists = options.some(opt => opt.value === currentRole);
    if (optionExists) {
        roleSelect.value = currentRole;
    } else {
        roleSelect.value = 'all';
    }
}

function openLoginHistory() {
    document.getElementById('login-history-modal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    
    document.getElementById('history-date-to').value = '';
    document.getElementById('history-date-from').value = '';
    document.getElementById('history-user-type').value = 'all';
    document.getElementById('history-search').value = '';
    historyPage = 1;
    
    updateHistoryRoleOptions();
    searchLoginHistory();
}

function closeLoginHistory() {
    document.getElementById('login-history-modal').classList.add('hidden');
    document.body.style.overflow = '';
}

function autoSearchHistory() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        historyPage = 1;
        searchLoginHistory();
    }, 300);
}

function debouncedHistorySearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        historyPage = 1;
        searchLoginHistory();
    }, 500);
}

async function searchLoginHistory() {
    if (isSearching) return;
    
    const loading = document.getElementById('login-history-loading');
    const table = document.getElementById('login-history-table');
    const noResults = document.getElementById('login-history-no-results');
    const initialMessage = document.getElementById('login-history-initial-message');
    const pagination = document.getElementById('login-history-pagination');
    
    isSearching = true;
    let showLoadingTimeout = setTimeout(() => {
        loading.classList.remove('hidden');
        table.classList.add('hidden');
        noResults.classList.add('hidden');
        initialMessage.classList.add('hidden');
        pagination.classList.add('hidden');
    }, 200);
    
    try {
        const dateFrom = document.getElementById('history-date-from').value;
        const dateTo = document.getElementById('history-date-to').value;
        const userType = document.getElementById('history-user-type').value;
        const role = document.getElementById('history-role').value;
        const search = document.getElementById('history-search').value;
        
        const params = new URLSearchParams({
            date_from: dateFrom,
            date_to: dateTo,
            user_type: userType,
            role: role,
            search: search,
            page: historyPage,
            limit: 10
        });
        
        const response = await fetch(`get_login_history.php?${params}`);
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        historyData = data.records || [];
        displayHistoryResults(data);
        
    } catch (error) {
        console.error('Error loading history:', error);
        alert('Error loading login history: ' + error.message);
    } finally {
        clearTimeout(showLoadingTimeout);
        loading.classList.add('hidden');
        isSearching = false;
    }
}

function displayHistoryResults(data) {
    const table = document.getElementById('login-history-table');
    const tbody = document.getElementById('login-history-tbody');
    const noResults = document.getElementById('login-history-no-results');
    const initialMessage = document.getElementById('login-history-initial-message');
    const pagination = document.getElementById('login-history-pagination');
    const dateIndicator = document.getElementById('history-date-indicator');
    const dateRange = document.getElementById('history-date-range');
    
    tbody.innerHTML = '';
    
    if (!data.records || data.records.length === 0) {
        table.classList.add('hidden');
        noResults.classList.remove('hidden');
        initialMessage.classList.add('hidden');
        dateIndicator.classList.add('hidden');
        pagination.classList.add('hidden');
        return;
    }
    
    table.classList.remove('hidden');
    noResults.classList.add('hidden');
    initialMessage.classList.add('hidden');
    
    const dates = data.records.map(r => new Date(r.login_time).toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    }));
    const uniqueDates = [...new Set(dates)];
    
    if (uniqueDates.length === 1) {
        dateRange.textContent = uniqueDates[0];
    } else if (uniqueDates.length > 1) {
        const sortedDates = uniqueDates.sort((a, b) => new Date(a) - new Date(b));
        dateRange.textContent = `${sortedDates[0]} - ${sortedDates[sortedDates.length - 1]}`;
    }
    
    dateIndicator.classList.remove('hidden');
    
    data.records.forEach(record => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50 transition-colors';
        
        const userTypeColors = {
            'employee': 'bg-purple-100 text-purple-700',
            'student': 'bg-blue-100 text-blue-700',
            'parent': 'bg-cyan-100 text-cyan-700'
        };
        
        const roleColors = {
            'superadmin': 'bg-red-100 text-red-700',
            'hr': 'bg-orange-100 text-orange-700',
            'teacher': 'bg-green-100 text-green-700',
            'registrar': 'bg-indigo-100 text-indigo-700',
            'cashier': 'bg-yellow-100 text-yellow-700',
            'guidance': 'bg-pink-100 text-pink-700',
            'attendance': 'bg-teal-100 text-teal-700',
            'student': 'bg-blue-100 text-blue-700',
            'parent': 'bg-cyan-100 text-cyan-700'
        };
        
        const userTypeColor = userTypeColors[record.user_type] || 'bg-gray-100 text-gray-700';
        const roleColor = roleColors[record.role] || 'bg-gray-100 text-gray-700';
        
        const loginDate = new Date(record.login_time);
        const logoutTime = record.logout_time ? new Date(record.logout_time) : null;
        
        let duration = '---';
        if (record.session_duration) {
            const hours = Math.floor(record.session_duration / 3600);
            const minutes = Math.floor((record.session_duration % 3600) / 60);
            duration = hours > 0 ? `${hours}h ${minutes}m` : `${minutes} min`;
        }
        
        row.innerHTML = `
            <td class="px-4 py-3">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ${userTypeColor}">
                    ${record.user_type.charAt(0).toUpperCase() + record.user_type.slice(1)}
                </span>
            </td>
            <td class="px-4 py-3 font-mono text-gray-600 text-xs">${record.id_number}</td>
            <td class="px-4 py-3 font-medium text-gray-900">${record.full_name || record.username}</td>
            <td class="px-4 py-3">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ${roleColor}">
                    ${record.role.charAt(0).toUpperCase() + record.role.slice(1)}
                </span>
            </td>
            <td class="px-4 py-3 text-gray-600">${loginDate.toLocaleDateString()}</td>
            <td class="px-4 py-3 text-gray-600 text-xs">${loginDate.toLocaleTimeString()}</td>
            <td class="px-4 py-3 text-gray-600 text-xs">
                ${logoutTime ? logoutTime.toLocaleTimeString() : '<span class="text-green-600 font-medium">Active</span>'}
            </td>
            <td class="px-4 py-3 text-gray-600">${duration}</td>
        `;
        
        tbody.appendChild(row);
    });
    
    const total = data.total || 0;
    const start = total > 0 ? ((historyPage - 1) * 10) + 1 : 0;
    const end = Math.min(historyPage * 10, total);
    
    document.getElementById('login-history-start').textContent = start;
    document.getElementById('login-history-end').textContent = end;
    document.getElementById('login-history-total').textContent = total;
    
    const prevBtn = document.getElementById('login-history-prev');
    const nextBtn = document.getElementById('login-history-next');
    
    prevBtn.disabled = historyPage === 1;
    nextBtn.disabled = end >= total;
    
    pagination.classList.remove('hidden');
}

function changeHistoryPage(direction) {
    historyPage += direction;
    if (historyPage < 1) historyPage = 1;
    searchLoginHistory();
}

function clearHistoryFilters() {
    document.getElementById('history-user-type').value = 'all';
    document.getElementById('history-role').value = 'all';
    document.getElementById('history-search').value = '';
    document.getElementById('history-date-from').value = '';
    document.getElementById('history-date-to').value = '';
    updateHistoryRoleOptions();
    historyPage = 1;
    searchLoginHistory();
}

// Validate date range - prevent dates before 2025 and future dates
function validateDateRange(input) {
    const selectedDate = new Date(input.value);
    const minDate = new Date('2025-01-01');
    const maxDate = new Date();
    maxDate.setHours(23, 59, 59, 999); // End of today
    
    if (selectedDate < minDate) {
        alert('Please select a date from 2025 onwards.');
        input.value = '';
        return false;
    }
    
    if (selectedDate > maxDate) {
        alert('Future dates are not allowed. Please select today or an earlier date.');
        input.value = '';
        return false;
    }
    
    // Validate From Date vs To Date
    const fromDateInput = document.getElementById('history-date-from');
    const toDateInput = document.getElementById('history-date-to');
    
    if (fromDateInput.value && toDateInput.value) {
        const fromDate = new Date(fromDateInput.value);
        const toDate = new Date(toDateInput.value);
        
        if (fromDate > toDate) {
            alert('From Date cannot be later than To Date.');
            input.value = '';
            return false;
        }
    }
    
    return true;
}

// Update date range constraints dynamically
function updateDateConstraints() {
    const dateFromInput = document.getElementById('history-date-from');
    const dateToInput = document.getElementById('history-date-to');
    const today = new Date().toISOString().split('T')[0];
    
    if (dateFromInput && dateToInput) {
        // If From Date is selected, set To Date minimum to From Date
        if (dateFromInput.value) {
            dateToInput.setAttribute('min', dateFromInput.value);
        } else {
            dateToInput.setAttribute('min', '2025-01-01');
        }
        
        // If To Date is selected, set From Date maximum to To Date
        if (dateToInput.value) {
            dateFromInput.setAttribute('max', dateToInput.value);
        } else {
            dateFromInput.setAttribute('max', today);
        }
    }
}

// Initialize date constraints on page load
document.addEventListener('DOMContentLoaded', function() {
    // Set min and max dates for date inputs to prevent selecting dates before 2025 and future dates
    const dateFromInput = document.getElementById('history-date-from');
    const dateToInput = document.getElementById('history-date-to');
    
    if (dateFromInput && dateToInput) {
        const today = new Date().toISOString().split('T')[0];
        dateFromInput.setAttribute('min', '2025-01-01');
        dateFromInput.setAttribute('max', today);
        dateToInput.setAttribute('min', '2025-01-01');
        dateToInput.setAttribute('max', today);
    }
    
    // Load Not Logged In Today sections
    loadNotLoggedIn('employees', 1);
    loadNotLoggedIn('students', 1);
});

// ===== NOT LOGGED IN TODAY FUNCTIONS =====

let employeesPage = 1;
let studentsPage = 1;
let employeesTotal = 0;
let studentsTotal = 0;
let allEmployees = [];
let allStudents = [];
const notLoggedInItemsPerPage = 10;

async function loadNotLoggedIn(type, page = 1) {
    const list = document.getElementById(`${type}-list`);
    const loading = document.getElementById(`${type}-loading`);
    const pagination = document.getElementById(`${type}-pagination`);
    
    if (!list || !loading) return;
    
    loading.classList.remove('hidden');
    
    try {
        const offset = (page - 1) * notLoggedInItemsPerPage;
        const url = `load_more_users.php?type=${type}&offset=${offset}&limit=${notLoggedInItemsPerPage}`;
        
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 10000);
        
        const response = await fetch(url, { signal: controller.signal });
        clearTimeout(timeoutId);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.error) {
            list.innerHTML = `<li class="text-center py-8"><p class="text-red-500 font-medium">Error: ${data.error}</p></li>`;
            return;
        }
        
        list.innerHTML = '';
        
        if (page === 1) {
            if (type === 'employees') {
                allEmployees = data.items || [];
            } else {
                allStudents = data.items || [];
            }
        }
        
        if (data.items.length === 0) {
            const emptyIcon = type === 'employees' 
                ? '<svg class="w-12 h-12 mx-auto mb-2 text-orange-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>'
                : '<svg class="w-12 h-12 mx-auto mb-2 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 14l9-5-9-5-9 5 9 5z"></path><path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path></svg>';
            const message = type === 'employees' ? 'All employees have logged in today!' : 'All students & parents have logged in today!';
            list.innerHTML = `<li class="text-center py-8">${emptyIcon}<p class="text-gray-500 font-medium">${message}</p><p class="text-gray-400 text-sm mt-1">Great attendance 🎉</p></li>`;
            pagination.classList.add('hidden');
        } else {
            const roleColors = {
                'teacher': { bg: 'bg-green-500', border: 'border-green-100', hover: 'hover:bg-green-50' },
                'registrar': { bg: 'bg-indigo-500', border: 'border-indigo-100', hover: 'hover:bg-indigo-50' },
                'hr': { bg: 'bg-orange-500', border: 'border-orange-100', hover: 'hover:bg-orange-50' },
                'cashier': { bg: 'bg-yellow-500', border: 'border-yellow-100', hover: 'hover:bg-yellow-50' },
                'guidance': { bg: 'bg-pink-500', border: 'border-pink-100', hover: 'hover:bg-pink-50' },
                'attendance': { bg: 'bg-teal-500', border: 'border-teal-100', hover: 'hover:bg-teal-50' },
                'student': { bg: 'bg-blue-500', border: 'border-blue-100', hover: 'hover:bg-blue-50' },
                'parent': { bg: 'bg-cyan-500', border: 'border-cyan-100', hover: 'hover:bg-cyan-50' }
            };
            
            data.items.forEach(item => {
                const li = document.createElement('li');
                const cleanItem = item.replace(/•\s*/, '').trim();
                const nameMatch = cleanItem.match(/^([^(]+)/);
                const name = nameMatch ? nameMatch[1].trim() : '';
                const roleMatch = cleanItem.match(/-\s*(\w+)\s*$/i);
                const role = roleMatch ? roleMatch[1].toLowerCase() : (type === 'employees' ? 'teacher' : 'student');
                const colors = roleColors[role] || (type === 'employees' ? roleColors['teacher'] : roleColors['student']);
                
                li.className = `flex items-center gap-3 p-3 rounded-lg border ${colors.border} ${colors.hover} transition-all`;
                
                let nameParts;
                if (name.includes(',')) {
                    nameParts = name.split(',').map(p => p.trim()).filter(p => p.length > 0);
                } else {
                    nameParts = name.split(' ').filter(p => p.length > 0);
                }
                
                let initials = '?';
                if (nameParts.length >= 2) {
                    initials = (nameParts[0][0] + nameParts[nameParts.length - 1][0]).toUpperCase();
                } else if (nameParts.length === 1 && nameParts[0].length >= 2) {
                    initials = nameParts[0].substring(0, 2).toUpperCase();
                }
                
                li.innerHTML = `
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 rounded-full ${colors.bg} flex items-center justify-center text-white font-bold text-sm shadow-md">
                            ${initials}
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">${item.replace(/•\s*/, '')}</p>
                        <p class="text-xs text-gray-500">Not logged in today</p>
                    </div>
                `;
                list.appendChild(li);
            });
            
            const total = data.total || 0;
            if (type === 'employees') {
                employeesTotal = total;
            } else {
                studentsTotal = total;
            }
            
            updateNotLoggedInPagination(type, page, total);
            
            if (total > notLoggedInItemsPerPage) {
                pagination.classList.remove('hidden');
            } else {
                pagination.classList.add('hidden');
            }
        }
        
    } catch (error) {
        const errorMessage = error.name === 'AbortError' ? 'Request timed out' : error.message;
        list.innerHTML = `<li class="text-center py-8">
            <svg class="w-12 h-12 text-red-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-red-500 font-medium">Error loading data</p>
            <p class="text-gray-500 text-sm mt-1">${errorMessage}</p>
            <button onclick="loadNotLoggedIn('${type}', ${page})" class="mt-3 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
                Retry
            </button>
        </li>`;
    } finally {
        if (loading) {
            loading.classList.add('hidden');
        }
    }
}

function updateNotLoggedInPagination(type, page, total) {
    const start = (page - 1) * notLoggedInItemsPerPage + 1;
    const end = Math.min(page * notLoggedInItemsPerPage, total);
    
    document.getElementById(`${type}-start`).textContent = start;
    document.getElementById(`${type}-end`).textContent = end;
    document.getElementById(`${type}-total`).textContent = total;
    
    const prevBtn = document.getElementById(`${type}-prev`);
    const nextBtn = document.getElementById(`${type}-next`);
    
    prevBtn.disabled = page === 1;
    nextBtn.disabled = end >= total;
}

function changeEmployeesPage(direction) {
    employeesPage += direction;
    if (employeesPage < 1) employeesPage = 1;
    loadNotLoggedIn('employees', employeesPage);
}

function changeStudentsPage(direction) {
    studentsPage += direction;
    if (studentsPage < 1) studentsPage = 1;
    loadNotLoggedIn('students', studentsPage);
}

function filterEmployees() {
    const searchTerm = document.getElementById('employee-search').value.toLowerCase();
    const roleFilter = document.getElementById('employee-role-filter').value.toLowerCase();
    
    const filtered = allEmployees.filter(emp => {
        const matchesSearch = emp.toLowerCase().includes(searchTerm);
        const matchesRole = roleFilter === 'all' || emp.toLowerCase().includes(roleFilter);
        return matchesSearch && matchesRole;
    });
    
    displayFilteredEmployees(filtered);
}

function filterStudents() {
    const searchTerm = document.getElementById('student-search').value.toLowerCase();
    const typeFilter = document.getElementById('student-type-filter').value.toLowerCase();
    
    const filtered = allStudents.filter(student => {
        const matchesSearch = student.toLowerCase().includes(searchTerm);
        const matchesType = typeFilter === 'all' || student.toLowerCase().includes(typeFilter);
        return matchesSearch && matchesType;
    });
    
    displayFilteredStudents(filtered);
}

function displayFilteredEmployees(employees) {
    const list = document.getElementById('employees-list');
    const pagination = document.getElementById('employees-pagination');
    
    list.innerHTML = '';
    
    if (employees.length === 0) {
        list.innerHTML = `<li class="text-center py-8">
            <svg class="w-12 h-12 mx-auto mb-2 text-orange-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            <p class="text-gray-500 font-medium">No employees found</p>
            <p class="text-gray-400 text-sm mt-1">Try adjusting your filters</p>
        </li>`;
        pagination.classList.add('hidden');
        return;
    }
    
    const roleColors = {
        'teacher': { bg: 'bg-green-500', border: 'border-green-100', hover: 'hover:bg-green-50' },
        'registrar': { bg: 'bg-indigo-500', border: 'border-indigo-100', hover: 'hover:bg-indigo-50' },
        'hr': { bg: 'bg-orange-500', border: 'border-orange-100', hover: 'hover:bg-orange-50' },
        'cashier': { bg: 'bg-yellow-500', border: 'border-yellow-100', hover: 'hover:bg-yellow-50' },
        'guidance': { bg: 'bg-pink-500', border: 'border-pink-100', hover: 'hover:bg-pink-50' },
        'attendance': { bg: 'bg-teal-500', border: 'border-teal-100', hover: 'hover:bg-teal-50' }
    };
    
    employees.forEach(emp => {
        const li = document.createElement('li');
        const cleanItem = emp.replace(/•\s*/, '').trim();
        const roleMatch = cleanItem.match(/-\s*(\w+)\s*$/i);
        const role = roleMatch ? roleMatch[1].toLowerCase() : 'teacher';
        const colors = roleColors[role] || roleColors['teacher'];
        
        li.className = `flex items-center gap-3 p-3 rounded-lg border ${colors.border} ${colors.hover} transition-all`;
        li.innerHTML = `
            <div class="flex-shrink-0">
                <div class="w-10 h-10 rounded-full ${colors.bg} flex items-center justify-center text-white font-bold text-sm shadow-md">
                    ${emp.substring(2, 4).toUpperCase()}
                </div>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">${emp.replace(/•\s*/, '')}</p>
                <p class="text-xs text-gray-500">Not logged in today</p>
            </div>
        `;
        list.appendChild(li);
    });
    
    pagination.classList.add('hidden');
}

function displayFilteredStudents(students) {
    const list = document.getElementById('students-list');
    const pagination = document.getElementById('students-pagination');
    
    list.innerHTML = '';
    
    if (students.length === 0) {
        list.innerHTML = `<li class="text-center py-8">
            <svg class="w-12 h-12 mx-auto mb-2 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            <p class="text-gray-500 font-medium">No students or parents found</p>
            <p class="text-gray-400 text-sm mt-1">Try adjusting your filters</p>
        </li>`;
        pagination.classList.add('hidden');
        return;
    }
    
    const roleColors = {
        'student': { bg: 'bg-blue-500', border: 'border-blue-100', hover: 'hover:bg-blue-50' },
        'parent': { bg: 'bg-cyan-500', border: 'border-cyan-100', hover: 'hover:bg-cyan-50' }
    };
    
    students.forEach(student => {
        const li = document.createElement('li');
        const cleanItem = student.replace(/•\s*/, '').trim();
        const isParent = cleanItem.toLowerCase().includes('parent');
        const role = isParent ? 'parent' : 'student';
        const colors = roleColors[role];
        
        li.className = `flex items-center gap-3 p-3 rounded-lg border ${colors.border} ${colors.hover} transition-all`;
        li.innerHTML = `
            <div class="flex-shrink-0">
                <div class="w-10 h-10 rounded-full ${colors.bg} flex items-center justify-center text-white font-bold text-sm shadow-md">
                    ${student.substring(2, 4).toUpperCase()}
                </div>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">${student.replace(/•\s*/, '')}</p>
                <p class="text-xs text-gray-500">Not logged in today</p>
            </div>
        `;
        list.appendChild(li);
    });
    
    pagination.classList.add('hidden');
}

function clearEmployeeFilters() {
    document.getElementById('employee-search').value = '';
    document.getElementById('employee-role-filter').value = 'all';
    employeesPage = 1;
    loadNotLoggedIn('employees', 1);
}

function clearStudentFilters() {
    document.getElementById('student-search').value = '';
    document.getElementById('student-type-filter').value = 'all';
    studentsPage = 1;
    loadNotLoggedIn('students', 1);
}

</script>

<!-- Login History Modal -->
<div id="login-history-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-7xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="bg-white/20 p-2 rounded-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-white">Login History</h2>
                        <p class="text-blue-100 text-sm">View and search past login records</p>
                    </div>
                </div>
                <button onclick="closeLoginHistory()" class="text-white hover:bg-white/20 p-2 rounded-lg transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="px-6 py-3 bg-gray-50 border-b border-gray-200">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2">
                    <label class="text-sm font-medium text-gray-700 whitespace-nowrap">User Type:</label>
                    <select id="history-user-type" onchange="updateHistoryRoleOptions(); autoSearchHistory();" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="all">All</option>
                        <option value="student">Student</option>
                        <option value="employee">Employee</option>
                        <option value="parent">Parent</option>
                    </select>
                </div>
                
                <div class="flex items-center gap-2">
                    <label class="text-sm font-medium text-gray-700 whitespace-nowrap">Role:</label>
                    <select id="history-role" onchange="autoSearchHistory()" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="all">All</option>
                    </select>
                </div>
                
                <div class="flex items-center gap-2 flex-1 min-w-[200px]">
                    <label class="text-sm font-medium text-gray-700 whitespace-nowrap">Search:</label>
                    <div class="relative flex-1">
                        <input type="text" id="history-search" placeholder="Name or ID..." oninput="debouncedHistorySearch()" class="w-full pl-8 pr-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <svg class="absolute left-2 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>
                
                <div class="flex items-center gap-2">
                    <label class="text-sm font-medium text-gray-700 whitespace-nowrap">From Date:</label>
                    <input type="date" id="history-date-from" onchange="if(validateDateRange(this)) { updateDateConstraints(); autoSearchHistory(); }" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <div class="flex items-center gap-2">
                    <label class="text-sm font-medium text-gray-700 whitespace-nowrap">To Date:</label>
                    <input type="date" id="history-date-to" onchange="if(validateDateRange(this)) { updateDateConstraints(); autoSearchHistory(); }" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>
                
                <button onclick="clearHistoryFilters()" class="px-4 py-1.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg text-sm font-medium transition-colors whitespace-nowrap">
                    Clear
                </button>
            </div>
        </div>
        
        <!-- Date Range Indicator -->
        <div id="history-date-indicator" class="hidden px-6 py-3 bg-blue-50 border-b border-blue-100">
            <div class="flex items-center gap-2 text-sm">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <span class="text-gray-600">Viewing records from:</span>
                <span id="history-date-range" class="font-semibold text-blue-700"></span>
            </div>
        </div>

        <!-- Table -->
        <div class="flex-1 overflow-auto">
            <div id="login-history-loading" class="flex items-center justify-center py-12">
                <div class="text-center">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-blue-200 border-t-blue-600 mb-4"></div>
                    <p class="text-gray-500">Loading login history...</p>
                </div>
            </div>
            
            <table id="login-history-table" class="w-full text-sm hidden">
                <thead class="bg-gray-50 border-b border-gray-200 sticky top-0">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">User Type</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">ID</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Name</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Role</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Date</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Login Time</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Logout Time</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Duration</th>
                    </tr>
                </thead>
                <tbody id="login-history-tbody" class="divide-y divide-gray-100">
                </tbody>
            </table>
            
            <div id="login-history-no-results" class="hidden flex items-center justify-center min-h-[400px]">
                <div class="text-center">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-gray-500 font-medium text-lg">No login records found</p>
                    <p class="text-gray-400 text-sm mt-2">Try adjusting your filters or select a date range</p>
                </div>
            </div>
            
            <div id="login-history-initial-message" class="flex items-center justify-center min-h-[400px]">
                <div class="text-center">
                    <svg class="w-16 h-16 mx-auto mb-4 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <p class="text-gray-600 font-medium text-lg mb-2">Search Login History</p>
                    <p class="text-gray-500 text-sm">Select filters and click Search to view login records</p>
                    <p class="text-gray-400 text-xs mt-2">💡 Tip: Leave dates empty to search all records</p>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div id="login-history-pagination" class="hidden px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
            <div class="text-sm text-gray-600">
                Showing <span id="login-history-start">1</span> to <span id="login-history-end">20</span> of <span id="login-history-total">0</span> records
            </div>
            <div class="flex gap-2">
                <button id="login-history-prev" onclick="changeHistoryPage(-1)" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                    Previous
                </button>
                <button id="login-history-next" onclick="changeHistoryPage(1)" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                    Next
                </button>
            </div>
        </div>
    </div>
</div>

</body>
</html>