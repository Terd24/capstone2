<?php
session_start();
header('Content-Type: application/json');

// Only SuperAdmin can toggle maintenance mode
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../StudentLogin/db_conn.php';

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? ''; // 'disable' only

// Only allow direct disable (turning OFF)
if ($action !== 'disable') {
    echo json_encode(['success' => false, 'message' => 'Only disable action is allowed without approval']);
    exit;
}

try {
    // Create system_config table if not exists
    $conn->query("CREATE TABLE IF NOT EXISTS system_config (
        config_key VARCHAR(50) PRIMARY KEY,
        config_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    $newValue = '0'; // Disable = 0
    
    $stmt = $conn->prepare("INSERT INTO system_config (config_key, config_value) 
                            VALUES ('maintenance_mode', ?) 
                            ON DUPLICATE KEY UPDATE config_value = ?");
    $stmt->bind_param('ss', $newValue, $newValue);
    
    if ($stmt->execute()) {
        // Log the action
        $superadmin_name = $_SESSION['superadmin_name'] ?? 'Super Admin';
        error_log("Maintenance mode disabled by SuperAdmin: $superadmin_name");
        
        echo json_encode([
            'success' => true,
            'message' => 'Maintenance mode disabled successfully',
            'maintenance_mode' => 'disabled'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to disable maintenance mode']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    error_log("Error disabling maintenance mode: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
