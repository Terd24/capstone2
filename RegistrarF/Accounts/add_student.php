<?php
include(__DIR__ . "/../../StudentLogin/db_conn.php");

// Initialize error messages and old values
$error_id = "";
$error_rfid = "";
$old_id = "";
$old_rfid = "";
$success_msg = "";

// Check if DB connection exists
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Safely collect form data with defaults and preserve for form repopulation
    $lrn = $_POST['lrn'] ?? '';
    $academic_track = $_POST['academic_track'] ?? '';
    $enrollment_status = $_POST['enrollment_status'] ?? '';
    $school_type = $_POST['school_type'] ?? '';

    $last_name = $_POST['last_name'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $middle_name = $_POST['middle_name'] ?? '';

    $school_year = $_POST['school_year'] ?? '';
    $grade_level = $_POST['grade_level'] ?? '';
    $semester = $_POST['semester'] ?? '';

    $dob = $_POST['dob'] ?? '';
    $birthplace = $_POST['birthplace'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $religion = $_POST['religion'] ?? '';

    $credentials = isset($_POST['credentials']) ? implode(",", $_POST['credentials']) : '';

    $payment_mode = $_POST['payment_mode'] ?? '';
    $address = $_POST['address'] ?? '';

    $father_name = $_POST['father_name'] ?? '';
    $father_occupation = $_POST['father_occupation'] ?? '';
    $father_contact = $_POST['father_contact'] ?? '';

    $mother_name = $_POST['mother_name'] ?? '';
    $mother_occupation = $_POST['mother_occupation'] ?? '';
    $mother_contact = $_POST['mother_contact'] ?? '';

    $guardian_name = $_POST['guardian_name'] ?? '';
    $guardian_occupation = $_POST['guardian_occupation'] ?? '';
    $guardian_contact = $_POST['guardian_contact'] ?? '';

    $last_school = $_POST['last_school'] ?? '';
    $last_school_year = $_POST['last_school_year'] ?? '';

    $id_number = $_POST['id_number'] ?? '';
    $raw_password = $_POST['password'] ?? '';
    $password = password_hash($raw_password, PASSWORD_DEFAULT);
    $rfid_uid = $_POST['rfid_uid'] ?? '';

    // Store all form data for repopulation
    $form_data = $_POST;

    $old_id = $id_number;
    $old_rfid = $rfid_uid;

    // Check duplicates - only if values are not empty
    if (!empty($id_number)) {
        $check_id = $conn->prepare("SELECT id_number FROM students WHERE id_number=?");
        $check_id->bind_param("s", $id_number);
        $check_id->execute();
        $check_id->store_result();
        if ($check_id->num_rows > 0) {
            $error_id = "Student ID already in use!";
        }
        $check_id->close();
    }

    if (!empty($rfid_uid)) {
        $check_rfid = $conn->prepare("SELECT rfid_uid FROM students WHERE rfid_uid=?");
        $check_rfid->bind_param("s", $rfid_uid);
        $check_rfid->execute();
        $check_rfid->store_result();
        if ($check_rfid->num_rows > 0) {
            $error_rfid = "RFID already in use!";
        }
        $check_rfid->close();
    }

    // Only insert if no errors
    if (empty($error_id) && empty($error_rfid)) {
        $sql = "INSERT INTO students (
            lrn, academic_track, enrollment_status, school_type,
            last_name, first_name, middle_name, 
            school_year, grade_level, semester,
            dob, birthplace, gender, religion, credentials, 
            payment_mode, address,
            father_name, father_occupation, father_contact,
            mother_name, mother_occupation, mother_contact,
            guardian_name, guardian_occupation, guardian_contact,
            last_school, last_school_year,
            id_number, password, rfid_uid
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sssssssssssssssssssssssssssssss",
            $lrn, $academic_track, $enrollment_status, $school_type,
            $last_name, $first_name, $middle_name,
            $school_year, $grade_level, $semester,
            $dob, $birthplace, $gender, $religion, $credentials,
            $payment_mode, $address,
            $father_name, $father_occupation, $father_contact,
            $mother_name, $mother_occupation, $mother_contact,
            $guardian_name, $guardian_occupation, $guardian_contact,
            $last_school, $last_school_year,
            $id_number, $password, $rfid_uid
        );

        if ($stmt->execute()) {
            $_SESSION['success_msg'] = "Student account created successfully!";
            // Redirect to AccountList.php to show success message
            header("Location: AccountList.php?type=student");
            exit;
        } else {
            echo "<div class='bg-red-500 text-white px-4 py-2 rounded mb-4'>Database error: " . $stmt->error . "</div>";
        }
    }
}

// Include the form
include("add_student_form.php");
?>
