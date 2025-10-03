<?php
session_start();

// Database connection
$conn = new mysqli('localhost', 'root', '', 'onecci_db');
if ($conn->connect_error) {
    die('DB connection failed: ' . $conn->connect_error);
}

// Check if user is Super Admin
if (!isset($_SESSION['superadmin_name'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $action = $input['action'] ?? '';
    $record_type = $input['record_type'] ?? '';
    $record_id = $input['record_id'] ?? '';
    $reason = $input['reason'] ?? '';
    
    if ($action === 'request_permanent_delete' && $record_type && $record_id && $reason) {
        try {
            // Determine table and request type
            if ($record_type === 'student') {
                $table = 'student_account';
                $request_type = 'permanent_delete_student';
            } elseif ($record_type === 'employee') {
                $table = 'employees';
                $request_type = 'permanent_delete_employee';
            } else {
                throw new Exception('Invalid record type');
            }
            
            // Check if record exists and is soft-deleted
            $stmt = $conn->prepare("SELECT id_number FROM $table WHERE id_number = ? AND deleted_at IS NOT NULL");
            $stmt->bind_param("s", $record_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception('Record not found or not deleted');
            }
            
            // Check if there's already a pending request for this record
            $stmt = $conn->prepare("SELECT id FROM approval_requests WHERE record_id = ? AND record_table = ? AND status = 'pending'");
            $stmt->bind_param("ss", $record_id, $table);
            $stmt->execute();
            $existing = $stmt->get_result();
            
            if ($existing->num_rows > 0) {
                throw new Exception('There is already a pending permanent deletion request for this record');
            }
            
            // Create approval request
            $stmt = $conn->prepare("INSERT INTO approval_requests (request_type, record_id, record_table, requested_by, request_reason) VALUES (?, ?, ?, ?, ?)");
            $requested_by = $_SESSION['superadmin_name'];
            $stmt->bind_param("sssss", $request_type, $record_id, $table, $requested_by, $reason);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'Permanent deletion request sent to School Owner for approval']);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request parameters']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
