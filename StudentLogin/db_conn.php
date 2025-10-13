<?php
// ⚙️ HOSTINGER DATABASE CONFIGURATION
// Update these values with your Hostinger database credentials
define('DB_HOST', 'localhost');           // Your Hostinger DB host
define('DB_USER', 'u502476186_onecci_user');   // Your Hostinger DB username
define('DB_PASS', 'OneCciMuzon2004');     // Your Hostinger DB password
define('DB_NAME', 'u502476186_onecci_db');   // Your Hostinger DB name

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    // Log error to file instead of displaying (security)
    error_log("Database connection failed: " . $conn->connect_error);
    die("Connection failed. Please contact administrator.");
}

$conn->set_charset("utf8mb4");
?>
