<?php
session_start();
include("../StudentLogin/db_conn.php");

// Require owner login
if (!isset($_SESSION['owner_id']) || $_SESSION['role'] !== 'owner') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get pagination and filter parameters
$page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
$filter = isset($_POST['filter']) ? $_POST['filter'] : 'all';
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build filter condition
$filter_condition = "status IN ('approved', 'rejected')";
if ($filter === 'approved') {
    $filter_condition = "status = 'approved'";
} elseif ($filter === 'rejected') {
    $filter_condition = "status = 'rejected'";
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM owner_approval_requests WHERE $filter_condition";
$count_result = $conn->query($count_query);
$total = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total / $per_page);

// Get paginated results
$query = "SELECT * FROM owner_approval_requests WHERE $filter_condition ORDER BY reviewed_at DESC LIMIT $per_page OFFSET $offset";
$result = $conn->query($query);

$requests = [];
if ($result && $result->num_rows > 0) {
    while ($request = $result->fetch_assoc()) {
        $requests[] = $request;
    }
}

// Return JSON response
echo json_encode([
    'success' => true,
    'requests' => $requests,
    'pagination' => [
        'current_page' => $page,
        'total_pages' => $total_pages,
        'total_records' => $total,
        'per_page' => $per_page,
        'offset' => $offset
    ]
]);
