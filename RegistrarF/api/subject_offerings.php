<?php
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (!isset($_SESSION['registrar_id'])) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit; }

require_once("../../StudentLogin/db_conn.php");

function json_input(){
  $raw = file_get_contents('php://input');
  $d = json_decode($raw, true);
  return is_array($d)? $d: [];
}

// Ensure tables
$conn->query("CREATE TABLE IF NOT EXISTS subjects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) UNIQUE,
  name VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$conn->query("CREATE TABLE IF NOT EXISTS subject_offerings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  subject_id INT NOT NULL,
  grade_level VARCHAR(20) NOT NULL,
  strand VARCHAR(50) NULL,
  semester ENUM('1st','2nd') NOT NULL,
  school_year_term VARCHAR(50) NULL,
  active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_subject_offerings_subject FOREIGN KEY(subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_offer (subject_id, grade_level, strand, semester, school_year_term)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$action = $_GET['action'] ?? null;
if(!$action){ $body = json_input(); $action = $body['action'] ?? 'list'; }

try{
  if($action === 'list'){
    $grade = $_GET['grade_level'] ?? '';
    $strand = $_GET['strand'] ?? '';
    $sem = $_GET['semester'] ?? '';
    $sy = $_GET['sy'] ?? '';

    $sql = "SELECT o.id, o.subject_id AS subject_id, s.name, s.code FROM subject_offerings o JOIN subjects s ON s.id=o.subject_id WHERE o.active=1 AND o.grade_level=? AND o.semester=?";
    $types = 'ss';
    $params = [$grade, $sem];

    if($strand !== ''){ $sql .= " AND (o.strand = ? OR o.strand IS NULL)"; $types.='s'; $params[]=$strand; }
    if($sy !== ''){ $sql .= " AND (o.school_year_term = ? OR o.school_year_term IS NULL)"; $types.='s'; $params[]=$sy; }

    $sql .= " ORDER BY s.name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $items = [];
    while($row=$res->fetch_assoc()){ $items[]=$row; }
    echo json_encode(['success'=>true,'items'=>$items]);
    exit;
  }

  $data = json_input();

  if($action === 'assign'){
    $sid = (int)($data['subject_id'] ?? 0);
    $grade = trim($data['grade_level'] ?? '');
    $strand = $data['strand'] ?? null; if ($strand==='') $strand=null;
    $sem = $data['semester'] ?? '';
    $sy = $data['sy'] ?? null; if ($sy==='') $sy=null;
    if($sid<=0 || $grade==='' || $sem===''){ echo json_encode(['success'=>false,'message'=>'Missing fields']); exit; }

    $stmt = $conn->prepare("INSERT INTO subject_offerings(subject_id, grade_level, strand, semester, school_year_term) VALUES(?,?,?,?,?) ON DUPLICATE KEY UPDATE active=VALUES(active)");
    $active = 1;
    $stmt->bind_param('issss', $sid, $grade, $strand, $sem, $sy);
    $stmt->execute();
    echo json_encode(['success'=>true]);
    exit;
  }

  if($action === 'remove'){
    $id = (int)($data['id'] ?? 0); if($id<=0){ echo json_encode(['success'=>false,'message':'Invalid id']); exit; }
    $stmt = $conn->prepare("DELETE FROM subject_offerings WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    echo json_encode(['success'=>true]);
    exit;
  }

  echo json_encode(['success'=>false,'message'=>'Unknown action']);
} catch (Throwable $e) {
  echo json_encode(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()]);
}
