<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['owner_id']) || $_SESSION['role'] !== 'owner') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../StudentLogin/db_conn.php';
require_once '../includes/log_system_notification.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'] ?? '';
    $action = $_POST['action'] ?? '';
    $owner_comments = $_POST['owner_comments'] ?? '';
    
    if (empty($request_id) || empty($action)) {
        echo json_encode(['success' => false, 'message' => 'Request ID and action are required']);
        exit;
    }
    
    if (!in_array($action, ['approve', 'reject'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
    }
    
    try {
        // Get the request details (support both HR and student deletions)
        $stmt = $conn->prepare("SELECT * FROM owner_approval_requests WHERE id = ? AND status = 'pending'");
        $stmt->bind_param('i', $request_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Request not found or already processed']);
            exit;
        }
        
        $request = $result->fetch_assoc();
        $stmt->close();
        
        $target_data = json_decode($request['target_data'], true);
        $request_details = json_decode($request['request_details'], true);
        $target_id = $request['target_id'];
        $request_type = $request['request_type'];
        
        // Determine if this is HR or student deletion
        $is_student_deletion = $request_type === 'student_deletion' || 
                              isset($target_data['student_id']) || 
                              isset($target_data['student_name']);
        $employee_id = $target_id; // Keep for backward compatibility
        
        $owner_name = $_SESSION['owner_name'] ?? 'Owner';
        $new_status = ($action === 'approve') ? 'approved' : 'rejected';
        
        // Update request status
        $update_stmt = $conn->prepare("UPDATE owner_approval_requests SET status = ?, reviewed_at = NOW(), reviewed_by = ?, owner_comments = ? WHERE id = ?");
        $update_stmt->bind_param('sssi', $new_status, $owner_name, $owner_comments, $request_id);
        $update_stmt->execute();
        $update_stmt->close();
        
        if ($action === 'approve') {
            // Perform the actual deletion
            // Use the original requester's name, not the approver's name
            $deleted_by = $request['requester_name'] ?? ($is_student_deletion ? 'Registrar' : 'Super Admin');
            $deletion_reason = $request_details['deletion_reason'] ?? 'Approved by Owner';
            
            if ($is_student_deletion) {
                // Delete student
                error_log("Deleting student: $target_id");
                $delete_stmt = $conn->prepare("UPDATE student_account SET deleted_at = NOW(), deleted_by = ?, deleted_reason = ? WHERE id_number = ?");
                $delete_stmt->bind_param('sss', $deleted_by, $deletion_reason, $target_id);
                
                if ($delete_stmt->execute()) {
                    $affected_rows = $delete_stmt->affected_rows;
                    error_log("Student deletion executed. Affected rows: $affected_rows");
                    
                    if ($affected_rows === 0) {
                        error_log("WARNING: No rows affected for student $target_id - student may not exist");
                    }
                    // Log system notification
                    try {
                        $student_name = $target_data['student_name'] ?? 'Student';
                        $notif_title = "Student Deletion Approved";
                        $notif_message = "Owner approved deletion of student: " . $student_name . " (ID: " . $target_id . ")";
                        
                        logSystemNotification(
                            $conn,
                            $notif_title,
                            $notif_message,
                            'success',
                            'Owner',
                            $owner_name,
                            'Registrar',
                            'student_deletion_approved',
                            'student_account',
                            $target_id,
                            $target_data,
                            null
                        );
                    } catch (Exception $notif_error) {
                        error_log("Error logging notification: " . $notif_error->getMessage());
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Student deletion approved and executed successfully'
                    ]);
                } else {
                    throw new Exception("Failed to delete student: " . $delete_stmt->error);
                }
                
                $delete_stmt->close();
            } else {
                // Delete employee
                $delete_stmt = $conn->prepare("UPDATE employees SET deleted_at = NOW(), deleted_by = ?, deletion_reason = ? WHERE id_number = ?");
                $delete_stmt->bind_param('sss', $deleted_by, $deletion_reason, $employee_id);
                
                if ($delete_stmt->execute()) {
                    // Log system notification
                    try {
                        $employee_name = $target_data['employee_name'] ?? 'Employee';
                        $notif_title = "HR Employee Deletion Approved";
                        $notif_message = "Owner approved deletion of employee: " . $employee_name . " (ID: " . $employee_id . ")";
                        
                        logSystemNotification(
                            $conn,
                            $notif_title,
                            $notif_message,
                            'success',
                            'Owner',
                            $owner_name,
                            'Super Admin',
                            'hr_employee_deletion_approved',
                            'employees',
                            $employee_id,
                            $target_data,
                            null
                        );
                    } catch (Exception $notif_error) {
                        error_log("Error logging notification: " . $notif_error->getMessage());
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Employee deletion approved and executed successfully'
                    ]);
                } else {
                    throw new Exception("Failed to delete employee: " . $delete_stmt->error);
                }
                
                $delete_stmt->close();
            }
        } else {
            // Rejection - just log notification
            try {
                if ($is_student_deletion) {
                    $student_name = $target_data['student_name'] ?? 'Student';
                    $notif_title = "Student Deletion Rejected";
                    $notif_message = "Owner rejected deletion request for student: " . $student_name . " (ID: " . $target_id . ")";
                    
                    logSystemNotification(
                        $conn,
                        $notif_title,
                        $notif_message,
                        'info',
                        'Owner',
                        $owner_name,
                        'Registrar',
                        'student_deletion_rejected',
                        'student_account',
                        $target_id,
                        $target_data,
                        null
                    );
                } else {
                    $employee_name = $target_data['employee_name'] ?? 'Employee';
                    $notif_title = "HR Employee Deletion Rejected";
                    $notif_message = "Owner rejected deletion request for employee: " . $employee_name . " (ID: " . $employee_id . ")";
                    
                    logSystemNotification(
                        $conn,
                        $notif_title,
                        $notif_message,
                        'info',
                        'Owner',
                        $owner_name,
                        'Super Admin',
                        'hr_employee_deletion_rejected',
                        'employees',
                        $employee_id,
                        $target_data,
                        null
                    );
                }
            } catch (Exception $notif_error) {
                error_log("Error logging notification: " . $notif_error->getMessage());
            }
            
            echo json_encode([
                'success' => true,
                'message' => ($is_student_deletion ? 'Student' : 'Employee') . ' deletion request rejected'
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

$conn->close();
?>
