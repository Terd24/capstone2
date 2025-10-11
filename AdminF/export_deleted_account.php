<?php
session_start();

// Require Super Admin login
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'onecci_db');
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get parameters
$account_id = $_GET['id'] ?? '';
$account_type = $_GET['type'] ?? '';

if (empty($account_id) || empty($account_type)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Validate account type
if (!in_array($account_type, ['student', 'employee'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid account type']);
    exit;
}

try {
    $account_data = [];
    $filename = '';
    
    if ($account_type === 'student') {
        // Get student data only (removed parent information due to schema mismatch)
        $query = "SELECT *
                  FROM student_account 
                  WHERE id_number = ?";
        
        // Debug information
        error_log("Searching for student ID: " . $account_id);
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $account_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Return a more helpful error message
            http_response_code(404);
            echo json_encode([
                'success' => false, 
                'message' => 'Student record with ID ' . $account_id . ' not found in the database'
            ]);
            exit;
        }
        
        $student = $result->fetch_assoc();
        
        // Get grades data
        $grades_query = "SELECT * FROM grades_record WHERE id_number = ?";
        $grades_stmt = $conn->prepare($grades_query);
        $grades_stmt->bind_param("s", $account_id);
        $grades_stmt->execute();
        $grades_result = $grades_stmt->get_result();
        $grades = $grades_result->fetch_all(MYSQLI_ASSOC);
        
        // Get balance data
        $balance_query = "SELECT * FROM student_fee_items WHERE id_number = ?";
        $balance_stmt = $conn->prepare($balance_query);
        $balance_stmt->bind_param("s", $account_id);
        $balance_stmt->execute();
        $balance_result = $balance_stmt->get_result();
        $balances = $balance_result->fetch_all(MYSQLI_ASSOC);
        
        // Get payment history
        $payments_query = "SELECT * FROM student_payments WHERE id_number = ?";
        $payments_stmt = $conn->prepare($payments_query);
        $payments_stmt->bind_param("s", $account_id);
        $payments_stmt->execute();
        $payments_result = $payments_stmt->get_result();
        $payments = $payments_result->fetch_all(MYSQLI_ASSOC);
        
        $account_data = [
            'account_type' => 'student',
            'export_date' => date('Y-m-d H:i:s'),
            'exported_by' => $_SESSION['superadmin_name'] ?? 'Super Admin',
            'student_info' => $student,
            'grades_records' => $grades,
            'fee_items' => $balances,
            'payment_history' => $payments
        ];
        
        // Save to file in exports/deleted_records folder
        $export_dir = '../exports/deleted_records/';
        if (!file_exists($export_dir)) {
            mkdir($export_dir, 0777, true);
        }
        
        $filename = 'student_' . $account_id . '_' . date('Y-m-d_His') . '.json';
        $file_path = $export_dir . $filename;
        
        // Save the data to file
        file_put_contents($file_path, json_encode($account_data, JSON_PRETTY_PRINT));
        
        // Add file path to the response
        $account_data['file_saved'] = true;
        $account_data['file_path'] = $file_path;
        
        $student_name = trim($student['first_name'] . ' ' . $student['last_name']);
        $filename = 'DELETED_STUDENT_' . $account_id . '_' . str_replace(' ', '_', $student_name) . '_' . date('Y-m-d_H-i-s') . '.json';
        
    } else if ($account_type === 'employee') {
        // Get employee data
        $query = "SELECT e.*, ea.username, ea.role, ea.created_at as account_created
                  FROM employees e
                  LEFT JOIN employee_accounts ea ON e.id_number = ea.employee_id
                  WHERE e.id_number = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $account_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Return a more helpful error message
            http_response_code(404);
            echo json_encode([
                'success' => false, 
                'message' => 'Employee record with ID ' . $account_id . ' not found in the database'
            ]);
            exit;
        }
        
        $employee = $result->fetch_assoc();
        
        // Get attendance records if available
        $attendance_query = "SELECT * FROM teacher_attendance WHERE teacher_id = ?";
        $attendance_stmt = $conn->prepare($attendance_query);
        $attendance_stmt->bind_param("s", $account_id);
        $attendance_stmt->execute();
        $attendance_result = $attendance_stmt->get_result();
        $attendance = $attendance_result->fetch_all(MYSQLI_ASSOC);
        
        $account_data = [
            'account_type' => 'employee',
            'export_date' => date('Y-m-d H:i:s'),
            'exported_by' => $_SESSION['superadmin_name'] ?? 'Super Admin',
            'employee_info' => $employee,
            'attendance_records' => $attendance
        ];
        
        // Save to file in exports/deleted_records folder
        $export_dir = '../exports/deleted_records/';
        if (!file_exists($export_dir)) {
            mkdir($export_dir, 0777, true);
        }
        
        $filename = 'employee_' . $account_id . '_' . date('Y-m-d_His') . '.json';
        $file_path = $export_dir . $filename;
        
        // Save the data to file
        file_put_contents($file_path, json_encode($account_data, JSON_PRETTY_PRINT));
        
        // Add file path to the response
        $account_data['file_saved'] = true;
        $account_data['file_path'] = $file_path;
        
        $employee_name = trim($employee['first_name'] . ' ' . $employee['last_name']);
        $filename = 'DELETED_EMPLOYEE_' . $account_id . '_' . str_replace(' ', '_', $employee_name) . '_' . date('Y-m-d_H-i-s') . '.json';
    }
    
    // Create exports directory if it doesn't exist
    $exports_dir = '../exports/deleted_accounts';
    if (!file_exists($exports_dir)) {
        mkdir($exports_dir, 0755, true);
    }
    
    // Save file to server
    $file_path = $exports_dir . '/' . $filename;
    $json_data = json_encode($account_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    if (file_put_contents($file_path, $json_data) === false) {
        throw new Exception('Failed to save export file');
    }
    
    // Set headers for file download
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($json_data));
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: 0');
    
    // Output the file content for download
    echo $json_data;
    
    // Log the export action
    $log_query = "INSERT INTO system_logs (action, details, performed_by, performed_at) VALUES (?, ?, ?, NOW())";
    $log_stmt = $conn->prepare($log_query);
    $action = "EXPORT_DELETED_ACCOUNT";
    $details = "Exported deleted {$account_type} account: {$account_id} to file: {$filename}";
    $performed_by = $_SESSION['superadmin_name'] ?? 'Super Admin';
    $log_stmt->bind_param("sss", $action, $details, $performed_by);
    $log_stmt->execute();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    $conn->close();
}
?>
