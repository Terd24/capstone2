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

    // Build dynamic filters; semester is optional
    $sql = "SELECT MIN(o.id) AS id,
                   MIN(o.subject_id) AS subject_id,
                   MAX(s.name) AS name,
                   MAX(s.code) AS code,
                   LOWER(TRIM(s.name)) AS _nkey,
                   LOWER(TRIM(COALESCE(s.code,''))) AS _ckey
            FROM subject_offerings o
            JOIN subjects s ON s.id=o.subject_id
            WHERE o.active=1 AND o.grade_level=?";
    $types = 's';
    $params = [$grade];

    if ($sem !== '') { $sql .= " AND o.semester=?"; $types .= 's'; $params[] = $sem; }
    if ($strand !== '') { $sql .= " AND COALESCE(o.strand,'') = ?"; $types .= 's'; $params[] = $strand; }
    // If school year provided, include both exact match and NULL (compatibility with old behavior)
    if ($sy !== '') { $sql .= " AND COALESCE(o.school_year_term,'') IN ('', ?)"; $types .= 's'; $params[] = $sy; }

    $sql .= " GROUP BY _nkey, _ckey ORDER BY name ASC";
    $stmt = $conn->prepare($sql);
    if(!$stmt){ echo json_encode(['success'=>false,'message'=>'Query error']); exit; }
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
    // Normalize to '' to avoid multiple NULL-allowed duplicates
    $strand = trim((string)($data['strand'] ?? ''));
    $sem = trim((string)($data['semester'] ?? ''));
    $sy = trim((string)($data['sy'] ?? ''));
    if($sid<=0 || $grade==='' || $sem===''){ echo json_encode(['success'=>false,'message'=>'Missing fields']); exit; }

    // Prevent duplicates: check existing row using COALESCE null-safe comparison
    $chk = $conn->prepare("SELECT id FROM subject_offerings WHERE subject_id=? AND grade_level=? AND COALESCE(strand,'')=? AND semester=? AND COALESCE(school_year_term,'')=? LIMIT 1");
    $chk->bind_param('issss', $sid, $grade, $strand, $sem, $sy);
    $chk->execute();
    $existing = $chk->get_result()->fetch_assoc();
    if ($existing) { echo json_encode(['success'=>true, 'id'=>$existing['id']]); exit; }

    $stmt = $conn->prepare("INSERT INTO subject_offerings(subject_id, grade_level, strand, semester, school_year_term, active) VALUES(?,?,?,?,?,1)");
    $stmt->bind_param('issss', $sid, $grade, $strand, $sem, $sy);
    $stmt->execute();
    echo json_encode(['success'=>true, 'id'=>$conn->insert_id]);
    exit;
  }

  if($action === 'remove'){
    $id = (int)($data['id'] ?? 0); if($id<=0){ echo json_encode(['success'=>false,'message'=>'Invalid id']); exit; }
    $delete_orphan = !empty($data['delete_orphan']);
    // Get subject_id before deletion
    $sid = 0;
    if ($get = $conn->prepare("SELECT subject_id FROM subject_offerings WHERE id=?")){
      $get->bind_param('i', $id);
      if ($get->execute()){
        $res = $get->get_result();
        if ($row = $res->fetch_assoc()) { $sid = (int)$row['subject_id']; }
      }
    }
    // Delete the offering
    $stmt = $conn->prepare("DELETE FROM subject_offerings WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    // Optionally delete subject if it has no more offerings
    if ($delete_orphan && $sid > 0){
      if ($chk = $conn->prepare("SELECT COUNT(*) AS c FROM subject_offerings WHERE subject_id=?")){
        $chk->bind_param('i', $sid);
        if ($chk->execute()){
          $rc = $chk->get_result()->fetch_assoc();
          if ((int)($rc['c'] ?? 0) === 0){
            // Delete subject; CASCADE removes nothing further due to FK in offerings
            if ($del = $conn->prepare("DELETE FROM subjects WHERE id=?")){
              $del->bind_param('i', $sid);
              $del->execute();
            }
          }
        }
      }
    }
    echo json_encode(['success'=>true]);
    exit;
  }

  echo json_encode(['success'=>false,'message'=>'Unknown action']);
} catch (Throwable $e) {
  echo json_encode(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()]);
}
