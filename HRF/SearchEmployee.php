<?php
header('Content-Type: application/json');
session_start();
include("../StudentLogin/db_conn.php");

// Allow only HR users
if (!((isset($_SESSION['role']) && $_SESSION['role'] === 'hr') || isset($_SESSION['hr_name']))) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Optional role filter (if provided). If absent, include all roles.
$roleFilter = isset($_GET['role']) ? trim($_GET['role']) : '';
$all = isset($_GET['all']);
$query = trim($_GET['query'] ?? '');
// Pagination params
$limit  = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 15;
$offset = isset($_GET['offset']) ? max(0, intval($_GET['offset'])) : 0;
$fetchLimit = $limit + 1; // fetch extra to detect more
$excludeScheduleId = isset($_GET['exclude_schedule_id']) ? intval($_GET['exclude_schedule_id']) : 0;

// Build SQL to fetch employees with optional role filter
$sql = "SELECT DISTINCT e.id_number, e.first_name, e.last_name,
               CONCAT(e.first_name, ' ', e.last_name) AS full_name,
               ws.schedule_name AS current_section,
               ws.id AS ws_id
        FROM employees e
        LEFT JOIN employee_accounts ea ON ea.employee_id = e.id_number
        LEFT JOIN employee_schedules es ON es.employee_id = e.id_number
        LEFT JOIN employee_work_schedules ws ON ws.id = es.schedule_id";

$params = []; $types = '';
$conditions = [];

if ($roleFilter !== '') {
    $conditions[] = 'ea.role = ?';
    $params[] = $roleFilter; $types .= 's';
}

if (!$all && $query !== '') {
    $conditions[] = "(e.id_number LIKE ? OR CONCAT(e.first_name, ' ', e.last_name) LIKE ?)";
    $like = "%$query%"; $params[] = $like; $params[] = $like; $types .= 'ss';
}

if ($conditions) { $sql .= ' WHERE ' . implode(' AND ', $conditions); }
// Exclude those already in the target schedule if requested
if ($excludeScheduleId > 0) {
    $sql .= ($conditions ? ' AND' : ' WHERE') . ' (es.schedule_id IS NULL OR es.schedule_id <> ?)';
    $params[] = $excludeScheduleId; $types .= 'i';
}
// Order: available first (no schedule), then alphabetically
$sql .= ' ORDER BY (ws.id IS NOT NULL) ASC, e.last_name, e.first_name LIMIT ? OFFSET ?';

$stmt = $conn->prepare($sql);
if (!$stmt) { echo json_encode(['error'=>'DB prepare error','details'=>$conn->error]); exit; }
// Add limit/offset bindings
$typesWithLO = $types . 'ii';
$paramsWithLO = array_merge($params, [$fetchLimit, $offset]);
$stmt->bind_param($typesWithLO, ...$paramsWithLO);
if (!$stmt->execute()) { echo json_encode(['error'=>'DB execute error','details'=>$stmt->error]); exit; }
$res = $stmt->get_result();
if (!$res) { echo json_encode(['error'=>'DB result error','details'=>$stmt->error]); exit; }

$employees = [];
while ($row = $res->fetch_assoc()) { $employees[] = $row; }
$has_more = count($employees) > $limit;
if ($has_more) { array_pop($employees); }
echo json_encode(['employees' => $employees, 'has_more' => $has_more]);
