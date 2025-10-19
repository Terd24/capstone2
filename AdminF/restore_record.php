<?php
session_start();
require_once '../StudentLogin/db_conn.php';

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$record_type = $input['record_type'] ?? '';
$record_id = $input['record_id'] ?? '';
$reason = $input['reason'] ?? '';
$start_date = $input['start_date'] ?? '';
$end_date = $input['end_date'] ?? '';

// Handle archive requests
if ($action === 'archive_login_logs' || $action === 'archive_attendance') {
    if (empty($start_date) || empty($end_date) || empty($reason)) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit;
    }
    
    try {
        if ($action === 'archive_login_logs') {
            $request_title = "Archive Login Logs";
            $request_description = "Request to archive login logs from $start_date to $end_date\n\nReason: $reason";
            $request_type = 'archive_login_logs';
            $requester_module = 'System Maintenance';
            $target_data = json_encode(['start_date' => $start_date, 'end_date' => $end_date]);
        } else {
            $request_title = "Archive Attendance Records";
            $request_description = "Request to archive attendance records from $start_date to $end_date\n\nReason: $reason";
            $request_type = 'archive_attendance';
            $requester_module = 'System Maintenance';
            $target_data = json_encode(['start_date' => $start_date, 'end_date' => $end_date]);
        }
        
        $priority = 'medium';
        $requester_name = $_SESSION['superadmin_name'] ?? 'Super Admin';
        $requester_role = 'superadmin';
        $target_id = $start_date . '_to_' . $end_date;
        
        $approval_stmt = $conn->prepare("INSERT INTO owner_approval_requests (request_title, request_description, request_type, priority, requester_name, requester_role, requester_module, target_id, target_data, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        
        if (!$approval_stmt) {
            echo json_encode(['success' => false, 'message' => 'Failed to prepare statement: ' . $conn->error]);
            exit;
        }
        
        $approval_stmt->bind_param("sssssssss", $request_title, $request_description, $request_type, $priority, $requester_name, $requester_role, $requester_module, $target_id, $target_data);
        
        if ($approval_stmt->execute()) {
            $insert_id = $approval_stmt->insert_id;
            echo json_encode([
                'success' => true, 
                'message' => 'Archive request submitted successfully! Waiting for Owner approval.',
                'request_id' => $insert_id,
                'debug' => [
                    'request_type' => $request_type,
                    'requester_role' => $requester_role,
                    'status' => 'pending'
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create approval request: ' . $approval_stmt->error]);
        }
        $approval_stmt->close();
        $conn->close();
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        exit;
    }
}

if ($action !== 'restore' || empty($record_type) || empty($record_id) || empty($reason)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

try {
    if ($record_type === 'student') {
        // Log what we're searching for
        file_put_contents('restore_debug.log', date('Y-m-d H:i:s') . " - Searching for student ID: '$record_id'\n", FILE_APPEND);
        
        $stmt = $conn->prepare("SELECT * FROM student_account WHERE id_number = ?");
        $stmt->bind_param("s", $record_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        file_put_contents('restore_debug.log', date('Y-m-d H:i:s') . " - Found rows: " . $result->num_rows . "\n", FILE_APPEND);
        
        if ($result->num_rows === 0) {
            // Try to find what IDs actually exist
            $all_ids = $conn->query("SELECT id_number FROM student_account WHERE deleted_at IS NOT NULL LIMIT 5");
            $existing = [];
            while ($row = $all_ids->fetch_assoc()) {
                $existing[] = $row['id_number'];
            }
            
            echo json_encode([
                'success' => false, 
                'message' => 'Student not found',
                'searched_for' => $record_id,
                'existing_deleted_ids' => $existing
            ]);
            exit;
        }
        
        $student = $result->fetch_assoc();
        $stmt->close();
        
        $request_title = "Restore Student: " . $student['first_name'] . " " . $student['last_name'];
        $request_description = "Request to restore deleted student with ID: " . $student['id_number'] . "\n\nReason: " . $reason;
        $request_type = 'restore_student';
        $priority = 'high';
        $requester_name = $_SESSION['superadmin_name'] ?? 'Super Admin';
        $requester_role = 'superadmin';
        $requester_module = 'Student Management';
        $target_id = $student['id_number'];
        $target_data = json_encode($student);
        
        $approval_stmt = $conn->prepare("INSERT INTO owner_approval_requests (request_title, request_description, request_type, priority, requester_name, requester_role, requester_module, target_id, target_data, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        $approval_stmt->bind_param("sssssssss", $request_title, $request_description, $request_type, $priority, $requester_name, $requester_role, $requester_module, $target_id, $target_data);
        
        if ($approval_stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Restore request submitted successfully! Waiting for Owner approval.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create approval request']);
        }
        $approval_stmt->close();
        
    } else if ($record_type === 'employee') {
        $stmt = $conn->prepare("SELECT * FROM employees WHERE id_number = ?");
        $stmt->bind_param("s", $record_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Employee not found']);
            exit;
        }
        
        $employee = $result->fetch_assoc();
        $stmt->close();
        
        $request_title = "Restore Employee: " . $employee['first_name'] . " " . $employee['last_name'];
        $request_description = "Request to restore deleted employee with ID: " . $employee['id_number'] . "\n\nReason: " . $reason;
        $request_type = 'restore_employee';
        $priority = 'high';
        $requester_name = $_SESSION['superadmin_name'] ?? 'Super Admin';
        $requester_role = 'superadmin';
        $requester_module = 'HR Management';
        $target_id = $employee['id_number'];
        $target_data = json_encode($employee);
        
        $approval_stmt = $conn->prepare("INSERT INTO owner_approval_requests (request_title, request_description, request_type, priority, requester_name, requester_role, requester_module, target_id, target_data, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        $approval_stmt->bind_param("sssssssss", $request_title, $request_description, $request_type, $priority, $requester_name, $requester_role, $requester_module, $target_id, $target_data);
        
        if ($approval_stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Restore request submitted successfully! Waiting for Owner approval.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create approval request']);
        }
        $approval_stmt->close();
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
