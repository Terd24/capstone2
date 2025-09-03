<?php
session_start();
include("../StudentLogin/db_conn.php");

// Handle different types of requests
if (isset($_GET['all']) && $_GET['all'] == '1') {
    // Return all students with their current schedule info
    $stmt = $conn->prepare(
        "SELECT sa.id_number, CONCAT(sa.first_name, ' ', sa.last_name) as full_name, sa.first_name, sa.last_name, sa.academic_track as program, sa.grade_level as year_section, sa.rfid_uid, sa.class_schedule, cs.section_name as current_section
         FROM student_account sa
         LEFT JOIN student_schedules ss ON sa.id_number = ss.student_id
         LEFT JOIN class_schedules cs ON ss.schedule_id = cs.id
         ORDER BY sa.first_name, sa.last_name 
         LIMIT 100"
    );
    $stmt->execute();
    $result = $stmt->get_result();
    
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    
    echo json_encode(['students' => $students]);
    exit;
}

if (isset($_GET['section'])) {
    // Search by section for bulk selection
    $section = $_GET['section'];
    $stmt = $conn->prepare(
        "SELECT id_number, CONCAT(first_name, ' ', last_name) as full_name, first_name, last_name, academic_track as program, grade_level as year_section, rfid_uid
         FROM student_account 
         WHERE grade_level LIKE ? OR academic_track LIKE ?
         ORDER BY first_name, last_name 
         LIMIT 50"
    );
    $searchTerm = "%$section%";
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    
    echo json_encode(['students' => $students]);
    exit;
}

// Regular search by name or ID with current schedule info
$query = $_GET['query'] ?? '';
if (!$query) {
    echo json_encode(['error' => 'Empty search']);
    exit;
}

$stmt = $conn->prepare(
    "SELECT sa.id_number, CONCAT(sa.first_name, ' ', sa.last_name) as full_name, sa.first_name, sa.last_name, sa.academic_track as program, sa.grade_level as year_section, sa.rfid_uid, sa.class_schedule, cs.section_name as current_section
     FROM student_account sa
     LEFT JOIN student_schedules ss ON sa.id_number = ss.student_id
     LEFT JOIN class_schedules cs ON ss.schedule_id = cs.id
     WHERE (CONCAT(sa.first_name, ' ', sa.last_name) LIKE ? OR sa.id_number LIKE ?)
     ORDER BY sa.first_name, sa.last_name
     LIMIT 10"
);
$searchTerm = "%$query%";
$stmt->bind_param("ss", $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}

echo json_encode(['students' => $students]);
