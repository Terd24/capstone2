<?php
session_start();

// Record logout time before destroying session
if (isset($_SESSION['id_number']) || isset($_SESSION['username'])) {
    require_once '../StudentLogin/db_conn.php';
    
    $id_number = $_SESSION['id_number'] ?? '';
    $username = $_SESSION['username'] ?? '';
    
    // Simple direct update - try both id_number and username
    if ($id_number) {
        $conn->query("UPDATE login_activity 
            SET logout_time = NOW(), 
                session_duration = TIMESTAMPDIFF(SECOND, login_time, NOW())
            WHERE id_number = '" . $conn->real_escape_string($id_number) . "'
            AND logout_time IS NULL 
            ORDER BY login_time DESC 
            LIMIT 1");
    }
    
    if ($username) {
        $conn->query("UPDATE login_activity 
            SET logout_time = NOW(), 
                session_duration = TIMESTAMPDIFF(SECOND, login_time, NOW())
            WHERE username = '" . $conn->real_escape_string($username) . "'
            AND logout_time IS NULL 
            ORDER BY login_time DESC 
            LIMIT 1");
    }
    
    $conn->close();
}

// Check user role before destroying session
$role = $_SESSION['role'] ?? '';

// Destroy all sessions
session_unset();
session_destroy();

// Prevent back button after logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect based on role
if ($role === 'superadmin' || $role === 'owner') {
    header("Location: ../admin_login.php");
} else {
    header("Location: ../StudentLogin/login.php");
}
exit;
?>
