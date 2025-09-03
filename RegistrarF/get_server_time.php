<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Set timezone to match your system
date_default_timezone_set('Asia/Manila');

// Return current server timestamp for 2025
echo json_encode([
    'timestamp' => time(),
    'date' => date('Y-m-d H:i:s'),
    'year' => date('Y')
]);
?>
