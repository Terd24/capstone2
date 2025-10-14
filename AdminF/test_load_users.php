<?php
// Simple test file to check if load_more_users.php works
session_start();
$_SESSION['role'] = 'superadmin'; // Simulate super admin

// Test the endpoint
$url = 'http://localhost/onecci/AdminF/load_more_users.php?type=employees&offset=0&limit=10';
$response = file_get_contents($url);

echo "<h1>Test Load More Users</h1>";
echo "<h2>Response:</h2>";
echo "<pre>";
print_r($response);
echo "</pre>";

echo "<h2>Decoded:</h2>";
echo "<pre>";
print_r(json_decode($response, true));
echo "</pre>";
?>
