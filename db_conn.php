<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "onecci_db"; // 👉 change this to your actual database name

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
