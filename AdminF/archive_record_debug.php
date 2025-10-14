<?php
// Debug version - shows actual errors
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

session_start();
require_once '../StudentLogin/db_conn.php';

header('Content-Type: application/json');

// Check if user is Super Admin
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$record_type = $data['record_type'] ?? '';
$record_id = $data['record_id'] ?? 0;

$response = ['success' => false, 'message' => 'Invalid request', 'debug' => [
    'action' => $action,
    'record_type' => $record_type,
    'record_id' => $record_id
]];

try {
    // Check if archive tables exist
    $table_check = $conn->query("SHOW TABLES LIKE 'archived_students'");
    $response['debug']['tables_exist'] = $table_check->num_rows > 0;
    
    if ($table_check->num_rows === 0) {
        $response['message'] = 'Archive tables not set up. Please run setup_archive_system.php first.';
        echo json_encode($response);
        exit;
    }
    
    if ($action === 'archive' && $record_type === 'student') {
        // Get student data
        $stmt = $conn->prepare("SELECT * FROM student_account WHERE id = ? AND deleted_at IS NOT NULL");
        $stmt->bind_param("i", $record_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $response['debug']['student_found'] = $result->num_rows > 0;
        
        if ($result->num_rows > 0) {
            $student = $result->fetch_assoc();
            $response['debug']['student_data'] = [
                'id' => $student['id'],
                'id_number' => $student['id_number'] ?? 'NULL',
                'name' => ($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')
            ];
            
            // Check archived_students table structure
            $columns_result = $conn->query("DESCRIBE archived_students");
            $columns = [];
            while ($col = $columns_result->fetch_assoc()) {
                $columns[] = $col['Field'];
            }
            $response['debug']['archive_table_columns'] = $columns;
            
            $response['message'] = 'Debug info collected';
            $response['success'] = true;
        } else {
            $response['message'] = 'Student not found or not deleted';
        }
    }
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    $response['debug']['exception'] = $e->getMessage();
}

echo json_encode($response);
?>
