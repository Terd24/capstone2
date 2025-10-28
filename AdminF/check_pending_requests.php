<?php
session_start();

// Check if user is logged in as superadmin, HR, or Registrar
if (!isset($_SESSION['role']) || !in_array(strtolower($_SESSION['role']), ['superadmin', 'hr', 'registrar'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../StudentLogin/db_conn.php';

header('Content-Type: application/json');

// Get requester name based on role
$user_role = strtolower($_SESSION['role']);
if ($user_role === 'superadmin') {
    $requester_name = $_SESSION['superadmin_name'] ?? $_SESSION['username'] ?? 'Super Admin';
} elseif ($user_role === 'hr') {
    $requester_name = $_SESSION['hr_name'] ?? $_SESSION['username'] ?? 'HR Staff';
} else {
    $requester_name = $_SESSION['registrar_name'] ?? $_SESSION['username'] ?? 'Registrar';
}

// Get all pending requests for this user
$query = "SELECT * FROM owner_approval_requests 
    WHERE status = 'pending' 
    AND requester_name = ? 
    ORDER BY requested_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $requester_name);
$stmt->execute();
$result = $stmt->get_result();

$pending_requests = [];
while ($row = $result->fetch_assoc()) {
    // For student deletion requests, verify the student still exists
    $target_data = $row['target_data'] ? json_decode($row['target_data'], true) : [];
    $is_student_deletion = $row['request_type'] === 'student_deletion' || 
                          isset($target_data['student_id']) || 
                          isset($target_data['id_number']) || 
                          isset($target_data['student_name']);
    
    if ($is_student_deletion) {
        // Check if student still exists in student_account table
        $student_id = $target_data['id_number'] ?? $target_data['student_id'] ?? $row['target_id'];
        if ($student_id) {
            $check_stmt = $conn->prepare("SELECT id_number FROM student_account WHERE id_number = ? AND deleted_at IS NULL");
            $check_stmt->bind_param("s", $student_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            // Only include if student still exists
            if ($check_result->num_rows > 0) {
                $pending_requests[] = [
                    'id' => $row['id'],
                    'request_type' => $row['request_type'],
                    'target_id' => $row['target_id'],
                    'target_data' => $row['target_data'],
                    'requested_at' => $row['requested_at']
                ];
            }
            $check_stmt->close();
        }
    } else {
        // For non-student requests, include as-is
        $pending_requests[] = [
            'id' => $row['id'],
            'request_type' => $row['request_type'],
            'target_id' => $row['target_id'],
            'target_data' => $row['target_data'],
            'requested_at' => $row['requested_at']
        ];
    }
}

echo json_encode([
    'success' => true,
    'pending_requests' => $pending_requests
]);

$stmt->close();
$conn->close();
?>
