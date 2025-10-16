<?php
// Prevent multiple inclusions
if (defined('DB_CONN_LOADED')) {
    return;
}
define('DB_CONN_LOADED', true);

// ⚙️ AUTO-DETECT ENVIRONMENT (Local vs Hostinger)
// Automatically switches between localhost and Hostinger credentials

// Check if running on localhost or live server
$isLocalhost = (
    $_SERVER['SERVER_NAME'] === 'localhost' || 
    $_SERVER['SERVER_ADDR'] === '127.0.0.1' ||
    $_SERVER['SERVER_ADDR'] === '::1'
);

if ($isLocalhost) {
    // 🏠 LOCALHOST CONFIGURATION (XAMPP)
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'onecci_db');
} else {
    // 🌐 HOSTINGER CONFIGURATION (Live Server)
    define('DB_HOST', 'localhost');
    define('DB_USER', 'u502476186_onecci_user');
    define('DB_PASS', 'OneCciMuzon2004');
    define('DB_NAME', 'u502476186_onecci_db');
}

// Set timezone to Philippine Time (applies to all PHP date/time functions)
date_default_timezone_set('Asia/Manila');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    // Log error to file instead of displaying (security)
    error_log("Database connection failed: " . $conn->connect_error);
    die("Connection failed. Please contact administrator.");
}

$conn->set_charset("utf8mb4");

// Set MySQL timezone to Philippine Time (applies to database NOW(), CURDATE(), etc.)
$conn->query("SET time_zone = '+08:00'");
?>
