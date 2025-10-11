<?php
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$role = $_SESSION['role'] ?? '';
if ($role !== 'teacher' && !isset($_SESSION['registrar_id'])) {
  echo json_encode(['error'=>'Unauthorized']);
  exit;
}

require_once("../../StudentLogin/db_conn.php");

$q = trim($_GET['query'] ?? '');
if ($q === ''){ echo json_encode(['students'=>[]]); exit; }

try{
  $like = "%$q%";
  $stmt = $conn->prepare("SELECT id_number, first_name, last_name, CONCAT(first_name,' ',last_name) AS full_name, academic_track AS program, grade_level AS year_section FROM student_account WHERE (id_number LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR CONCAT(first_name,' ',last_name) LIKE ?) LIMIT 50");
  $stmt->bind_param('ssss', $like, $like, $like, $like);
  $stmt->execute();
  $res = $stmt->get_result();
  $out = [];
  while($row=$res->fetch_assoc()){ $out[]=$row; }
  echo json_encode(['students'=>$out]);
  exit;
}catch(Throwable $e){
  echo json_encode(['error'=>'Server error']);
  exit;
}
