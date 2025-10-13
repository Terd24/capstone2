<?php
session_start();
include '../StudentLogin/db_conn.php';

// Require Super Admin login
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Function to generate next Employee ID
function generateNextEmployeeId($conn) {
    $currentYear = date('Y');
    $prefix = 'CCI' . $currentYear . '-';
    
    // Get the highest existing employee ID for current year
    $query = "SELECT id_number FROM employees WHERE id_number LIKE ? ORDER BY id_number DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    $searchPattern = $prefix . '%';
    $stmt->bind_param("s", $searchPattern);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $lastId = $row['id_number'];
        // Extract the numeric part after the dash
        $parts = explode('-', $lastId);
        if (count($parts) == 2) {
            $numericPart = intval($parts[1]);
            $nextNumber = $numericPart + 1;
        } else {
            $nextNumber = 1;
        }
    } else {
        $nextNumber = 1;
    }
    
    // Format as CCI2025-001, CCI2025-002, etc. (3 digits)
    return $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
}

$nextId = generateNextEmployeeId($conn);
echo json_encode(['next_id' => $nextId]);
?>
