<?php
session_start();
header('Content-Type: application/json');
ob_start();

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    ob_end_clean();
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$conn = new mysqli('localhost', 'root', '', 'onecci_db');
if ($conn->connect_error) {
    ob_end_clean();
    die(json_encode(['success' => false, 'message' => 'DB failed']));
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);
$mode = isset($data['maintenance_mode']) ? $data['maintenance_mode'] : 'disabled';

$conn->query("CREATE TABLE IF NOT EXISTS system_config (config_key VARCHAR(50) PRIMARY KEY, config_value TEXT, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)");

$val = ($mode === 'enabled') ? '1' : '0';

$stmt = $conn->prepare("INSERT INTO system_config (config_key, config_value) VALUES ('maintenance_mode', ?) ON DUPLICATE KEY UPDATE config_value = ?");
$stmt->bind_param('ss', $val, $val);

$success = $stmt->execute();
$stmt->close();
$conn->close();

ob_end_clean();

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Updated', 'maintenance_mode' => $mode]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed']);
}
exit;
