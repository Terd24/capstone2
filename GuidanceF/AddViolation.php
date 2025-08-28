<?php
session_start();
include("../StudentLogin/db_conn.php");

// Allow only guidance role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'guidance') {
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_number = $_POST['id_number'] ?? '';
    $violation_date = $_POST['violation_date'] ?? '';
    $violation_type = $_POST['violation_type'] ?? '';

    if (!$id_number || !$violation_date || !$violation_type) {
        echo json_encode(["error" => "Missing required fields"]);
        exit;
    }

    // ✅ Count how many times this violation type already exists for the student
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM guidance_records WHERE id_number = ? AND remarks LIKE ?");
    $like = $violation_type . "%"; // match violation type
    $stmt->bind_param("ss", $id_number, $like);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $count = $result['total'];

    // ✅ Determine offense level
    if ($count == 0) $level = "1st Offense";
    else if ($count == 1) $level = "2nd Offense";
    else if ($count == 2) $level = "3rd Offense";
    else $level = ($count + 1) . "th Offense";

    // ✅ Combine violation type + level
    $remarks = $violation_type . " - " . $level;

    // ✅ Insert new record
    $stmt = $conn->prepare("INSERT INTO guidance_records (id_number, record_date, remarks) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $id_number, $violation_date, $remarks);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "remarks" => $remarks]);
    } else {
        echo json_encode(["error" => "Failed to save violation"]);
    }
    exit;
}
?>
