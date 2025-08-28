<?php
include '../StudentLogin/db_conn.php';
header('Content-Type: application/json');

if (!isset($_POST['rfid_uid'], $_POST['fee_type'], $_POST['amount'])) {
    echo json_encode(["success" => false, "error" => "Missing parameters"]);
    exit;
}

$rfid_uid = strtoupper(trim($_POST['rfid_uid']));
$fee_type = trim($_POST['fee_type']);
$amount = floatval($_POST['amount']);
$paid = isset($_POST['paid']) ? floatval($_POST['paid']) : 0;

// ✅ Get student ID
$stmt = $conn->prepare("SELECT id_number FROM student_account WHERE UPPER(rfid_uid) = ?");
$stmt->bind_param("s", $rfid_uid);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo json_encode(["success" => false, "error" => "Student not found"]);
    exit;
}
$row = $res->fetch_assoc();
$id_number = $row['id_number'];
$stmt->close();

// ✅ Get latest term
$term_stmt = $conn->prepare("SELECT school_year_term FROM student_balances WHERE id_number = ? ORDER BY school_year_term DESC LIMIT 1");
$term_stmt->bind_param("s", $id_number);
$term_stmt->execute();
$term_res = $term_stmt->get_result();
if ($term_res->num_rows === 0) {
    echo json_encode(["success" => false, "error" => "No balance record for student"]);
    exit;
}
$term_row = $term_res->fetch_assoc();
$school_year_term = $term_row['school_year_term'];
$term_stmt->close();

// ✅ Insert new fee
$insert = $conn->prepare("INSERT INTO student_fee_items (id_number, school_year_term, fee_type, amount, paid) VALUES (?,?,?,?,?)");
$insert->bind_param("sssdd", $id_number, $school_year_term, $fee_type, $amount, $paid);

if ($insert->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => $conn->error]);
}
$insert->close();
$conn->close();
?>
