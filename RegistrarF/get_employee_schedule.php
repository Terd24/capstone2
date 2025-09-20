<?php
header('Content-Type: application/json');
session_start();
include("../StudentLogin/db_conn.php");

if (!isset($_SESSION['registrar_id'])) {
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

$response = [
    'success' => true,
    'schedule' => [
        'id' => (int)$schedule['id'],
        'schedule_name' => $schedule['schedule_name'],
        'start_time' => $schedule['start_time'],
        'end_time' => $schedule['end_time'],
        'days' => $schedule['days'],
        'has_day_schedules' => count($days) > 0,
        'day_schedules' => $days
    ]
];

echo json_encode($response);
