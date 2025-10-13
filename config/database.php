<?php
// Database Configuration for Hostinger
// IMPORTANT: Update these values with your Hostinger database credentials

define('DB_HOST', 'localhost');  // Change to your Hostinger DB host
define('DB_USER', 'root');       // Change to your Hostinger DB username
define('DB_PASS', '');           // Change to your Hostinger DB password
define('DB_NAME', 'onecci_db');  // Change to your Hostinger DB name

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");
?>
