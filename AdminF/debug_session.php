<?php
session_start();
header('Content-Type: application/json');

echo json_encode([
    'session_exists' => isset($_SESSION),
    'role' => $_SESSION['role'] ?? 'NOT SET',
    'role_lowercase' => isset($_SESSION['role']) ? strtolower($_SESSION['role']) : 'NOT SET',
    'is_superadmin' => (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'superadmin'),
    'superadmin_name' => $_SESSION['superadmin_name'] ?? 'NOT SET',
    'id_number' => $_SESSION['id_number'] ?? 'NOT SET',
    'all_session_keys' => array_keys($_SESSION)
], JSON_PRETTY_PRINT);
?>
