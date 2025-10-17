<?php
session_start();
include("../StudentLogin/db_conn.php");

// Record logout time before destroying session
if (isset($_SESSION['id_number']) || isset($_SESSION['username'])) {
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
}

// Log the logout if user is logged in
if (isset($_SESSION['owner_id']) && $_SESSION['role'] === 'owner') {
    $log_stmt = $conn->prepare("INSERT INTO system_logs (action_type, performed_by, user_role, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
    $action = "owner_logout";
    $description = "Owner logged out";
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $log_stmt->bind_param("ssssss", $action, $_SESSION['owner_username'], $_SESSION['role'], $description, $ip, $user_agent);
    $log_stmt->execute();
}

// Clear all session variables
session_unset();
session_destroy();

// Prevent back button after logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Redirect to Admin/Owner login page
header("Location: ../admin_login.php");
exit;
?>
