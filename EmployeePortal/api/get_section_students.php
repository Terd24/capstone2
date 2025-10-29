<?php
session_start();
include('../../StudentLogin/db_conn.php');

// Require TEACHER role
$role = $_SESSION['role'] ?? '';
if ($role !== 'teacher') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$teacher_id = $_SESSION['id_number'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['section_name'])) {
    $section_name = $_GET['section_name'];

    try {
        // Verify this teacher is assigned to this section
        $verify_stmt = $conn->prepare("SELECT COUNT(*) as count FROM teacher_sections WHERE teacher_id = ? AND section_name = ?");
        $verify_stmt->bind_param("ss", $teacher_id, $section_name);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        $verify_row = $verify_result->fetch_assoc();
        
        if ($verify_row['count'] == 0) {
            echo json_encode(['success' => false, 'message' => 'You are not assigned to this section']);
            exit;
        }
        
        // Get the schedule_id for this section name
        $schedule_stmt = $conn->prepare("SELECT id FROM class_schedules WHERE section_name = ?");
        $schedule_stmt->bind_param("s", $section_name);
        $schedule_stmt->execute();
        $schedule_result = $schedule_stmt->get_result();
        $schedule_row = $schedule_result->fetch_assoc();
        
        if (!$schedule_row) {
            echo json_encode(['success' => false, 'message' => 'Schedule not found for this section']);
            exit;
        }
        
        $schedule_id = $schedule_row['id'];
        
        // Get students assigned to this schedule from student_schedules table
        $query = "SELECT sa.id_number, 
                  CONCAT(sa.first_name, ' ', sa.last_name) as student_name,
                  sa.grade_level,
                  sa.academic_track
                  FROM student_schedules ss
                  JOIN student_account sa ON ss.student_id = sa.id_number
                  WHERE ss.schedule_id = ?
                  ORDER BY sa.last_name, sa.first_name";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $schedule_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $students = [];
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'students' => $students
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
