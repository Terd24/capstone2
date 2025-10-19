<?php
session_start();

// Only SuperAdmin can download backups
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    die('Unauthorized');
}

require_once '../StudentLogin/db_conn.php';

$filename = $_GET['file'] ?? '';

if (empty($filename)) {
    die('No filename specified');
}

// Security: Only allow downloading from backups directory and prevent directory traversal
$filename = basename($filename); // Remove any path components
$filepath = '../backups/' . $filename;

if (!file_exists($filepath)) {
    die('Backup file not found');
}

// Log the download
error_log("SuperAdmin downloading backup: $filename");

// Trigger browser download
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filepath));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

readfile($filepath);
exit;
?>
