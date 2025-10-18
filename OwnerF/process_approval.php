<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'owner') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../StudentLogin/db_conn.php';

$requestId = $_POST['request_id'] ?? '';
$action = $_POST['action'] ?? ''; // 'approve' or 'reject'
$notes = $_POST['notes'] ?? '';

if (empty($requestId) || empty($action)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$reviewedBy = $_SESSION['username'] ?? 'Owner';
$status = $action === 'approve' ? 'approved' : 'rejected';

try {
    // Get request details
    $stmt = $conn->prepare("SELECT * FROM approval_requests WHERE id = ? AND status = 'pending'");
    $stmt->bind_param('i', $requestId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Request not found or already processed']);
        exit;
    }
    
    $request = $result->fetch_assoc();
    $stmt->close();
    
    // Update request status
    $stmt = $conn->prepare("UPDATE approval_requests SET status = ?, reviewed_by = ?, reviewed_at = NOW(), review_notes = ? WHERE id = ?");
    $stmt->bind_param('sssi', $status, $reviewedBy, $notes, $requestId);
    $stmt->execute();
    $stmt->close();
    
    // If approved, execute the action
    if ($action === 'approve') {
        $recordId = $request['record_id'];
        $requestType = $request['request_type'];
        $recordData = json_decode($request['record_data'], true);
        
        switch ($requestType) {
            case 'restore_student':
                $stmt = $conn->prepare("UPDATE students SET deleted_at = NULL WHERE id_number = ?");
                $stmt->bind_param('s', $recordId);
                $stmt->execute();
                break;
                
            case 'restore_employee':
                $stmt = $conn->prepare("UPDATE employees SET deleted_at = NULL WHERE id_number = ?");
                $stmt->bind_param('s', $recordId);
                $stmt->execute();
                break;
                
            case 'permanent_delete_student':
                $stmt = $conn->prepare("DELETE FROM students WHERE id_number = ?");
                $stmt->bind_param('s', $recordId);
                $stmt->execute();
                break;
                
            case 'permanent_delete_employee':
                $stmt = $conn->prepare("DELETE FROM employees WHERE id_number = ?");
                $stmt->bind_param('s', $recordId);
                $stmt->execute();
                break;
                
            case 'add_hr_employee':
                // Add the HR employee
                if ($recordData) {
                    // Start transaction
                    $conn->begin_transaction();
                    
                    try {
                        // Insert employee
                        $stmt = $conn->prepare("INSERT INTO employees (id_number, first_name, middle_name, last_name, position, department, email, phone, address, hire_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("ssssssssss", 
                            $recordData['id_number'],
                            $recordData['first_name'],
                            $recordData['middle_name'],
                            $recordData['last_name'],
                            $recordData['position'],
                            $recordData['department'],
                            $recordData['email'],
                            $recordData['phone'],
                            $recordData['address'],
                            $recordData['hire_date']
                        );
                        $stmt->execute();
                        
                        // Create account
                        $hashed_password = password_hash($recordData['password'], PASSWORD_DEFAULT);
                        $account_stmt = $conn->prepare("INSERT INTO employee_accounts (employee_id, username, password, role) VALUES (?, ?, ?, ?)");
                        $account_stmt->bind_param("ssss", 
                            $recordData['id_number'],
                            $recordData['username'],
                            $hashed_password,
                            $recordData['role']
                        );
                        $account_stmt->execute();
                        
                        $conn->commit();
                    } catch (Exception $e) {
                        $conn->rollback();
                        throw $e;
                    }
                }
                break;
                
            case 'delete_hr_employee':
                // Delete HR employee and their account
                $stmt = $conn->prepare("DELETE FROM employee_accounts WHERE employee_id = ?");
                $stmt->bind_param('s', $recordId);
                $stmt->execute();
                
                $stmt = $conn->prepare("DELETE FROM employees WHERE id_number = ?");
                $stmt->bind_param('s', $recordId);
                $stmt->execute();
                break;
        }
        
        if (isset($stmt)) {
            $stmt->close();
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => $action === 'approve' ? 'Request approved and executed successfully' : 'Request rejected',
        'action' => $action
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
