<?php
session_start();
include(__DIR__ . "/../../StudentLogin/db_conn.php");

// Require registrar login
if (!isset($_SESSION['registrar_id'])) {
    header("Location: ../StudentLogin/login.php");
    exit;
}

$student_id = $_GET['id'] ?? '';
$student_data = null;

if (!empty($student_id)) {
    $stmt = $conn->prepare("SELECT * FROM students WHERE id_number = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student_data = $result->fetch_assoc();
    $stmt->close();
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
    $rfid_uid = $_POST['rfid_uid'] ?? '';

    // Update student record
    $update_sql = "UPDATE students SET 
        lrn = ?, academic_track = ?, enrollment_status = ?, school_type = ?,
        last_name = ?, first_name = ?, middle_name = ?, 
        school_year = ?, grade_level = ?, semester = ?,
        dob = ?, birthplace = ?, gender = ?, religion = ?, credentials = ?, 
        payment_mode = ?, address = ?,
        father_name = ?, father_occupation = ?, father_contact = ?,
        mother_name = ?, mother_occupation = ?, mother_contact = ?,
        guardian_name = ?, guardian_occupation = ?, guardian_contact = ?,
        last_school = ?, last_school_year = ?,
        id_number = ?, rfid_uid = ?
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
        $id_number, $rfid_uid, $student_id
    );

    if ($update_stmt->execute()) {
        $_SESSION['success_msg'] = "Student information updated successfully!";
        header("Location: ../AccountList.php?type=student");
        exit;
    } else {
        $error_msg = "Error updating student information.";
    }
    $update_stmt->close();
}

if (!$student_data) {
    header("Location: ../AccountList.php?type=student");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Student Information - CCI</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
input[type=number]::-webkit-inner-spin-button,
input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
input[type=number] { -moz-appearance: textfield; }
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
</head>
<body class="bg-gradient-to-br from-[#f3f6fb] to-[#e6ecf7] font-sans min-h-screen text-gray-900">

<!-- Header -->
<div class="bg-[#0B2C62] text-white px-6 py-4 flex items-center shadow-lg">
    <button onclick="window.location.href='../AccountList.php?type=student'" class="text-2xl mr-4 hover:text-[#FBB917] transition">←</button>
    <h1 class="text-xl font-bold tracking-wide">Student Information</h1>
</div>

<!-- Student Info Modal -->
<div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white w-full max-w-5xl rounded-2xl shadow-2xl overflow-hidden border border-gray-200 transform transition-all scale-100" id="modalContent">
        
        <!-- Header -->
        <div class="flex justify-between items-center border-b border-gray-200 px-6 py-4 bg-[#1E4D92] text-white">
            <h2 class="text-lg font-semibold">Student Information</h2>
            <div class="flex gap-3">
                <button id="editBtn" onclick="toggleEdit()" class="px-4 py-2 bg-[#2F8D46] text-white rounded-lg hover:bg-[#256f37] transition">Edit</button>
                <button onclick="window.location.href='../AccountList.php?type=student'" class="text-2xl font-bold hover:text-gray-300">&times;</button>
            </div>
        </div>

        <!-- Form -->
        <form id="studentForm" method="POST" class="px-6 py-6 grid grid-cols-1 md:grid-cols-3 gap-6 overflow-y-auto max-h-[80vh] no-scrollbar">
            <input type="hidden" name="update_student" value="1">

            <!-- Row: LRN and Academic Track -->
            <div class="col-span-3 grid grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-semibold mb-1">LRN</label>
                    <input type="number" name="lrn" value="<?= htmlspecialchars($student_data['lrn'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">Academic Track / Course</label>
                    <select name="academic_track" disabled class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                        <option value="">-- Select Academic Track / Course --</option>
                        <optgroup label="Elementary">
                            <option value="Elementary" <?= ($student_data['academic_track'] ?? '') === 'Elementary' ? 'selected' : '' ?>>Elementary</option>
                        </optgroup>
                        <optgroup label="Junior High School">
                            <option value="Junior High School" <?= ($student_data['academic_track'] ?? '') === 'Junior High School' ? 'selected' : '' ?>>Junior High School</option>
                        </optgroup>
                        <optgroup label="Senior High School Strands">
                            <option value="STEM" <?= ($student_data['academic_track'] ?? '') === 'STEM' ? 'selected' : '' ?>>STEM (Science, Technology, Engineering & Mathematics)</option>
                            <option value="ABM" <?= ($student_data['academic_track'] ?? '') === 'ABM' ? 'selected' : '' ?>>ABM (Accountancy, Business & Management)</option>
                            <option value="HUMSS" <?= ($student_data['academic_track'] ?? '') === 'HUMSS' ? 'selected' : '' ?>>HUMSS (Humanities & Social Sciences)</option>
                            <option value="GAS" <?= ($student_data['academic_track'] ?? '') === 'GAS' ? 'selected' : '' ?>>GAS (General Academic Strand)</option>
                            <option value="TVL" <?= ($student_data['academic_track'] ?? '') === 'TVL' ? 'selected' : '' ?>>TVL (Technical-Vocational-Livelihood)</option>
                            <option value="Arts and Design" <?= ($student_data['academic_track'] ?? '') === 'Arts and Design' ? 'selected' : '' ?>>Arts and Design</option>
                        </optgroup>
                        <optgroup label="College Courses">
                            <option value="BS Information Technology" <?= ($student_data['academic_track'] ?? '') === 'BS Information Technology' ? 'selected' : '' ?>>BS Information Technology</option>
                            <option value="BS Computer Science" <?= ($student_data['academic_track'] ?? '') === 'BS Computer Science' ? 'selected' : '' ?>>BS Computer Science</option>
                            <option value="BS Business Administration" <?= ($student_data['academic_track'] ?? '') === 'BS Business Administration' ? 'selected' : '' ?>>BS Business Administration</option>
                            <option value="BS Accountancy" <?= ($student_data['academic_track'] ?? '') === 'BS Accountancy' ? 'selected' : '' ?>>BS Accountancy</option>
                            <option value="BS Hospitality Management" <?= ($student_data['academic_track'] ?? '') === 'BS Hospitality Management' ? 'selected' : '' ?>>BS Hospitality Management</option>
                            <option value="BS Education" <?= ($student_data['academic_track'] ?? '') === 'BS Education' ? 'selected' : '' ?>>BS Education</option>
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
            </div>

            <!-- Row: Full Name -->
            <div class="col-span-3 grid grid-cols-3 gap-6">
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
            </div>

            <!-- Other Student Info -->
            <div>
                <label class="block text-sm font-semibold mb-1">School Year</label>
                <input type="text" name="school_year" value="<?= htmlspecialchars($student_data['school_year'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Grade Level</label>
                <input type="text" name="grade_level" value="<?= htmlspecialchars($student_data['grade_level'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Semester</label>
                <select name="semester" disabled class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                    <option value="">Select Term</option>
                    <option value="1st" <?= ($student_data['semester'] ?? '') === '1st' ? 'selected' : '' ?>>1st Term</option>
                    <option value="2nd" <?= ($student_data['semester'] ?? '') === '2nd' ? 'selected' : '' ?>>2nd Term</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Date of Birth</label>
                <input type="date" name="dob" value="<?= htmlspecialchars($student_data['dob'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Birthplace</label>
                <input type="text" name="birthplace" value="<?= htmlspecialchars($student_data['birthplace'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Gender</label>
                <div class="flex items-center gap-6 mt-1">
                    <label class="flex items-center gap-2">
                        <input type="radio" name="gender" value="M" <?= ($student_data['gender'] ?? '') === 'M' ? 'checked' : '' ?> disabled class="student-field"> Male
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="radio" name="gender" value="F" <?= ($student_data['gender'] ?? '') === 'F' ? 'checked' : '' ?> disabled class="student-field"> Female
                    </label>
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Religion</label>
                <input type="text" name="religion" value="<?= htmlspecialchars($student_data['religion'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
            </div>

            <!-- Credentials -->
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

            <!-- Mode of Payment -->
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
            <div class="col-span-3 grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold mb-1">Complete Address</label>
                    <textarea name="address" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field"><?= htmlspecialchars($student_data['address'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Parents / Guardian Info -->
            <div class="col-span-3 space-y-6">
                <h3 class="font-semibold mt-4">Father's Info</h3>
                <div class="grid grid-cols-3 gap-6">
                    <input type="text" name="father_name" placeholder="Name" value="<?= htmlspecialchars($student_data['father_name'] ?? '') ?>" readonly class="border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                    <input type="text" name="father_occupation" placeholder="Occupation" value="<?= htmlspecialchars($student_data['father_occupation'] ?? '') ?>" readonly class="border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                    <input type="tel" name="father_contact" placeholder="Contact No." value="<?= htmlspecialchars($student_data['father_contact'] ?? '') ?>" readonly class="border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                </div>

                <h3 class="font-semibold mt-4">Mother's Info</h3>
                <div class="grid grid-cols-3 gap-6">
                    <input type="text" name="mother_name" placeholder="Name" value="<?= htmlspecialchars($student_data['mother_name'] ?? '') ?>" readonly class="border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                    <input type="text" name="mother_occupation" placeholder="Occupation" value="<?= htmlspecialchars($student_data['mother_occupation'] ?? '') ?>" readonly class="border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                    <input type="tel" name="mother_contact" placeholder="Contact No." value="<?= htmlspecialchars($student_data['mother_contact'] ?? '') ?>" readonly class="border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                </div>

                <h3 class="font-semibold mt-4">Guardian's Info</h3>
                <div class="grid grid-cols-3 gap-6">
                    <input type="text" name="guardian_name" placeholder="Name" value="<?= htmlspecialchars($student_data['guardian_name'] ?? '') ?>" readonly class="border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                    <input type="text" name="guardian_occupation" placeholder="Occupation" value="<?= htmlspecialchars($student_data['guardian_occupation'] ?? '') ?>" readonly class="border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                    <input type="tel" name="guardian_contact" placeholder="Contact No." value="<?= htmlspecialchars($student_data['guardian_contact'] ?? '') ?>" readonly class="border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                </div>
            </div>

            <!-- Last School Attended -->
            <div class="col-span-3 grid grid-cols-2 gap-6 mt-4">
                <div>
                    <label class="block text-sm font-semibold mb-1">Last School Attended</label>
                    <input type="text" name="last_school" placeholder="School Name" value="<?= htmlspecialchars($student_data['last_school'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">School Year</label>
                    <input type="text" name="last_school_year" placeholder="School Year" value="<?= htmlspecialchars($student_data['last_school_year'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                </div>
            </div>

            <!-- Personal Account Section -->
            <div class="w-full flex justify-center mt-6">
                <div class="w-full max-w-3xl flex justify-center items-center border-b border-gray-200 px-6 py-2 bg-[#1E4D92] text-white">
                    <h2 class="text-lg font-semibold">PERSONAL ACCOUNT</h2>
                </div>
            </div>

            <div class="col-span-3 grid grid-cols-3 gap-6 mt-6">
                <!-- Student ID -->
                <div>
                    <label class="block text-sm font-semibold mb-1">Student ID</label>
                    <input type="number" name="id_number" value="<?= htmlspecialchars($student_data['id_number'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-sm font-semibold mb-1">Password</label>
                    <div class="relative">
                        <input type="password" value="••••••••" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50">
                    </div>
                </div>

                <!-- RFID Number -->
                <div>
                    <label class="block text-sm font-semibold mb-1">RFID Number</label>
                    <input type="number" name="rfid_uid" value="<?= htmlspecialchars($student_data['rfid_uid'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="col-span-3 flex justify-end gap-4 pt-6 border-t border-gray-200">
                <button type="button" onclick="window.location.href='../AccountList.php?type=student'" class="px-5 py-2 border border-[#1E4D92] text-[#1E4D92] rounded-xl hover:bg-[#1E4D92] hover:text-white transition">Back to List</button>
                <button type="submit" id="saveBtn" class="px-5 py-2 bg-[#2F8D46] text-white rounded-xl shadow hover:bg-[#256f37] transition hidden">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleEdit() {
    const editBtn = document.getElementById('editBtn');
    const saveBtn = document.getElementById('saveBtn');
    const fields = document.querySelectorAll('.student-field');
    
    const isEditing = editBtn.textContent === 'Cancel';
    
    if (isEditing) {
        // Cancel editing
        editBtn.textContent = 'Edit';
        editBtn.className = 'px-4 py-2 bg-[#2F8D46] text-white rounded-lg hover:bg-[#256f37] transition';
        saveBtn.classList.add('hidden');
        
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
        saveBtn.classList.remove('hidden');
        
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
    }
}
</script>

</body>
</html>
