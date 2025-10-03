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
    
    if ($action === 'restore' && $record_type && $record_id) {
        try {
            $conn->begin_transaction();
            
            // Determine table based on record type
            if ($record_type === 'student') {
                $table = 'student_account';
            } elseif ($record_type === 'employee') {
                $table = 'employees';
            } else {
                throw new Exception('Invalid record type');
            }
            
            // Get record data before restoration for logging
            $stmt = $conn->prepare("SELECT * FROM $table WHERE id_number = ? AND deleted_at IS NOT NULL");
            $stmt->bind_param("s", $record_id);
            $stmt->execute();
            $record_data = $stmt->get_result()->fetch_assoc();
            
            if (!$record_data) {
                throw new Exception('Record not found or not deleted');
            }
            
            // Restore the record (remove deleted_at timestamp)
            $stmt = $conn->prepare("UPDATE $table SET deleted_at = NULL, deleted_by = NULL, deleted_reason = NULL WHERE id_number = ?");
            $stmt->bind_param("s", $record_id);
            $stmt->execute();
            
            // Log the restoration
            $stmt = $conn->prepare("INSERT INTO deletion_log (action_type, record_id, record_table, performed_by, reason, record_data) VALUES ('restore', ?, ?, ?, 'Record restored by Super Admin', ?)");
            $record_json = json_encode($record_data);
            $performed_by = $_SESSION['superadmin_name'];
            $stmt->bind_param("ssss", $record_id, $table, $performed_by, $record_json);
            $stmt->execute();
            
            $conn->commit();
            
            echo json_encode(['success' => true, 'message' => ucfirst($record_type) . ' record restored successfully']);
            
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request parameters']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
