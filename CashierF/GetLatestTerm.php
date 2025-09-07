<?php
include '../StudentLogin/db_conn.php';
header('Content-Type: application/json');

try {
    // Get the latest school year term from student_fee_items table
    $stmt = $conn->prepare("SELECT school_year_term FROM student_fee_items ORDER BY school_year_term DESC LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode(['success' => true, 'latest_term' => $row['school_year_term']]);
    } else {
        // If no records found, return current academic year with 1st semester
        $currentYear = date('Y');
        $nextYear = $currentYear + 1;
        $defaultTerm = "$currentYear-$nextYear 1st Semester";
        echo json_encode(['success' => true, 'latest_term' => $defaultTerm]);
    }
    
    $stmt->close();
} catch (Exception $e) {
    // Fallback to current academic year
    $currentYear = date('Y');
    $nextYear = $currentYear + 1;
    $defaultTerm = "$currentYear-$nextYear 1st Semester";
    echo json_encode(['success' => true, 'latest_term' => $defaultTerm]);
}

$conn->close();
?>
