<?php
session_start();
require_once '../StudentLogin/db_conn.php';

// Check if user is registrar
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'registrar') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

// Get last check time
$lastCheck = isset($_GET['last_check']) ? $_GET['last_check'] : date('Y-m-d H:i:s', strtotime('-5 minutes'));

// Check for new document requests
$query = "SELECT dr.*, s.first_name, s.last_name, s.id_number as student_id
          FROM document_requests dr
          JOIN student_account s ON dr.student_id = s.id
          WHERE dr.status = 'pending'
          AND dr.request_date > ?
          ORDER BY dr.request_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $lastCheck);
$stmt->execute();
$result = $stmt->get_result();

$newRequests = [];
while ($row = $result->fetch_assoc()) {
    $newRequests[] = [
        'id' => $row['id'],
        'student_name' => $row['first_name'] . ' ' . $row['last_name'],
        'student_id' => $row['student_id'],
        'document_name' => $row['document_name'],
        'request_date' => $row['request_date']
    ];
}

echo json_encode([
    'success' => true,
    'new_requests' => $newRequests,
    'count' => count($newRequests),
    'current_time' => date('Y-m-d H:i:s')
]);

$stmt->close();
$conn->close();
?>
