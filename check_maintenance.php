<?php
// Function to check if maintenance mode is enabled
function isMaintenanceMode() {
    $conn = new mysqli('localhost', 'root', '', 'onecci_db');
    if ($conn->connect_error) {
        return false;
    }
    
    $result = $conn->query("SELECT config_value FROM system_config WHERE config_key = 'maintenance_mode'");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $conn->close();
        return $row['config_value'] === 'enabled';
    }
    
    $conn->close();
    return false;
}

// Function to check if user is admin
function isAdminUser() {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    $role = strtolower($_SESSION['role'] ?? '');
    return in_array($role, ['superadmin', 'hr', 'admin', 'owner']);
}

// Check maintenance mode and redirect if needed
function checkMaintenanceMode() {
    if (isMaintenanceMode() && !isAdminUser()) {
        header('Location: /onecci/maintenance.php');
        exit;
    }
}
?>
