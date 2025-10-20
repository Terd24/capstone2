<?php
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$role = $_SESSION['role'] ?? '';
if ($role !== 'teacher') {
  echo json_encode(['success' => false, 'error' => 'Unauthorized']);
  exit;
}

require_once("../../StudentLogin/db_conn.php");

try {
  $teacher_id = $_SESSION['id_number'] ?? '';
  
  if (empty($teacher_id)) {
    echo json_encode(['success' => false, 'error' => 'Teacher ID not found']);
    exit;
  }
  
  // Get teacher's assigned subjects
  $stmt = $conn->prepare("SELECT id, subject_name FROM teacher_subjects WHERE teacher_id = ? ORDER BY subject_name ASC");
  $stmt->bind_param('s', $teacher_id);
  $stmt->execute();
  $result = $stmt->get_result();
  
  $subjects = [];
  while ($row = $result->fetch_assoc()) {
    $subjects[] = $row;
  }
  
  echo json_encode(['success' => true, 'subjects' => $subjects]);
  exit;
  
} catch (Throwable $e) {
  echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
  exit;
}
