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
    $rfid_uid = $_POST['rfid_uid'] ?? '';
    $password = $_POST['password'] ?? '';
    $parent_password = $_POST['parent_password'] ?? '';

    // Update student record
    if (!empty($password)) {
        // Update with new password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $update_sql = "UPDATE student_account SET 
            lrn = ?, academic_track = ?, enrollment_status = ?, school_type = ?,
            last_name = ?, first_name = ?, middle_name = ?, username = ?,
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
            "ssssssssssssssssssssssssssssssss",
            $lrn, $academic_track, $enrollment_status, $school_type,
            $last_name, $first_name, $middle_name, $username,
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
            last_name = ?, first_name = ?, middle_name = ?, username = ?,
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
            "sssssssssssssssssssssssssssssss",
            $lrn, $academic_track, $enrollment_status, $school_type,
            $last_name, $first_name, $middle_name, $username,
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
                            <label class="block text-sm font-semibold mb-1">LRN</label>
                            <input type="number" name="lrn" value="<?= htmlspecialchars($student_data['lrn'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
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
                                    <input type="radio" name="enrollment_status" value="OLD" <?= ($student_data['enrollment_status'] ?? '') === 'OLD' ? 'checked' : '' ?> disabled class="student-field"> OLD
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="radio" name="enrollment_status" value="NEW" <?= ($student_data['enrollment_status'] ?? '') === 'NEW' ? 'checked' : '' ?> disabled class="student-field"> NEW
                                </label>
                            </div>
                        </div>

                        <!-- Row: Full Name -->
                        <div>
                            <label class="block text-sm font-semibold mb-1">Last Name</label>
                            <input type="text" name="last_name" value="<?= htmlspecialchars($student_data['last_name'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">First Name</label>
                            <input type="text" name="first_name" value="<?= htmlspecialchars($student_data['first_name'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Middle Name <span class="text-gray-500 text-xs">(Optional)</span></label>
                            <input type="text" name="middle_name" value="<?= htmlspecialchars($student_data['middle_name'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                        </div>

                        <!-- Row: School Year, Grade Level, Semester -->
                        <div>
                            <label class="block text-sm font-semibold mb-1">School Year</label>
                            <input type="text" name="school_year" value="<?= htmlspecialchars($student_data['school_year'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
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
                            <label class="block text-sm font-semibold mb-1">Birthplace</label>
                            <input type="text" name="birthplace" value="<?= htmlspecialchars($student_data['birthplace'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Gender</label>
                            <select name="gender" disabled class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field" data-initial="<?= htmlspecialchars($genderVal) ?>">
                                <option value="">-- Select Gender --</option>
                                <option value="M" <?= ($genderVal === 'M') ? 'selected' : '' ?>>Male</option>
                                <option value="F" <?= ($genderVal === 'F') ? 'selected' : '' ?>>Female</option>
                            </select>
                        </div>

                        <!-- Row: Religion, Credentials, Payment Mode -->
                        <div>
                            <label class="block text-sm font-semibold mb-1">Religion</label>
                            <input type="text" name="religion" value="<?= htmlspecialchars($student_data['religion'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
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
                            <label class="block text-sm font-semibold mb-1">Complete Address</label>
                            <textarea name="address" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field"><?= htmlspecialchars($student_data['address'] ?? '') ?></textarea>
                        </div>

                        <!-- Father's Information -->
                        <div class="col-span-3 mt-6">
                            <h4 class="font-semibold text-gray-700 mb-3">Father's Information</h4>
                            <div class="grid grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold mb-1">Name</label>
                                    <input type="text" name="father_name" value="<?= htmlspecialchars($student_data['father_name'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold mb-1">Occupation</label>
                                    <input type="text" name="father_occupation" value="<?= htmlspecialchars($student_data['father_occupation'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold mb-1">Contact</label>
                                    <input type="tel" name="father_contact" value="<?= htmlspecialchars($student_data['father_contact'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                                </div>
                            </div>
                        </div>

                        <!-- Mother's Information -->
                        <div class="col-span-3 mt-6">
                            <h4 class="font-semibold text-gray-700 mb-3">Mother's Information</h4>
                            <div class="grid grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold mb-1">Name</label>
                                    <input type="text" name="mother_name" value="<?= htmlspecialchars($student_data['mother_name'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold mb-1">Occupation</label>
                                    <input type="text" name="mother_occupation" value="<?= htmlspecialchars($student_data['mother_occupation'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold mb-1">Contact</label>
                                    <input type="tel" name="mother_contact" value="<?= htmlspecialchars($student_data['mother_contact'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                                </div>
                            </div>
                        </div>

                        <!-- Guardian's Information -->
                        <div class="col-span-3 mt-6">
                            <h4 class="font-semibold text-gray-700 mb-3">Guardian's Information</h4>
                            <div class="grid grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold mb-1">Name</label>
                                    <input type="text" name="guardian_name" value="<?= htmlspecialchars($student_data['guardian_name'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold mb-1">Occupation</label>
                                    <input type="text" name="guardian_occupation" value="<?= htmlspecialchars($student_data['guardian_occupation'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold mb-1">Contact</label>
                                    <input type="tel" name="guardian_contact" value="<?= htmlspecialchars($student_data['guardian_contact'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                                </div>
                            </div>
                        </div>

                        <!-- Last School Attended -->
                        <div class="col-span-3 mt-6">
                            <h4 class="font-semibold text-gray-700 mb-3">Last School Attended</h4>
                            <div class="grid grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold mb-1">School Name</label>
                                    <input type="text" name="last_school" value="<?= htmlspecialchars($student_data['last_school'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold mb-1">School Year</label>
                                    <input type="text" name="last_school_year" value="<?= htmlspecialchars($student_data['last_school_year'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
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
                            <label class="block text-sm font-semibold mb-1">Username</label>
                            <input type="text" name="parent_username" value="<?= htmlspecialchars($parent_username) ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
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
                    <div class="grid grid-cols-3 gap-6">
                        <!-- Student ID -->
                        <div>
                            <label class="block text-sm font-semibold mb-1">Student ID</label>
                            <input type="number" name="id_number" value="<?= htmlspecialchars($student_data['id_number'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                        </div>

                        <!-- Username -->
                        <div>
                            <label class="block text-sm font-semibold mb-1">Username</label>
                            <input type="text" name="username" value="<?= htmlspecialchars($student_data['username'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                        </div>

                        <!-- Password -->
                        <div>
                            <label class="block text-sm font-semibold mb-1">Password</label>
                            <input type="text" name="password" placeholder="Enter new password" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                            <small class="text-gray-500">Leave blank to keep current password</small>
                        </div>

                        <!-- RFID Number -->
                        <div>
                            <label class="block text-sm font-semibold mb-1">RFID Number</label>
                            <input type="number" name="rfid_uid" value="<?= htmlspecialchars($student_data['rfid_uid'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
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
            if (field.type === 'text' || field.type === 'number' || field.type === 'date' || field.tagName === 'TEXTAREA') {
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
document.addEventListener('DOMContentLoaded', function() {
    updateGradeLevels();
    
    const academicTrack = document.querySelector('select[name="academic_track"]');
    if (academicTrack) {
        academicTrack.addEventListener('change', updateGradeLevels);
    }
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
</script>
<?php if (!$embed): ?>
</body>
</html>
<?php endif; ?>
