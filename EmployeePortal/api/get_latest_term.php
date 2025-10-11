<?php
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$role = $_SESSION['role'] ?? '';
if ($role !== 'teacher' && !isset($_SESSION['registrar_id'])) {
  echo json_encode(['success'=>false,'message'=>'Unauthorized']);
  exit;
}

require_once("../../StudentLogin/db_conn.php");

try{
  $latest = '';
  if ($q = $conn->query("SELECT school_year_term FROM grades_record WHERE school_year_term IS NOT NULL AND school_year_term<>'' ORDER BY school_year_term DESC LIMIT 1")){
    if ($row = $q->fetch_assoc()) $latest = $row['school_year_term'];
  }
  if ($latest === ''){
    // Default to current SY 1st Term
    $y = (int)date('Y');
    $m = (int)date('n');
    $start = ($m>=8)? $y : $y-1;
    $latest = sprintf('%d-%d 1st Term', $start, $start+1);
  }
  echo json_encode(['success'=>true,'latest_term'=>$latest]);
  exit;
}catch(Throwable $e){
  echo json_encode(['success'=>false,'message'=>'Server error']);
  exit;
}
