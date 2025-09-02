<?php
session_start();
include("../StudentLogin/db_conn.php");

// Require registrar login
if (!isset($_SESSION['registrar_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get the latest term from grades_record table
$query = "SELECT school_year_term FROM grades_record ORDER BY school_year_term DESC LIMIT 1";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $latest_term = $row['school_year_term'];
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'latest_term' => $latest_term
    ]);
} else {
    // No terms found, return default
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'latest_term' => '2024-2025 2nd Term'
    ]);
}

$conn->close();
?>
