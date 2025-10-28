<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'registrar') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../StudentLogin/db_conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_deletion_request') {
    $studentId = $_POST['student_id'] ?? '';
    
    if (empty($studentId)) {
        echo json_encode(['success' => false, 'message' => 'Student ID is required']);
        exit;
    }
    
    try {
        $requester_name = $_SESSION['registrar_name'] ?? $_SESSION['username'] ?? 'Registrar';
        
        // Delete the pending request
        $stmt = $conn->prepare("DELETE FROM owner_approval_requests 
                                WHERE target_id = ? 
                                AND request_type = 'student_deletion' 
                                AND status = 'pending'
                                AND requester_name = ?");
        $stmt->bind_param('ss', $studentId, $requester_name);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Deletion request cancelled successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'No pending deletion request found for this student'
                ]);
            }
        } else {
            throw new Exception("Failed to cancel deletion request: " . $stmt->error);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

$conn->close();
?>
