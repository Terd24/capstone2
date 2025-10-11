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

$action = $_GET['action'] ?? ($_POST['action'] ?? null);
if(!$action){ $body = json_input(); $action = $body['action'] ?? 'list'; }

// Create table if missing (safe)
$conn->query("CREATE TABLE IF NOT EXISTS subjects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) UNIQUE,
  name VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

try {
  if ($action === 'list') {
    $q = trim($_GET['q'] ?? '');
    if ($q !== '') {
      $stmt = $conn->prepare("SELECT id, code, name FROM subjects WHERE name LIKE CONCAT('%', ?, '%') OR code LIKE CONCAT('%', ?, '%') ORDER BY name ASC");
      $stmt->bind_param('ss', $q, $q);
    } else {
      $stmt = $conn->prepare("SELECT id, code, name FROM subjects ORDER BY name ASC");
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $items = [];
    while($row = $res->fetch_assoc()){ $items[] = $row; }
    echo json_encode(['success'=>true,'items'=>$items]);
    exit;
  }

  $data = json_input();

  if ($action === 'create') {
    $code = $data['code'] ?? null; $name = trim($data['name'] ?? '');
    if ($name === '') { echo json_encode(['success'=>false,'message'=>'Name is required']); exit; }
    if ($code !== null && $code !== '') {
      $stmt = $conn->prepare("INSERT INTO subjects(code, name) VALUES(?, ?) ON DUPLICATE KEY UPDATE name=VALUES(name)");
      $stmt->bind_param('ss', $code, $name);
      $stmt->execute();
      // Get id by code (unique)
      $q = $conn->prepare("SELECT id FROM subjects WHERE code = ?");
      $q->bind_param('s', $code);
      $q->execute();
      $id = ($q->get_result()->fetch_assoc()['id']) ?? 0;
    } else {
      $stmt = $conn->prepare("INSERT INTO subjects(name) VALUES(?)");
      $stmt->bind_param('s', $name);
      $stmt->execute();
      $id = $conn->insert_id;
      if (!$id) { // fallback by name
        $q = $conn->prepare("SELECT id FROM subjects WHERE name = ? ORDER BY id DESC LIMIT 1");
        $q->bind_param('s', $name);
        $q->execute();
        $id = ($q->get_result()->fetch_assoc()['id']) ?? 0;
      }
    }
    echo json_encode(['success'=>true,'message'=>'Saved','id'=>$id]);
    exit;
  }

  if ($action === 'update') {
    $id = (int)($data['id'] ?? 0); $code = $data['code'] ?? null; $name = trim($data['name'] ?? '');
    if ($id <= 0 || $name === '') { echo json_encode(['success'=>false,'message'=>'Invalid data']); exit; }
    if ($code !== null && $code !== '') {
      $stmt = $conn->prepare("UPDATE subjects SET code=?, name=? WHERE id=?");
      $stmt->bind_param('ssi', $code, $name, $id);
    } else {
      $stmt = $conn->prepare("UPDATE subjects SET code=NULL, name=? WHERE id=?");
      $stmt->bind_param('si', $name, $id);
    }
    $stmt->execute();
    echo json_encode(['success'=>true,'message'=>'Updated']);
    exit;
  }

  if ($action === 'delete') {
    $id = (int)($data['id'] ?? 0); if ($id<=0){ echo json_encode(['success'=>false,'message'=>'Invalid id']); exit; }
    $stmt = $conn->prepare("DELETE FROM subjects WHERE id=?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    echo json_encode(['success'=>true]);
    exit;
  }

  echo json_encode(['success'=>false,'message'=>'Unknown action']);
} catch (Throwable $e) {
  echo json_encode(['success'=>false,'message'=>'Server error','error'=>$e->getMessage()]);
}
