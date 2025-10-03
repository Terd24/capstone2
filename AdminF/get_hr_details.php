<?php
session_start();

// Database connection
$conn = new mysqli('localhost', 'root', '', 'onecci_db');
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

// Require Super Admin login
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Set content type to JSON
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $employee_id = $_GET['employee_id'] ?? '';

    if (empty($employee_id)) {
        echo json_encode(['success' => false, 'message' => 'Employee ID is required']);
        exit;
    }

    // Get employee details and HR account info
    $query = "
        SELECT 
            e.id_number,
            e.first_name,
            e.last_name,
            e.middle_name,
            e.position,
            e.department,
            e.email,
            e.phone,
            e.hire_date,
            ea.username,
            ea.role,
            ea.created_at
        FROM employees e
        JOIN employee_accounts ea ON e.id_number = ea.employee_id
        WHERE e.id_number = ? AND ea.role = 'hr'
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'HR account not found']);
        exit;
    }

    $data = $result->fetch_assoc();

    // Prepare employee data
    $employee = [
        'id_number' => $data['id_number'],
        'first_name' => $data['first_name'],
        'last_name' => $data['last_name'],
        'middle_name' => $data['middle_name'],
        'position' => $data['position'],
        'department' => $data['department'],
        'email' => $data['email'],
        'phone' => $data['phone'],
        'hire_date' => $data['hire_date'] ? date('d/m/Y', strtotime($data['hire_date'])) : null
    ];

    // Prepare account data
    $account = [
        'username' => $data['username'],
        'role' => $data['role'],
        'created_at' => $data['created_at']
    ];

    echo json_encode([
        'success' => true,
        'employee' => $employee,
        'account' => $account
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>
