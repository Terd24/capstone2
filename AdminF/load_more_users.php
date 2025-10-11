<?php
session_start();

// Require Super Admin login
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'onecci_db');
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$type = $_GET['type'] ?? '';
$offset = (int)($_GET['offset'] ?? 0);
$limit = 20; // Load 20 more items at a time

$today = date('Y-m-d');
$response = ['items' => [], 'hasMore' => false];

if ($type === 'employees') {
    // Get teachers who haven't logged in today
    // Check both employee_accounts table and employees table for teacher identification
    $query = "
        SELECT DISTINCT e.id_number, e.first_name, e.last_name, e.middle_name
        FROM employees e
        LEFT JOIN employee_accounts ea ON e.id_number = ea.employee_id
        LEFT JOIN login_activity la ON e.id_number = la.id_number AND DATE(la.login_time) = ?
        WHERE la.id_number IS NULL 
        AND (ea.role = 'teacher' OR e.role = 'teacher' OR e.position LIKE '%teacher%' OR e.position LIKE '%Teacher%')
        ORDER BY e.last_name, e.first_name
        LIMIT ? OFFSET ?
    ";
    
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param('sii', $today, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $full_name = trim($row['first_name'] . ' ' . $row['last_name']);
            $response['items'][] = "• {$full_name} ({$row['id_number']})";
        }
        
        // Check if there are more items
        $count_query = "
            SELECT COUNT(DISTINCT e.id_number)
            FROM employees e
            LEFT JOIN employee_accounts ea ON e.id_number = ea.employee_id
            LEFT JOIN login_activity la ON e.id_number = la.id_number AND DATE(la.login_time) = ?
            WHERE la.id_number IS NULL 
            AND (ea.role = 'teacher' OR e.role = 'teacher' OR e.position LIKE '%teacher%' OR e.position LIKE '%Teacher%')
        ";
        
        if ($count_stmt = $conn->prepare($count_query)) {
            $count_stmt->bind_param('s', $today);
            $count_stmt->execute();
            $count_result = $count_stmt->get_result();
            $total = $count_result->fetch_row()[0];
            $response['hasMore'] = ($offset + $limit) < $total;
            $count_stmt->close();
        }
        
        $stmt->close();
    }
    
} elseif ($type === 'students') {
    // Get students who haven't logged in today
    $query = "
        SELECT DISTINCT s.id_number, s.first_name, s.last_name, s.middle_name
        FROM student_account s
        LEFT JOIN login_activity la ON s.id_number = la.id_number AND DATE(la.login_time) = ?
        WHERE la.id_number IS NULL AND (s.deleted_at IS NULL OR s.deleted_at = '')
        ORDER BY s.last_name, s.first_name
        LIMIT ? OFFSET ?
    ";
    
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param('sii', $today, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $full_name = trim($row['first_name'] . ', ' . $row['last_name']);
            $response['items'][] = "• {$full_name} ({$row['id_number']})";
        }
        
        // Check if there are more items
        $count_query = "
            SELECT COUNT(DISTINCT s.id_number)
            FROM student_account s
            LEFT JOIN login_activity la ON s.id_number = la.id_number AND DATE(la.login_time) = ?
            WHERE la.id_number IS NULL AND (s.deleted_at IS NULL OR s.deleted_at = '')
        ";
        
        if ($count_stmt = $conn->prepare($count_query)) {
            $count_stmt->bind_param('s', $today);
            $count_stmt->execute();
            $count_result = $count_stmt->get_result();
            $total = $count_result->fetch_row()[0];
            $response['hasMore'] = ($offset + $limit) < $total;
            $count_stmt->close();
        }
        
        $stmt->close();
    }
}

$conn->close();

header('Content-Type: application/json');
echo json_encode($response);
?>
