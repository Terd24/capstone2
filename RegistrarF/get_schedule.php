<?php
session_start();
include("../StudentLogin/db_conn.php");

// Require registrar login
if (!isset($_SESSION['registrar_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$schedule_id = $_GET['id'] ?? null;

if (!$schedule_id) {
    echo json_encode(['success' => false, 'message' => 'Schedule ID required']);
    exit;
}

// Get schedule details
$stmt = $conn->prepare("SELECT * FROM class_schedules WHERE id = ?");
$stmt->bind_param("i", $schedule_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $schedule = $result->fetch_assoc();
    
    // Get day-specific schedules if they exist
    $day_stmt = $conn->prepare("SELECT day_name, start_time, end_time FROM day_schedules WHERE schedule_id = ?");
    $day_stmt->bind_param("i", $schedule_id);
    $day_stmt->execute();
    $day_result = $day_stmt->get_result();
    
    $day_schedules = [];
    while ($day_row = $day_result->fetch_assoc()) {
        $day_schedules[$day_row['day_name']] = [
            'start_time' => $day_row['start_time'],
            'end_time' => $day_row['end_time'],
            'enabled' => true
        ];
    }
    
    $schedule['day_schedules'] = $day_schedules;
    $schedule['has_day_schedules'] = count($day_schedules) > 0;
    
    echo json_encode(['success' => true, 'schedule' => $schedule]);
} else {
    echo json_encode(['success' => false, 'message' => 'Schedule not found']);
}
?>
