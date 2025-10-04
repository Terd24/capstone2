<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include(__DIR__ . "/../../StudentLogin/db_conn.php");

// Require registrar login
if (!isset($_SESSION['registrar_id'])) {
    header("Location: ../../StudentLogin/login.php");
    exit;
}

// Initialize error messages and old values
$error_id = $_SESSION['error_id'] ?? "";
$error_rfid = $_SESSION['error_rfid'] ?? "";
$old_id = $_SESSION['old_id'] ?? "";
$old_rfid = $_SESSION['old_rfid'] ?? "";
$success_msg = $_SESSION['success_msg'] ?? "";
$error_msg = $_SESSION['error_msg'] ?? "";
$form_data = $_SESSION['form_data'] ?? [];
$show_modal = $_SESSION['show_modal'] ?? false;

// Check if DB connection exists
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !defined('ADD_ACCOUNT_HANDLED')) {
    define('ADD_ACCOUNT_HANDLED', true);
    $account_type = $_POST['account_type'] ?? '';
    
    if ($account_type === 'unified_student_parent') {
        // Reset error flags to avoid stale session values
        $error_id = '';
        $error_rfid = '';
        $error_msg = '';
        $_SESSION['error_id'] = '';
        $_SESSION['error_rfid'] = '';

        // Handle unified student and parent account creation
        $lrn = trim($_POST['lrn'] ?? '');
        $academic_track = trim($_POST['academic_track'] ?? '');
        $enrollment_status = trim($_POST['enrollment_status'] ?? '');
        $school_type = trim($_POST['school_type'] ?? '');

        $last_name = trim($_POST['last_name'] ?? '');
        $first_name = trim($_POST['first_name'] ?? '');
        $middle_name = trim($_POST['middle_name'] ?? '');

        $school_year = trim($_POST['school_year'] ?? '');
        $grade_level = trim($_POST['grade_level'] ?? '');
        $semester = trim($_POST['semester'] ?? '');

        $dob = $_POST['dob'] ?? '';
        $birthplace = trim($_POST['birthplace'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $religion = trim($_POST['religion'] ?? '');

        $credentials = isset($_POST['credentials']) ? implode(",", $_POST['credentials']) : '';

        $payment_mode = trim($_POST['payment_mode'] ?? '');
        $address = trim($_POST['address'] ?? '');

        $father_name = trim($_POST['father_name'] ?? '');
        $father_occupation = trim($_POST['father_occupation'] ?? '');
        $father_contact = trim($_POST['father_contact'] ?? '');

        $mother_name = trim($_POST['mother_name'] ?? '');
        $mother_occupation = trim($_POST['mother_occupation'] ?? '');
        $mother_contact = trim($_POST['mother_contact'] ?? '');

        $guardian_name = trim($_POST['guardian_name'] ?? '');
        $guardian_occupation = trim($_POST['guardian_occupation'] ?? '');
        $guardian_contact = trim($_POST['guardian_contact'] ?? '');

        $last_school = trim($_POST['last_school'] ?? '');
        $last_school_year = trim($_POST['last_school_year'] ?? '');

        $id_number = trim($_POST['id_number'] ?? '');
        $raw_password = $_POST['password'] ?? '';
        $password = password_hash($raw_password, PASSWORD_DEFAULT);
        $rfid_uid = trim($_POST['rfid_uid'] ?? '');
        $username = trim($_POST['username'] ?? '');
        
        // Parent account fields (parent has their own username, but links to the child via student ID internally)
        $parent_username = trim($_POST['parent_username'] ?? '');
        $parent_password = $_POST['parent_password'] ?? '';
        $parent_hashed_password = !empty($parent_password) ? password_hash($parent_password, PASSWORD_DEFAULT) : '';

        // Store all form data for repopulation
        $form_data = $_POST;

        $old_id = $id_number;
        $old_rfid = $rfid_uid;

        // Check duplicates - only if values are not empty
        if (!empty($id_number)) {
            $check_id = $conn->prepare("SELECT id_number FROM student_account WHERE id_number=?");
            $check_id->bind_param("s", $id_number);
            $check_id->execute();
            $check_id->store_result();
            if ($check_id->num_rows > 0) {
                $error_id = "Student ID already in use!";
            }
            $check_id->close();
        }

        // Check username duplicates
        if (!empty($username)) {
            $check_username = $conn->prepare("SELECT username FROM student_account WHERE username=?");
            $check_username->bind_param("s", $username);
            $check_username->execute();
            $check_username->store_result();
            if ($check_username->num_rows > 0) {
                $error_msg = "Student username already in use!";
                // Keep modal open and preserve form inputs
                $form_data = $_POST;
                $show_modal = true;
                $_SESSION['error_msg'] = $error_msg;
                $_SESSION['form_data'] = $form_data;
                $_SESSION['show_modal'] = true;
            }
            $check_username->close();
        }
        
        // Check parent username duplicates
        if (!empty($parent_username)) {
            $check_parent_username = $conn->prepare("SELECT username FROM parent_account WHERE username=?");
            $check_parent_username->bind_param("s", $parent_username);
            $check_parent_username->execute();
            $check_parent_username->store_result();
            if ($check_parent_username->num_rows > 0) {
                $error_msg = "Parent username already in use!";
                // Keep modal open and preserve inputs
                $form_data = $_POST;
                $show_modal = true;
                $_SESSION['error_msg'] = $error_msg;
                $_SESSION['form_data'] = $form_data;
                $_SESSION['show_modal'] = true;
            }
            $check_parent_username->close();
        }

        if (!empty($rfid_uid)) {
            $check_rfid = $conn->prepare("SELECT rfid_uid FROM student_account WHERE rfid_uid=?");
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
            empty($id_number) || empty($raw_password) || empty($rfid_uid) || empty($username) ||
            empty($parent_username) || empty($parent_password)) {
            
            $error_msg = "All required fields must be filled. (Middle name is optional)";
            $form_data = $_POST;
            $show_modal = true;
            $_SESSION['error_msg'] = $error_msg;
            $_SESSION['form_data'] = $form_data;
            $_SESSION['show_modal'] = true;
            // Don't redirect - stay on current page to show error
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

        // Validate contact numbers: digits only and exactly 11 if provided
        if (!empty($father_contact)) {
            if (!preg_match('/^[0-9]+$/', $father_contact)) {
                $validation_errors[] = "Father's contact must contain digits only.";
            } elseif (strlen($father_contact) !== 11) {
                $validation_errors[] = "Father's contact must be exactly 11 digits.";
            }
        }
        if (!empty($mother_contact)) {
            if (!preg_match('/^[0-9]+$/', $mother_contact)) {
                $validation_errors[] = "Mother's contact must contain digits only.";
            } elseif (strlen($mother_contact) !== 11) {
                $validation_errors[] = "Mother's contact must be exactly 11 digits.";
            }
        }
        if (!empty($guardian_contact)) {
            if (!preg_match('/^[0-9]+$/', $guardian_contact)) {
                $validation_errors[] = "Guardian's contact must contain digits only.";
            } elseif (strlen($guardian_contact) !== 11) {
                $validation_errors[] = "Guardian's contact must be exactly 11 digits.";
            }
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

        // Validate student ID: numbers only, exactly 11 digits
        if (!preg_match('/^[0-9]+$/', $id_number)) {
            $validation_errors[] = "Student ID must contain digits only.";
        } elseif (strlen($id_number) !== 11) {
            $validation_errors[] = "Student ID must be exactly 11 digits.";
        }

        // Validate RFID: numbers only, exactly 10 digits
        if (!preg_match('/^[0-9]+$/', $rfid_uid)) {
            $validation_errors[] = "RFID must contain digits only.";
        } elseif (strlen($rfid_uid) !== 10) {
            $validation_errors[] = "RFID must be exactly 10 digits.";
        }

        // Validate username (letters, numbers, underscores only)
        if (!preg_match('/^[A-Za-z0-9_]+$/', $username)) {
            $validation_errors[] = "Student username can only contain letters, numbers, and underscores.";
        }
        
        // Validate parent username (letters, numbers, underscores only)
        if (!empty($parent_username) && !preg_match('/^[A-Za-z0-9_]+$/', $parent_username)) {
            $validation_errors[] = "Parent username can only contain letters, numbers, and underscores.";
        }

        // If there are validation errors, return them
        if (!empty($validation_errors)) {
            $error_msg = implode('<br>', $validation_errors);
            $form_data = $_POST;
            $show_modal = true;
            $_SESSION['error_msg'] = $error_msg;
            $_SESSION['form_data'] = $form_data;
            $_SESSION['show_modal'] = true;
            // Don't redirect - stay on current page to show error
        }

        // Only insert if no validation errors and no duplicate errors
        // Include $error_msg to block insert when any prior checks set an error (e.g., duplicate username)
        if (empty($error_id) && empty($error_rfid) && empty($validation_errors) && empty($error_msg)) {
            // Begin transaction to avoid partial inserts
            $conn->begin_transaction();

            $sql = "INSERT INTO student_account (
                lrn, academic_track, enrollment_status, school_type,
                last_name, first_name, middle_name, 
                school_year, grade_level, semester,
                dob, birthplace, gender, religion, credentials, 
                payment_mode, address,
                father_name, father_occupation, father_contact,
                mother_name, mother_occupation, mother_contact,
                guardian_name, guardian_occupation, guardian_contact,
                last_school, last_school_year,
                id_number, password, rfid_uid, username
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "ssssssssssssssssssssssssssssssss",
                $lrn, $academic_track, $enrollment_status, $school_type,
                $last_name, $first_name, $middle_name,
                $school_year, $grade_level, $semester,
                $dob, $birthplace, $gender, $religion, $credentials,
                $payment_mode, $address,
                $father_name, $father_occupation, $father_contact,
                $mother_name, $mother_occupation, $mother_contact,
                $guardian_name, $guardian_occupation, $guardian_contact,
                $last_school, $last_school_year,
                $id_number, $password, $rfid_uid, $username
            );

            $student_ok = $stmt->execute();
            if ($student_ok) {
                // Get the student ID for parent account creation
                $student_id = $id_number;
                $student_full_name = $first_name . ' ' . $last_name;
                
                // Create parent account
                if (!empty($parent_username) && !empty($parent_password)) {
                    // Insert minimal parent record: username, password, and link to child via student ID
                    $parent_sql = "INSERT INTO parent_account (username, password, child_id) VALUES (?, ?, ?)";
                    $parent_stmt = $conn->prepare($parent_sql);
                    $parent_stmt->bind_param(
                        "sss",
                        $parent_username,
                        $parent_hashed_password,
                        $student_id
                    );
                    
                    $parent_ok = $parent_stmt->execute();
                    $parent_stmt->close();

                    if (!$parent_ok) {
                        // Parent insert failed; rollback student to avoid duplicates on retry
                        $conn->rollback();
                        $error_msg = "Parent account creation failed. No data was saved. Please try again.";
                        $_SESSION['error_msg'] = $error_msg;
                        $_SESSION['form_data'] = $_POST;
                        $_SESSION['show_modal'] = true;
                        // Stop further processing
                        return;
                    }
                }

                // Insert submitted credentials into student's submitted_documents list
                // So that Documents page shows them immediately
                if (!empty($credentials)) {
                    $credItems = array_filter(array_map('trim', explode(',', $credentials)));
                    if (!empty($credItems)) {
                        $remarks = 'Submitted via Registrar onboarding';
                        $ins = $conn->prepare("INSERT INTO submitted_documents (id_number, document_name, date_submitted, remarks) VALUES (?, ?, NOW(), ?)");
                        if ($ins) {
                            foreach ($credItems as $docName) {
                                // Guard against excessively long names
                                $docName = substr($docName, 0, 255);
                                $ins->bind_param("sss", $student_id, $docName, $remarks);
                                $ins->execute();
                            }
                            $ins->close();
                        }
                    }
                }

                // All good: commit
                $conn->commit();
                $_SESSION['success_msg'] = !empty($parent_username) ? "Student and parent accounts created successfully!" : "Student account created successfully!";
                // Clear any lingering duplicate flags in session
                $_SESSION['error_id'] = '';
                $_SESSION['error_rfid'] = '';
                $_SESSION['error_msg'] = '';
                $_SESSION['form_data'] = [];
                $_SESSION['show_modal'] = false;
                header("Location: AccountList.php");
                exit;
            } else {
                // Student insert failed
                $conn->rollback();
                echo "<div class='bg-red-500 text-white px-4 py-2 rounded mb-4'>Database error: " . $stmt->error . "</div>";
            }
        }
        
        // If there are duplicate errors, show them inline
        if (!empty($error_id) || !empty($error_rfid)) {
            $form_data = $_POST;
            $old_id = $id_number;
            $old_rfid = $rfid_uid;
            $show_modal = true;
            // Don't redirect - stay on current page to show error
        }
    } elseif ($account_type === 'registrar') {
        // Handle registrar account creation
        $last_name = trim($_POST['last_name'] ?? '');
        $first_name = trim($_POST['first_name'] ?? '');
        $middle_name = trim($_POST['middle_name'] ?? '');
        $dob = $_POST['dob'] ?? '';
        $birthplace = trim($_POST['birthplace'] ?? '');
        $gender = $_POST['gender'] ?? '';
        $address = trim($_POST['address'] ?? '');
        $id_number = trim($_POST['id_number'] ?? '');
        $password = $_POST['password'] ?? '';
        $username = trim($_POST['username'] ?? '');
        
        // Validation
        if (empty($last_name) || empty($first_name) || empty($dob) || empty($birthplace) || empty($gender) || 
            empty($address) || empty($id_number) || empty($password) || empty($username)) {
            $error_msg = "Please fill in all required fields.";
            $form_data = $_POST;
            $show_modal = true;
            // Don't redirect - stay on current page to show error
        }

        // ID must be digits only and exactly 11
        if (!empty($id_number)) {
            if (!preg_match('/^[0-9]+$/', $id_number)) {
                $error_msg = ($error_msg ? $error_msg.'<br>' : '') . "Registrar ID must contain digits only.";
                $show_modal = true; $form_data = $_POST;
            } elseif (strlen($id_number) !== 11) {
                $error_msg = ($error_msg ? $error_msg.'<br>' : '') . "Registrar ID must be exactly 11 digits.";
                $show_modal = true; $form_data = $_POST;
            }
        }

        // Username pattern check
        if (!empty($username) && !preg_match('/^[A-Za-z0-9_]+$/', $username)) {
            $error_msg = ($error_msg ? $error_msg.'<br>' : '') . "Username can only contain letters, numbers, and underscores.";
            $form_data = $_POST;
            $show_modal = true;
        }
        
        // Check if ID already exists
        $check_stmt = $conn->prepare("SELECT registrar_id FROM registrar_account WHERE id_number = ?");
        $check_stmt->bind_param("s", $id_number);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error_msg = "ID number already exists. Please use a different value.";
            $form_data = $_POST;
            $show_modal = true;
        }
        
        // Check if username already exists
        $check_username_stmt = $conn->prepare("SELECT registrar_id FROM registrar_account WHERE username = ?");
        $check_username_stmt->bind_param("s", $username);
        $check_username_stmt->execute();
        $check_username_result = $check_username_stmt->get_result();
        
        if ($check_username_result->num_rows > 0) {
            $error_msg = "Username already exists. Please use a different username.";
            $form_data = $_POST;
            $show_modal = true;
        }
        $check_username_stmt->close();
        
        if (empty($error_msg)) {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert into registrar_account table
            $stmt = $conn->prepare("INSERT INTO registrar_account (first_name, last_name, middle_name, dob, birthplace, gender, address, id_number, password, username) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssss", $first_name, $last_name, $middle_name, $dob, $birthplace, $gender, $address, $id_number, $hashed_password, $username);
            
            if ($stmt->execute()) {
                $_SESSION['success_msg'] = "Registrar account created successfully!";
                header("Location: AccountList.php?type=registrar");
                exit;
            } else {
                $error_msg = "Error creating registrar account. Please try again.";
                $form_data = $_POST;
                $show_modal = true;
                // Don't redirect - stay on current page to show error
            }
            
            $stmt->close();
            $check_stmt->close();
        }
    } elseif ($account_type === 'cashier') {
        // Handle cashier account creation
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $middle_name = trim($_POST['middle_name'] ?? '');
        $dob = $_POST['dob'] ?? '';
        $birthplace = trim($_POST['birthplace'] ?? '');
        $gender = $_POST['gender'] ?? '';
        $address = trim($_POST['address'] ?? '');
        $id_number = trim($_POST['id_number'] ?? '');
        $password = $_POST['password'] ?? '';
        $username = trim($_POST['username'] ?? '');
        
        // Validation
        if (empty($first_name) || empty($last_name) || empty($dob) || empty($birthplace) || empty($gender) || 
            empty($address) || empty($id_number) || empty($password) || empty($username)) {
            $error_msg = "Please fill in all required fields.";
            $form_data = $_POST;
            $show_modal = true;
        }

        // ID must be digits only and exactly 11
        if (!empty($id_number)) {
            if (!preg_match('/^[0-9]+$/', $id_number)) {
                $error_msg = ($error_msg ? $error_msg.'<br>' : '') . "Cashier ID must contain digits only.";
                $show_modal = true; $form_data = $_POST;
            } elseif (strlen($id_number) !== 11) {
                $error_msg = ($error_msg ? $error_msg.'<br>' : '') . "Cashier ID must be exactly 11 digits.";
                $show_modal = true; $form_data = $_POST;
            }
        }

        // Username pattern check
        if (!empty($username) && !preg_match('/^[A-Za-z0-9_]+$/', $username)) {
            $error_msg = ($error_msg ? $error_msg.'<br>' : '') . "Username can only contain letters, numbers, and underscores.";
            $form_data = $_POST;
            $show_modal = true;
        }
        
        // Check if ID already exists
        $check_stmt = $conn->prepare("SELECT id FROM cashier_account WHERE id_number = ?");
        $check_stmt->bind_param("s", $id_number);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error_msg = "ID number already exists. Please use a different ID number.";
            $form_data = $_POST;
            $show_modal = true;
        }
        
        // Check if username already exists
        $check_username_stmt = $conn->prepare("SELECT id FROM cashier_account WHERE username = ?");
        $check_username_stmt->bind_param("s", $username);
        $check_username_stmt->execute();
        $check_username_result = $check_username_stmt->get_result();
        
        if ($check_username_result->num_rows > 0) {
            $error_msg = "Username already exists. Please use a different username.";
            $form_data = $_POST;
            $show_modal = true;
        }
        $check_username_stmt->close();
        
        if (empty($error_msg)) {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert cashier account
            $stmt = $conn->prepare("INSERT INTO cashier_account (first_name, last_name, middle_name, dob, birthplace, gender, address, id_number, password, username) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssssss", $first_name, $last_name, $middle_name, $dob, $birthplace, $gender, $address, $id_number, $hashed_password, $username);
            
            if ($stmt->execute()) {
                $_SESSION['success_msg'] = "Cashier account created successfully!";
                header("Location: AccountList.php?type=cashier");
                exit;
            } else {
                $error_msg = "Error creating cashier account. Please try again.";
                $form_data = $_POST;
                $show_modal = true;
            }
            
            $stmt->close();
            $check_stmt->close();
        }
    } elseif ($account_type === 'guidance') {
        // Handle guidance account creation
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $middle_name = trim($_POST['middle_name'] ?? '');
        $dob = $_POST['dob'] ?? '';
        $birthplace = trim($_POST['birthplace'] ?? '');
        $gender = $_POST['gender'] ?? '';
        $address = trim($_POST['address'] ?? '');
        $id_number = trim($_POST['id_number'] ?? '');
        $password = $_POST['password'] ?? '';
        $username = trim($_POST['username'] ?? '');
        
        // Validation
        if (empty($first_name) || empty($last_name) || empty($dob) || empty($birthplace) || empty($gender) || 
            empty($address) || empty($id_number) || empty($password) || empty($username)) {
            $error_msg = "Please fill in all required fields.";
            $form_data = $_POST;
            $show_modal = true;
        }

        // ID must be digits only and exactly 11
        if (!empty($id_number)) {
            if (!preg_match('/^[0-9]+$/', $id_number)) {
                $error_msg = ($error_msg ? $error_msg.'<br>' : '') . "Guidance ID must contain digits only.";
                $show_modal = true; $form_data = $_POST;
            } elseif (strlen($id_number) !== 11) {
                $error_msg = ($error_msg ? $error_msg.'<br>' : '') . "Guidance ID must be exactly 11 digits.";
                $show_modal = true; $form_data = $_POST;
            }
        }

        // Username pattern check
        if (!empty($username) && !preg_match('/^[A-Za-z0-9_]+$/', $username)) {
            $error_msg = ($error_msg ? $error_msg.'<br>' : '') . "Username can only contain letters, numbers, and underscores.";
            $form_data = $_POST;
            $show_modal = true;
        }
        
        // Check if ID already exists
        $check_stmt = $conn->prepare("SELECT id FROM guidance_account WHERE id_number = ?");
        $check_stmt->bind_param("s", $id_number);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error_msg = "ID number already exists. Please use a different ID number.";
            $form_data = $_POST;
            $show_modal = true;
        }
        
        // Check if username already exists
        $check_username_stmt = $conn->prepare("SELECT id FROM guidance_account WHERE username = ?");
        $check_username_stmt->bind_param("s", $username);
        $check_username_stmt->execute();
        $check_username_result = $check_username_stmt->get_result();
        
        if ($check_username_result->num_rows > 0) {
            $error_msg = "Username already exists. Please use a different username.";
            $form_data = $_POST;
            $show_modal = true;
        }
        $check_username_stmt->close();
        
        if (empty($error_msg)) {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert guidance account
            $insert_sql = "INSERT INTO guidance_account (first_name, last_name, middle_name, dob, birthplace, gender, address, id_number, password, username) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("ssssssssss", $first_name, $last_name, $middle_name, $dob, $birthplace, $gender, $address, $id_number, $hashed_password, $username);
            
            if ($insert_stmt->execute()) {
                $_SESSION['success_msg'] = "Guidance account created successfully!";
                header("Location: AccountList.php?type=guidance");
                exit;
            } else {
                $error_msg = "Error creating guidance account. Please try again.";
                $form_data = $_POST;
                $show_modal = true;
            }
            
            $stmt->close();
            $check_stmt->close();
        }
    } elseif ($account_type === 'parent') {
        // Handle parent account creation
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $middle_name = trim($_POST['middle_name'] ?? '');
        $child_id = trim($_POST['child_id'] ?? '');
        $child_name = trim($_POST['child_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $username = trim($_POST['username'] ?? '');
        
        // Validation
        if (empty($first_name) || empty($last_name) || empty($child_id) || 
            empty($password) || empty($username)) {
            $error_msg = "Please fill in all required fields.";
            $form_data = $_POST;
            $show_modal = true;
        }

        // Child ID must be digits only and exactly 11
        if (!empty($child_id)) {
            if (!preg_match('/^[0-9]+$/', $child_id)) {
                $error_msg = ($error_msg ? $error_msg+'<br>' : '') . "Student ID must contain digits only.";
                $show_modal = true; $form_data = $_POST;
            } elseif (strlen($child_id) !== 11) {
                $error_msg = ($error_msg ? $error_msg+'<br>' : '') . "Student ID must be exactly 11 digits.";
                $show_modal = true; $form_data = $_POST;
            }
        }

        // Username pattern check
        if (!empty($username) && !preg_match('/^[A-Za-z0-9_]+$/', $username)) {
            $error_msg = ($error_msg ? $error_msg.'<br>' : '') . "Username can only contain letters, numbers, and underscores.";
            $form_data = $_POST;
            $show_modal = true;
        }
        
        // Check if child ID exists in students table
        $check_child_stmt = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) as full_name FROM student_account WHERE id_number = ?");
        $check_child_stmt->bind_param("s", $child_id);
        $check_child_stmt->execute();
        $check_child_result = $check_child_stmt->get_result();
        
        if ($check_child_result->num_rows === 0) {
            $error_msg = "Student with ID $child_id not found. Please verify the student ID.";
            $form_data = $_POST;
            $show_modal = true;
        } else {
            // Get child's full name
            $child_data = $check_child_result->fetch_assoc();
            $child_name = $child_data['full_name'];
        }
        $check_child_stmt->close();
        
        // Check if parent already exists for this child
        $check_parent_stmt = $conn->prepare("SELECT parent_id FROM parent_account WHERE child_id = ?");
        $check_parent_stmt->bind_param("s", $child_id);
        $check_parent_stmt->execute();
        $check_parent_result = $check_parent_stmt->get_result();
        
        if ($check_parent_result->num_rows > 0) {
            $error_msg = "A parent account already exists for this student. Each student can only have one parent account.";
            $form_data = $_POST;
            $show_modal = true;
        }
        $check_parent_stmt->close();
        
        // Check if username already exists
        $check_username_stmt = $conn->prepare("SELECT parent_id FROM parent_account WHERE username = ?");
        $check_username_stmt->bind_param("s", $username);
        $check_username_stmt->execute();
        $check_username_result = $check_username_stmt->get_result();
        
        if ($check_username_result->num_rows > 0) {
            $error_msg = "Username already exists. Please use a different username.";
            $form_data = $_POST;
            $show_modal = true;
        }
        $check_username_stmt->close();
        
        if (empty($error_msg)) {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert into parent_account table (minimal fields)
            $stmt = $conn->prepare("INSERT INTO parent_account (username, password, child_id) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $hashed_password, $child_id);
            
            if ($stmt->execute()) {
                $_SESSION['success_msg'] = "Parent account created successfully!";
                header("Location: AccountList.php");
                exit;
            } else {
                $error_msg = "Error creating parent account. Please try again.";
                $form_data = $_POST;
                $show_modal = true;
            }
            
            $stmt->close();
        }
    } elseif ($account_type === 'attendance') {
        // Handle attendance account creation
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Validation
        if (empty($username) || empty($password)) {
            $error_msg = "Please fill in all required fields.";
            $form_data = $_POST;
            $show_modal = true;
        }
        
        // Validate username (letters, numbers, underscores only)
        if (!empty($username) && !preg_match('/^[A-Za-z0-9_]+$/', $username)) {
            $error_msg = "Username can only contain letters, numbers, and underscores.";
            $form_data = $_POST;
            $show_modal = true;
        }
        
        // Check if username already exists
        if (empty($error_msg)) {
            $check_stmt = $conn->prepare("SELECT id FROM attendance_account WHERE username = ?");
            $check_stmt->bind_param("s", $username);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $error_msg = "Username already exists. Please use a different username.";
                $form_data = $_POST;
                $show_modal = true;
            }
            $check_stmt->close();
        }
        
        // Hash password and insert if no errors
        if (empty($error_msg)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $insert_stmt = $conn->prepare("INSERT INTO attendance_account (username, password) VALUES (?, ?)");
            $insert_stmt->bind_param("ss", $username, $hashed_password);
            
            if ($insert_stmt->execute()) {
                $_SESSION['success_msg'] = "Attendance account created successfully!";
                header("Location: AccountList.php?type=attendance");
                exit;
            } else {
                $error_msg = "Error creating attendance account. Please try again.";
                $form_data = $_POST;
                $show_modal = true;
            }
            $insert_stmt->close();
        }
    }
}

?>

<!-- Success notification -->
<?php if (!empty($success_msg)): ?>
<div id="notif" class="bg-green-400 text-white px-3 py-2 rounded shadow mt-4 w-fit ml-auto mr-auto text-center">
    <?= htmlspecialchars($success_msg) ?>
</div>
<?php endif; ?>

<!-- Add Account Modal -->
<div id="addAccountModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white w-full max-w-5xl rounded-2xl shadow-2xl overflow-hidden border border-gray-200 transform transition-all scale-95" id="modalContent">
        
        <!-- Header -->
        <div class="flex justify-between items-center border-b border-gray-200 px-6 py-4 bg-[#0B2C62] text-white">
            <h2 class="text-lg font-semibold">Add New Account</h2>
            <button onclick="closeModal()" class="text-2xl font-bold hover:text-gray-300">&times;</button>
        </div>


        <!-- Error Messages -->
        <?php if (!empty($error_msg)): ?>
            <div class="mx-6 mt-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
                <?= $error_msg ?>
            </div>
        <?php endif; ?>

        <!-- Unified Student and Parent Form -->
        <div id="unifiedForm" class="account-form">
            <form method="POST" action="AccountList.php" autocomplete="off" class="px-6 py-6 grid grid-cols-1 md:grid-cols-3 gap-6 overflow-y-auto max-h-[80vh] no-scrollbar">
                <input type="hidden" name="account_type" value="unified_student_parent">
                
                <!-- Personal Information Section -->
                <div class="col-span-3 bg-gray-50 border-2 border-gray-400 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="tracking-wide">PERSONAL INFORMATION</span>
                    </h3>
                    
                    <!-- Row: LRN and Academic Track -->
                    <div class="grid grid-cols-3 gap-6 mb-4">
                        <div>
                            <label class="block text-sm font-semibold mb-1">LRN *</label>
                            <input type="text" name="lrn" required value="<?= htmlspecialchars($form_data['lrn'] ?? '') ?>" pattern="^[0-9]{12}$" maxlength="12" data-maxlen="12" inputmode="numeric" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46] digits-only" title="Please enter exactly 12 digits">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Academic Track / Course *</label>
                            <select name="academic_track" required class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                                <option value="">-- Select Academic Track / Course --</option>

                                <optgroup label="Pre-Elementary">
                                    <option value="Pre-Elementary" <?= ($form_data['academic_track'] ?? '') === 'Pre-Elementary' ? 'selected' : '' ?>>Kinder</option>
                                </optgroup>

                                <optgroup label="Elementary">
                                    <option value="Elementary" <?= ($form_data['academic_track'] ?? '') === 'Elementary' ? 'selected' : '' ?>>Elementary</option>
                                </optgroup>

                                <optgroup label="Junior High School">
                                    <option value="Junior High School" <?= ($form_data['academic_track'] ?? '') === 'Junior High School' ? 'selected' : '' ?>>Junior High School</option>
                                </optgroup>

                                <optgroup label="Senior High School Strands">
                                    <option value="ABM" <?= ($form_data['academic_track'] ?? '') === 'ABM' ? 'selected' : '' ?>>ABM (Accountancy, Business & Management)</option>
                                    <option value="GAS" <?= ($form_data['academic_track'] ?? '') === 'GAS' ? 'selected' : '' ?>>GAS (General Academic Strand)</option>
                                    <option value="HE" <?= ($form_data['academic_track'] ?? '') === 'HE' ? 'selected' : '' ?>>HE (Home Economics)</option>
                                    <option value="HUMSS" <?= ($form_data['academic_track'] ?? '') === 'HUMSS' ? 'selected' : '' ?>>HUMSS (Humanities & Social Sciences)</option>
                                    <option value="ICT" <?= ($form_data['academic_track'] ?? '') === 'ICT' ? 'selected' : '' ?>>ICT (Information and Communications Technology)</option>
                                    <option value="SPORTS" <?= ($form_data['academic_track'] ?? '') === 'SPORTS' ? 'selected' : '' ?>>SPORTS</option>
                                    <option value="STEM" <?= ($form_data['academic_track'] ?? '') === 'STEM' ? 'selected' : '' ?>>STEM (Science, Technology, Engineering & Mathematics)</option>
                                </optgroup>

                                <optgroup label="College Courses">
                                    <option value="Bachelor of Physical Education (BPed)" <?= ($form_data['academic_track'] ?? '') === 'Bachelor of Physical Education (BPed)' ? 'selected' : '' ?>>Bachelor of Physical Education (BPed)</option>
                                    <option value="Bachelor of Early Childhood Education (BECEd)" <?= ($form_data['academic_track'] ?? '') === 'Bachelor of Early Childhood Education (BECEd)' ? 'selected' : '' ?>>Bachelor of Early Childhood Education (BECEd)</option>
                                </optgroup>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Enrollment Status *</label>
                            <div class="flex items-center gap-6 mt-1">
                                <label class="flex items-center gap-2">
                                    <input type="radio" name="enrollment_status" value="OLD" <?= ($form_data['enrollment_status'] ?? '') === 'OLD' ? 'checked' : '' ?> onchange="toggleNewOptions()"> OLD
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="radio" name="enrollment_status" value="NEW" <?= ($form_data['enrollment_status'] ?? '') === 'NEW' ? 'checked' : '' ?> onchange="toggleNewOptions()"> NEW
                                </label>
                            </div>
                            <div id="newOptions" class="flex items-center gap-6 mt-3 <?= ($form_data['enrollment_status'] ?? '') === 'NEW' ? '' : 'hidden' ?> ml-4">
                                <label class="flex items-center gap-2">
                                    <input type="radio" name="school_type" value="PUBLIC" <?= ($form_data['school_type'] ?? '') === 'PUBLIC' ? 'checked' : '' ?>> Public
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="radio" name="school_type" value="PRIVATE" <?= ($form_data['school_type'] ?? '') === 'PRIVATE' ? 'checked' : '' ?>> Private
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Row: Full Name -->
                    <div class="grid grid-cols-3 gap-6 mb-4">
                        <div>
                            <label class="block text-sm font-semibold mb-1">First Name *</label>
                            <input type="text" name="first_name" autocomplete="off" required value="<?= htmlspecialchars($form_data['first_name'] ?? '') ?>" pattern="[A-Za-z\s]+" title="Please enter letters only" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46] letters-only">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Last Name *</label>
                            <input type="text" name="last_name" autocomplete="off" required value="<?= htmlspecialchars($form_data['last_name'] ?? '') ?>" pattern="[A-Za-z\s]+" title="Please enter letters only" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46] letters-only">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Middle Name <span class="text-gray-500 text-xs">(Optional)</span></label>
                            <input type="text" name="middle_name" autocomplete="off" value="<?= htmlspecialchars($form_data['middle_name'] ?? '') ?>" pattern="[A-Za-z\s]*" title="Please enter letters only" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46] letters-only">
                        </div>
                    </div>
                    
                    <!-- Academic Info Row -->
                    <div class="grid grid-cols-3 gap-6 mb-4">
                        <div>
                            <label class="block text-sm font-semibold mb-1">School Year *</label>
                            <select id="schoolYearSelect" name="school_year" required class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                                <option value="">Select Year</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Grade Level *</label>
                            <select id="gradeLevel" name="grade_level" required class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                                <option value="">-- Select Grade Level --</option>
                                <?php if (!empty($form_data['grade_level'])): ?>
                                    <option value="<?= htmlspecialchars($form_data['grade_level']) ?>" selected><?= htmlspecialchars($form_data['grade_level']) ?></option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Semester *</label>
                            <select name="semester" required class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                                <option value="">Select Term</option>
                                <option value="1st" <?= ($form_data['semester'] ?? '') === '1st' ? 'selected' : '' ?>>1st Term</option>
                                <option value="2nd" <?= ($form_data['semester'] ?? '') === '2nd' ? 'selected' : '' ?>>2nd Term</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Personal Details Row -->
                    <div class="grid grid-cols-3 gap-6 mb-4">
                        <div>
                            <label class="block text-sm font-semibold mb-1">Date of Birth *</label>
                            <input type="date" name="dob" required value="<?= htmlspecialchars($form_data['dob'] ?? '') ?>" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Birthplace *</label>
                            <input type="text" name="birthplace" autocomplete="off" required value="<?= htmlspecialchars($form_data['birthplace'] ?? '') ?>" pattern="[A-Za-z\s,.-]+" title="Please enter a valid location" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Gender *</label>
                            <select name="gender" required class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                                <option value="">Select Gender</option>
                                <option value="Male" <?= ($form_data['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= ($form_data['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Religion and Payment Row -->
                    <div class="grid grid-cols-3 gap-6 mb-4">
                        <div>
                            <label class="block text-sm font-semibold mb-1">Religion *</label>
                            <input type="text" name="religion" required value="<?= htmlspecialchars($form_data['religion'] ?? '') ?>" pattern="[A-Za-z\s]+" title="Please enter letters only" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Mode of Payment *</label>
                            <div class="flex items-center gap-6 mt-1">
                                <label class="flex items-center gap-2"><input type="radio" name="payment_mode" value="Cash" <?= ($form_data['payment_mode'] ?? '') === 'Cash' ? 'checked' : '' ?> required> Cash</label>
                                <label class="flex items-center gap-2"><input type="radio" name="payment_mode" value="Installment" <?= ($form_data['payment_mode'] ?? '') === 'Installment' ? 'checked' : '' ?> required> Installment</label>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Credentials Submitted</label>
                            <div class="grid grid-cols-2 gap-y-2 text-sm">
                                <?php $saved_credentials = $form_data['credentials'] ?? []; ?>
                                <label class="flex items-center gap-2"><input type="checkbox" name="credentials[]" value="F-138" <?= in_array('F-138', $saved_credentials) ? 'checked' : '' ?>> <span>F-138</span></label>
                                <label class="flex items-center gap-2"><input type="checkbox" name="credentials[]" value="Good Moral" <?= in_array('Good Moral', $saved_credentials) ? 'checked' : '' ?>> <span>Good Moral</span></label>
                                <label class="flex items-center gap-2"><input type="checkbox" name="credentials[]" value="PSA Birth" <?= in_array('PSA Birth', $saved_credentials) ? 'checked' : '' ?>> <span>PSA Birth</span></label>
                                <label class="flex items-center gap-2"><input type="checkbox" name="credentials[]" value="ESC Certification" <?= in_array('ESC Certification', $saved_credentials) ? 'checked' : '' ?>> <span>ESC Certification</span></label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Address -->
                    <div class="grid grid-cols-2 gap-6 mb-4">
                        <div>
                            <label class="block text-sm font-semibold mb-1">Complete Address</label>
                            <textarea name="address" autocomplete="off" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]"><?= htmlspecialchars($form_data['address'] ?? '') ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Family Information -->
                    <div class="space-y-4">
                        <h4 class="font-semibold text-gray-700">Father's Information</h4>
                        <div class="grid grid-cols-3 gap-6">
                            <input type="text" name="father_name" placeholder="Name *" autocomplete="off" required value="<?= htmlspecialchars($form_data['father_name'] ?? '') ?>" pattern="[A-Za-z\s]+" title="Please enter letters only" class="border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                            <input type="text" name="father_occupation" placeholder="Occupation" value="<?= htmlspecialchars($form_data['father_occupation'] ?? '') ?>" pattern="[A-Za-z\s]*" title="Please enter letters only" class="border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                            <input type="tel" name="father_contact" placeholder="Contact No." value="<?= htmlspecialchars($form_data['father_contact'] ?? '') ?>" pattern="^[0-9]{11}$" maxlength="11" title="Please enter 11 digits" class="border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46] digits-only" data-maxlen="11" inputmode="numeric">
                        </div>
                        
                        <h4 class="font-semibold text-gray-700">Mother's Information</h4>
                        <div class="grid grid-cols-3 gap-6">
                            <input type="text" name="mother_name" placeholder="Name *" autocomplete="off" required value="<?= htmlspecialchars($form_data['mother_name'] ?? '') ?>" pattern="[A-Za-z\s]+" title="Please enter letters only" class="border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                            <input type="text" name="mother_occupation" placeholder="Occupation" value="<?= htmlspecialchars($form_data['mother_occupation'] ?? '') ?>" pattern="[A-Za-z\s]*" title="Please enter letters only" class="border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                            <input type="tel" name="mother_contact" placeholder="Contact No." value="<?= htmlspecialchars($form_data['mother_contact'] ?? '') ?>" pattern="^[0-9]{11}$" maxlength="11" title="Please enter 11 digits" class="border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46] digits-only" data-maxlen="11" inputmode="numeric">
                        </div>
                        
                        <h4 class="font-semibold text-gray-700">Guardian's Information</h4>
                        <div class="grid grid-cols-3 gap-6">
                            <input type="text" name="guardian_name" placeholder="Name" autocomplete="off" value="<?= htmlspecialchars($form_data['guardian_name'] ?? '') ?>" pattern="[A-Za-z\s]*" title="Please enter letters only" class="border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                            <input type="text" name="guardian_occupation" placeholder="Occupation" value="<?= htmlspecialchars($form_data['guardian_occupation'] ?? '') ?>" pattern="[A-Za-z\s]*" title="Please enter letters only" class="border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                            <input type="tel" name="guardian_contact" placeholder="Contact No." value="<?= htmlspecialchars($form_data['guardian_contact'] ?? '') ?>" pattern="^[0-9]{11}$" maxlength="11" title="Please enter 11 digits" class="border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46] digits-only" data-maxlen="11" inputmode="numeric">
                        </div>
                    </div>
                    
                    <!-- Last School Attended -->
                    <div class="grid grid-cols-2 gap-6 mt-4">
                        <div>
                            <label class="block text-sm font-semibold mb-1">Last School Attended</label>
                            <input type="text" name="last_school" placeholder="School Name" autocomplete="off" value="<?= htmlspecialchars($form_data['last_school'] ?? '') ?>" pattern="[A-Za-z\s.-]*" title="Please enter letters only" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">School Year</label>
                            <select id="lastSchoolYearSelect" name="last_school_year" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                                <option value="">Select Year</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Parent Personal Account Section -->
                <div class="col-span-3 bg-gray-50 border-2 border-gray-400 rounded-lg p-4 mt-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <span class="tracking-wide">PARENT PERSONAL ACCOUNT</span>
                    </h3>
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold mb-1">Parent Username *</label>
                            <input type="text" name="parent_username" autocomplete="off" required value="<?= htmlspecialchars($form_data['parent_username'] ?? '') ?>" pattern="[A-Za-z0-9_]+" title="Username can only contain letters, numbers, and underscores" class="w-full border px-3 py-2 rounded-lg focus:ring-2 <?= (!empty($error_msg) && stripos($error_msg, 'Parent username') !== false) ? 'border-red-500 focus:ring-red-500 bg-red-50' : 'border-gray-300 focus:ring-[#2F8D46]' ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Parent Password *</label>
                            <input type="password" name="parent_password" autocomplete="new-password" required value="<?= htmlspecialchars($form_data['parent_password'] ?? '') ?>" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                        </div>
                    </div>
                </div>
                
                <!-- Personal Account Section -->
                <div class="col-span-3 bg-gray-50 border-2 border-gray-400 rounded-lg p-4 mt-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <span class="tracking-wide">PERSONAL ACCOUNT</span>
                    </h3>
                    <div class="grid grid-cols-4 gap-6">
                        <div>
                            <label class="block text-sm font-semibold mb-1">Username *</label>
                            <input type="text" name="username" autocomplete="off" required value="<?= htmlspecialchars($form_data['username'] ?? '') ?>" pattern="^[A-Za-z0-9_]+$" title="Username can only contain letters, numbers, and underscores" class="w-full border px-3 py-2 rounded-lg focus:ring-2 <?= (!empty($error_msg) && stripos($error_msg, 'Student username') !== false) ? 'border-red-500 focus:ring-red-500 bg-red-50' : 'border-gray-300 focus:ring-[#2F8D46]' ?>">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Student ID *</label>
                            <input type="text" name="id_number" autocomplete="off" required value="<?= htmlspecialchars($old_id ?? '') ?>" pattern="^[0-9]{11}$" maxlength="11" title="Please enter exactly 11 digits" class="w-full border px-3 py-2 rounded-lg focus:ring-2 <?= !empty($error_id) ? 'border-red-500 focus:ring-red-500 bg-red-50' : 'border-gray-300 focus:ring-[#2F8D46]' ?> digits-only" data-maxlen="11" inputmode="numeric">
                            <?php if (!empty($error_id)): ?>
                                <p class="text-red-500 text-sm mt-1 font-medium"><?= htmlspecialchars($error_id) ?></p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Password *</label>
                            <input type="password" name="password" autocomplete="new-password" required value="<?= htmlspecialchars($form_data['password'] ?? '') ?>" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">RFID Number *</label>
                            <input type="text" name="rfid_uid" autocomplete="off" required value="<?= htmlspecialchars($old_rfid ?? '') ?>" pattern="^[0-9]{10}$" maxlength="10" title="Please enter exactly 10 digits" class="w-full border px-3 py-2 rounded-lg focus:ring-2 <?= !empty($error_rfid) ? 'border-red-500 focus:ring-red-500 bg-red-50' : 'border-gray-300 focus:ring-[#2F8D46]' ?> digits-only" data-maxlen="10" inputmode="numeric">
                            <?php if (!empty($error_rfid)): ?>
                                <p class="text-red-500 text-sm mt-1 font-medium"><?= htmlspecialchars($error_rfid) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Submit Buttons -->
                <div class="col-span-3 flex justify-end gap-4 pt-6 border-t border-gray-200">
                    <button type="button" onclick="closeModal()" class="px-5 py-2 border border-[#0B2C62] text-[#0B2C62] rounded-xl hover:bg-[#0B2C62] hover:text-white transition">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-[#2F8D46] text-white rounded-xl shadow hover:bg-[#256f37] transition">Create Student & Parent Accounts</button>
                </div>
            </form>
        </div>

    </div>
</div>

<!-- Hide Scrollbar -->
<style>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<script>
// Unified form functions
function toggleNewOptions() {
    const newOptions = document.getElementById("newOptions");
    const isNew = document.querySelector('input[name="enrollment_status"]:checked')?.value === "NEW";
    newOptions.classList.toggle("hidden", !isNew);
}

// Grade level options mapping
const gradeOptions = {
  "Pre-Elementary": ["Kinder"],
  "Elementary": ["Grade 1","Grade 2","Grade 3","Grade 4","Grade 5","Grade 6"],
  "Junior High School": ["Grade 7","Grade 8","Grade 9","Grade 10"],
  "Senior High School Strands": ["Grade 11","Grade 12"],

  // Allow direct strand selections to work too
  "ABM": ["Grade 11","Grade 12"],
  "GAS": ["Grade 11","Grade 12"],
  "HE": ["Grade 11","Grade 12"],
  "HUMSS": ["Grade 11","Grade 12"],
  "ICT": ["Grade 11","Grade 12"],
  "SPORTS": ["Grade 11","Grade 12"],
  "STEM": ["Grade 11","Grade 12"],

  "College Courses": ["1st Year","2nd Year","3rd Year","4th Year"],
  "Bachelor of Physical Education (BPed)": ["1st Year","2nd Year","3rd Year","4th Year"],
  "Bachelor of Early Childhood Education (BECEd)": ["1st Year","2nd Year","3rd Year","4th Year"]
};

// Function to populate grade levels
function populateGradeLevels(selectedTrack, selectedGrade = '') {
    const academicTrack = document.querySelector('select[name="academic_track"]');
    const gradeLevel = document.getElementById('gradeLevel');
    
    if (!academicTrack || !gradeLevel) return;
    
    const selected = selectedTrack ? academicTrack.options[academicTrack.selectedIndex].parentNode.label : '';
    const course = selectedTrack;

    gradeLevel.innerHTML = '<option value="">-- Select Grade Level --</option>';

    let levels = [];
    if (gradeOptions[selected]) {
        levels = gradeOptions[selected];
    } else if (gradeOptions[course]) {
        levels = gradeOptions[course];
    }

    levels.forEach(level => {
        let option = document.createElement("option");
        option.value = level;
        option.textContent = level;
        if (level === selectedGrade) option.selected = true;
        gradeLevel.appendChild(option);
    });
}

// Wait for DOM to be fully loaded
// Populate helper: last school year (N years back)
function populateLastSchoolYears(yearsBack = 5) {
    const lastSySelect = document.getElementById('lastSchoolYearSelect');
    if (!lastSySelect) return;
    // Preserve the first placeholder option, clear the rest
    while (lastSySelect.options.length > 1) lastSySelect.remove(1);
    const now = new Date();
    const year = now.getFullYear();
    const savedLastSY = '<?= $form_data["last_school_year"] ?? "" ?>';
    for (let i = yearsBack; i >= 1; i--) {
        const start = year - i;
        const end = year - i + 1;
        const label = `${start}-${end}`;
        const opt = document.createElement('option');
        opt.value = label; opt.textContent = label;
        if (savedLastSY && savedLastSY === label) opt.selected = true;
        lastSySelect.appendChild(opt);
    }
    // Add current academic year as well (e.g., 2025-2026)
    const currentLabel = `${year}-${year+1}`;
    const curOpt = document.createElement('option');
    curOpt.value = currentLabel; curOpt.textContent = currentLabel;
    if (savedLastSY && savedLastSY === currentLabel) curOpt.selected = true;
    lastSySelect.appendChild(curOpt);
}

document.addEventListener('DOMContentLoaded', function() {
    // Set up academic track change listener
    const academicTrack = document.querySelector('select[name="academic_track"]');
    if (academicTrack) {
        academicTrack.addEventListener('change', function() {
            populateGradeLevels(academicTrack.value);
        });
    }
    
    // Initialize grade levels on page load if academic track is selected
    const savedTrack = '<?= $form_data["academic_track"] ?? "" ?>';
    const savedGrade = '<?= $form_data["grade_level"] ?? "" ?>';
    if (savedTrack && academicTrack) {
        populateGradeLevels(savedTrack, savedGrade);
    }

    // Populate School Year dropdown: previous, current, next
    const sySelect = document.getElementById('schoolYearSelect');
    if (sySelect) {
        // Determine academic year boundary (use calendar year; adjust if you have custom cutoff)
        const now = new Date();
        const year = now.getFullYear();
        // Build labels like 2024-2025
        const prev = `${year-1}-${year}`;
        const curr = `${year}-${year+1}`;
        const next = `${year+1}-${year+2}`;

        const options = [prev, curr, next];
        const savedSY = '<?= $form_data["school_year"] ?? "" ?>';
        options.forEach(val => {
            const opt = document.createElement('option');
            opt.value = val; opt.textContent = val;
            if (savedSY && savedSY === val) opt.selected = true;
            sySelect.appendChild(opt);
        });
        // If no saved selection, default to current
        if (!savedSY) {
            sySelect.value = curr;
        }
    }
});

function openModal(){ 
    document.getElementById('addAccountModal').classList.remove('hidden'); 
    document.getElementById('modalContent').classList.remove('scale-95');
    document.getElementById('modalContent').classList.add('scale-100');
    
    // Initialize grade levels if academic track is already selected
    setTimeout(() => {
        const academicTrack = document.querySelector('select[name="academic_track"]');
        const savedTrack = '<?= $form_data["academic_track"] ?? "" ?>';
        const savedGrade = '<?= $form_data["grade_level"] ?? "" ?>';
        if (savedTrack && academicTrack) {
            populateGradeLevels(savedTrack, savedGrade);
        }
        // Ensure last school year is (re)populated when opening
        populateLastSchoolYears(5);
    }, 100);
}

// Auto-open modal if there are errors from form submission
document.addEventListener('DOMContentLoaded', function() {
    const hasErrors = '<?= !empty($error_msg) || !empty($error_id) || !empty($error_rfid) ? "true" : "false" ?>';
    const showModal = '<?= $show_modal ? "true" : "false" ?>';
    
    if (hasErrors === 'true' && showModal === 'true') {
        console.log('Auto-opening modal due to form errors');
        openModal();
    }
});

function closeModal(){ 
    document.getElementById('addAccountModal').classList.add('hidden'); 
}

// Enforce digits-only and max length on inputs with class 'digits-only'
document.addEventListener('input', function(e){
    const el = e.target;
    if (el.classList && el.classList.contains('digits-only')) {
        const max = parseInt(el.getAttribute('data-maxlen')) || 0;
        // Strip non-digits
        el.value = el.value.replace(/\D+/g, '');
        // Enforce max length
        if (max > 0 && el.value.length > max) {
            el.value = el.value.slice(0, max);
        }
    }
});

</script>

<?php
// Clear session errors after JavaScript has read them
if (!($_SERVER["REQUEST_METHOD"] == "POST")) {
    unset($_SESSION['error_id'], $_SESSION['error_rfid'], $_SESSION['old_id'], $_SESSION['old_rfid'], $_SESSION['success_msg'], $_SESSION['error_msg'], $_SESSION['form_data'], $_SESSION['show_modal']);
}
?>