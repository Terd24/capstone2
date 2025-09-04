<?php
include '../StudentLogin/db_conn.php';
header('Content-Type: application/json');

if (!isset($_GET['rfid_uid'])) {
    echo json_encode(["error" => "No RFID provided"]);
    exit;
}

$rfid_uid = strtoupper(trim($_GET['rfid_uid']));
$selected_term = isset($_GET['term']) ? trim($_GET['term']) : null;

// Get student basic info
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

// Determine which term to use
if ($selected_term) {
    // Use the selected term
    $school_year_term = $selected_term;
} else {
    // Get latest term from fee items (if any)
    $term_stmt = $conn->prepare("SELECT school_year_term FROM student_fee_items WHERE id_number = ? ORDER BY school_year_term DESC LIMIT 1");
    $term_stmt->bind_param("s", $id_number);
    $term_stmt->execute();
    $term_res = $term_stmt->get_result();
    
    if ($term_res->num_rows === 0) {
        $school_year_term = null;
    } else {
        $term_row = $term_res->fetch_assoc();
        $school_year_term = $term_row['school_year_term'];
    }
    $term_stmt->close();
}

// Check if we have a valid term
if (!$school_year_term) {
    // No fee items, but still check for payment history
    $hist_stmt = $conn->prepare("SELECT date, or_number, fee_type, amount, payment_method 
                                 FROM student_payments 
                                 WHERE id_number = ? 
                                 ORDER BY date DESC");
    $hist_stmt->bind_param("s", $id_number);
    $hist_stmt->execute();
    $hist_res = $hist_stmt->get_result();
    $history = [];
    while ($row = $hist_res->fetch_assoc()) {
        $history[] = $row;
    }
    $hist_stmt->close();
    
    echo json_encode([
        "id_number" => $id_number,
        "full_name" => $student['full_name'],
        "program" => $student['program'],
        "year_section" => $student['year_section'],
        "school_year_term" => "No balance record",
        "fee_items" => [],
        "gross_total" => 0,
        "total_paid" => 0,
        "remaining_balance" => 0,
        "history" => $history
    ]);
    exit;
}

// Get all fee items for this student and term
$item_stmt = $conn->prepare("SELECT id, fee_type, amount, paid FROM student_fee_items WHERE id_number = ? AND school_year_term = ? ORDER BY fee_type");
$item_stmt->bind_param("ss", $id_number, $school_year_term);
$item_stmt->execute();
$item_res = $item_stmt->get_result();

$fee_items = [];
$gross_total = 0;
$total_paid = 0;

while ($row = $item_res->fetch_assoc()) {
    $fee_items[] = $row;
    $gross_total += floatval($row['amount']);
    $total_paid += floatval($row['paid']);
}
$item_stmt->close();

$remaining_balance = $gross_total - $total_paid;

// Get payment history (if any)
$hist_stmt = $conn->prepare("SELECT date, or_number, fee_type, amount, payment_method 
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

// Final JSON response
echo json_encode([
    "id_number" => $id_number,
    "full_name" => $student['full_name'],
    "program" => $student['program'],
    "year_section" => $student['year_section'],
    "school_year_term" => $school_year_term,
    "fee_items" => $fee_items,
    "gross_total" => $gross_total,
    "total_paid" => $total_paid,
    "remaining_balance" => $remaining_balance,
    "history" => $history
]);
?>