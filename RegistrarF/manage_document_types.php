<?php
session_start();
header('Content-Type: application/json');
include("../StudentLogin/db_conn.php");

// Require registrar login
if (!isset($_SESSION['registrar_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Ensure table exists (add is_submittable flag)
$conn->query("CREATE TABLE IF NOT EXISTS document_types (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  is_requestable TINYINT(1) NOT NULL DEFAULT 1,
  is_submittable TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
// Best-effort add for older installs
@$conn->query("ALTER TABLE document_types ADD COLUMN IF NOT EXISTS is_submittable TINYINT(1) NOT NULL DEFAULT 1");

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

try {
    if ($action === 'list') {
        $stmt = $conn->prepare("SELECT id, name, is_requestable, is_submittable, created_at FROM document_types ORDER BY name ASC");
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) { $rows[] = $r; }
        echo json_encode(['success' => true, 'items' => $rows]);
        exit;
    }

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $mode = $_POST['mode'] ?? '';
        // If flags provided explicitly, honor them; otherwise set by mode to keep types independent
        if (isset($_POST['is_requestable']) || isset($_POST['is_submittable'])) {
            $is_requestable = isset($_POST['is_requestable']) ? (int)($_POST['is_requestable'] ? 1 : 0) : 0;
            $is_submittable = isset($_POST['is_submittable']) ? (int)($_POST['is_submittable'] ? 1 : 0) : 0;
        } else if ($mode === 'submit') {
            $is_requestable = 0; $is_submittable = 1;
        } else if ($mode === 'request') {
            $is_requestable = 1; $is_submittable = 0;
        } else {
            // default legacy: both true
            $is_requestable = 1; $is_submittable = 1;
        }
        if ($name === '') { throw new Exception('Name is required'); }

        $stmt = $conn->prepare("INSERT INTO document_types (name, is_requestable, is_submittable) VALUES (?, ?, ?)");
        if (!$stmt) { throw new Exception($conn->error); }
        $stmt->bind_param('sii', $name, $is_requestable, $is_submittable);
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => 'Document type added', 'id' => $conn->insert_id]);
        exit;
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        if ($id <= 0 || $name === '') { throw new Exception('Invalid data'); }

        // Preserve existing flags unless explicitly provided
        $cur = $conn->prepare("SELECT is_requestable, is_submittable FROM document_types WHERE id=? LIMIT 1");
        $cur->bind_param('i', $id);
        $cur->execute();
        $curRes = $cur->get_result();
        $row = $curRes->fetch_assoc();
        if (!$row) { throw new Exception('Document type not found'); }
        $cur->close();

        $is_requestable = isset($_POST['is_requestable']) ? (int)($_POST['is_requestable'] ? 1 : 0) : (int)$row['is_requestable'];
        $is_submittable = isset($_POST['is_submittable']) ? (int)($_POST['is_submittable'] ? 1 : 0) : (int)$row['is_submittable'];

        $stmt = $conn->prepare("UPDATE document_types SET name=?, is_requestable=?, is_submittable=? WHERE id=?");
        if (!$stmt) { throw new Exception($conn->error); }
        $stmt->bind_param('siii', $name, $is_requestable, $is_submittable, $id);
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => 'Document type updated']);
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) { throw new Exception('Invalid ID'); }
        $stmt = $conn->prepare("DELETE FROM document_types WHERE id=?");
        if (!$stmt) { throw new Exception($conn->error); }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => 'Document type deleted']);
        exit;
    }

    throw new Exception('Unknown action');
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
