<?php
session_start();
header('Content-Type: application/json');

// Require owner login
if (!isset($_SESSION['owner_id']) || $_SESSION['role'] !== 'owner') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include("../StudentLogin/db_conn.php");

$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$limit = 5; // Load 5 notifications at a time

// Get notifications with offset
$query = "SELECT * FROM system_notifications 
          ORDER BY is_read ASC, created_at DESC 
          LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($notification = $result->fetch_assoc()) {
    $notifications[] = $notification;
}

// Check if there are more notifications
$count_query = "SELECT COUNT(*) as total FROM system_notifications";
$count_result = $conn->query($count_query);
$total = $count_result->fetch_assoc()['total'];
$has_more = ($offset + $limit) < $total;

echo json_encode([
    'success' => true,
    'notifications' => $notifications,
    'has_more' => $has_more,
    'total' => $total
]);
?>
