<?php
session_start();
include '../StudentLogin/db_conn.php';

// Check if user is logged in as registrar
if (!isset($_SESSION['registrar_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grade_id'])) {
    $grade_id = intval($_POST['grade_id']);
    
    try {
        // Delete the grade record
        $delete_query = "DELETE FROM grades_record WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("i", $grade_id);
        
        if ($delete_stmt->execute()) {
            if ($delete_stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Grade deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Grade not found']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete grade']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
