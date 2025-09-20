<?php
header('Content-Type: application/json');
session_start();
include("../StudentLogin/db_conn.php");

if (!isset($_SESSION['registrar_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Optional role filter (if provided). If absent, include all roles.
$roleFilter = isset($_GET['role']) ? trim($_GET['role']) : '';
$all = isset($_GET['all']);
$query = trim($_GET['query'] ?? '');

// Build SQL to fetch employees with optional role filter
$sql = "SELECT DISTINCT e.id_number, e.first_name, e.last_name,
               CONCAT(e.first_name, ' ', e.last_name) AS full_name,
               ws.schedule_name AS current_section
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
$sql .= ' ORDER BY e.last_name, e.first_name LIMIT 200';

$stmt = $conn->prepare($sql);
if (!$stmt) { echo json_encode(['error'=>'DB prepare error','details'=>$conn->error]); exit; }
if (!empty($params)) { $stmt->bind_param($types, ...$params); }
if (!$stmt->execute()) { echo json_encode(['error'=>'DB execute error','details'=>$stmt->error]); exit; }
$res = $stmt->get_result();
if (!$res) { echo json_encode(['error'=>'DB result error','details'=>$stmt->error]); exit; }

$employees = [];
while ($row = $res->fetch_assoc()) { $employees[] = $row; }
echo json_encode(['employees' => $employees]);
