<?php
session_start();
include '../StudentLogin/db_conn.php';

// Check if user is logged in as teacher
$role = $_SESSION['role'] ?? '';
if ($role !== 'teacher') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grade_id'])) {
    $grade_id = intval($_POST['grade_id']);
    $teacher_id = $_SESSION['id_number'] ?? '';
    
    try {
        // Verify that this grade belongs to a subject the teacher handles
        // First get the grade record
        $check_query = "SELECT g.*, ts.teacher_id 
                       FROM grades_record g
                       LEFT JOIN teacher_subjects ts ON g.subject = ts.subject_name AND ts.teacher_id = ?
                       WHERE g.id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("si", $teacher_id, $grade_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Grade not found']);
            exit;
        }
        
        $grade = $result->fetch_assoc();
        
        // Check if teacher is authorized to delete this grade
        // Teacher can delete if they handle this subject
        if ($grade['teacher_id'] !== $teacher_id && $grade['teacher_id'] !== null) {
            echo json_encode(['success' => false, 'message' => 'You are not authorized to delete this grade']);
            exit;
        }
        
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
