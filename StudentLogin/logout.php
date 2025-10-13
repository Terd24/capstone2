<?php
session_start();

// Check user role before destroying session
$role = $_SESSION['role'] ?? '';

session_unset();
session_destroy();

// Prevent caching of the last page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect based on role
if ($role === 'superadmin' || $role === 'owner') {
    header("Location: ../admin_login.php");
} else {
    header("Location: login.php");
}
exit;
?>