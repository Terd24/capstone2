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
    $stmt = $conn->prepare("SELECT * FROM student_account WHERE id_number = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student_data = $result->fetch_assoc();
    $stmt->close();
}

// Handle delete request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_student'])) {
    $delete_stmt = $conn->prepare("DELETE FROM student_account WHERE id_number = ?");
    $delete_stmt->bind_param("s", $student_id);
    
    if ($delete_stmt->execute()) {
        $_SESSION['success_msg'] = "Student account deleted successfully!";
        header("Location: ../AccountList.php?type=student");
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
    $password = $_POST['password'] ?? '';

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
            id_number = ?, rfid_uid = ?, password = ?
            WHERE id_number = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param(
            "sssssssssssssssssssssssssssssssss",
            $lrn, $academic_track, $enrollment_status, $school_type,
            $last_name, $first_name, $middle_name, $username,
            $school_year, $grade_level, $semester,
            $dob, $birthplace, $gender, $religion, $credentials,
            $payment_mode, $address,
            $father_name, $father_occupation, $father_contact,
            $mother_name, $mother_occupation, $mother_contact,
            $guardian_name, $guardian_occupation, $guardian_contact,
            $last_school, $last_school_year,
            $id_number, $rfid_uid, $hashed_password, $student_id
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
            id_number = ?, rfid_uid = ?
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
            $id_number, $rfid_uid, $student_id
        );
    }

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
<header class="bg-[#0B2C62] text-white shadow-lg">
    <div class="container mx-auto px-6 py-4">
        <div class="flex justify-between items-center">
        <div class="flex items-center space-x-4">
        <button onclick="window.location.href='registrardashboard.php'" class="bg-white bg-opacity-20 hover:bg-opacity-30 p-2 rounded-lg transition">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
          </svg>
        </button>
        <div>
                <h1 class="text-xl font-bold">Account Management</h1>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <img src="../../images/LogoCCI.png" alt="Cornerstone College Inc." class="h-12 w-12 rounded-full bg-white p-1">
                <div class="text-right">
                    <h1 class="text-xl font-bold">Cornerstone College Inc.</h1>
                    <p class="text-blue-200 text-sm">Grade Management System</p>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Student Info Modal -->
<div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white w-full max-w-5xl rounded-2xl shadow-2xl overflow-hidden border-2 border-[#0B2C62] transform transition-all scale-100" id="modalContent">
        
        <!-- Header -->
        <div class="flex justify-between items-center border-b border-gray-200 px-6 py-4 bg-[#0B2C62] rounded-t-2xl">
            <h2 class="text-lg font-semibold text-white">Student Information</h2>
            <div class="flex gap-3">
                <button id="editBtn" onclick="toggleEdit()" class="px-4 py-2 bg-[#2F8D46] text-white rounded-lg hover:bg-[#256f37] transition">Edit</button>
                <button id="deleteBtn" onclick="confirmDelete()" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition">Delete</button>
                <button onclick="window.location.href='../AccountList.php?type=student'" class="text-2xl font-bold text-white hover:text-gray-300">&times;</button>
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
                        <!-- Row: Student ID, First Name, Last Name -->
                        <div>
                            <label class="block text-sm font-semibold mb-1">Student ID</label>
                            <input type="number" name="id_number" value="<?= htmlspecialchars($student_data['id_number'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">First Name</label>
                            <input type="text" name="first_name" value="<?= htmlspecialchars($student_data['first_name'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Last Name</label>
                            <input type="text" name="last_name" value="<?= htmlspecialchars($student_data['last_name'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                        </div>
                        
                        <!-- Row: Phone, Date of Birth -->
                        <div>
                            <label class="block text-sm font-semibold mb-1">Phone</label>
                            <input type="tel" name="phone" value="<?= htmlspecialchars($student_data['phone'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Date of Birth</label>
                            <input type="date" name="date_of_birth" value="<?= htmlspecialchars($student_data['date_of_birth'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                        </div>
                        <div></div>
                        
                        <!-- Row: Academic Track, Grade Level, Email -->
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
                <select name="grade_level" id="gradeLevel" disabled class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                    <option value="">-- Select Grade Level --</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($student_data['email'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
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
                        <div>
                            <label class="block text-sm font-semibold mb-1">Username</label>
                            <input type="text" name="username" value="<?= htmlspecialchars($student_data['username'] ?? '') ?>" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Role</label>
                            <input type="text" value="Student" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Password</label>
                            <input type="text" name="password" placeholder="Enter new password" readonly class="w-full border border-gray-300 px-3 py-2 rounded-lg bg-gray-50 student-field">
                            <small class="text-gray-500">Leave blank to keep current password</small>
                        </div>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="col-span-3 flex justify-end gap-4 pt-6 border-t border-gray-200">
                <button type="button" onclick="window.location.href='../AccountList.php?type=student'" class="px-5 py-2 border border-blue-600 text-blue-900 rounded-xl hover:bg-[#0B2C62] hover:text-white transition">Back to List</button>
                <button type="submit" id="saveBtn" class="px-5 py-2 bg-green-600 text-white rounded-xl shadow hover:bg-green-700 transition hidden">
                    Save Changes
                </button>
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

function toggleEdit() {
    const editBtn = document.getElementById('editBtn');
    const saveBtn = document.getElementById('saveBtn');
    const fields = document.querySelectorAll('.student-field');
    
    const isEditing = editBtn.textContent === 'Cancel';
    
    if (isEditing) {
        // Cancel editing
        editBtn.textContent = 'Edit';
        editBtn.className = 'px-4 py-2 bg-[#0B2C62] text-white rounded-lg hover:bg-blue-900 transition';
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
});

// Delete confirmation function
function confirmDelete() {
    showDeleteModal();
}

function showDeleteModal() {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[60]';
    modal.innerHTML = `
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 transform transition-all">
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
    const modal = document.querySelector('[class*="z-[60]"]');
    if (modal) {
        modal.remove();
    }
}

function proceedDelete() {
    // Create a form to submit the delete request
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = '<input type="hidden" name="delete_student" value="1">';
    document.body.appendChild(form);
    form.submit();
}
</script>

</body>
</html>