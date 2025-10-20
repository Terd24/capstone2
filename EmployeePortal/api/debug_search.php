<?php
session_start();
header('Content-Type: application/json');

require_once("../../StudentLogin/db_conn.php");

$employee_id = $_SESSION['id_number'] ?? '';

// Get teacher's assigned sections
$section_query = "SELECT ws.schedule_name 
                  FROM employees e
                  LEFT JOIN employee_schedules es ON e.id_number = es.employee_id
                  LEFT JOIN employee_work_schedules ws ON es.schedule_id = ws.id
                  WHERE e.id_number = ? LIMIT 1";
$section_stmt = $conn->prepare($section_query);
$section_stmt->bind_param('s', $employee_id);
$section_stmt->execute();
$section_result = $section_stmt->get_result();
$teacher_section = $section_result->fetch_assoc();

// Get all students with their grade_level
$students_query = "SELECT id_number, CONCAT(first_name, ' ', last_name) as name, grade_level, academic_track FROM student_account LIMIT 10";
$students_result = $conn->query($students_query);
$students = [];
while ($row = $students_result->fetch_assoc()) {
    $students[] = $row;
}

echo json_encode([
    'teacher_id' => $employee_id,
    'teacher_sections' => $teacher_section['schedule_name'] ?? 'NONE',
    'sample_students' => $students
], JSON_PRETTY_PRINT);
