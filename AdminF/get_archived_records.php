<?php
// Prevent any output before JSON
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once '../StudentLogin/db_conn.php';

// Clear any output buffer
ob_end_clean();

// Set JSON header first
header('Content-Type: application/json');

// Check if user is Super Admin
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$type = $_GET['type'] ?? '';
$search = $_GET['search'] ?? '';
$offset = (int)($_GET['offset'] ?? 0);
$limit = (int)($_GET['limit'] ?? 20);

$response = ['success' => false, 'data' => [], 'total' => 0];

try {
    if ($type === 'students') {
        // Get archived students
        $query = "SELECT archive_id, original_id, id_number, first_name, last_name, grade_level, 
                         archived_at, archived_by, archive_reason as deletion_reason 
                  FROM archived_students";
        
        $count_query = "SELECT COUNT(*) as total FROM archived_students";
        
        // Add search filter if provided
        if (!empty($search)) {
            $search_term = "%{$search}%";
            $query .= " WHERE id_number LIKE ? OR first_name LIKE ? OR last_name LIKE ?";
            $count_query .= " WHERE id_number LIKE ? OR first_name LIKE ? OR last_name LIKE ?";
        }
        
        $query .= " ORDER BY archived_at DESC LIMIT ? OFFSET ?";
        
        // Get total count
        if (!empty($search)) {
            $count_stmt = $conn->prepare($count_query);
            $count_stmt->bind_param("sss", $search_term, $search_term, $search_term);
        } else {
            $count_stmt = $conn->prepare($count_query);
        }
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $response['total'] = $count_result->fetch_assoc()['total'];
        
        // Get records
        if (!empty($search)) {
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssii", $search_term, $search_term, $search_term, $limit, $offset);
        } else {
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $limit, $offset);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $response['data'][] = $row;
        }
        
        $response['success'] = true;
        
    } elseif ($type === 'employees') {
        // Get archived employees
        $query = "SELECT archive_id, original_id, id_number, first_name, last_name, position, department,
                         archived_at, archived_by, archive_reason as deletion_reason 
                  FROM archived_employees";
        
        $count_query = "SELECT COUNT(*) as total FROM archived_employees";
        
        // Add search filter if provided
        if (!empty($search)) {
            $search_term = "%{$search}%";
            $query .= " WHERE id_number LIKE ? OR first_name LIKE ? OR last_name LIKE ?";
            $count_query .= " WHERE id_number LIKE ? OR first_name LIKE ? OR last_name LIKE ?";
        }
        
        $query .= " ORDER BY archived_at DESC LIMIT ? OFFSET ?";
        
        // Get total count
        if (!empty($search)) {
            $count_stmt = $conn->prepare($count_query);
            $count_stmt->bind_param("sss", $search_term, $search_term, $search_term);
        } else {
            $count_stmt = $conn->prepare($count_query);
        }
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $response['total'] = $count_result->fetch_assoc()['total'];
        
        // Get records
        if (!empty($search)) {
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssii", $search_term, $search_term, $search_term, $limit, $offset);
        } else {
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $limit, $offset);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $response['data'][] = $row;
        }
        
        $response['success'] = true;
        
    } else {
        $response['message'] = 'Invalid type';
    }
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
?>
