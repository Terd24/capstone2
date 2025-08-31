<?php
include(__DIR__ . "/../../StudentLogin/db_conn.php");

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['check_duplicates'])) {
    $id_number = $_POST['id_number'] ?? '';
    $rfid_uid = $_POST['rfid_uid'] ?? '';
    
    $response = [
        'error_id' => '',
        'error_rfid' => ''
    ];
    
    // Check Student ID duplicate
    if (!empty($id_number)) {
        $check_id = $conn->prepare("SELECT id_number FROM student_account WHERE id_number=?");
        $check_id->bind_param("s", $id_number);
        $check_id->execute();
        $check_id->store_result();
        if ($check_id->num_rows > 0) {
            $response['error_id'] = "Student ID already in use!";
        }
        $check_id->close();
    }
    
    // Check RFID duplicate
    if (!empty($rfid_uid)) {
        $check_rfid = $conn->prepare("SELECT rfid_uid FROM student_account WHERE rfid_uid=?");
        $check_rfid->bind_param("s", $rfid_uid);
        $check_rfid->execute();
        $check_rfid->store_result();
        if ($check_rfid->num_rows > 0) {
            $response['error_rfid'] = "RFID already in use!";
        }
        $check_rfid->close();
    }
    
    echo json_encode($response);
    exit;
}
?>
