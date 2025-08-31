<?php
session_start();
include(__DIR__ . "/../../StudentLogin/db_conn.php");

// Set content type to JSON
header('Content-Type: application/json');

// Check if request is POST and child_id is provided
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['child_id'])) {
    $child_id = trim($_POST['child_id']);
    
    if (!empty($child_id)) {
        // Query to get student name by ID
        $stmt = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) as full_name FROM student_account WHERE id_number = ?");
        $stmt->bind_param("s", $child_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $student = $result->fetch_assoc();
            echo json_encode([
                'success' => true,
                'child_name' => $student['full_name']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Student not found'
            ]);
        }
        $stmt->close();
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Child ID is required'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}
?>
