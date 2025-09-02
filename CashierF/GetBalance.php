<?php
include '../StudentLogin/db_conn.php';
header('Content-Type: application/json');

if (!isset($_GET['rfid_uid'])) {
    echo json_encode(["error" => "No RFID provided"]);
    exit;
}

$rfid_uid = strtoupper(trim($_GET['rfid_uid']));

// ✅ Get student basic info
$stmt = $conn->prepare("SELECT id_number, CONCAT(first_name, ' ', last_name) as full_name, academic_track as program, grade_level as year_section FROM student_account WHERE UPPER(rfid_uid) = ?");
$stmt->bind_param("s", $rfid_uid);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo json_encode(["error" => "RFID not found"]);
    exit;
}
$student = $res->fetch_assoc();
$id_number = $student['id_number'];
$stmt->close();

// ✅ Get latest term (if any)
$term_stmt = $conn->prepare("SELECT school_year_term FROM student_balances WHERE id_number = ? ORDER BY school_year_term DESC LIMIT 1");
$term_stmt->bind_param("s", $id_number);
$term_stmt->execute();
$term_res = $term_stmt->get_result();

if ($term_res->num_rows === 0) {
    // 🔹 No balance record, but return student info with zero balance
    echo json_encode([
        "id_number" => $id_number,
        "full_name" => $student['full_name'],
        "program" => $student['program'],
        "year_section" => $student['year_section'],
        "school_year_term" => "No balance record",
        "tuition_fee" => 0,
        "other_fees" => 0,
        "student_fees" => 0,
        "gross_total" => 0,
        "history" => []
    ]);
    exit;
}

$term_row = $term_res->fetch_assoc();
$school_year_term = $term_row['school_year_term'];
$term_stmt->close();

// ✅ Get balance data
$bal_stmt = $conn->prepare("SELECT tuition_fee, other_fees, student_fees FROM student_balances WHERE id_number = ? AND school_year_term = ? LIMIT 1");
$bal_stmt->bind_param("ss", $id_number, $school_year_term);
$bal_stmt->execute();
$bal_res = $bal_stmt->get_result();
$bal = $bal_res->fetch_assoc();

$tuition_fee = $bal['tuition_fee'] ?? 0;
$other_fees = $bal['other_fees'] ?? 0;
$student_fees = $bal['student_fees'] ?? 0;
$gross_total = $tuition_fee + $other_fees + $student_fees;
$bal_stmt->close();

// ✅ Get additional fee items
$item_stmt = $conn->prepare("SELECT fee_type, amount, paid FROM student_fee_items 
                             WHERE id_number = ? AND school_year_term = ?");
$item_stmt->bind_param("ss", $id_number, $school_year_term);
$item_stmt->execute();
$item_res = $item_stmt->get_result();

$fee_items = [];
while ($row = $item_res->fetch_assoc()) {
    $fee_items[] = $row;
    $gross_total += ($row['amount'] ?? 0);
}
$item_stmt->close();


// ✅ Get payment history (if any)
$hist_stmt = $conn->prepare("SELECT date, or_number, (misc_fee + other_school_fee + tuition_fee) AS amount 
                             FROM student_payments 
                             WHERE id_number = ? AND school_year_term = ? 
                             ORDER BY date DESC");
$hist_stmt->bind_param("ss", $id_number, $school_year_term);
$hist_stmt->execute();
$hist_res = $hist_stmt->get_result();
$history = [];
while ($row = $hist_res->fetch_assoc()) {
    $history[] = $row;
}
$hist_stmt->close();

// ✅ Final JSON response
echo json_encode([
    "id_number" => $id_number,
    "full_name" => $student['full_name'],
    "program" => $student['program'],
    "year_section" => $student['year_section'],
    "school_year_term" => $school_year_term,
    "tuition_fee" => $tuition_fee,
    "other_fees" => $other_fees,
    "student_fees" => $student_fees,
    "gross_total" => $gross_total,
    "custom_fees" => $fee_items,
    "history" => $history
]);
?>