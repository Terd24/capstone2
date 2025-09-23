<?php
session_start();
include("../StudentLogin/db_conn.php");

// Pagination parameters
$limit  = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 15;
$offset = isset($_GET['offset']) ? max(0, intval($_GET['offset'])) : 0;
$fetchLimit = $limit + 1; // fetch one extra to know if there are more
// Optionally exclude students already in a specific schedule
$excludeScheduleId = isset($_GET['exclude_schedule_id']) ? intval($_GET['exclude_schedule_id']) : 0;

// Handle different types of requests
if (isset($_GET['all']) && $_GET['all'] == '1') {
    // Return all students with their current schedule info
    $sql = "SELECT sa.id_number, CONCAT(sa.first_name, ' ', sa.last_name) as full_name, sa.first_name, sa.last_name, sa.academic_track as program, sa.grade_level as year_section, sa.rfid_uid, sa.class_schedule, cs.section_name as current_section, ss.schedule_id
            FROM student_account sa
            LEFT JOIN student_schedules ss ON sa.id_number = ss.student_id
            LEFT JOIN class_schedules cs ON ss.schedule_id = cs.id";
    $types = '';
    $params = [];
    if ($excludeScheduleId > 0) {
        $sql .= " WHERE (ss.schedule_id IS NULL OR ss.schedule_id <> ?)";
        $types .= 'i';
        $params[] = $excludeScheduleId;
    }
    // Order: available first (no schedule), then alphabetically
    $sql .= " ORDER BY (ss.schedule_id IS NOT NULL) ASC, sa.last_name, sa.first_name LIMIT ? OFFSET ?";
    $types .= 'ii';
    $params[] = $fetchLimit; $params[] = $offset;
    $stmt = $conn->prepare($sql);
    if(!$stmt){ echo json_encode(['error'=>'DB prepare error','details'=>$conn->error]); exit; }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $has_more = count($students) > $limit;
    if ($has_more) { array_pop($students); }
    
    echo json_encode(['students' => $students, 'has_more' => $has_more]);
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
         LIMIT ? OFFSET ?"
    );
    $searchTerm = "%$section%";
    $stmt->bind_param("ssii", $searchTerm, $searchTerm, $fetchLimit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $has_more = count($students) > $limit;
    if ($has_more) { array_pop($students); }
    
    echo json_encode(['students' => $students, 'has_more' => $has_more]);
    exit;
}

// Regular search by name or ID with current schedule info
$query = $_GET['query'] ?? '';
if (!$query) {
    echo json_encode(['error' => 'Empty search']);
    exit;
}

$sql = "SELECT sa.id_number, CONCAT(sa.first_name, ' ', sa.last_name) as full_name, sa.first_name, sa.last_name, sa.academic_track as program, sa.grade_level as year_section, sa.rfid_uid, sa.class_schedule, cs.section_name as current_section, ss.schedule_id
        FROM student_account sa
        LEFT JOIN student_schedules ss ON sa.id_number = ss.student_id
        LEFT JOIN class_schedules cs ON ss.schedule_id = cs.id
        WHERE (
            CONCAT(sa.first_name, ' ', sa.last_name) LIKE ?
            OR sa.id_number LIKE ?
            OR sa.rfid_uid = ?
            OR sa.rfid_uid LIKE ?
        )";
if ($excludeScheduleId > 0) { $sql .= " AND (ss.schedule_id IS NULL OR ss.schedule_id <> ?)"; }
$sql .= " ORDER BY (ss.schedule_id IS NOT NULL) ASC, sa.last_name, sa.first_name LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if(!$stmt){ echo json_encode(['error'=>'DB prepare error','details'=>$conn->error]); exit; }
$searchTerm = "%$query%";
$rfidExact = $query;
$rfidLike = "%$query%";
if ($excludeScheduleId > 0) {
    $stmt->bind_param("ssssiii", $searchTerm, $searchTerm, $rfidExact, $rfidLike, $excludeScheduleId, $fetchLimit, $offset);
} else {
    $stmt->bind_param("ssssii", $searchTerm, $searchTerm, $rfidExact, $rfidLike, $fetchLimit, $offset);
}
$stmt->execute();
$result = $stmt->get_result();

$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}
$has_more = count($students) > $limit;
if ($has_more) { array_pop($students); }

echo json_encode(['students' => $students, 'has_more' => $has_more]);
