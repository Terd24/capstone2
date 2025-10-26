<?php
// check_new_notifications.php - Simple polling endpoint for real-time notifications
// This is called periodically by JavaScript to check for new notifications

header('Content-Type: application/json');
header('Cache-Control: no-cache');

session_start();

// Only allow authenticated students
if (!isset($_SESSION['id_number']) || $_SESSION['role'] !== 'student') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$student_id = $_SESSION['id_number'];

include 'db_conn.php';

// Get the latest notification timestamp for this student
$stmt = $conn->prepare("SELECT MAX(date_sent) as latest_notification FROM notifications WHERE student_id = ?");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$latest = $result->fetch_assoc()['latest_notification'];
$stmt->close();

// Get unread count
$stmt = $conn->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE student_id = ? AND is_read = 0");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();
$unread_count = $result->fetch_assoc()['unread_count'];
$stmt->close();

echo json_encode([
    'latest_notification' => $latest,
    'unread_count' => $unread_count,
    'timestamp' => time()
]);

$conn->close();
?>
