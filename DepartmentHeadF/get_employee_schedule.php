<?php
header('Content-Type: application/json');
session_start();
include("../StudentLogin/db_conn.php");

// Allow HR and Department Head users
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['hr', 'department_head'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid schedule id']);
    exit;
}

// Fetch main schedule
$stmt = $conn->prepare("SELECT id, schedule_name, start_time, end_time, days FROM employee_work_schedules WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Schedule not found']);
    exit;
}
$schedule = $res->fetch_assoc();

// Fetch day-specific schedules (if any)
$days = [];
$day_stmt = $conn->prepare("SELECT day_name, start_time, end_time FROM employee_work_day_schedules WHERE schedule_id = ? ORDER BY FIELD(day_name,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')");
$day_stmt->bind_param("i", $id);
$day_stmt->execute();
$day_res = $day_stmt->get_result();
while ($row = $day_res->fetch_assoc()) {
    $days[$row['day_name']] = [
        'start_time' => $row['start_time'],
        'end_time'   => $row['end_time']
    ];
}

// Fetch teacher info
$teacher_name = $schedule['schedule_name']; // Default to schedule_name
$teacher_id = null;
$teacher_stmt = $conn->prepare("SELECT e.id_number, CONCAT(e.first_name, ' ', e.last_name) as full_name 
                                 FROM employee_schedules es 
                                 JOIN employees e ON es.employee_id = e.id_number 
                                 WHERE es.schedule_id = ? LIMIT 1");
$teacher_stmt->bind_param("i", $id);
$teacher_stmt->execute();
$teacher_res = $teacher_stmt->get_result();
if ($teacher_res->num_rows > 0) {
    $teacher_row = $teacher_res->fetch_assoc();
    $teacher_name = $teacher_row['full_name'];
    $teacher_id = $teacher_row['id_number'];
}

// Ensure teacher_subjects table exists
$conn->query("CREATE TABLE IF NOT EXISTS teacher_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id VARCHAR(20) NOT NULL,
    subject_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    UNIQUE KEY unique_teacher_subject (teacher_id, subject_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Ensure teacher_sections table exists
$conn->query("CREATE TABLE IF NOT EXISTS teacher_sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id VARCHAR(20) NOT NULL,
    section_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    UNIQUE KEY unique_teacher_section (teacher_id, section_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Fetch assigned subjects
$subjects = [];
if ($teacher_id) {
    $subj_stmt = $conn->prepare("SELECT subject_name FROM teacher_subjects WHERE teacher_id = ?");
    if ($subj_stmt) {
        $subj_stmt->bind_param("s", $teacher_id);
        $subj_stmt->execute();
        $subj_res = $subj_stmt->get_result();
        while ($row = $subj_res->fetch_assoc()) {
            $subjects[] = $row['subject_name'];
        }
    }
}

// Fetch assigned sections
$sections = [];
if ($teacher_id) {
    $sect_stmt = $conn->prepare("SELECT section_name FROM teacher_sections WHERE teacher_id = ?");
    if ($sect_stmt) {
        $sect_stmt->bind_param("s", $teacher_id);
        $sect_stmt->execute();
        $sect_res = $sect_stmt->get_result();
        while ($row = $sect_res->fetch_assoc()) {
            $sections[] = $row['section_name'];
        }
    }
}

$response = [
    'success' => true,
    'schedule' => [
        'id' => (int)$schedule['id'],
        'schedule_name' => $schedule['schedule_name'],
        'teacher_name' => $teacher_name,
        'teacher_id' => $teacher_id,
        'start_time' => $schedule['start_time'],
        'end_time' => $schedule['end_time'],
        'days' => $schedule['days'],
        'has_day_schedules' => count($days) > 0,
        'day_schedules' => $days,
        'subjects' => $subjects,
        'sections' => $sections
    ]
];

echo json_encode($response);
