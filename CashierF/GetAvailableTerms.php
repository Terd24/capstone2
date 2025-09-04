<?php
session_start();
require_once '../StudentLogin/db_conn.php';

// Check if user is logged in and has cashier role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'cashier') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if request method is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Validate required parameter
if (!isset($_GET['id_number'])) {
    echo json_encode(['success' => false, 'message' => 'Missing student ID']);
    exit;
}

$id_number = trim($_GET['id_number']);

try {
    // Get all available terms for this student from both fee items and payments
    $stmt = $conn->prepare("
        SELECT DISTINCT school_year_term 
        FROM (
            SELECT school_year_term FROM student_fee_items WHERE id_number = ?
            UNION
            SELECT school_year_term FROM student_payments WHERE id_number = ?
        ) AS combined_terms
        WHERE school_year_term IS NOT NULL AND school_year_term != ''
        ORDER BY school_year_term DESC
    ");
    
    $stmt->bind_param("ss", $id_number, $id_number);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $terms = [];
    while ($row = $result->fetch_assoc()) {
        $terms[] = $row['school_year_term'];
    }
    
    echo json_encode([
        'success' => true,
        'terms' => $terms,
        'count' => count($terms)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
