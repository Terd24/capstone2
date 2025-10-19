<?php
// Set error handler to catch all errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// Start output buffering
ob_start();

try {
    session_start();
    require_once '../StudentLogin/db_conn.php';
    
    // Clear buffer and set JSON header
    ob_end_clean();
    header('Content-Type: application/json');
    
    // Check if user is Super Admin
    if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
    }
    
    // Get input data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit;
    }
    
    $action = $data['action'] ?? '';
    $record_type = $data['record_type'] ?? '';
    $record_id = $data['record_id'] ?? '';
    $reason = $data['reason'] ?? '';
    
    if ($action !== 'archive' || empty($record_type) || empty($record_id) || empty($reason)) {
        echo json_encode(['success' => false, 'message' => 'Invalid request parameters']);
        exit;
    }
    
    // Ensure approval requests table exists
    $conn->query("CREATE TABLE IF NOT EXISTS owner_approval_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        request_title VARCHAR(255) NOT NULL,
        request_description TEXT NOT NULL,
        request_type ENUM('delete_account', 'restore_account', 'system_maintenance', 'data_modification', 'user_management', 'add_hr_employee', 'delete_hr_employee', 'restore_student', 'restore_employee', 'archive_student', 'archive_employee', 'other') NOT NULL,
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
    
    // Archive student - Create approval request
    if ($record_type === 'student') {
        // Get student data using id_number
        $stmt = $conn->prepare("SELECT * FROM student_account WHERE id_number = ? AND deleted_at IS NOT NULL");
        if (!$stmt) {
            throw new Exception('Failed to prepare statement: ' . $conn->error);
        }
        
        $stmt->bind_param("s", $record_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Student not found or not deleted']);
            exit;
        }
        
        $student = $result->fetch_assoc();
        $stmt->close();
        
        // Create approval request instead of archiving directly
        $request_title = "Archive Student: " . $student['first_name'] . " " . $student['last_name'];
        $request_description = "Request to permanently archive deleted student with ID: " . $student['id_number'] . "\n\nReason: " . $reason;
        $request_type = 'archive_student';
        $priority = 'critical';
        $requester_name = $_SESSION['superadmin_name'] ?? 'Super Admin';
        $requester_role = 'superadmin';
        $requester_module = 'Student Management';
        $target_id = $student['id_number'];
        $target_data = json_encode($student);
        
        $approval_stmt = $conn->prepare("INSERT INTO owner_approval_requests (request_title, request_description, request_type, priority, requester_name, requester_role, requester_module, target_id, target_data, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        $approval_stmt->bind_param("sssssssss", $request_title, $request_description, $request_type, $priority, $requester_name, $requester_role, $requester_module, $target_id, $target_data);
        
        if ($approval_stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Archive request submitted successfully! Waiting for Owner approval.',
                'student_id' => $student['id_number'],
                'requires_approval' => true
            ]);
        } else {
            throw new Exception("Failed to create approval request: " . $approval_stmt->error);
        }
        
        $approval_stmt->close();
        exit;
    }
    
    // Archive employee - Create approval request
    elseif ($record_type === 'employee') {
        // Get employee data using id_number
        $stmt = $conn->prepare("SELECT * FROM employees WHERE id_number = ? AND deleted_at IS NOT NULL");
        if (!$stmt) {
            throw new Exception('Failed to prepare statement: ' . $conn->error);
        }
        
        $stmt->bind_param("s", $record_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Employee not found or not deleted']);
            exit;
        }
        
        $employee = $result->fetch_assoc();
        $stmt->close();
        
        // Create approval request instead of archiving directly
        $request_title = "Archive Employee: " . $employee['first_name'] . " " . $employee['last_name'];
        $request_description = "Request to permanently archive deleted employee with ID: " . $employee['id_number'] . "\n\nReason: " . $reason;
        $request_type = 'archive_employee';
        $priority = 'critical';
        $requester_name = $_SESSION['superadmin_name'] ?? 'Super Admin';
        $requester_role = 'superadmin';
        $requester_module = 'HR Management';
        $target_id = $employee['id_number'];
        $target_data = json_encode($employee);
        
        $approval_stmt = $conn->prepare("INSERT INTO owner_approval_requests (request_title, request_description, request_type, priority, requester_name, requester_role, requester_module, target_id, target_data, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        $approval_stmt->bind_param("sssssssss", $request_title, $request_description, $request_type, $priority, $requester_name, $requester_role, $requester_module, $target_id, $target_data);
        
        if ($approval_stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Archive request submitted successfully! Waiting for Owner approval.',
                'employee_id' => $employee['id_number'],
                'requires_approval' => true
            ]);
        } else {
            throw new Exception("Failed to create approval request: " . $approval_stmt->error);
        }
        
        $approval_stmt->close();
        exit;
    }
    
    else {
        echo json_encode(['success' => false, 'message' => 'Invalid record type']);
        exit;
    }
    
    // OLD CODE BELOW - KEPT FOR REFERENCE BUT NOT EXECUTED
    // This code is now replaced by approval requests above
    if (false) {
        $student = $result->fetch_assoc();
        $archived_by = $_SESSION['superadmin_name'] ?? 'Super Admin';
        $archive_reason = $student['deleted_reason'] ?? 'No reason provided';
        
        // Prepare ALL student values matching actual student_account table
        $original_id = (int)$student['id'];
        $lrn = $conn->real_escape_string($student['lrn'] ?? '');
        $password = $conn->real_escape_string($student['password'] ?? '');
        $academic_track = $conn->real_escape_string($student['academic_track'] ?? '');
        $enrollment_status = $conn->real_escape_string($student['enrollment_status'] ?? '');
        $school_type = $conn->real_escape_string($student['school_type'] ?? '');
        $last_name = $conn->real_escape_string($student['last_name'] ?? '');
        $first_name = $conn->real_escape_string($student['first_name'] ?? '');
        $middle_name = $conn->real_escape_string($student['middle_name'] ?? '');
        $school_year = $conn->real_escape_string($student['school_year'] ?? '');
        $grade_level = $conn->real_escape_string($student['grade_level'] ?? '');
        $semester = $conn->real_escape_string($student['semester'] ?? '');
        $dob = isset($student['dob']) && $student['dob'] ? "'" . $conn->real_escape_string($student['dob']) . "'" : 'NULL';
        $birthplace = $conn->real_escape_string($student['birthplace'] ?? '');
        $gender = $conn->real_escape_string($student['gender'] ?? '');
        $religion = $conn->real_escape_string($student['religion'] ?? '');
        $credentials = $conn->real_escape_string($student['credentials'] ?? '');
        $payment_mode = $conn->real_escape_string($student['payment_mode'] ?? '');
        $address = $conn->real_escape_string($student['address'] ?? '');
        $father_name = $conn->real_escape_string($student['father_name'] ?? '');
        $father_occupation = $conn->real_escape_string($student['father_occupation'] ?? '');
        $father_contact = $conn->real_escape_string($student['father_contact'] ?? '');
        $mother_name = $conn->real_escape_string($student['mother_name'] ?? '');
        $mother_occupation = $conn->real_escape_string($student['mother_occupation'] ?? '');
        $mother_contact = $conn->real_escape_string($student['mother_contact'] ?? '');
        $guardian_name = $conn->real_escape_string($student['guardian_name'] ?? '');
        $guardian_occupation = $conn->real_escape_string($student['guardian_occupation'] ?? '');
        $guardian_contact = $conn->real_escape_string($student['guardian_contact'] ?? '');
        $last_school = $conn->real_escape_string($student['last_school'] ?? '');
        $last_school_year = $conn->real_escape_string($student['last_school_year'] ?? '');
        $id_number = $conn->real_escape_string($student['id_number'] ?? '');
        $username = $conn->real_escape_string($student['username'] ?? '');
        $rfid_uid = $conn->real_escape_string($student['rfid_uid'] ?? '');
        $created_at = isset($student['created_at']) && $student['created_at'] ? "'" . $conn->real_escape_string($student['created_at']) . "'" : 'NULL';
        $class_schedule = $conn->real_escape_string($student['class_schedule'] ?? '');
        $deleted_at = isset($student['deleted_at']) && $student['deleted_at'] ? "'" . $conn->real_escape_string($student['deleted_at']) . "'" : 'NULL';
        $deleted_by = $conn->real_escape_string($student['deleted_by'] ?? '');
        $deleted_reason = $conn->real_escape_string($student['deleted_reason'] ?? '');
        $must_change_password = isset($student['must_change_password']) ? (int)$student['must_change_password'] : 0;
        $archived_by_esc = $conn->real_escape_string($archived_by);
        $archive_reason_esc = $conn->real_escape_string($archive_reason);
        
        // Insert into archive with ALL columns
        $insert_query = "INSERT INTO archived_students (
            original_id, lrn, password, academic_track, enrollment_status, school_type,
            last_name, first_name, middle_name, school_year, grade_level, semester,
            dob, birthplace, gender, religion, credentials, payment_mode, address,
            father_name, father_occupation, father_contact,
            mother_name, mother_occupation, mother_contact,
            guardian_name, guardian_occupation, guardian_contact,
            last_school, last_school_year, id_number, username, rfid_uid,
            created_at, class_schedule, deleted_at, deleted_by, deleted_reason,
            must_change_password, archived_at, archived_by, archive_reason
        ) VALUES (
            $original_id, '$lrn', '$password', '$academic_track', '$enrollment_status', '$school_type',
            '$last_name', '$first_name', '$middle_name', '$school_year', '$grade_level', '$semester',
            $dob, '$birthplace', '$gender', '$religion', '$credentials', '$payment_mode', '$address',
            '$father_name', '$father_occupation', '$father_contact',
            '$mother_name', '$mother_occupation', '$mother_contact',
            '$guardian_name', '$guardian_occupation', '$guardian_contact',
            '$last_school', '$last_school_year', '$id_number', '$username', '$rfid_uid',
            $created_at, '$class_schedule', $deleted_at, '$deleted_by', '$deleted_reason',
            $must_change_password, NOW(), '$archived_by_esc', '$archive_reason_esc'
        )";
        
        if (!$conn->query($insert_query)) {
            throw new Exception('Failed to archive student: ' . $conn->error);
        }
        
        // Delete from student_account table permanently
        $delete_stmt = $conn->prepare("DELETE FROM student_account WHERE id = ?");
        $delete_stmt->bind_param("i", $record_id);
        $delete_stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Student archived successfully',
            'student_id' => $student['id_number'] ?? $record_id
        ]);
        exit;
    }
    
    // Archive employee
    elseif ($record_type === 'employee') {
        // Get employee data
        $stmt = $conn->prepare("SELECT * FROM employees WHERE id = ? AND deleted_at IS NOT NULL");
        if (!$stmt) {
            throw new Exception('Failed to prepare statement: ' . $conn->error);
        }
        
        $stmt->bind_param("i", $record_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Employee not found or not deleted']);
            exit;
        }
        
        $employee = $result->fetch_assoc();
        $archived_by = $_SESSION['superadmin_name'] ?? 'Super Admin';
        $archive_reason = $employee['deleted_reason'] ?? 'No reason provided';
        
        // Prepare ALL employee values matching actual employee table
        $original_id = (int)$employee['id'];
        $id_number = $conn->real_escape_string($employee['id_number'] ?? '');
        $first_name = $conn->real_escape_string($employee['first_name'] ?? '');
        $middle_name = $conn->real_escape_string($employee['middle_name'] ?? '');
        $last_name = $conn->real_escape_string($employee['last_name'] ?? '');
        $position = $conn->real_escape_string($employee['position'] ?? '');
        $department = $conn->real_escape_string($employee['department'] ?? '');
        $email = $conn->real_escape_string($employee['email'] ?? '');
        $phone = $conn->real_escape_string($employee['phone'] ?? '');
        $address = $conn->real_escape_string($employee['address'] ?? '');
        $created_at = isset($employee['created_at']) && $employee['created_at'] ? "'" . $conn->real_escape_string($employee['created_at']) . "'" : 'NULL';
        $hire_date = isset($employee['hire_date']) && $employee['hire_date'] ? "'" . $conn->real_escape_string($employee['hire_date']) . "'" : 'NULL';
        $rfid_uid = $conn->real_escape_string($employee['rfid_uid'] ?? '');
        $deleted_at = isset($employee['deleted_at']) && $employee['deleted_at'] ? "'" . $conn->real_escape_string($employee['deleted_at']) . "'" : 'NULL';
        $deleted_by = $conn->real_escape_string($employee['deleted_by'] ?? '');
        $deleted_reason = $conn->real_escape_string($employee['deleted_reason'] ?? '');
        $archive_scheduled = isset($employee['archive_scheduled']) ? (int)$employee['archive_scheduled'] : 0;
        $archive_scheduled_by = $conn->real_escape_string($employee['archive_scheduled_by'] ?? '');
        $archive_scheduled_at = isset($employee['archive_scheduled_at']) && $employee['archive_scheduled_at'] ? "'" . $conn->real_escape_string($employee['archive_scheduled_at']) . "'" : 'NULL';
        $archived_by_esc = $conn->real_escape_string($archived_by);
        $archive_reason_esc = $conn->real_escape_string($archive_reason);
        
        // Insert into archive with ALL columns
        $insert_query = "INSERT INTO archived_employees (
            original_id, id_number, first_name, middle_name, last_name,
            position, department, email, phone, address, created_at, hire_date,
            rfid_uid, deleted_at, deleted_by, deleted_reason,
            archive_scheduled, archive_scheduled_by, archive_scheduled_at,
            archived_at, archived_by, archive_reason
        ) VALUES (
            $original_id, '$id_number', '$first_name', '$middle_name', '$last_name',
            '$position', '$department', '$email', '$phone', '$address', $created_at, $hire_date,
            '$rfid_uid', $deleted_at, '$deleted_by', '$deleted_reason',
            $archive_scheduled, '$archive_scheduled_by', $archive_scheduled_at,
            NOW(), '$archived_by_esc', '$archive_reason_esc'
        )";
        
        if (!$conn->query($insert_query)) {
            throw new Exception('Failed to archive employee: ' . $conn->error);
        }
        
        // Delete from employees table permanently
        $delete_stmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
        $delete_stmt->bind_param("i", $record_id);
        $delete_stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Employee archived successfully',
            'employee_id' => $employee['id_number']
        ]);
        exit;
    }
    
    else {
        echo json_encode(['success' => false, 'message' => 'Invalid record type']);
        exit;
    }
    
} catch (Exception $e) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
    exit;
} catch (Error $e) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Fatal error: ' . $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine()
    ]);
    exit;
}
?>
