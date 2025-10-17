<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../StudentLogin/db_conn.php';

$type = $_GET['type'] ?? 'login';
$search = $_GET['search'] ?? '';

if ($type === 'login') {
    $query = "SELECT * FROM login_logs_archive";
    if ($search) {
        $query .= " WHERE username LIKE ? OR ip_address LIKE ?";
    }
    $query .= " ORDER BY archived_at DESC LIMIT 100";
    
    if ($search) {
        $stmt = $conn->prepare($query);
        $search_param = "%$search%";
        $stmt->bind_param('ss', $search_param, $search_param);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($query);
    }
    
    $records = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
        }
    }
    
    echo json_encode(['success' => true, 'records' => $records, 'type' => 'login']);
    
} else {
    $query = "SELECT * FROM attendance_archive";
    if ($search) {
        $query .= " WHERE id_number LIKE ? OR name LIKE ?";
    }
    $query .= " ORDER BY archived_at DESC LIMIT 100";
    
    if ($search) {
        $stmt = $conn->prepare($query);
        $search_param = "%$search%";
        $stmt->bind_param('ss', $search_param, $search_param);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($query);
    }
    
    $records = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
        }
    }
    
    echo json_encode(['success' => true, 'records' => $records, 'type' => 'attendance']);
}

$conn->close();
?>
