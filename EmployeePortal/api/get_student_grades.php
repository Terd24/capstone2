<?php
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

// Allow teacher (and registrar for compatibility)
$role = $_SESSION['role'] ?? '';
if ($role !== 'teacher' && !isset($_SESSION['registrar_id'])) {
  echo json_encode(['success'=>false,'message'=>'Unauthorized']);
  exit;
}

require_once("../../StudentLogin/db_conn.php");

define('SAFE_TERM_REGEX','/^(\d{4}-\d{4})\s+(1st|2nd)\s+Term$/');

$student_id = $_POST['student_id'] ?? $_GET['student_id'] ?? '';
$requested_term = $_POST['term'] ?? $_GET['term'] ?? '';
$student_id = trim($student_id);
$requested_term = trim($requested_term);

if ($student_id === ''){
  echo json_encode(['success'=>false,'message'=>'Missing student_id']);
  exit;
}

$terms = [];
$selected_term = '';

try {
  // Get distinct terms for this student
  if ($stmt = $conn->prepare("SELECT DISTINCT school_year_term FROM grades_record WHERE id_number=? AND school_year_term IS NOT NULL AND school_year_term<>'' ORDER BY school_year_term DESC")){
    $stmt->bind_param('s', $student_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row=$res->fetch_assoc()){ $terms[] = $row['school_year_term']; }
    $stmt->close();
  }

  if ($requested_term !== '' && preg_match(SAFE_TERM_REGEX, $requested_term)) {
    $selected_term = $requested_term;
  } elseif (!empty($terms)) {
    $selected_term = $terms[0];
  }

  // Build grades query
  $grades = [];
  if ($selected_term !== ''){
    $q = $conn->prepare("SELECT id, id_number, subject, teacher_name, prelim, midterm, pre_finals, finals, school_year_term FROM grades_record WHERE id_number=? AND school_year_term=? ORDER BY subject ASC");
    $q->bind_param('ss', $student_id, $selected_term);
  } else {
    $q = $conn->prepare("SELECT id, id_number, subject, teacher_name, prelim, midterm, pre_finals, finals, school_year_term FROM grades_record WHERE id_number=? ORDER BY school_year_term DESC, subject ASC");
    $q->bind_param('s', $student_id);
  }
  if ($q){
    $q->execute();
    $r = $q->get_result();
    while($row=$r->fetch_assoc()){ $grades[]=$row; }
    $q->close();
  }

  echo json_encode(['success'=>true,'grades'=>$grades,'terms'=>$terms,'selected_term'=>$selected_term]);
  exit;
} catch (Throwable $e){
  echo json_encode(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()]);
  exit;
}
