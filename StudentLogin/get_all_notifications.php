<?php
// get_all_notifications.php - Fetch all notifications for real-time updates
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

// Get all notifications
$stmt = $conn->prepare("SELECT id, message, date_sent, is_read FROM notifications WHERE student_id = ? ORDER BY date_sent DESC");
$stmt->bind_param("s", $student_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}
$stmt->close();

// Get unread count
$stmt2 = $conn->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE student_id = ? AND is_read = 0");
$stmt2->bind_param("s", $student_id);
$stmt2->execute();
$result2 = $stmt2->get_result();
$unread_count = $result2->fetch_assoc()['unread_count'];
$stmt2->close();

echo json_encode([
    'success' => true,
    'notifications' => $notifications,
    'unread_count' => $unread_count,
    'timestamp' => time()
]);

$conn->close();
?>
