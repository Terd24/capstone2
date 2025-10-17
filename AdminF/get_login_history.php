<?php
// Prevent any output before JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);
ob_start();

session_start();

// Require Super Admin login
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    ob_end_clean();
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../StudentLogin/db_conn.php';

if (!$conn) {
    ob_end_clean();
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Get parameters
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$userType = $_GET['user_type'] ?? 'all';
$role = $_GET['role'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$limit = (int)($_GET['limit'] ?? 10);
$export = $_GET['export'] ?? '';

$offset = ($page - 1) * $limit;

// Build query
$query = "
    SELECT 
        la.user_type, 
        la.id_number, 
        la.username, 
        la.role, 
        la.login_time,
        la.logout_time,
        la.session_duration,
        CASE 
            WHEN la.user_type = 'student' THEN CONCAT(s.first_name, ' ', s.last_name)
            WHEN la.user_type = 'employee' THEN CONCAT(e.first_name, ' ', e.last_name)
            WHEN la.user_type = 'parent' THEN CONCAT('Parent of ', sc.first_name, ' ', sc.last_name)
            ELSE la.username
        END as full_name
    FROM login_activity la
    LEFT JOIN student_account s ON la.id_number = s.id_number AND la.user_type = 'student'
    LEFT JOIN employees e ON la.id_number = e.id_number AND la.user_type = 'employee'
    LEFT JOIN student_account sc ON la.id_number = sc.id_number AND la.user_type = 'parent'
    WHERE 1=1
";

$params = [];
$types = '';

// Add date filter only if dates are provided
if (!empty($dateFrom) && !empty($dateTo)) {
    $query .= " AND DATE(la.login_time) BETWEEN ? AND ?";
    $params[] = $dateFrom;
    $params[] = $dateTo;
    $types .= 'ss';
} elseif (!empty($dateFrom)) {
    $query .= " AND DATE(la.login_time) >= ?";
    $params[] = $dateFrom;
    $types .= 's';
} elseif (!empty($dateTo)) {
    $query .= " AND DATE(la.login_time) <= ?";
    $params[] = $dateTo;
    $types .= 's';
}

// Add user type filter
if ($userType !== 'all') {
    $query .= " AND la.user_type = ?";
    $params[] = $userType;
    $types .= 's';
}

// Add role filter
if ($role !== 'all') {
    $query .= " AND la.role = ?";
    $params[] = $role;
    $types .= 's';
}

// Add search filter
if (!empty($search)) {
    $query .= " AND (la.id_number LIKE ? OR la.username LIKE ? OR 
                CONCAT(s.first_name, ' ', s.last_name) LIKE ? OR 
                CONCAT(e.first_name, ' ', e.last_name) LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'ssss';
}

// Handle CSV export
if ($export === 'csv') {
    $query .= " ORDER BY la.login_time DESC";
    
    if ($stmt = $conn->prepare($query)) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        ob_end_clean();
        
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="login_history_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Add CSV headers
        fputcsv($output, ['Date', 'User Type', 'ID', 'Name', 'Role', 'Login Time', 'Logout Time', 'Duration (minutes)']);
        
        // Add data rows
        while ($row = $result->fetch_assoc()) {
            $duration = $row['session_duration'] ? round($row['session_duration'] / 60) : '';
            fputcsv($output, [
                date('Y-m-d', strtotime($row['login_time'])),
                ucfirst($row['user_type']),
                $row['id_number'],
                $row['full_name'] ?: $row['username'],
                ucfirst($row['role']),
                date('Y-m-d H:i:s', strtotime($row['login_time'])),
                $row['logout_time'] ? date('Y-m-d H:i:s', strtotime($row['logout_time'])) : 'Active',
                $duration
            ]);
        }
        
        fclose($output);
        $stmt->close();
        exit;
    }
}

// Get total count - build a simpler count query
$countQuery = "
    SELECT COUNT(*)
    FROM login_activity la
    LEFT JOIN student_account s ON la.id_number = s.id_number AND la.user_type = 'student'
    LEFT JOIN employees e ON la.id_number = e.id_number AND la.user_type = 'employee'
    LEFT JOIN student_account sc ON la.id_number = sc.id_number AND la.user_type = 'parent'
    WHERE 1=1
";

// Add same filters as main query
$countParams = [];
$countTypes = '';

// Add date filter only if dates are provided
if (!empty($dateFrom) && !empty($dateTo)) {
    $countQuery .= " AND DATE(la.login_time) BETWEEN ? AND ?";
    $countParams[] = $dateFrom;
    $countParams[] = $dateTo;
    $countTypes .= 'ss';
} elseif (!empty($dateFrom)) {
    $countQuery .= " AND DATE(la.login_time) >= ?";
    $countParams[] = $dateFrom;
    $countTypes .= 's';
} elseif (!empty($dateTo)) {
    $countQuery .= " AND DATE(la.login_time) <= ?";
    $countParams[] = $dateTo;
    $countTypes .= 's';
}

if ($userType !== 'all') {
    $countQuery .= " AND la.user_type = ?";
    $countParams[] = $userType;
    $countTypes .= 's';
}

if ($role !== 'all') {
    $countQuery .= " AND la.role = ?";
    $countParams[] = $role;
    $countTypes .= 's';
}

if (!empty($search)) {
    $countQuery .= " AND (la.id_number LIKE ? OR la.username LIKE ? OR 
                CONCAT(s.first_name, ' ', s.last_name) LIKE ? OR 
                CONCAT(e.first_name, ' ', e.last_name) LIKE ?)";
    $searchParam = "%$search%";
    $countParams[] = $searchParam;
    $countParams[] = $searchParam;
    $countParams[] = $searchParam;
    $countParams[] = $searchParam;
    $countTypes .= 'ssss';
}

$total = 0;
if ($countStmt = $conn->prepare($countQuery)) {
    if (!empty($countParams)) {
        $countStmt->bind_param($countTypes, ...$countParams);
    }
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $total = $countResult->fetch_row()[0];
    $countStmt->close();
}

// Get paginated results
$query .= " ORDER BY la.login_time DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$records = [];
if ($stmt = $conn->prepare($query)) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
    
    $stmt->close();
}

ob_end_clean();
header('Content-Type: application/json');
echo json_encode([
    'records' => $records,
    'total' => (int)$total,
    'page' => $page,
    'limit' => $limit
]);
?>
