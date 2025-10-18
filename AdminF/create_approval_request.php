<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../StudentLogin/db_conn.php';

$requestType = $_POST['request_type'] ?? '';
$recordId = $_POST['record_id'] ?? '';
$recordName = $_POST['record_name'] ?? '';
$recordData = $_POST['record_data'] ?? '{}';

if (empty($requestType) || empty($recordId)) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$requestedBy = $_SESSION['superadmin_name'] ?? $_SESSION['username'] ?? 'SuperAdmin';

try {
    $stmt = $conn->prepare("INSERT INTO approval_requests (request_type, record_id, record_name, record_data, requested_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('sssss', $requestType, $recordId, $recordName, $recordData, $requestedBy);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Approval request sent to Owner successfully',
            'request_id' => $stmt->insert_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create approval request']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
