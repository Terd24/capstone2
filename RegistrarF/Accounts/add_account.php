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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $account_type = $_POST['account_type'] ?? '';
    
    if ($account_type === 'student') {
        // Handle student account creation
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
        $username = $_POST['username'] ?? '';

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
                $error_msg = "Username already in use!";
            }
            $check_username->close();
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
            empty($id_number) || empty($raw_password) || empty($rfid_uid) || empty($username)) {
            
            $error_msg = "All required fields must be filled. (Middle name is optional)";
            $form_data = $_POST;
            $show_modal = true;
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

        // Validate username (letters, numbers, underscores only)
        if (!preg_match('/^[A-Za-z0-9_]+$/', $username)) {
            $validation_errors[] = "Username can only contain letters, numbers, and underscores.";
        }

        // If there are validation errors, return them
        if (!empty($validation_errors)) {
            $error_msg = implode('<br>', $validation_errors);
            $form_data = $_POST;
            $show_modal = true;
            // Don't redirect - stay on current page to show error
        }

        // Only insert if no validation errors and no duplicate errors
        if (empty($error_id) && empty($error_rfid) && empty($validation_errors)) {
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

            if ($stmt->execute()) {
                $_SESSION['success_msg'] = "Student account created successfully!";
                header("Location: AccountList.php?type=student");
                exit;
            } else {
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
        $rfid_uid = trim($_POST['rfid_uid'] ?? '');
        $username = trim($_POST['username'] ?? '');
        
        // Validation
        if (empty($last_name) || empty($first_name) || empty($dob) || empty($birthplace) || empty($gender) || 
            empty($address) || empty($id_number) || empty($password) || empty($username)) {
            $error_msg = "Please fill in all required fields.";
            $form_data = $_POST;
            $show_modal = true;
            // Don't redirect - stay on current page to show error
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
        
        $insert_stmt->close();
        $check_stmt->close();
    } elseif ($account_type === 'parent') {
        // Handle parent account creation
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $middle_name = trim($_POST['middle_name'] ?? '');
        $child_id = trim($_POST['child_id'] ?? '');
        $child_name = trim($_POST['child_name'] ?? '');
        $id_number = trim($_POST['id_number'] ?? '');
        $password = $_POST['password'] ?? '';
        $username = trim($_POST['username'] ?? '');
        
        // Validation
        if (empty($first_name) || empty($last_name) || empty($child_id) || 
            empty($id_number) || empty($password) || empty($username)) {
            $error_msg = "Please fill in all required fields.";
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
        
        // Check if ID already exists
        $check_stmt = $conn->prepare("SELECT parent_id FROM parent_account WHERE id_number = ?");
        $check_stmt->bind_param("s", $id_number);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error_msg = "ID number already exists. Please use a different ID number.";
            $form_data = $_POST;
            $show_modal = true;
        }
        
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
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert into parent_account table
        $stmt = $conn->prepare("INSERT INTO parent_account (first_name, last_name, middle_name, child_id, child_name, id_number, password, username) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $first_name, $last_name, $middle_name, $child_id, $child_name, $id_number, $hashed_password, $username);
        
        if ($stmt->execute()) {
            $_SESSION['success_msg'] = "Parent account created successfully!";
            header("Location: AccountList.php?type=parent");
            exit;
        } else {
            $error_msg = "Error creating parent account. Please try again.";
            $form_data = $_POST;
            $show_modal = true;
        }
        
        $insert_stmt->close();
        $check_stmt->close();
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
        <div class="flex justify-between items-center border-b border-gray-200 px-6 py-4 bg-[#1E4D92] text-white">
            <h2 class="text-lg font-semibold">Add New Account</h2>
            <button onclick="closeModal()" class="text-2xl font-bold hover:text-gray-300">&times;</button>
        </div>

        <!-- Account Type Selection -->
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <label class="block text-sm font-semibold mb-2">Select Account Type *</label>
            <select id="modalAccountType" onchange="handleModalAccountTypeChange()" class="w-full max-w-md border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46]" required>
                <option value="">-- Choose Account Type to Create --</option>
                <option value="student">Student Account</option>
                <option value="registrar">Registrar Account</option>
                <option value="cashier">Cashier Account</option>
                <option value="guidance">Guidance Account</option>
                <option value="parent">Parent Account</option>
            </select>
        </div>

        <!-- No Selection Message -->
        <div id="noSelectionMessage" class="px-6 py-12 text-center" style="display: block;">
            <div class="max-w-md mx-auto">
                <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Select Account Type</h3>
                <p class="text-gray-500">Choose the type of account you want to create from the dropdown above to get started.</p>
            </div>
        </div>

        <!-- Error Messages -->
        <?php if (!empty($error_msg)): ?>
            <div class="mx-6 mt-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
                <?= $error_msg ?>
            </div>
        <?php endif; ?>

        <!-- Include Form Files -->
        <?php include("add_student_form.php"); ?>
        <?php include("add_registrar_form.php"); ?>
        <?php include("add_cashier_form.php"); ?>
        <?php include("add_guidance_form.php"); ?>
        <?php include("add_parent_form.php"); ?>

    </div>
</div>

<!-- Hide Scrollbar -->
<style>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<script>
// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, checking for forms...');
    
    // Force check after a delay to ensure includes are loaded
    setTimeout(() => {
        const studentForm = document.getElementById('studentForm');
        const registrarForm = document.getElementById('registrarForm');
        console.log('Student form exists:', !!studentForm);
        console.log('Registrar form exists:', !!registrarForm);
        
        if (studentForm) console.log('Student form HTML:', studentForm.outerHTML.substring(0, 100));
        if (registrarForm) console.log('Registrar form HTML:', registrarForm.outerHTML.substring(0, 100));
    }, 500);
});

// Handle modal account type change
function handleModalAccountTypeChange() {
    const selectElement = document.getElementById('modalAccountType');
    const accountType = selectElement.value;
    console.log('=== MODAL ACCOUNT TYPE CHANGE ===');
    console.log('Selected value:', accountType);
    console.log('Select element:', selectElement);
    
    // Force show the form immediately
    if (accountType === 'student') {
        showStudentForm();
    } else if (accountType === 'registrar') {
        showRegistrarForm();
    } else if (accountType === 'cashier') {
        showCashierForm();
    } else if (accountType === 'guidance') {
        showGuidanceForm();
    } else if (accountType === 'parent') {
        showParentForm();
    } else {
        showSelectionMessage();
    }
}

function hideAllForms() {
    const forms = ['studentForm', 'registrarForm', 'cashierForm', 'guidanceForm', 'parentForm', 'noSelectionMessage'];
    forms.forEach(formId => {
        const form = document.getElementById(formId);
        if (form) {
            form.style.display = 'none';
        }
    });
}

function showStudentForm() {
    hideAllForms();
    document.getElementById('studentForm').style.display = 'block';
    console.log('✅ STUDENT FORM DISPLAYED');
}

function showRegistrarForm() {
    hideAllForms();
    document.getElementById('registrarForm').style.display = 'block';
    console.log('✅ REGISTRAR FORM DISPLAYED');
}

function showCashierForm() {
    hideAllForms();
    document.getElementById('cashierForm').style.display = 'block';
    console.log('✅ CASHIER FORM DISPLAYED');
}

function showGuidanceForm() {
    hideAllForms();
    document.getElementById('guidanceForm').style.display = 'block';
    console.log('✅ GUIDANCE FORM DISPLAYED');
}

function showParentForm() {
    hideAllForms();
    document.getElementById('parentForm').style.display = 'block';
    console.log('✅ PARENT FORM DISPLAYED');
}

function showSelectionMessage() {
    hideAllForms();
    document.getElementById('noSelectionMessage').style.display = 'block';
    console.log('✅ SELECTION MESSAGE DISPLAYED');
}

function openModal(){ 
    document.getElementById('addAccountModal').classList.remove('hidden'); 
    document.getElementById('modalContent').classList.remove('scale-95');
    document.getElementById('modalContent').classList.add('scale-100');
    
    // Check if there's a saved account type from form data (for error cases)
    const savedAccountType = '<?= htmlspecialchars($form_data['account_type'] ?? '') ?>';
    
    if (savedAccountType) {
        // Show the form that had errors
        document.getElementById('modalAccountType').value = savedAccountType;
        if (savedAccountType === 'student') {
            showStudentForm();
        } else if (savedAccountType === 'registrar') {
            showRegistrarForm();
        } else if (savedAccountType === 'cashier') {
            showCashierForm();
        } else if (savedAccountType === 'guidance') {
            showGuidanceForm();
        } else if (savedAccountType === 'parent') {
            showParentForm();
        }
        console.log('Restored form for account type:', savedAccountType);
    } else {
        // Reset to show selection message when modal opens normally
        document.getElementById('modalAccountType').value = '';
        showSelectionMessage();
    }
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

// Legacy function for compatibility
function showAccountForm(selectedType = null) {
    const accountType = selectedType || document.getElementById('accountType').value;
    if (accountType === 'student') {
        showStudentForm();
    } else if (accountType === 'registrar') {
        showRegistrarForm();
    } else if (accountType === 'cashier') {
        showCashierForm();
    } else if (accountType === 'guidance') {
        showGuidanceForm();
    } else if (accountType === 'parent') {
        showParentForm();
    } else {
        showSelectionMessage();
    }
}
</script>

<?php
// Clear session errors after JavaScript has read them
if (!($_SERVER["REQUEST_METHOD"] == "POST")) {
    unset($_SESSION['error_id'], $_SESSION['error_rfid'], $_SESSION['old_id'], $_SESSION['old_rfid'], $_SESSION['success_msg'], $_SESSION['error_msg'], $_SESSION['form_data'], $_SESSION['show_modal']);
}
?>
