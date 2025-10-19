<?php
session_start();

// Check if user is logged in as superadmin
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../StudentLogin/db_conn.php';

header('Content-Type: application/json');

// Get the last check timestamp from the request
$last_check = isset($_GET['last_check']) ? $_GET['last_check'] : date('Y-m-d H:i:s', strtotime('-5 minutes'));

// Check for newly deleted students since last check
$students_query = "SELECT id_number, first_name, last_name, middle_name, grade_level, academic_track, 
                          deleted_at, deleted_by, deleted_reason 
                   FROM student_account 
                   WHERE deleted_at IS NOT NULL 
                   AND deleted_at > ? 
                   ORDER BY deleted_at DESC";

$stmt = $conn->prepare($students_query);
$stmt->bind_param("s", $last_check);
$stmt->execute();
$students_result = $stmt->get_result();

$new_deleted_students = [];
while ($row = $students_result->fetch_assoc()) {
    $new_deleted_students[] = [
        'id_number' => $row['id_number'],
        'first_name' => $row['first_name'],
        'last_name' => $row['last_name'],
        'middle_name' => $row['middle_name'],
        'grade_level' => $row['grade_level'],
        'academic_track' => $row['academic_track'],
        'deleted_at' => $row['deleted_at'],
        'deleted_by' => $row['deleted_by'],
        'deleted_reason' => $row['deleted_reason'],
        'type' => 'student'
    ];
}

// Check for newly deleted employees since last check
$employees_query = "SELECT id_number, first_name, last_name, middle_name, position, department,
                           deleted_at, deleted_by, deletion_reason as deleted_reason 
                    FROM employees 
                    WHERE deleted_at IS NOT NULL 
                    AND deleted_at > ? 
                    ORDER BY deleted_at DESC";

$stmt2 = $conn->prepare($employees_query);
$stmt2->bind_param("s", $last_check);
$stmt2->execute();
$employees_result = $stmt2->get_result();

$new_deleted_employees = [];
while ($row = $employees_result->fetch_assoc()) {
    $new_deleted_employees[] = [
        'id_number' => $row['id_number'],
        'first_name' => $row['first_name'],
        'last_name' => $row['last_name'],
        'middle_name' => $row['middle_name'],
        'position' => $row['position'],
        'department' => $row['department'],
        'deleted_at' => $row['deleted_at'],
        'deleted_by' => $row['deleted_by'],
        'deleted_reason' => $row['deleted_reason'],
        'type' => 'employee'
    ];
}

echo json_encode([
    'success' => true,
    'new_students' => $new_deleted_students,
    'new_employees' => $new_deleted_employees,
    'current_time' => date('Y-m-d H:i:s')
]);

$stmt->close();
$stmt2->close();
$conn->close();
?>
