<?php
session_start();
header("Content-Type: application/json");
require_once("../StudentLogin/db_conn.php"); // adjust if needed

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'guidance') {
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$id_number = $_POST['id_number'] ?? null;
$violations = isset($_POST['violations']) ? json_decode($_POST['violations'], true) : [];

if (!$id_number) {
    echo json_encode(["error" => "Missing student ID"]);
    exit;
}

// ✅ Clear old records from correct table
if (!$conn->query("DELETE FROM guidance_records WHERE id_number = '". $conn->real_escape_string($id_number) ."'")) {
    echo json_encode(["error" => "Delete failed: " . $conn->error]);
    exit;
}

// ✅ Prepare insert
$stmt = $conn->prepare("INSERT INTO guidance_records (id_number, record_date, remarks) VALUES (?, ?, ?)");
if (!$stmt) {
    echo json_encode(["error" => "Prepare failed: " . $conn->error]);
    exit;
}

foreach ($violations as $v) {
    $date = date("Y-m-d"); // default today
    $remarks = $v;

    if (preg_match('/\((.*?)\)$/', $v, $matches)) {
        $dateText = $matches[1];
        $timestamp = strtotime($dateText);
        if ($timestamp) {
            $date = date("Y-m-d", $timestamp);
        }
        $remarks = trim(preg_replace('/\s*\(.*?\)$/', '', $v));
    }

    $stmt->bind_param("sss", $id_number, $date, $remarks);
    if (!$stmt->execute()) {
        echo json_encode(["error" => "Insert failed: " . $stmt->error]);
        exit;
    }
}
$stmt->close();

echo json_encode(["success" => true]);
