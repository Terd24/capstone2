<?php
session_start();

// Temporarily disable role check for testing
// if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'registrar') {
//     echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
//     exit();
// }

include '../StudentLogin/db_conn.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id'])) {
    $student_id = $_POST['student_id'];
    $selected_term = isset($_POST['term']) ? $_POST['term'] : null;

    try {
        // Get student info
        $student_query = "SELECT id_number, CONCAT(first_name, ' ', last_name) as name, academic_track as program, grade_level 
                         FROM student_account 
                         WHERE id_number = ? OR rfid_uid = ?";
        $student_stmt = $conn->prepare($student_query);
        $student_stmt->bind_param("ss", $student_id, $student_id);
        $student_stmt->execute();
        $student_result = $student_stmt->get_result();
        
        if ($student_result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Student not found']);
            exit();
        }
        
        $student = $student_result->fetch_assoc();
        
        // Get available terms for this student
        $terms_query = "SELECT DISTINCT school_year_term FROM grades_record WHERE id_number = ? ORDER BY school_year_term DESC";
        $terms_stmt = $conn->prepare($terms_query);
        $terms_stmt->bind_param("s", $student['id_number']);
        $terms_stmt->execute();
        $terms_result = $terms_stmt->get_result();
        
        $terms = [];
        while ($row = $terms_result->fetch_assoc()) {
            $terms[] = $row['school_year_term'];
        }
        
        // If no term selected, use the latest one
        if (!$selected_term && count($terms) > 0) {
            $selected_term = $terms[0];
        }
        
        // Get grades for selected term
        $grades_query = "SELECT id, subject, teacher_name, prelim, midterm, pre_finals, finals, school_year_term 
                        FROM grades_record 
                        WHERE id_number = ? AND school_year_term = ?";
        $grades_stmt = $conn->prepare($grades_query);
        $grades_stmt->bind_param("ss", $student['id_number'], $selected_term);
        $grades_stmt->execute();
        $grades_result = $grades_stmt->get_result();
        
        $grades = [];
        while ($row = $grades_result->fetch_assoc()) {
            $grades[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'student' => $student,
            'grades' => $grades,
            'terms' => $terms,
            'selected_term' => $selected_term
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
