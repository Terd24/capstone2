<?php
session_start();
include(__DIR__ . "/../../StudentLogin/db_conn.php");

// Require registrar login
if (!isset($_SESSION['registrar_id'])) {
    header("Location: ../StudentLogin/login.php");
    exit;
}

$student_id = isset($_GET['id']) ? trim($_GET['id']) : '';
$embed = isset($_GET['embed']) && $_GET['embed'] === '1';
$student_data = null;
// Parent account info
$parent_username = '';

if (!empty($student_id)) {
    // Fetch student plus any linked parent username in one query
    $stmt = $conn->prepare("SELECT s.*, p.username AS parent_username
                            FROM student_account s
                            LEFT JOIN parent_account p ON p.child_id = s.id_number
                            WHERE s.id_number = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student_data = $result->fetch_assoc();
    $stmt->close();

    if ($student_data && !empty($student_data['parent_username'])) {
        $parent_username = $student_data['parent_username'];
    }

    // Fallback direct lookup (in case multiple rows or join failed to populate)
    if (empty($parent_username)) {
        $pstmt = $conn->prepare("SELECT username FROM parent_account WHERE child_id = ? LIMIT 1");
        $pstmt->bind_param("s", $student_id);
        $pstmt->execute();
        $pres = $pstmt->get_result();
        if ($pres && $pres->num_rows >= 1) {
            $prow = $pres->fetch_assoc();
            $parent_username = $prow['username'] ?? '';
        }
        $pstmt->close();
    }
}

// Handle delete request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_student'])) {
    // Prefer POSTed id_number; fallback to the id from query string if necessary
    $target_id = $_POST['id_number'] ?? $student_id;
    
    // Ensure soft delete columns exist
    $conn->query("ALTER TABLE student_account ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL DEFAULT NULL");
    $conn->query("ALTER TABLE student_account ADD COLUMN IF NOT EXISTS deleted_by VARCHAR(255) NULL");
    $conn->query("ALTER TABLE student_account ADD COLUMN IF NOT EXISTS deleted_reason TEXT NULL");
    
    // Use soft delete - mark as deleted but keep in database
    $delete_stmt = $conn->prepare("UPDATE student_account SET deleted_at = NOW(), deleted_by = ?, deleted_reason = ? WHERE id_number = ?");
    $deleted_by = $_SESSION['registrar_name'] ?? 'Registrar';
    $deleted_reason = 'Deleted by registrar for administrative purposes';
    $delete_stmt->bind_param("sss", $deleted_by, $deleted_reason, $target_id);
    
    if ($delete_stmt->execute()) {
        $_SESSION['success_msg'] = "Student account deleted successfully!";
        header("Location: /onecci/RegistrarF/AccountList.php");
        exit;
    } else {
        $error_msg = "Error deleting student account.";
    }
    $delete_stmt->close();
}

// Handle edit form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_student'])) {
    // Collect form data
    $lrn = $_POST['lrn'] ?? '';
    $academic_track = $_POST['academic_track'] ?? '';
    $enrollment_status = $_POST['enrollment_status'] ?? '';
    $school_type = $_POST['school_type'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $middle_name = $_POST['middle_name'] ?? '';
    $username = $_POST['username'] ?? '';
    $school_year = $_POST['school_year'] ?? '';
    $grade_level = $_POST['grade_level'] ?? '';
    $semester = $_POST['semester'] ?? '';
    // Handle separate date fields for student
    $dob_day = $_POST['dob_day'] ?? '';
    $dob_month = $_POST['dob_month'] ?? '';
    $dob_year = $_POST['dob_year'] ?? '';
    
    // Combine into date format if all parts are provided
    $dob = '';
    if (!empty($dob_day) && !empty($dob_month) && !empty($dob_year)) {
        $dob = sprintf('%04d-%02d-%02d', $dob_year, $dob_month, $dob_day);
    } else {
        // Fallback to single dob field if separate fields not provided
        $dob = $_POST['dob'] ?? '';
    }
    $birthplace = $_POST['birthplace'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $religion = $_POST['religion'] ?? '';
    $credentials = isset($_POST['credentials']) ? implode(",", $_POST['credentials']) : '';
    $payment_mode = $_POST['payment_mode'] ?? '';
    $address = $_POST['address'] ?? '';
    
    // Father's Information - separate fields
    $father_first_name = trim($_POST['father_first_name'] ?? '');
    $father_last_name = trim($_POST['father_last_name'] ?? '');
    $father_middle_name = trim($_POST['father_middle_name'] ?? '');
    $father_name = trim($father_first_name . ' ' . $father_middle_name . ' ' . $father_last_name);
    $father_occupation = $_POST['father_occupation'] ?? '';
    $father_contact = $_POST['father_contact'] ?? '';
    
    // Mother's Information - separate fields
    $mother_first_name = trim($_POST['mother_first_name'] ?? '');
    $mother_last_name = trim($_POST['mother_last_name'] ?? '');
    $mother_middle_name = trim($_POST['mother_middle_name'] ?? '');
    $mother_name = trim($mother_first_name . ' ' . $mother_middle_name . ' ' . $mother_last_name);
    $mother_occupation = $_POST['mother_occupation'] ?? '';
    $mother_contact = $_POST['mother_contact'] ?? '';
    
    // Guardian's Information - separate fields
    $guardian_first_name = trim($_POST['guardian_first_name'] ?? '');
    $guardian_last_name = trim($_POST['guardian_last_name'] ?? '');
    $guardian_middle_name = trim($_POST['guardian_middle_name'] ?? '');
    $guardian_name = trim($guardian_first_name . ' ' . $guardian_middle_name . ' ' . $guardian_last_name);
    $guardian_occupation = $_POST['guardian_occupation'] ?? '';
    $guardian_contact = $_POST['guardian_contact'] ?? '';
    $last_school = $_POST['last_school'] ?? '';
    $last_school_year = $_POST['last_school_year'] ?? '';
    $id_number = $_POST['id_number'] ?? '';
    $rfid_uid = trim($_POST['rfid_uid'] ?? '');
    // Convert empty RFID to NULL to avoid duplicate key errors
    $rfid_uid = ($rfid_uid === '') ? null : $rfid_uid;
    $password = $_POST['password'] ?? '';
    $parent_password = $_POST['parent_password'] ?? '';

    // SERVER-SIDE VALIDATION - Prevent empty required fields
    $validation_errors = [];
    
    // Validate required fields
    if (empty(trim($lrn))) $validation_errors[] = "LRN is required.";
    if (empty(trim($last_name))) $validation_errors[] = "Last name is required.";
    if (empty(trim($first_name))) $validation_errors[] = "First name is required.";
    if (empty(trim($birthplace))) $validation_errors[] = "Birthplace is required.";
    if (empty(trim($religion))) $validation_errors[] = "Religion is required.";
    if (empty(trim($address))) $validation_errors[] = "Complete address is required.";
    if (empty(trim($father_first_name))) $validation_errors[] = "Father's first name is required.";
    if (empty(trim($father_last_name))) $validation_errors[] = "Father's last name is required.";
    if (empty(trim($father_occupation))) $validation_errors[] = "Father's occupation is required.";
    if (empty(trim($mother_first_name))) $validation_errors[] = "Mother's first name is required.";
    if (empty(trim($mother_last_name))) $validation_errors[] = "Mother's last name is required.";
    if (empty(trim($mother_occupation))) $validation_errors[] = "Mother's occupation is required.";
    if (empty(trim($guardian_first_name))) $validation_errors[] = "Guardian's first name is required.";
    if (empty(trim($guardian_last_name))) $validation_errors[] = "Guardian's last name is required.";
    if (empty(trim($guardian_occupation))) $validation_errors[] = "Guardian's occupation is required.";
    if (empty(trim($last_school))) $validation_errors[] = "Last school name is required.";
    if (empty(trim($last_school_year))) $validation_errors[] = "Last school year is required.";
    
    // Validate data types
    if (!empty($lrn) && !preg_match('/^[0-9]+$/', $lrn)) {
        $validation_errors[] = "LRN must contain numbers only.";
    }
    if (!empty($last_name) && !preg_match('/^[A-Za-z\s]+$/', $last_name)) {
        $validation_errors[] = "Last name must contain letters only.";
    }
    if (!empty($first_name) && !preg_match('/^[A-Za-z\s]+$/', $first_name)) {
        $validation_errors[] = "First name must contain letters only.";
    }
    if (!empty($middle_name) && !preg_match('/^[A-Za-z\s]*$/', $middle_name)) {
        $validation_errors[] = "Middle name must contain letters only.";
    }
    if (!empty($birthplace) && !preg_match('/^[A-Za-z\s,.-]+$/', $birthplace)) {
        $validation_errors[] = "Birthplace must contain valid location characters only.";
    }
    if (!empty($religion) && !preg_match('/^[A-Za-z\s]+$/', $religion)) {
        $validation_errors[] = "Religion must contain letters only.";
    }
    if (!empty($address) && strlen($address) < 20) {
        $validation_errors[] = "Complete address must be at least 20 characters long.";
    }
    if (!empty($father_occupation) && !preg_match('/^[A-Za-z\s]+$/', $father_occupation)) {
        $validation_errors[] = "Father's occupation must contain letters only.";
    }
    if (!empty($mother_occupation) && !preg_match('/^[A-Za-z\s]+$/', $mother_occupation)) {
        $validation_errors[] = "Mother's occupation must contain letters only.";
    }
    if (!empty($guardian_occupation) && !preg_match('/^[A-Za-z\s]+$/', $guardian_occupation)) {
        $validation_errors[] = "Guardian's occupation must contain letters only.";
    }
    
    // Validate parent name fields (letters and spaces only)
    if (!empty($father_first_name) && !preg_match('/^[A-Za-z\s]+$/', $father_first_name)) {
        $validation_errors[] = "Father's first name must contain letters only.";
    }
    if (!empty($father_last_name) && !preg_match('/^[A-Za-z\s]+$/', $father_last_name)) {
        $validation_errors[] = "Father's last name must contain letters only.";
    }
    if (!empty($father_middle_name) && !preg_match('/^[A-Za-z\s]*$/', $father_middle_name)) {
        $validation_errors[] = "Father's middle name must contain letters only.";
    }
    
    if (!empty($mother_first_name) && !preg_match('/^[A-Za-z\s]+$/', $mother_first_name)) {
        $validation_errors[] = "Mother's first name must contain letters only.";
    }
    if (!empty($mother_last_name) && !preg_match('/^[A-Za-z\s]+$/', $mother_last_name)) {
        $validation_errors[] = "Mother's last name must contain letters only.";
    }
    if (!empty($mother_middle_name) && !preg_match('/^[A-Za-z\s]*$/', $mother_middle_name)) {
        $validation_errors[] = "Mother's middle name must contain letters only.";
    }
    
    if (!empty($guardian_first_name) && !preg_match('/^[A-Za-z\s]+$/', $guardian_first_name)) {
        $validation_errors[] = "Guardian's first name must contain letters only.";
    }
    if (!empty($guardian_last_name) && !preg_match('/^[A-Za-z\s]+$/', $guardian_last_name)) {
        $validation_errors[] = "Guardian's last name must contain letters only.";
    }
    if (!empty($guardian_middle_name) && !preg_match('/^[A-Za-z\s]*$/', $guardian_middle_name)) {
        $validation_errors[] = "Guardian's middle name must contain letters only.";
    }
    
    // If validation fails, redirect back with error message
    if (!empty($validation_errors)) {
        $_SESSION['error_msg'] = implode('<br>', $validation_errors);
        header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . urlencode($student_id));
        exit;
    }

    // Update student record (username is excluded as it should remain readonly)
    if (!empty($password)) {
        // Update with new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $update_sql = "UPDATE student_account SET 
            lrn = ?, academic_track = ?, enrollment_status = ?, school_type = ?,
            last_name = ?, first_name = ?, middle_name = ?,
            school_year = ?, grade_level = ?, semester = ?,
            dob = ?, birthplace = ?, gender = ?, religion = ?, credentials = ?, 
            payment_mode = ?, address = ?,
            father_name = ?, father_occupation = ?, father_contact = ?,
            mother_name = ?, mother_occupation = ?, mother_contact = ?,
            guardian_name = ?, guardian_occupation = ?, guardian_contact = ?,
            last_school = ?, last_school_year = ?,
            rfid_uid = ?, password = ?
            WHERE id_number = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param(
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
            $rfid_uid, $hashed_password, $student_id
        );
    } else {
        // Update without changing password
        $update_sql = "UPDATE student_account SET 
            lrn = ?, academic_track = ?, enrollment_status = ?, school_type = ?,
            last_name = ?, first_name = ?, middle_name = ?,
            school_year = ?, grade_level = ?, semester = ?,
            dob = ?, birthplace = ?, gender = ?, religion = ?, credentials = ?, 
            payment_mode = ?, address = ?,
            father_name = ?, father_occupation = ?, father_contact = ?,
            mother_name = ?, mother_occupation = ?, mother_contact = ?,
            guardian_name = ?, guardian_occupation = ?, guardian_contact = ?,
            last_school = ?, last_school_year = ?,
            rfid_uid = ?
            WHERE id_number = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param(
            "ssssssssssssssssssssssssssssss",
            $lrn, $academic_track, $enrollment_status, $school_type,
            $last_name, $first_name, $middle_name,
            $school_year, $grade_level, $semester,
            $dob, $birthplace, $gender, $religion, $credentials,
            $payment_mode, $address,
            $father_name, $father_occupation, $father_contact,
            $mother_name, $mother_occupation, $mother_contact,
            $guardian_name, $guardian_occupation, $guardian_contact,
            $last_school, $last_school_year,
            $rfid_uid, $student_id
        );
    }

    if ($update_stmt->execute()) {
        // Sync credentials <-> submitted_documents
        $credentialOptions = ['F-138','Good Moral','PSA Birth','ESC Certification'];
        $selectedCreds = array_values(array_filter(array_map('trim', explode(',', $credentials))));

        // Fetch existing submitted credential document names for this student
        $existing = [];
        if ($q = $conn->prepare("SELECT document_name FROM submitted_documents WHERE id_number = ?")) {
            $q->bind_param("s", $student_id);
            if ($q->execute()) {
                $res = $q->get_result();
                while ($row = $res->fetch_assoc()) {
                    $name = $row['document_name'];
                    if (in_array($name, $credentialOptions, true)) {
                        $existing[$name] = true;
                    }
                }
            }
            $q->close();
        }

        // Insert newly checked credentials not yet in submitted_documents
        if ($ins = $conn->prepare("INSERT INTO submitted_documents (id_number, document_name, date_submitted, remarks) VALUES (?, ?, NOW(), ?)")) {
            $remarks = 'Submitted';
            foreach ($selectedCreds as $docName) {
                if (in_array($docName, $credentialOptions, true) && !isset($existing[$docName]) && $docName !== '') {
                    $doc = substr($docName, 0, 255);
                    $ins->bind_param("sss", $student_id, $doc, $remarks);
                    $ins->execute();
                }
            }
            $ins->close();
        }

        // Delete unchecked credential items from submitted_documents
        $selectedSet = array_flip($selectedCreds);
        if ($del = $conn->prepare("DELETE FROM submitted_documents WHERE id_number = ? AND document_name = ?")) {
            foreach ($credentialOptions as $opt) {
                if (!isset($selectedSet[$opt]) && isset($existing[$opt])) {
                    $doc = substr($opt, 0, 255);
                    $del->bind_param("ss", $student_id, $doc);
                    $del->execute();
                }
            }
            $del->close();
        }
        // Update parent password if provided
        if (!empty($parent_password)) {
            $parent_hashed = password_hash($parent_password, PASSWORD_DEFAULT);
            $up_parent = $conn->prepare("UPDATE parent_account SET password = ? WHERE child_id = ?");
            $up_parent->bind_param("ss", $parent_hashed, $student_id);
            $up_parent->execute();
            $up_parent->close();
        }
        $_SESSION['success_msg'] = "Student information updated successfully!";
        header("Location: /onecci/RegistrarF/AccountList.php?type=student");
        exit;
    } else {
        $error_msg = "Error updating student information.";
    }
    $update_stmt->close();
}

if (!$student_data) {
    header("Location: /onecci/RegistrarF/AccountList.php?type=student");
    exit;
}

// Parse combined parent names into separate fields for display
function parseFullName($fullName) {
    // Re-index array to avoid "Undefined array key" warnings
    $parts = array_values(array_filter(array_map('trim', explode(' ', $fullName ?? ''))));
    $result = ['first' => '', 'middle' => '', 'last' => ''];
    
    if (count($parts) === 1) {
        $result['first'] = $parts[0];
    } elseif (count($parts) === 2) {
        $result['first'] = $parts[0];
        $result['last'] = $parts[1];
    } elseif (count($parts) >= 3) {
        $result['first'] = $parts[0];
        $result['last'] = array_pop($parts);
        array_shift($parts); // Remove first name
        $result['middle'] = implode(' ', $parts);
    }
    
    return $result;
}

// Parse father's name (only if not already split in database)
if (empty($student_data['father_first_name']) && !empty($student_data['father_name'])) {
    $father_parsed = parseFullName($student_data['father_name']);
    $student_data['father_first_name'] = $father_parsed['first'];
    $student_data['father_middle_name'] = $father_parsed['middle'];
    $student_data['father_last_name'] = $father_parsed['last'];
}

// Parse mother's name (only if not already split in database)
if (empty($student_data['mother_first_name']) && !empty($student_data['mother_name'])) {
    $mother_parsed = parseFullName($student_data['mother_name']);
    $student_data['mother_first_name'] = $mother_parsed['first'];
    $student_data['mother_middle_name'] = $mother_parsed['middle'];
    $student_data['mother_last_name'] = $mother_parsed['last'];
}

// Parse guardian's name (only if not already split in database)
if (empty($student_data['guardian_first_name']) && !empty($student_data['guardian_name'])) {
    $guardian_parsed = parseFullName($student_data['guardian_name']);
    $student_data['guardian_first_name'] = $guardian_parsed['first'];
    $student_data['guardian_middle_name'] = $guardian_parsed['middle'];
    $student_data['guardian_last_name'] = $guardian_parsed['last'];
}

// Normalize gender value for display (handles 'M'/'F' or 'Male'/'Female', any case/spacing)
$genderVal = '';
if ($student_data) {
    $g = strtoupper(trim($student_data['gender'] ?? ''));
    if ($g === 'MALE') $g = 'M';
    if ($g === 'FEMALE') $g = 'F';
    if ($g === 'M' || $g === 'F') $genderVal = $g;
}
?>
<?php if (!$embed): ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Information - CCI</title>
<script src="https://cdn.tailwindcss.com">\n</script>
<style>
input[type=number]::-webkit-inner-spin-button,
input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
input[type=number] { -moz-appearance: textfield; }
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
</head>
<body class="bg-gradient-to-br from-[#f3f6fb] to-[#e6ecf7] font-sans min-h-screen text-gray-900">
<?php endif; ?>



<!-- Student Info Modal -->
<div class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center pointer-events-none" style="z-index:2147483647;">
    <div class="bg-white w-full max-w-5xl rounded-2xl shadow-2xl overflow-hidden border-2 border-[#0B2C62] transform transition-all scale-100 pointer-events-auto" id="modalContent" style="z-index:2147483647;">
        
        <!-- Header -->
        <div class="flex justify-between items-center border-b border-gray-200 px-6 py-4 bg-[#0B2C62] rounded-t-2xl">
            <h2 class="text-lg font-semibold text-white">Student Information</h2>
            <div class="flex gap-3 items-center">
                <!-- Save on header (hidden until Edit is pressed) -->
                <button type="submit" id="saveBtn" form="studentForm" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition hidden">Save Changes</button>
                <button type="button" id="editBtn" onclick="toggleEdit()" class="px-4 py-2 bg-[#2F8D46] text-white rounded-lg hover:bg-[#256f37] transition">Edit</button>
                <form method="post" action="<?= $_SERVER['PHP_SELF'] ?>?id=<?= htmlspecialchars($student_id) ?>" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this student account? This action cannot be undone.')">
                    <input type="hidden" name="delete_student" value="1">
                    <input type="hidden" name="id_number" value="<?= htmlspecialchars($student_data['id_number'] ?? '') ?>">
                    <button type="submit" id="deleteBtn" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">Delete</button>
                </form>
                <button type="button" onclick="closeModal()" class="text-2xl font-bold text-white hover:text-gray-300">&times;</button>
            </div>
        </div>

        <!-- Form -->
        <form id="studentForm" method="POST" class="px-6 py-6 grid grid-cols-1 md:grid-cols-3 gap-6 overflow-y-auto max-h-[80vh] no-scrollbar">
            <input type="hidden" name="update_student" value="1">

            <!-- Personal Information Section -->
            <div class="col-span-3 mb-6">
                <div class="bg-gray-50 border-2 border-gray-400 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        PERSONAL INFORMATION
                    </h3>
                    <div class="grid grid-cols-3 gap-6">
                        <!-- Row: LRN, Academic Track, Enrollment Status -->
                        <div>
                            <label class="block text-sm font-semibold mb-1">LRN *</label>
                            <input type="text" name="lrn" value="<?= htmlspecialchars($student_data['lrn'] ?? '') ?>" readonly required pattern="^[0-9]{12}$" maxlength="12" data-maxlen="12" inputmode="numeric" oninput="this.value = this.value.replace(/\D/g, '').slice(0, 12)" class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field digits-only" title="Please enter exactly 12 digits">
                            <small class="text-red-500 text-xs error-message hidden">LRN is required</small>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Academic Track / Course</label>
                            <select name="academic_track" disabled class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                                <option value="">-- Select Academic Track / Course --</option>

                                <optgroup label="Pre-Elementary">
                                    <option value="Pre-Elementary" <?= ($student_data['academic_track'] ?? '') === 'Pre-Elementary' ? 'selected' : '' ?>>Kinder</option>
                                </optgroup>

                                <optgroup label="Elementary">
                                    <option value="Elementary" <?= ($student_data['academic_track'] ?? '') === 'Elementary' ? 'selected' : '' ?>>Elementary</option>
                                </optgroup>

                                <optgroup label="Junior High School">
                                    <option value="Junior High School" <?= ($student_data['academic_track'] ?? '') === 'Junior High School' ? 'selected' : '' ?>>Junior High School</option>
                                </optgroup>

                                <optgroup label="Senior High School Strands">
                                    <option value="ABM" <?= ($student_data['academic_track'] ?? '') === 'ABM' ? 'selected' : '' ?>>ABM (Accountancy, Business & Management)</option>
                                    <option value="GAS" <?= ($student_data['academic_track'] ?? '') === 'GAS' ? 'selected' : '' ?>>GAS (General Academic Strand)</option>
                                    <option value="HE" <?= ($student_data['academic_track'] ?? '') === 'HE' ? 'selected' : '' ?>>HE (Home Economics)</option>
                                    <option value="HUMSS" <?= ($student_data['academic_track'] ?? '') === 'HUMSS' ? 'selected' : '' ?>>HUMSS (Humanities & Social Sciences)</option>
                                    <option value="ICT" <?= ($student_data['academic_track'] ?? '') === 'ICT' ? 'selected' : '' ?>>ICT (Information and Communications Technology)</option>
                                    <option value="SPORTS" <?= ($student_data['academic_track'] ?? '') === 'SPORTS' ? 'selected' : '' ?>>SPORTS</option>
                                    <option value="STEM" <?= ($student_data['academic_track'] ?? '') === 'STEM' ? 'selected' : '' ?>>STEM (Science, Technology, Engineering & Mathematics)</option>
                                </optgroup>

                                <optgroup label="College Courses">
                                    <option value="Bachelor of Physical Education (BPed)" <?= ($student_data['academic_track'] ?? '') === 'Bachelor of Physical Education (BPed)' ? 'selected' : '' ?>>Bachelor of Physical Education (BPed)</option>
                                    <option value="Bachelor of Early Childhood Education (BECEd)" <?= ($student_data['academic_track'] ?? '') === 'Bachelor of Early Childhood Education (BECEd)' ? 'selected' : '' ?>>Bachelor of Early Childhood Education (BECEd)</option>
                                </optgroup>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Enrollment Status</label>
                            <div class="flex items-center gap-6 mt-1">
                                <label class="flex items-center gap-2">
                                    <input type="radio" name="enrollment_status" value="OLD" <?= (isset($student_data['enrollment_status']) && $student_data['enrollment_status'] === 'OLD') ? 'checked' : '' ?> disabled class="student-field" onchange="toggleNewOptions()"> OLD
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="radio" name="enrollment_status" value="NEW" <?= (isset($student_data['enrollment_status']) && $student_data['enrollment_status'] === 'NEW') ? 'checked' : '' ?> disabled class="student-field" onchange="toggleNewOptions()"> NEW
                                </label>
                            </div>
                            <div id="newOptions" class="flex items-center gap-6 mt-3 <?= (isset($student_data['enrollment_status']) && $student_data['enrollment_status'] === 'NEW') ? '' : 'hidden' ?> ml-4">
                                <label class="flex items-center gap-2">
                                    <input type="radio" name="school_type" value="PUBLIC" <?= (isset($student_data['school_type']) && ($student_data['school_type'] === 'PUBLIC' || $student_data['school_type'] === 'Public')) ? 'checked' : '' ?> disabled class="student-field"> Public
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="radio" name="school_type" value="PRIVATE" <?= (isset($student_data['school_type']) && ($student_data['school_type'] === 'PRIVATE' || $student_data['school_type'] === 'Private')) ? 'checked' : '' ?> disabled class="student-field"> Private
                                </label>
                            </div>
                        </div>

                        <!-- Row: Full Name -->
                                                 <div>
                            <label class="block text-sm font-semibold mb-1">First Name *</label>
                            <input type="text" name="first_name" value="<?= htmlspecialchars($student_data['first_name'] ?? '') ?>" readonly required pattern="[A-Za-z\s]+" title="Please enter letters only" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')" class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field letters-only">
                            <small class="text-red-500 text-xs error-message hidden">First Name is required</small>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Last Name *</label>
                            <input type="text" name="last_name" value="<?= htmlspecialchars($student_data['last_name'] ?? '') ?>" readonly required pattern="[A-Za-z\s]+" title="Please enter letters only" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')" class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field letters-only">
                            <small class="text-red-500 text-xs error-message hidden">Last Name is required</small>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Middle Name <span class="text-gray-500 text-xs">(Optional)</span></label>
                            <input type="text" name="middle_name" value="<?= htmlspecialchars($student_data['middle_name'] ?? '') ?>" readonly pattern="[A-Za-z\s]*" title="Please enter letters only" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')" class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field letters-only">
                        </div>

                        <!-- Row: School Year, Grade Level, Semester -->
                        <div>
                            <label class="block text-sm font-semibold mb-1">School Year</label>
                            <select id="schoolYearSelect" name="school_year" disabled class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                                <option value="">Select Year</option>
                                <?php
                                $saved_sy = $student_data['school_year'] ?? '';
                                if (!empty($saved_sy)) {
                                    echo '<option value="' . htmlspecialchars($saved_sy) . '" selected>' . htmlspecialchars($saved_sy) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Grade Level</label>
                            <select id="gradeLevel" name="grade_level" disabled class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                                <option value="">-- Select Grade Level --</option>
                                <?php if (!empty($student_data['grade_level'])): ?>
                                    <option value="<?= htmlspecialchars($student_data['grade_level']) ?>" selected><?= htmlspecialchars($student_data['grade_level']) ?></option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Semester</label>
                            <select name="semester" disabled class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                                <option value="">Select Term</option>
                                <option value="1st" <?= ($student_data['semester'] ?? '') === '1st' ? 'selected' : '' ?>>1st Term</option>
                                <option value="2nd" <?= ($student_data['semester'] ?? '') === '2nd' ? 'selected' : '' ?>>2nd Term</option>
                            </select>
                        </div>

                        <!-- Row: Date of Birth, Birthplace, Gender -->
                        <div>
                            <label class="block text-sm font-semibold mb-1">Date of Birth</label>
                            <div class="grid grid-cols-3 gap-2">
                                <select name="dob_month" disabled class="border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                                    <option value="">Month</option>
                                    <?php
                                    $months = [
                                        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                                        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                                        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                                    ];
                                    $selected_month = '';
                                    if (!empty($student_data['dob'])) {
                                        $selected_month = date('n', strtotime($student_data['dob']));
                                    }
                                    foreach ($months as $num => $name) {
                                        $selected = ($selected_month == $num) ? 'selected' : '';
                                        echo "<option value='$num' $selected>$name</option>";
                                    }
                                    ?>
                                </select>
                                <select name="dob_day" disabled class="border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                                    <option value="">Day</option>
                                    <?php
                                    $selected_day = '';
                                    if (!empty($student_data['dob'])) {
                                        $selected_day = date('j', strtotime($student_data['dob']));
                                    }
                                    for ($i = 1; $i <= 31; $i++) {
                                        $selected = ($selected_day == $i) ? 'selected' : '';
                                        echo "<option value='$i' $selected>$i</option>";
                                    }
                                    ?>
                                </select>
                                <select name="dob_year" disabled class="border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                                    <option value="">Year</option>
                                    <?php
                                    $current_year = date('Y');
                                    $selected_year = '';
                                    if (!empty($student_data['dob'])) {
                                        $selected_year = date('Y', strtotime($student_data['dob']));
                                    }
                                    for ($year = ($current_year - 4); $year >= ($current_year - 70); $year--) {
                                        $selected = ($selected_year == $year) ? 'selected' : '';
                                        echo "<option value='$year' $selected>$year</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Birthplace *</label>
                            <input type="text" name="birthplace" value="<?= htmlspecialchars($student_data['birthplace'] ?? '') ?>" readonly required pattern="[A-Za-z\s,.\-]+" title="Please enter a valid location" oninput="this.value = this.value.replace(/[^A-Za-z\s,.\-]/g, '')" class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                            <small class="text-red-500 text-xs error-message hidden">Birthplace is required</small>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Gender</label>
                            <select name="gender" disabled class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                                <option value="">-- Select Gender --</option>
                                <option value="M" <?= (isset($student_data['gender']) && (strtoupper($student_data['gender']) === 'M' || strtoupper($student_data['gender']) === 'MALE')) ? 'selected' : '' ?>>Male</option>
                                <option value="F" <?= (isset($student_data['gender']) && (strtoupper($student_data['gender']) === 'F' || strtoupper($student_data['gender']) === 'FEMALE')) ? 'selected' : '' ?>>Female</option>
                            </select>
                        </div>

                        <!-- Row: Religion, Credentials, Payment Mode -->
                        <div>
                            <label class="block text-sm font-semibold mb-1">Religion *</label>
                            <input type="text" name="religion" value="<?= htmlspecialchars($student_data['religion'] ?? '') ?>" readonly required pattern="[A-Za-z\s]+" title="Please enter letters only" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')" class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field letters-only">
                            <small class="text-red-500 text-xs error-message hidden">Religion is required</small>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Credentials Submitted</label>
                            <div class="grid grid-cols-2 gap-y-2 text-sm ml-2">
                                <?php $saved_credentials = explode(',', $student_data['credentials'] ?? ''); ?>
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="credentials[]" value="F-138" <?= in_array('F-138', $saved_credentials) ? 'checked' : '' ?> disabled class="student-field"> 
                                    <span>F-138</span>
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="credentials[]" value="Good Moral" <?= in_array('Good Moral', $saved_credentials) ? 'checked' : '' ?> disabled class="student-field"> 
                                    <span>Good Moral</span>
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="credentials[]" value="PSA Birth" <?= in_array('PSA Birth', $saved_credentials) ? 'checked' : '' ?> disabled class="student-field"> 
                                    <span>PSA Birth</span>
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="credentials[]" value="ESC Certification" <?= in_array('ESC Certification', $saved_credentials) ? 'checked' : '' ?> disabled class="student-field"> 
                                    <span>ESC Certification</span>
                                </label>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Mode of Payment</label>
                            <div class="flex items-center gap-6 mt-1">
                                <label class="flex items-center gap-2">
                                    <input type="radio" name="payment_mode" value="Cash" <?= ($student_data['payment_mode'] ?? '') === 'Cash' ? 'checked' : '' ?> disabled class="student-field"> Cash
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="radio" name="payment_mode" value="Installment" <?= ($student_data['payment_mode'] ?? '') === 'Installment' ? 'checked' : '' ?> disabled class="student-field"> Installment
                                </label>
                            </div>
                        </div>

                        <!-- Complete Address -->
                        <div class="col-span-3">
                            <label class="block text-sm font-semibold mb-1">Complete Address *</label>
                            <textarea name="address" readonly required minlength="20" maxlength="500" placeholder="Enter complete address (e.g., Block 8, Lot 15, Subdivision Name, Barangay, City, Province)" title="Please enter a complete address with at least 20 characters including street, barangay, city/municipality, and province" class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field"><?= htmlspecialchars($student_data['address'] ?? '') ?></textarea>
                            <p class="text-xs text-gray-500 mt-1">Minimum 20 characters. Include street, barangay, city/municipality, and province.</p>
                            <small class="text-red-500 text-xs error-message hidden">Complete Address is required</small>
                        </div>

                        <!-- Father's Information -->
                        <div class="col-span-3 mt-6">
                            <h4 class="font-semibold text-gray-700 mb-3">Father's Information *</h4>
                            <div class="grid grid-cols-3 gap-6 mb-2">
                                <div>
                                    <label class="block text-sm font-semibold mb-1">First Name *</label>
                                    <input type="text" name="father_first_name" value="<?= htmlspecialchars($student_data['father_first_name'] ?? '') ?>" readonly required pattern="[A-Za-z\s]+" title="Please enter letters only" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')" class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field letters-only">
                                    <small class="text-red-500 text-xs error-message hidden">Father's first name is required</small>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold mb-1">Last Name *</label>
                                    <input type="text" name="father_last_name" value="<?= htmlspecialchars($student_data['father_last_name'] ?? '') ?>" readonly required pattern="[A-Za-z\s]+" title="Please enter letters only" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')" class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field letters-only">
                                    <small class="text-red-500 text-xs error-message hidden">Father's last name is required</small>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold mb-1">Middle Name <span class="text-gray-500 text-xs">(Optional)</span></label>
                                    <input type="text" name="father_middle_name" value="<?= htmlspecialchars($student_data['father_middle_name'] ?? '') ?>" readonly pattern="[A-Za-z\s]*" title="Please enter letters only" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')" class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field letters-only">
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold mb-1">Occupation *</label>
                                    <input type="text" name="father_occupation" value="<?= htmlspecialchars($student_data['father_occupation'] ?? '') ?>" readonly required pattern="[A-Za-z\s]+" title="Please enter letters only" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')" class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field letters-only">
                                    <small class="text-red-500 text-xs error-message hidden">Father's occupation is required</small>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold mb-1">Contact</label>
                                    <input type="tel" name="father_contact" value="<?= htmlspecialchars($student_data['father_contact'] ?? '') ?>" pattern="^[0-9]{11}$" maxlength="11" title="Please enter 11 digits" oninput="this.value = this.value.replace(/\D/g, '').slice(0, 11)" class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-white student-field digits-only" data-maxlen="11" inputmode="numeric">
                                </div>
                            </div>
                        </div>

                        <!-- Mother's Information -->
                        <div class="col-span-3 mt-6">
                            <h4 class="font-semibold text-gray-700 mb-3">Mother's Information *</h4>
                            <div class="grid grid-cols-3 gap-6 mb-2">
                                <div>
                                    <label class="block text-sm font-semibold mb-1">First Name *</label>
                                    <input type="text" name="mother_first_name" value="<?= htmlspecialchars($student_data['mother_first_name'] ?? '') ?>" readonly required pattern="[A-Za-z\s]+" title="Please enter letters only" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')" class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field letters-only">
                                    <small class="text-red-500 text-xs error-message hidden">Mother's first name is required</small>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold mb-1">Last Name *</label>
                                    <input type="text" name="mother_last_name" value="<?= htmlspecialchars($student_data['mother_last_name'] ?? '') ?>" readonly required pattern="[A-Za-z\s]+" title="Please enter letters only" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')" class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field letters-only">
                                    <small class="text-red-500 text-xs error-message hidden">Mother's last name is required</small>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold mb-1">Middle Name <span class="text-gray-500 text-xs">(Optional)</span></label>
                                    <input type="text" name="mother_middle_name" value="<?= htmlspecialchars($student_data['mother_middle_name'] ?? '') ?>" readonly pattern="[A-Za-z\s]*" title="Please enter letters only" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')" class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field letters-only">
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold mb-1">Occupation *</label>
                                    <input type="text" name="mother_occupation" value="<?= htmlspecialchars($student_data['mother_occupation'] ?? '') ?>" readonly required pattern="[A-Za-z\s]+" title="Please enter letters only" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')" class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field letters-only">
                                    <small class="text-red-500 text-xs error-message hidden">Mother's occupation is required</small>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold mb-1">Contact</label>
                                    <input type="tel" name="mother_contact" value="<?= htmlspecialchars($student_data['mother_contact'] ?? '') ?>" pattern="^[0-9]{11}$" maxlength="11" title="Please enter 11 digits" oninput="this.value = this.value.replace(/\D/g, '').slice(0, 11)" class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-white student-field digits-only" data-maxlen="11" inputmode="numeric">
                                </div>
                            </div>
                        </div>

                        <!-- Guardian's Information -->
                        <div class="col-span-3 mt-6">
                            <h4 class="font-semibold text-gray-700 mb-3">Guardian's Information *</h4>
                            <div class="grid grid-cols-3 gap-6 mb-2">
                                <div>
                                    <label class="block text-sm font-semibold mb-1">First Name *</label>
                                    <input type="text" name="guardian_first_name" value="<?= htmlspecialchars($student_data['guardian_first_name'] ?? '') ?>" readonly required pattern="[A-Za-z\s]+" title="Please enter letters only" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')" class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field letters-only">
                                    <small class="text-red-500 text-xs error-message hidden">Guardian's first name is required</small>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold mb-1">Last Name *</label>
                                    <input type="text" name="guardian_last_name" value="<?= htmlspecialchars($student_data['guardian_last_name'] ?? '') ?>" readonly required pattern="[A-Za-z\s]+" title="Please enter letters only" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')" class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field letters-only">
                                    <small class="text-red-500 text-xs error-message hidden">Guardian's last name is required</small>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold mb-1">Middle Name <span class="text-gray-500 text-xs">(Optional)</span></label>
                                    <input type="text" name="guardian_middle_name" value="<?= htmlspecialchars($student_data['guardian_middle_name'] ?? '') ?>" readonly pattern="[A-Za-z\s]*" title="Please enter letters only" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')" class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field letters-only">
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold mb-1">Occupation *</label>
                                    <input type="text" name="guardian_occupation" value="<?= htmlspecialchars($student_data['guardian_occupation'] ?? '') ?>" readonly required pattern="[A-Za-z\s]+" title="Please enter letters only" oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '')" class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field letters-only">
                                    <small class="text-red-500 text-xs error-message hidden">Guardian's occupation is required</small>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold mb-1">Contact</label>
                                    <input type="tel" name="guardian_contact" value="<?= htmlspecialchars($student_data['guardian_contact'] ?? '') ?>" pattern="^[0-9]{11}$" maxlength="11" title="Please enter 11 digits" oninput="this.value = this.value.replace(/\D/g, '').slice(0, 11)" class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-white student-field digits-only" data-maxlen="11" inputmode="numeric">
                                </div>
                            </div>
                        </div>

                        <!-- Last School Attended -->
                        <div class="col-span-3 mt-6">
                            <h4 class="font-semibold text-gray-700 mb-3">Last School Attended</h4>
                            <div class="grid grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold mb-1">School Name *</label>
                                    <input type="text" name="last_school" value="<?= htmlspecialchars($student_data['last_school'] ?? '') ?>" readonly required class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                                    <small class="text-red-500 text-xs error-message hidden">Last school name is required</small>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold mb-1">School Year *</label>
                                    <select id="lastSchoolYearSelect" name="last_school_year" disabled required class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                                        <option value="">Select Year</option>
                                        <?php
                                        $saved_last_sy = $student_data['last_school_year'] ?? '';
                                        if (!empty($saved_last_sy)) {
                                            echo '<option value="' . htmlspecialchars($saved_last_sy) . '" selected>' . htmlspecialchars($saved_last_sy) . '</option>';
                                        }
                                        ?>
                                    </select>
                                    <small class="text-red-500 text-xs error-message hidden">Last school year is required</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Parent Personal Account Section -->
            <div class="col-span-3 mb-6">
                <div class="bg-gray-50 border-2 border-gray-400 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        PARENT PERSONAL ACCOUNT
                    </h3>
                    
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold mb-1">Parent Username <span class="text-gray-500 font-normal">(Auto-generated)</span></label>
                            <input type="text" name="parent_username" autocomplete="off" value="<?= htmlspecialchars($parent_username) ?>" pattern="^[a-z]+[0-9]{6}muzon@parent\.cci\.edu\.ph$" title="Auto-generated: lastname000000muzon@parent.cci.edu.ph" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46] student-field" readonly style="background-color:#f3f4f6; cursor:not-allowed;">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Password</label>
                            <input type="text" name="parent_password" placeholder="Enter new password" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                            <small class="text-gray-500">Leave blank to keep current password</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Personal Account Section -->
            <div class="col-span-3 mb-6">
                <div class="bg-gray-50 border-2 border-gray-400 rounded-lg p-4">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        PERSONAL ACCOUNT
                    </h3>
                    <div class="grid grid-cols-2 gap-6">
                        <!-- Username -->
                        <div>
                            <label class="block text-sm font-semibold mb-1">Username <span class="text-gray-500 font-normal">(Auto-generated)</span></label>
                            <input type="text" name="username" autocomplete="off" value="<?= htmlspecialchars($student_data['username'] ?? '') ?>" pattern="^[a-z]+[0-9]{6}muzon@student\.cci\.edu\.ph$" title="Auto-generated: lastname000000muzon@student.cci.edu.ph" class="w-full border border-gray-300 px-3 py-2 rounded-lg focus:ring-2 focus:ring-[#2F8D46] student-field" readonly style="background-color:#f3f4f6; cursor:not-allowed;">
                        </div>

                        <!-- Password -->
                        <div>
                            <label class="block text-sm font-semibold mb-1">Password</label>
                            <input type="text" name="password" placeholder="Enter new password" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                            <small class="text-gray-500">Leave blank to keep current password</small>
                        </div>
                     </div>

                    <div class="grid grid-cols-2 gap-6">
                                                <div>
                            <label class="block text-sm font-semibold mb-1">Student ID</label>
                            <input type="number" name="id_number" value="<?= htmlspecialchars($student_data['id_number'] ?? '') ?>" readonly disabled class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field cursor-not-allowed">
                        </div>
                        <!-- RFID Number -->
                        <div>
                            <label class="block text-sm font-semibold mb-1">RFID Number</label>
                            <input type="text" name="rfid_uid" id="rfidInput" autocomplete="off" value="<?= htmlspecialchars($student_data['rfid_uid'] ?? '') ?>" readonly pattern="^[0-9]{10}$" maxlength="10" title="Please enter exactly 10 digits (optional)" class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field digits-only" data-maxlen="10" inputmode="numeric">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Buttons (no Save here; Save moved to header) -->
            <div class="col-span-3 flex justify-end gap-4 pt-6 border-t border-gray-200">
                <a href="/onecci/RegistrarF/AccountList.php?type=student" onclick="return closeModalEmbedAware(event);" class="px-5 py-2 border border-blue-600 text-blue-900 rounded-xl hover:bg-[#0B2C62] hover:text-white transition inline-flex items-center justify-center">Back to List</a>
            </div>
        </form>
    </div>
</div>

<script>
// Grade level options mapping
const gradeOptions = {
    "Elementary": ["Grade 1", "Grade 2", "Grade 3", "Grade 4", "Grade 5", "Grade 6"],
    "Junior High School": ["Grade 7", "Grade 8", "Grade 9", "Grade 10"],
    "Senior High School Strands": ["Grade 11", "Grade 12"],
    "STEM": ["Grade 11", "Grade 12"],
    "ABM": ["Grade 11", "Grade 12"],
    "HUMSS": ["Grade 11", "Grade 12"],
    "GAS": ["Grade 11", "Grade 12"],
    "TVL": ["Grade 11", "Grade 12"],
    "Arts and Design": ["Grade 11", "Grade 12"],
    "BS Information Technology": ["1st Year", "2nd Year", "3rd Year", "4th Year"],
    "BS Computer Science": ["1st Year", "2nd Year", "3rd Year", "4th Year"],
    "BS Business Administration": ["1st Year", "2nd Year", "3rd Year", "4th Year"],
    "BS Accountancy": ["1st Year", "2nd Year", "3rd Year", "4th Year"],
    "BS Hospitality Management": ["1st Year", "2nd Year", "3rd Year", "4th Year"],
    "BS Education": ["1st Year", "2nd Year", "3rd Year", "4th Year"]
};

function updateGradeLevels() {
    const academicTrack = document.querySelector('select[name="academic_track"]');
    const gradeLevel = document.getElementById('gradeLevel');
    
    if (!academicTrack || !gradeLevel) return;
    
    const selectedTrack = academicTrack.value;
    const selectedGrade = gradeLevel.value;
    
    // Get the optgroup label if it exists
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
        const option = document.createElement('option');
        option.value = level;
        option.textContent = level;
        if (level === selectedGrade) option.selected = true;
        gradeLevel.appendChild(option);
    });
}

// Capture original form state (once per edit session)
function captureOriginal() {
    const fields = document.querySelectorAll('.student-field');
    fields.forEach(f => {
        if (f.dataset.origCaptured === '1') return;
        if (f.tagName === 'SELECT' || f.tagName === 'TEXTAREA' || ['text','number','date'].includes(f.type)) {
            f.dataset.originalValue = f.value;
        } else if (f.type === 'radio' || f.type === 'checkbox') {
            f.dataset.originalChecked = f.checked ? '1' : '0';
        }
        f.dataset.origCaptured = '1';
    });
}

// Restore original form state
function restoreOriginal() {
    const fields = document.querySelectorAll('.student-field');
    fields.forEach(f => {
        if (f.tagName === 'SELECT' || f.tagName === 'TEXTAREA' || ['text','number','date'].includes(f.type)) {
            if (f.dataset.originalValue !== undefined) f.value = f.dataset.originalValue;
        } else if (f.type === 'radio' || f.type === 'checkbox') {
            if (f.dataset.originalChecked !== undefined) f.checked = (f.dataset.originalChecked === '1');
        }
    });
}

// Toggle visibility of school type options based on enrollment status
function toggleNewOptions() {
    const newOptions = document.getElementById("newOptions");
    const isNew = document.querySelector('input[name="enrollment_status"]:checked')?.value === "NEW";
    if (newOptions) {
        newOptions.classList.toggle("hidden", !isNew);
    }
}

function toggleEdit() {
    const editBtn = document.getElementById('editBtn');
    const saveBtn = document.getElementById('saveBtn');
    const delBtn = document.getElementById('deleteBtn');
    const fields = document.querySelectorAll('.student-field');
    
    const isEditing = editBtn.textContent === 'Cancel';
    
    if (isEditing) {
        // Cancel editing
        editBtn.textContent = 'Edit';
        editBtn.className = 'px-4 py-2 bg-[#2F8D46] text-white rounded-lg hover:bg-[#256f37] transition';
        // Revert any unsaved changes
        restoreOriginal();
        saveBtn.classList.add('hidden');
        if (delBtn) delBtn.classList.remove('hidden');
        
        // Make fields readonly
        fields.forEach(field => {
            if (field.type === 'text' || field.type === 'number' || field.type === 'date' || field.tagName === 'TEXTAREA') {
                field.readOnly = true;
                field.classList.add('bg-gray-50');
                field.classList.remove('bg-white');
            } else if (field.type === 'radio' || field.type === 'checkbox' || field.tagName === 'SELECT') {
                field.disabled = true;
            }
        });
    } else {
        // Enable editing
        editBtn.textContent = 'Cancel';
        editBtn.className = 'px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition';
        // Snapshot current values once when entering edit mode
        captureOriginal();
        saveBtn.classList.remove('hidden');
        if (delBtn) delBtn.classList.add('hidden');
        
        // Make fields editable
        fields.forEach(field => {
            // Skip username fields - they should always remain readonly (auto-generated)
            if (field.name === 'username' || field.name === 'parent_username') {
                return;
            }
            
            if (field.type === 'text' || field.type === 'tel' || field.type === 'number' || field.type === 'date' || field.tagName === 'TEXTAREA') {
                field.readOnly = false;
                field.classList.remove('bg-gray-50');
                field.classList.add('bg-white');
            } else if (field.type === 'radio' || field.type === 'checkbox' || field.tagName === 'SELECT') {
                field.disabled = false;
            }
        });
        
        // Update grade levels when entering edit mode
        updateGradeLevels();
    }
}

// Initialize grade levels on page load and add event listener
// Populate helper: last school year (N years back)
function populateLastSchoolYears(yearsBack = 5) {
    const lastSySelect = document.getElementById('lastSchoolYearSelect');
    if (!lastSySelect) return;
    
    const savedLastSY = lastSySelect.querySelector('option[selected]')?.value || '';
    
    // Clear existing options except placeholder
    const selectYearOption = lastSySelect.querySelector('option[value=""]');
    lastSySelect.innerHTML = '';
    if (selectYearOption) lastSySelect.appendChild(selectYearOption);
    
    const now = new Date();
    const year = now.getFullYear();
    
    // Generate past years
    for (let i = yearsBack; i >= 1; i--) {
        const start = year - i;
        const end = year - i + 1;
        const label = `${start}-${end}`;
        const opt = document.createElement('option');
        opt.value = label;
        opt.textContent = label;
        if (savedLastSY && savedLastSY === label) {
            opt.selected = true;
        }
        lastSySelect.appendChild(opt);
    }
    
    // Add current academic year
    const currentLabel = `${year}-${year+1}`;
    const curOpt = document.createElement('option');
    curOpt.value = currentLabel;
    curOpt.textContent = currentLabel;
    if (savedLastSY && savedLastSY === currentLabel) {
        curOpt.selected = true;
    }
    lastSySelect.appendChild(curOpt);
    
    // If saved year is not in the list, add it
    const allOptions = Array.from(lastSySelect.options).map(opt => opt.value);
    if (savedLastSY && !allOptions.includes(savedLastSY)) {
        const opt = document.createElement('option');
        opt.value = savedLastSY;
        opt.textContent = savedLastSY;
        opt.selected = true;
        lastSySelect.appendChild(opt);
    }
}

// Setup auto-generated Student Username (EXACT COPY from add_account.php)
function setupAutoUsername() {
    const lastNameField = document.querySelector('input[name="last_name"]');
    const idField = document.querySelector('input[name="id_number"]');
    const usernameField = document.querySelector('input[name="username"]');
    
    if (!lastNameField || !idField || !usernameField) return;

    const lettersOnly = (s) => (s || '').toLowerCase().replace(/[^a-z]/g, '');
    const last6 = (s) => {
        const digits = (s || '').replace(/\D/g, '');
        return digits.slice(-6).padStart(6, '0');
    };

    const updateUsername = () => {
        const lastName = lettersOnly(lastNameField.value);
        const idNumber = idField.value;
        const tail = last6(idNumber);
        
        if (lastName && tail.length === 6) {
            const username = `${lastName}${tail}muzon@student.cci.edu.ph`;
            usernameField.value = username;
            usernameField.readOnly = true;
            usernameField.style.backgroundColor = '#f3f4f6';
            usernameField.style.cursor = 'not-allowed';
        }
    };

    // Initial fill and listeners
    updateUsername();
    lastNameField.addEventListener('input', updateUsername);
    idField.addEventListener('input', updateUsername);
    
    // Poll every 500ms to check if Student ID gets auto-filled
    const pollForStudentId = setInterval(() => {
        if (idField.value && idField.value.length === 11) {
            updateUsername();
            clearInterval(pollForStudentId);
        }
    }, 500);
    
    // Stop polling after 10 seconds
    setTimeout(() => clearInterval(pollForStudentId), 10000);
}

// Setup auto-generated Parent Username (SAME LOGIC as student username)
function setupAutoParentUsername() {
    const lastNameField = document.querySelector('input[name="last_name"]');
    const idField = document.querySelector('input[name="id_number"]');
    const parentUsernameField = document.querySelector('input[name="parent_username"]');
    
    if (!lastNameField || !idField || !parentUsernameField) return;

    const lettersOnly = (s) => (s || '').toLowerCase().replace(/[^a-z]/g, '');
    const last6 = (s) => {
        const digits = (s || '').replace(/\D/g, '');
        return digits.slice(-6).padStart(6, '0');
    };

    const updateParentUsername = () => {
        const lastName = lettersOnly(lastNameField.value);
        const idNumber = idField.value;
        const tail = last6(idNumber);
        
        if (lastName && tail.length === 6) {
            const parentUsername = `${lastName}${tail}muzon@parent.cci.edu.ph`;
            parentUsernameField.value = parentUsername;
            parentUsernameField.readOnly = true;
            parentUsernameField.style.backgroundColor = '#f3f4f6';
            parentUsernameField.style.cursor = 'not-allowed';
        }
    };

    // Initial fill and listeners
    updateParentUsername();
    lastNameField.addEventListener('input', updateParentUsername);
    idField.addEventListener('input', updateParentUsername);
    
    // Poll every 500ms to check if Student ID gets auto-filled
    const pollForStudentId = setInterval(() => {
        if (idField.value && idField.value.length === 11) {
            updateParentUsername();
            clearInterval(pollForStudentId);
        }
    }, 500);
    
    // Stop polling after 10 seconds
    setTimeout(() => clearInterval(pollForStudentId), 10000);
}

document.addEventListener('DOMContentLoaded', function() {
    updateGradeLevels();
    
    // Populate School Year dropdown
    const sySelect = document.getElementById('schoolYearSelect');
    if (sySelect) {
        const now = new Date();
        const year = now.getFullYear();
        const prev = `${year-1}-${year}`;
        const curr = `${year}-${year+1}`;
        const next = `${year+1}-${year+2}`;
        
        const options = [prev, curr, next];
        const savedSY = sySelect.querySelector('option[selected]')?.value || '';
        
        // Clear existing options except the saved one
        const selectYearOption = sySelect.querySelector('option[value=""]');
        const savedOption = sySelect.querySelector('option[selected]');
        sySelect.innerHTML = '';
        if (selectYearOption) sySelect.appendChild(selectYearOption);
        
        options.forEach(val => {
            const opt = document.createElement('option');
            opt.value = val;
            opt.textContent = val;
            if (savedSY && savedSY === val) {
                opt.selected = true;
            }
            sySelect.appendChild(opt);
        });
        
        // If saved year is not in the list, add it
        if (savedSY && !options.includes(savedSY)) {
            const opt = document.createElement('option');
            opt.value = savedSY;
            opt.textContent = savedSY;
            opt.selected = true;
            sySelect.appendChild(opt);
        }
    }
    
    // Populate Last School Year dropdown
    populateLastSchoolYears(5);
    
    const academicTrack = document.querySelector('select[name="academic_track"]');
    if (academicTrack) {
        academicTrack.addEventListener('change', updateGradeLevels);
    }
    
    // Setup auto-generated usernames (with delays to ensure fields are ready)
    setTimeout(() => {
        setupAutoUsername();
        setupAutoParentUsername();
    }, 100);
    setTimeout(() => {
        setupAutoUsername();
        setupAutoParentUsername();
    }, 500);
    setTimeout(() => {
        setupAutoUsername();
        setupAutoParentUsername();
    }, 1000);
    
    // Force-initialize gender select if not pre-selected
    const genderEl = document.querySelector('select[name="gender"]');
    if (genderEl && !genderEl.value && genderEl.dataset.initial) {
        genderEl.value = genderEl.dataset.initial;
    }
    // Reinforce click handlers to ensure they work in embedded contexts
    const editBtn = document.getElementById('editBtn');
    const deleteBtn = document.getElementById('deleteBtn');
    const backLink = document.querySelector('a[href*="AccountList.php"][onclick]');
    if (editBtn) {
        editBtn.addEventListener('click', function(e){ e.stopPropagation(); toggleEdit(); });
    }
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function(e){ e.stopPropagation(); confirmDelete(); });
    }
    if (backLink) {
        backLink.addEventListener('click', function(e){ e.stopPropagation(); });
    }
});

// Intercept Back link in embed mode; allow normal navigation otherwise
function closeModalEmbedAware(e) {
    const params = new URLSearchParams(window.location.search);
    const isEmbed = params.get('embed') === '1';
    if (isEmbed) {
        e.preventDefault();
        closeModal();
        return false;
    }
    return true;
}

// Close student modal (handles embed and full page modes)
function closeModal() {
    const params = new URLSearchParams(window.location.search);
    const isEmbed = params.get('embed') === '1';
    if (isEmbed) {
        // If embedded, just remove the modal overlay to reveal parent page
        const overlay = document.querySelector('.fixed.inset-0');
        if (overlay) overlay.remove();
    } else {
        // Standalone page: navigate back to account list (absolute path)
        window.location.href = '/onecci/RegistrarF/AccountList.php?type=student';
    }
}

// Delete confirmation function
function confirmDelete() {
    if (confirm('Are you sure you want to delete this student account? This action cannot be undone.')) {
        // Create a form and submit it
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = window.location.href;
        
        const deleteInput = document.createElement('input');
        deleteInput.type = 'hidden';
        deleteInput.name = 'delete_student';
        deleteInput.value = '1';
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id_number';
        idInput.value = document.querySelector('input[name="id_number"]').value;
        
        form.appendChild(deleteInput);
        form.appendChild(idInput);
        document.body.appendChild(form);
        form.submit();
    }
}

function showDeleteModal() {
    const modal = document.createElement('div');
    modal.id = 'confirmDeleteOverlay';
    modal.className = 'fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center pointer-events-none';
    modal.style.zIndex = '2147483647';
    modal.innerHTML = `
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 transform transition-all pointer-events-auto" style="z-index:2147483647;">
            <div class="p-6">
                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">Delete Student Account</h3>
                <p class="text-gray-600 text-center mb-6">Are you sure you want to delete this student account? This action cannot be undone.</p>
                <div class="flex gap-3 justify-end">
                    <button onclick="closeDeleteModal()" class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
                        Cancel
                    </button>
                    <button onclick="proceedDelete()" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
                        Delete Account
                    </button>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function closeDeleteModal() {
    const modal = document.getElementById('confirmDeleteOverlay');
    if (modal) {
        modal.remove();
    }
}

function proceedDelete() {
    // Create a form to submit the delete request
    const form = document.createElement('form');
    form.method = 'POST';
    // Submit the current student's id as well to ensure server has the correct target
    const idField = document.querySelector('input[name="id_number"]');
    const idVal = idField ? idField.value : '';
    form.innerHTML = '<input type="hidden" name="delete_student" value="1">' +
                     '<input type="hidden" name="id_number" value="' + idVal + '">';
    document.body.appendChild(form);
    form.submit();
}

// Date validation and dynamic day updating
document.addEventListener('DOMContentLoaded', function() {
    const monthSelect = document.querySelector('select[name="dob_month"]');
    const daySelect = document.querySelector('select[name="dob_day"]');
    const yearSelect = document.querySelector('select[name="dob_year"]');
    
    function updateDaysInMonth() {
        if (!monthSelect || !daySelect || !yearSelect) return;
        
        const month = parseInt(monthSelect.value);
        const year = parseInt(yearSelect.value);
        const currentDay = daySelect.value;
        
        if (!month || !year) return;
        
        // Get number of days in the selected month/year
        const daysInMonth = new Date(year, month, 0).getDate();
        
        // Clear existing day options (except first placeholder)
        while (daySelect.options.length > 1) {
            daySelect.remove(1);
        }
        
        // Add day options for the selected month
        for (let day = 1; day <= daysInMonth; day++) {
            const option = document.createElement('option');
            option.value = day;
            option.textContent = day;
            if (currentDay == day) {
                option.selected = true;
            }
            daySelect.appendChild(option);
        }
    }
    
    if (monthSelect && yearSelect) {
        monthSelect.addEventListener('change', updateDaysInMonth);
        yearSelect.addEventListener('change', updateDaysInMonth);
    }
});

// Form validation before submit
// Clear custom validity when user types
document.querySelectorAll('#studentForm input, #studentForm textarea, #studentForm select').forEach(field => {
    field.addEventListener('input', function() {
        this.setCustomValidity('');
    });
});

document.getElementById('studentForm').addEventListener('submit', function(e) {
    const form = this;
    
    // Clear all custom validities first
    form.querySelectorAll('input, textarea, select').forEach(field => {
        field.setCustomValidity('');
    });
    
    // Enable all disabled fields before submission (disabled fields don't submit)
    const disabledFields = form.querySelectorAll('[disabled]');
    disabledFields.forEach(field => {
        field.disabled = false;
    });
    
    // Check HTML5 validity - this will show native browser validation messages
    if (!form.checkValidity()) {
        e.preventDefault();
        form.reportValidity();
        return false;
    }
    
    // Additional custom validation
    const errors = [];
    
    // Get all form fields
    const lrn = document.querySelector('input[name="lrn"]')?.value.trim() || '';
    const lastName = document.querySelector('input[name="last_name"]')?.value.trim() || '';
    const firstName = document.querySelector('input[name="first_name"]')?.value.trim() || '';
    const middleName = document.querySelector('input[name="middle_name"]')?.value.trim() || '';
    const birthplace = document.querySelector('input[name="birthplace"]')?.value.trim() || '';
    const religion = document.querySelector('input[name="religion"]')?.value.trim() || '';
    const address = document.querySelector('textarea[name="address"]')?.value.trim() || '';
    const schoolYear = document.querySelector('input[name="school_year"]')?.value.trim() || '';
    const fatherFirstName = document.querySelector('input[name="father_first_name"]')?.value.trim() || '';
    const fatherLastName = document.querySelector('input[name="father_last_name"]')?.value.trim() || '';
    const fatherOccupation = document.querySelector('input[name="father_occupation"]')?.value.trim() || '';
    const motherFirstName = document.querySelector('input[name="mother_first_name"]')?.value.trim() || '';
    const motherLastName = document.querySelector('input[name="mother_last_name"]')?.value.trim() || '';
    const motherOccupation = document.querySelector('input[name="mother_occupation"]')?.value.trim() || '';
    const guardianFirstName = document.querySelector('input[name="guardian_first_name"]')?.value.trim() || '';
    const guardianLastName = document.querySelector('input[name="guardian_last_name"]')?.value.trim() || '';
    const guardianOccupation = document.querySelector('input[name="guardian_occupation"]')?.value.trim() || '';
    const lastSchool = document.querySelector('input[name="last_school"]')?.value.trim() || '';
    const lastSchoolYear = document.querySelector('select[name="last_school_year"]')?.value.trim() || '';
    
    // Required field validation
    if (!lrn) errors.push("LRN is required");
    if (!lastName) errors.push("Last name is required");
    if (!firstName) errors.push("First name is required");
    if (!birthplace) errors.push("Birthplace is required");
    if (!religion) errors.push("Religion is required");
    if (!address) errors.push("Complete address is required");
    if (!fatherFirstName) errors.push("Father's first name is required");
    if (!fatherLastName) errors.push("Father's last name is required");
    if (!fatherOccupation) errors.push("Father's occupation is required");
    if (!motherFirstName) errors.push("Mother's first name is required");
    if (!motherLastName) errors.push("Mother's last name is required");
    if (!motherOccupation) errors.push("Mother's occupation is required");
    if (!guardianFirstName) errors.push("Guardian's first name is required");
    if (!guardianLastName) errors.push("Guardian's last name is required");
    if (!guardianOccupation) errors.push("Guardian's occupation is required");
    if (!lastSchool) errors.push("Last school name is required");
    if (!lastSchoolYear) errors.push("Last school year is required");
    
    // Data type validations
    if (lrn && !/^[0-9]+$/.test(lrn)) errors.push("LRN must contain numbers only");
    if (lastName && !/^[A-Za-z\s]+$/.test(lastName)) errors.push("Last name must contain letters only");
    if (firstName && !/^[A-Za-z\s]+$/.test(firstName)) errors.push("First name must contain letters only");
    if (middleName && !/^[A-Za-z\s]*$/.test(middleName)) errors.push("Middle name must contain letters only");
    if (birthplace && !/^[A-Za-z\s,.\-]+$/.test(birthplace)) errors.push("Birthplace must contain valid location characters only");
    if (religion && !/^[A-Za-z\s]+$/.test(religion)) errors.push("Religion must contain letters only");
    if (schoolYear && !/^[0-9\-]+$/.test(schoolYear)) errors.push("School year must be in format like 2024-2025");
    
    // Address validation
    if (address && address.length < 20) errors.push("Complete address must be at least 20 characters long");
    if (address && address.length > 500) errors.push("Complete address must not exceed 500 characters");
    if (address && !/.*[,\s].*/i.test(address)) errors.push("Complete address must include street, barangay, city/municipality, and province.");
    
    // Occupation validation
    if (fatherOccupation && !/^[A-Za-z\s]+$/.test(fatherOccupation)) errors.push("Father's occupation must contain letters only");
    if (motherOccupation && !/^[A-Za-z\s]+$/.test(motherOccupation)) errors.push("Mother's occupation must contain letters only");
    if (guardianOccupation && !/^[A-Za-z\s]+$/.test(guardianOccupation)) errors.push("Guardian's occupation must contain letters only");
    
    // If there are validation errors, use HTML5 validation to show inline errors
    if (errors.length > 0) {
        e.preventDefault();
        
        // Set custom validation messages on fields
        if (!lrn) {
            const field = form.querySelector('input[name="lrn"]');
            if (field) {
                field.setCustomValidity("Please fill out this field.");
                field.reportValidity();
                return false;
            }
        }
        if (!lastName) {
            const field = form.querySelector('input[name="last_name"]');
            if (field) {
                field.setCustomValidity("Please fill out this field.");
                field.reportValidity();
                return false;
            }
        }
        if (!firstName) {
            const field = form.querySelector('input[name="first_name"]');
            if (field) {
                field.setCustomValidity("Please fill out this field.");
                field.reportValidity();
                return false;
            }
        }
        if (!address) {
            const field = form.querySelector('textarea[name="address"]');
            if (field) {
                field.setCustomValidity("Please fill out this field.");
                field.reportValidity();
                return false;
            }
        }
        if (address && address.length < 20) {
            const field = form.querySelector('textarea[name="address"]');
            if (field) {
                field.setCustomValidity("Complete address must be at least 20 characters long.");
                field.reportValidity();
                return false;
            }
        }
        if (address && !/.*[,\s].*/i.test(address)) {
            const field = form.querySelector('textarea[name="address"]');
            if (field) {
                field.setCustomValidity("Complete address must include street, barangay, city/municipality, and province.");
                field.reportValidity();
                return false;
            }
        }
        
        // For other errors, show the first one
        if (errors.length > 0) {
            alert("Please fix the following errors:\n\n" + errors.join("\n"));
        }
        
        return false;
    }
    
    return true;
});

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

// Prevent numbers and special characters in name fields
function preventNumbersInNames(event) {
    const char = String.fromCharCode(event.which || event.keyCode);
    // Allow only letters, spaces, hyphens, apostrophes, and periods
    const namePattern = /^[a-zA-Z\s\-'.]+$/;
    if (!namePattern.test(char)) {
        event.preventDefault();
        return false;
    }
}

// Initialize input validation for letters-only fields
document.addEventListener('DOMContentLoaded', function() {
    // Apply validation to all fields with 'letters-only' class
    const lettersOnlyFields = document.querySelectorAll('.letters-only');
    
    lettersOnlyFields.forEach(field => {
        field.addEventListener('keypress', preventNumbersInNames);
        field.addEventListener('paste', function(e) {
            setTimeout(() => {
                // Clean pasted content
                const value = this.value;
                const cleanValue = value.replace(/[^a-zA-Z\s\-'.]/g, '');
                if (value !== cleanValue) {
                    this.value = cleanValue;
                    this.style.border = '2px solid #ef4444';
                    setTimeout(() => {
                        this.style.border = '';
                    }, 1000);
                }
            }, 10);
        });
    });
});
</script>
<?php if (!$embed): ?>
</body>
</html>
<?php endif; ?>
