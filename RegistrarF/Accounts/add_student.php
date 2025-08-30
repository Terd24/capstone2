<?php
include(__DIR__ . "/../../StudentLogin/db_conn.php");

// Initialize error messages and old values
$error_id = $_SESSION['error_id'] ?? "";
$error_rfid = $_SESSION['error_rfid'] ?? "";
$old_id = $_SESSION['old_id'] ?? "";
$old_rfid = $_SESSION['old_rfid'] ?? "";
$success_msg = $_SESSION['success_msg'] ?? "";
$error_msg = $_SESSION['error_msg'] ?? "";
$form_data = $_SESSION['form_data'] ?? [];
$show_modal = $_SESSION['show_modal'] ?? false;

// Clear session errors after retrieving them
unset($_SESSION['error_id'], $_SESSION['error_rfid'], $_SESSION['old_id'], $_SESSION['old_rfid'], $_SESSION['success_msg'], $_SESSION['error_msg'], $_SESSION['form_data'], $_SESSION['show_modal']);

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

    // Validate required fields (middle_name is optional)
    if (empty($lrn) || empty($last_name) || empty($first_name) || empty($dob) || 
        empty($birthplace) || empty($gender) || empty($religion) || empty($academic_track) || 
        empty($grade_level) || empty($semester) || empty($school_year) || empty($enrollment_status) || 
        empty($payment_mode) || empty($father_name) || empty($mother_name) || 
        empty($id_number) || empty($raw_password) || empty($rfid_uid)) {
        
        $_SESSION['error_msg'] = "All required fields must be filled. (Middle name is optional)";
        $_SESSION['form_data'] = $form_data;
        $_SESSION['show_modal'] = true;
        header("Location: AccountList.php");
        exit();
    }

    // Server-side validation for data types
    $validation_errors = [];

    // Validate LRN (numbers only)
    if (!preg_match('/^[0-9]+$/', $lrn)) {
        $validation_errors[] = "LRN must contain numbers only.";
    }

    // Validate names (letters and spaces only)
    if (!preg_match('/^[A-Za-z\s]+$/', $last_name)) {
        $validation_errors[] = "Last name must contain letters only.";
    }
    if (!preg_match('/^[A-Za-z\s]+$/', $first_name)) {
        $validation_errors[] = "First name must contain letters only.";
    }
    if (!empty($middle_name) && !preg_match('/^[A-Za-z\s]*$/', $middle_name)) {
        $validation_errors[] = "Middle name must contain letters only.";
    }

    // Validate birthplace (letters, spaces, commas, periods, dashes)
    if (!preg_match('/^[A-Za-z\s,.-]+$/', $birthplace)) {
        $validation_errors[] = "Birthplace must contain valid location characters only.";
    }

    // Validate religion (letters and spaces only)
    if (!preg_match('/^[A-Za-z\s]+$/', $religion)) {
        $validation_errors[] = "Religion must contain letters only.";
    }

    // Validate school year (numbers and dash only)
    if (!preg_match('/^[0-9\-]+$/', $school_year)) {
        $validation_errors[] = "School year must be in format like 2024-2025.";
    }

    // Validate parent names (letters and spaces only)
    if (!preg_match('/^[A-Za-z\s]+$/', $father_name)) {
        $validation_errors[] = "Father's name must contain letters only.";
    }
    if (!preg_match('/^[A-Za-z\s]+$/', $mother_name)) {
        $validation_errors[] = "Mother's name must contain letters only.";
    }

    // Validate contact numbers (numbers, spaces, dashes, parentheses, plus signs)
    if (!empty($father_contact) && !preg_match('/^[0-9+\-\s()]+$/', $father_contact)) {
        $validation_errors[] = "Father's contact must contain numbers only.";
    }
    if (!empty($mother_contact) && !preg_match('/^[0-9+\-\s()]+$/', $mother_contact)) {
        $validation_errors[] = "Mother's contact must contain numbers only.";
    }
    if (!empty($guardian_contact) && !preg_match('/^[0-9+\-\s()]*$/', $guardian_contact)) {
        $validation_errors[] = "Guardian's contact must contain numbers only.";
    }

    // Validate occupations (letters and spaces only, optional)
    if (!empty($father_occupation) && !preg_match('/^[A-Za-z\s]*$/', $father_occupation)) {
        $validation_errors[] = "Father's occupation must contain letters only.";
    }
    if (!empty($mother_occupation) && !preg_match('/^[A-Za-z\s]*$/', $mother_occupation)) {
        $validation_errors[] = "Mother's occupation must contain letters only.";
    }
    if (!empty($guardian_occupation) && !preg_match('/^[A-Za-z\s]*$/', $guardian_occupation)) {
        $validation_errors[] = "Guardian's occupation must contain letters only.";
    }

    // Validate guardian name (letters and spaces only, optional)
    if (!empty($guardian_name) && !preg_match('/^[A-Za-z\s]*$/', $guardian_name)) {
        $validation_errors[] = "Guardian's name must contain letters only.";
    }

    // Validate last school (letters, spaces, periods, dashes, optional)
    if (!empty($last_school) && !preg_match('/^[A-Za-z\s.-]*$/', $last_school)) {
        $validation_errors[] = "Last school name must contain letters only.";
    }

    // Validate last school year (numbers and dash only, optional)
    if (!empty($last_school_year) && !preg_match('/^[0-9\-]*$/', $last_school_year)) {
        $validation_errors[] = "Last school year must be in format like 2023-2024.";
    }

    // Validate student ID (numbers only)
    if (!preg_match('/^[0-9]+$/', $id_number)) {
        $validation_errors[] = "Student ID must contain numbers only.";
    }

    // Validate RFID (numbers only)
    if (!preg_match('/^[0-9]+$/', $rfid_uid)) {
        $validation_errors[] = "RFID must contain numbers only.";
    }

    // If there are validation errors, return them
    if (!empty($validation_errors)) {
        $_SESSION['error_msg'] = implode('<br>', $validation_errors);
        $_SESSION['form_data'] = $form_data;
        $_SESSION['show_modal'] = true;
        header("Location: AccountList.php");
        exit();
    }

    // Only insert if no validation errors and no duplicate errors
    if (empty($error_id) && empty($error_rfid) && empty($validation_errors)) {
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
    
    // If there are duplicate errors, store them in session for display
    if (!empty($error_id) || !empty($error_rfid)) {
        if (!empty($error_id)) {
            $_SESSION['error_id'] = $error_id;
        }
        if (!empty($error_rfid)) {
            $_SESSION['error_rfid'] = $error_rfid;
        }
        $_SESSION['form_data'] = $form_data;
        $_SESSION['old_id'] = $old_id;
        $_SESSION['old_rfid'] = $old_rfid;
        $_SESSION['show_modal'] = true;
        header("Location: AccountList.php");
        exit();
    }
}

// Include the form
include("add_student_form.php");
?>
