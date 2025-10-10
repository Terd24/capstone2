<?php
session_start();
include("../StudentLogin/db_conn.php");

// Require registrar login
if (!isset($_SESSION['registrar_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Function to generate next Student ID
function generateNextStudentId($conn) {
    // Get the highest existing student ID
    $query = "SELECT id_number FROM student_account WHERE id_number LIKE '022%' ORDER BY id_number DESC LIMIT 1";
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $lastId = $row['id_number'];
        // Extract the numeric part and increment
        $numericPart = intval(substr($lastId, 3)); // Remove '022' prefix
        $nextNumber = $numericPart + 1;
    } else {
        // First student, start with 1
        $nextNumber = 1;
    }
    
    // Format as 02200000001, 02200000002, etc.
    return '022' . str_pad($nextNumber, 8, '0', STR_PAD_LEFT);
}

try {
    $nextId = generateNextStudentId($conn);
    $digitsOnly = preg_replace('/\D+/', '', $nextId);
    $last6 = substr($digitsOnly, -6);
    echo json_encode(['success' => true, 'next_id' => $nextId, 'last6' => $last6]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
