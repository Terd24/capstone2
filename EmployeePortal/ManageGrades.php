<?php
// Enable error reporting for debugging
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
}

session_start();
include("../StudentLogin/db_conn.php");
include("../includes/grading_helpers.php");

// Require TEACHER role only
$role = $_SESSION['role'] ?? '';
if ($role !== 'teacher') {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    header("Location: ../StudentLogin/login.php");
    exit;
}
// Current teacher name
$teacher_name = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));

// Handle form submission for adding/updating grades
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $id_number = $_POST['student_id'] ?? '';
        $subject = $_POST['subject'] ?? '';
        $school_year_term = !empty($_POST['school_year_term']) ? $_POST['school_year_term'] : '2024-2025 2nd Term';
        $grading_system = $_POST['grading_system'] ?? 'COLLEGE';
        $teacher_name = $_POST['teacher'] ?? '';
        
        // Validate required fields
        if (empty($id_number) || empty($subject) || empty($school_year_term)) {
            throw new Exception('Missing required fields: student_id, subject, or school_year_term');
        }
        
        // Check if grade record exists
        $check_stmt = $conn->prepare("SELECT id FROM grades_record WHERE id_number = ? AND subject = ? AND school_year_term = ?");
        if (!$check_stmt) {
            throw new Exception('Database prepare failed: ' . $conn->error);
        }
        $check_stmt->bind_param("sss", $id_number, $subject, $school_year_term);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
    
    if ($grading_system === 'K12') {
        // K-12 Grading System (Quarters)
        $first_quarter = $_POST['first_quarter'] ?: null;
        $second_quarter = $_POST['second_quarter'] ?: null;
        $third_quarter = $_POST['third_quarter'] ?: null;
        $fourth_quarter = $_POST['fourth_quarter'] ?: null;
        
        if ($result->num_rows > 0) {
            // Update existing record
            $update_stmt = $conn->prepare("UPDATE grades_record SET first_quarter = ?, second_quarter = ?, third_quarter = ?, fourth_quarter = ?, grading_system = ?, teacher_name = ? WHERE id_number = ? AND subject = ? AND school_year_term = ?");
            if (!$update_stmt) {
                throw new Exception('Update prepare failed: ' . $conn->error);
            }
            $update_stmt->bind_param("ddddsssss", $first_quarter, $second_quarter, $third_quarter, $fourth_quarter, $grading_system, $teacher_name, $id_number, $subject, $school_year_term);
            if (!$update_stmt->execute()) {
                throw new Exception('Update execute failed: ' . $update_stmt->error);
            }
            $success_msg = "Grade updated successfully!";
        } else {
            // Insert new record
            $insert_stmt = $conn->prepare("INSERT INTO grades_record (id_number, subject, school_year_term, first_quarter, second_quarter, third_quarter, fourth_quarter, grading_system, teacher_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$insert_stmt) {
                throw new Exception('Insert prepare failed: ' . $conn->error);
            }
            $insert_stmt->bind_param("sssddddss", $id_number, $subject, $school_year_term, $first_quarter, $second_quarter, $third_quarter, $fourth_quarter, $grading_system, $teacher_name);
            if (!$insert_stmt->execute()) {
                throw new Exception('Insert execute failed: ' . $insert_stmt->error);
            }
            $success_msg = "Grade added successfully!";
        }
    } else {
        // College Grading System (Terms)
        $prelim = $_POST['prelim'] ?: null;
        $midterm = $_POST['midterm'] ?: null;
        $pre_finals = $_POST['prefinals'] ?: null;
        $finals = $_POST['finals'] ?: null;
        
        if ($result->num_rows > 0) {
            // Update existing record
            $update_stmt = $conn->prepare("UPDATE grades_record SET prelim = ?, midterm = ?, pre_finals = ?, finals = ?, grading_system = ?, teacher_name = ? WHERE id_number = ? AND subject = ? AND school_year_term = ?");
            if (!$update_stmt) {
                throw new Exception('Update prepare failed: ' . $conn->error);
            }
            $update_stmt->bind_param("ddddsssss", $prelim, $midterm, $pre_finals, $finals, $grading_system, $teacher_name, $id_number, $subject, $school_year_term);
            if (!$update_stmt->execute()) {
                throw new Exception('Update execute failed: ' . $update_stmt->error);
            }
            $success_msg = "Grade updated successfully!";
        } else {
            // Insert new record
            $insert_stmt = $conn->prepare("INSERT INTO grades_record (id_number, subject, school_year_term, prelim, midterm, pre_finals, finals, grading_system, teacher_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$insert_stmt) {
                throw new Exception('Insert prepare failed: ' . $conn->error);
            }
            $insert_stmt->bind_param("sssddddss", $id_number, $subject, $school_year_term, $prelim, $midterm, $pre_finals, $finals, $grading_system, $teacher_name);
            if (!$insert_stmt->execute()) {
                throw new Exception('Insert execute failed: ' . $insert_stmt->error);
            }
            $success_msg = "Grade added successfully!";
        }
    }
        
        // Return JSON response for AJAX
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => $success_msg]);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        exit;
    }
}

// Handle success message from redirect
if (isset($_GET['success'])) {
    $success_msg = $_GET['success'];
}

// Get distinct school year terms from database
$terms_query = "SELECT DISTINCT school_year_term FROM grades_record ORDER BY school_year_term DESC";
$terms_result = $conn->query($terms_query);

// Fetch available teachers (employees with teacher accounts)
$teachers_sql = "SELECT e.id_number, CONCAT(e.first_name, ' ', e.last_name) AS full_name
                 FROM employees e
                 INNER JOIN employee_accounts a ON a.employee_id = e.id_number AND a.role = 'teacher'
                 WHERE e.deleted_at IS NULL OR e.deleted_at IS NULL
                 ORDER BY full_name";
$teachers_result = $conn->query($teachers_sql);

// Handle entries parameter
$entries_limit = 10; // default
if (isset($_GET['entries'])) {
    if ($_GET['entries'] === 'all') {
        $entries_limit = null;
    } else {
        $entries_limit = intval($_GET['entries']);
    }
}

// Fetch all grades for display
$grades_query = "SELECT g.*, CONCAT(s.first_name, ' ', s.last_name) as student_name 
                 FROM grades_record g 
                 LEFT JOIN student_account s ON g.id_number = s.id_number 
                 ORDER BY g.school_year_term DESC, g.subject ASC";
if ($entries_limit) {
    $grades_query .= " LIMIT " . $entries_limit;
}
$grades_result = $conn->query($grades_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Grades - Cornerstone College Inc.</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .school-gradient { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #1e40af 100%); }
        .card-shadow { box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .no-spinner::-webkit-outer-spin-button,
        .no-spinner::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        .no-spinner[type=number] {
            -moz-appearance: textfield;
        }
        
        /* Enhanced search input with icon */
        .search-wrapper {
            position: relative;
        }
        .search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #9CA3AF;
            pointer-events: none;
            z-index: 1;
        }
        .search-input-with-icon {
            padding-left: 48px !important;
        }
        
        /* Hide scrollbar but keep functionality */
        .custom-scrollbar {
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE and Edge */
        }
        .custom-scrollbar::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
        }
        
        /* Smooth animations */
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Student card hover effect */
        .student-card {
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
        }
        .student-card:hover {
            border-left-color: #10B981;
            transform: translateX(4px);
        }
        
        /* Empty state illustration */
        .empty-state {
            opacity: 0.6;
        }
        
        /* Loading spinner */
        .spinner {
            border: 3px solid #f3f4f6;
            border-top: 3px solid #10B981;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">

<!-- Header -->
<header class="bg-[#0B2C62] text-white shadow-lg">
    <div class="container mx-auto px-6 py-4">
        <div class="flex justify-between items-center">
        <div class="flex items-center space-x-4">
        <button onclick="handleBackNavigation()" class="bg-white bg-opacity-20 hover:bg-opacity-30 p-2 rounded-lg transition">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
          </svg>
        </button>
        <div>
        <h1 class="text-xl font-bold">Grades Management</h1>
                
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <img src="../images/LogoCCI.png" alt="Cornerstone College Inc." class="h-12 w-12 rounded-full bg-white p-1">
                <div class="text-right">
                    <h1 class="text-xl font-bold">Cornerstone College Inc.</h1>
                    <p class="text-blue-200 text-sm">Grade Management System</p>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Main Content -->
<div class="container mx-auto px-6 py-8">
    <!-- Student Search Section -->
    <div id="student-search-section" class="mb-6">
        <div class="bg-white rounded-xl card-shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">Search Students</h2>
                    <p class="text-sm text-gray-500 mt-1">Find students to manage their grades</p>
                </div>
                <div class="bg-green-100 p-3 rounded-lg">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>
            
            <div class="search-wrapper mb-4">
                <svg class="search-icon w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input type="text" id="studentSearchInput" placeholder="Type student name or ID number..." 
                       class="search-input-with-icon w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all">
            </div>
            
            <div id="student-results" class="space-y-2 max-h-96 overflow-y-auto custom-scrollbar">
                <div class="empty-state text-center py-12">
                    <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <p class="text-gray-400 text-sm">Start typing to search for students</p>
                    <p class="text-gray-300 text-xs mt-1">Search by name or ID number</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Individual Student Grades View (Hidden by default) -->
    <div id="student-grades-view" class="hidden">

        <!-- Student Header -->
        <div class="bg-white rounded-2xl card-shadow p-6 mb-6">
            <!-- Success Message -->
            <div id="successMessage" class="hidden mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded-lg fade-in flex items-center gap-3">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span id="successText" class="font-medium"></span>
            </div>
            
            <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4 mb-6">
                <div class="flex items-center gap-4">
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 p-4 rounded-xl shadow-lg">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800" id="student-name-display">Student Grades</h2>
                        <p class="text-gray-600 text-sm" id="student-id-display">Academic Performance Overview</p>
                    </div>
                </div>
                <button id="addGradeBtn" type="button" onclick="showAddGradeModal()" class="bg-green-600 hover:bg-green-700 text-white px-5 py-3 rounded-lg font-medium inline-flex items-center gap-2 transition-all shadow-md hover:shadow-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Add New Grade
                </button>
            </div>
            
            <!-- Term Selector -->
            <div class="bg-gray-50 rounded-lg p-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    School Year & Term
                </label>
                <select id="termSelector" class="w-full md:w-auto px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white transition-all" onchange="loadGradesByTerm()">
                    <!-- Options will be populated dynamically -->
                </select>
            </div>
        </div>

        <!-- Grades Display -->
        <div id="grades-container" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Grade cards will be populated here -->
        </div>
    </div>

    <!-- Add Grade Modal -->
    <div id="addGradeModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-gray-800">Add New Grade</h3>
                <button onclick="hideAddGradeModal()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form id="addGradeForm" class="space-y-4">
                <input type="hidden" id="modal-student-id" name="student_id">
                <input type="hidden" id="modal-grading-system" name="grading_system">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                    <select id="subjectSelect" name="subject" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                        <option value="">-- Select Subject --</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teacher</label>
                    <div class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-700">
                      <?= htmlspecialchars($teacher_name ?: 'Teacher') ?>
                    </div>
                    <input type="hidden" id="teacherHidden" name="teacher" value="<?= htmlspecialchars($teacher_name) ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">School Year & Term</label>
                    <div class="grid grid-cols-2 gap-2">
                        <select id="modalSchoolYear" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                            <?php if ($terms_result && $terms_result->num_rows > 0): ?>
                                <?php 
                                  $terms_result->data_seek(0);
                                  $years = [];
                                  while ($row = $terms_result->fetch_assoc()): 
                                    $term = $row['school_year_term'];
                                    if (preg_match('/^(\d{4}-\d{4})\s+\d(?:st|nd|rd|th)\s+Term$/i', $term, $m)) {
                                        $years[$m[1]] = true;
                                    }
                                ?>
                                <?php endwhile; ?>
                                <?php foreach(array_keys($years) as $y): ?>
                                    <option value="<?= htmlspecialchars($y) ?>"><?= htmlspecialchars($y) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <select id="modalTermOnly" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                            <option value="1st Term">1st Term</option>
                            <option value="2nd Term">2nd Term</option>
                        </select>
                    </div>
                    <!-- Hidden combined field preserved for backend compatibility -->
                    <select id="modalTermSelect" name="school_year_term" class="hidden">
                        <?php if ($terms_result && $terms_result->num_rows > 0): ?>
                            <?php 
                              $terms_result->data_seek(0);
                              while ($row = $terms_result->fetch_assoc()): 
                                $term = $row['school_year_term'];
                            ?>
                              <option value="<?= htmlspecialchars($term) ?>"><?= htmlspecialchars($term) ?></option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <!-- K-12 Grade Fields (shown conditionally) -->
                <div id="k12-grades" class="hidden">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">1st Quarter</label>
                            <input type="number" name="first_quarter" min="0" max="100" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 no-spinner">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">2nd Quarter</label>
                            <input type="number" name="second_quarter" min="0" max="100" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 no-spinner">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 mt-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">3rd Quarter</label>
                            <input type="number" name="third_quarter" min="0" max="100" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 no-spinner">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">4th Quarter</label>
                            <input type="number" name="fourth_quarter" min="0" max="100" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 no-spinner">
                        </div>
                    </div>
                </div>
                
                <!-- College Grade Fields (shown conditionally) -->
                <div id="college-grades" class="hidden">
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Prelim</label>
                            <input type="number" name="prelim" min="0" max="100" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 no-spinner">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Midterm</label>
                            <input type="number" name="midterm" min="0" max="100" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 no-spinner">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Finals</label>
                            <input type="number" name="finals" min="0" max="100" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 no-spinner">
                        </div>
                    </div>
                </div>
                
                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="hideAddGradeModal()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2 rounded-lg">Cancel</button>
                    <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg">Save Grade</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteConfirmModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-6 w-full max-w-sm mx-4">
        <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Delete Grade</h3>
            <p class="text-sm text-gray-500 mb-6">Are you sure you want to delete this grade? This action cannot be undone.</p>
            <div class="flex gap-3">
                <button onclick="hideDeleteModal()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg font-medium">
                    Cancel
                </button>
                <button onclick="confirmDelete()" class="flex-1 bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg font-medium">
                    Delete
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// --------- K-12 vs College Grading System Support ---------

/**
 * Determines if a student is K-12 or College based on grade level
 * @param {string} gradeLevel - The student's grade level
 * @returns {string} - "K12" or "COLLEGE"
 */
function determineGradingSystem(gradeLevel) {
    if (!gradeLevel) return "COLLEGE";
    
    const level = gradeLevel.toLowerCase().trim();
    
    // K-12 patterns
    if (level.includes('kinder')) return "K12";
    if (/grade\s*[1-9]/.test(level)) return "K12";
    if (/grade\s*1[0-2]/.test(level)) return "K12";
    
    // College patterns
    if (/[1-4](st|nd|rd|th)\s*year/.test(level)) return "COLLEGE";
    
    return "COLLEGE"; // Default to college if uncertain
}

/**
 * Calculates the average grade based on grading system
 * @param {object} grade - Grade record object
 * @returns {number|null} - Average grade or null if incomplete
 */
function calculateAverage(grade) {
    const gradingSystem = grade.grading_system || determineGradingSystem(currentStudentInfo.gradeLevelText);
    
    if (gradingSystem === 'K12') {
        const quarters = [
            parseFloat(grade.first_quarter),
            parseFloat(grade.second_quarter),
            parseFloat(grade.third_quarter),
            parseFloat(grade.fourth_quarter)
        ].filter(g => !isNaN(g) && g !== null);
        
        if (quarters.length === 0) return null;
        return quarters.reduce((sum, g) => sum + g, 0) / quarters.length;
    } else {
        // College: Use prelim, midterm, finals (NOT pre_finals)
        const terms = [
            parseFloat(grade.prelim),
            parseFloat(grade.midterm),
            parseFloat(grade.finals)
        ].filter(g => !isNaN(g) && g !== null);
        
        if (terms.length === 0) return null;
        return terms.reduce((sum, g) => sum + g, 0) / terms.length;
    }
}

/**
 * Determines pass/fail status
 * @param {number} average - The calculated average
 * @returns {object} - Status object with text, color, and bgColor
 */
function getGradeStatus(average) {
    if (average === null) {
        return { text: 'INCOMPLETE', color: 'text-gray-600', bgColor: 'bg-gray-100' };
    }
    if (average >= 75) {
        return { text: 'PASSED', color: 'text-green-600', bgColor: 'bg-green-100' };
    }
    return { text: 'FAILED', color: 'text-red-600', bgColor: 'bg-red-100' };
}

// --------- Dynamic Subject Options (API-driven from Manage Subjects) ---------

// Parse grade text to detect Kinder, Grades, or College Year (encoded 101..104)
function parseGradeLevel(glText){
  if (!glText) return null;
  if (/kinder/i.test(glText)) return 0;
  const g = String(glText).match(/\b(?:Grade\s*(\d+)|G(\d+))\b/i);
  if (g) return parseInt(g[1]||g[2], 10);
  const y = String(glText).match(/(\d)\s*(st|nd|rd|th)\s*Year/i);
  if (y) return 100 + parseInt(y[1],10); // 101..104 encode college year
  return null;
}

function getSemesterFromTermString(termStr){
  const t = String(termStr||'');
  const result = /2nd/i.test(t) ? '2nd' : '1st';
  console.log('getSemesterFromTermString - Input:', termStr, '| Output:', result);
  return result;
}

function mapStrandFromProgram(program){
  const p = String(program||'').toUpperCase();
  if (/ABM/.test(p)) return 'ABM';
  if (/STEM/.test(p)) return 'STEM';
  if (/HUMSS/.test(p)) return 'HUMSS';
  if (/GAS/.test(p)) return 'GAS';
  if (/ICT/.test(p)) return 'TVL-ICT';
  if (/(HE|HOME ECONOMICS)/.test(p)) return 'TVL-HE';
  if (/SPORT/.test(p)) return 'SPORTS';
  // College programs
  if (/BPED/.test(p)) return 'BPED';
  if (/BECED/.test(p)) return 'BECED';
  // Generic fallback: short alphabetic code becomes the strand (e.g., "BSIT")
  if (/^[A-Z]{2,6}$/.test(p)) return p;
  return '';
}

function normalizeGradeLabel(gl){
  let s = String(gl||'').trim();
  // Remove section parts like " - Section A" or " | ..."
  s = s.replace(/\s*[-|].*$/, '');
  const L = s.toLowerCase();
  // Kinder
  if (/kinder\s*1/.test(L)) return 'Kinder 1';
  if (/kinder\s*2/.test(L)) return 'Kinder 2';
  if (/^kinder$/.test(L)) return 'Kinder 1';
  // Grades
  const g = L.match(/grade\s*(\d{1,2})/);
  if (g){ return `Grade ${parseInt(g[1],10)}`; }
  // Year -> College year
  // Worded forms
  if (/first\s*year/.test(L)) return '1st Year';
  if (/second\s*year/.test(L)) return '2nd Year';
  if (/third\s*year/.test(L)) return '3rd Year';
  if (/fourth\s*year/.test(L)) return '4th Year';
  const y1 = L.match(/(1|2|3|4)\s*(st|nd|rd|th)?\s*year/);
  if (y1){
    const n = parseInt(y1[1],10);
    return (n===1? '1st': n===2? '2nd': n===3? '3rd': '4th') + ' Year';
  }
  const y2 = L.match(/year\s*(1|2|3|4)/);
  if (y2){
    const n = parseInt(y2[1],10);
    return (n===1? '1st': n===2? '2nd': n===3? '3rd': '4th') + ' Year';
  }
  return s; // fallback
}

async function fetchOfferedSubjects(gradeLevelText, program, termValue){
  // Get the offered subjects for this specific student's grade/strand/semester
  const gradeStr = normalizeGradeLabel(gradeLevelText);
  const isCollege = /(1st|2nd|3rd|4th)\s+Year$/i.test(gradeStr);
  const strand = isCollege ? '' : mapStrandFromProgram(program);
  const semester = getSemesterFromTermString(termValue);
  
  // Don't pass school year - we only want to filter by semester/term
  // This allows subjects to show for any school year as long as the semester matches
  const sy = '';
  
  console.log('=== FETCH OFFERED SUBJECTS ===');
  console.log('Input termValue:', termValue);
  console.log('Extracted semester:', semester);
  console.log('Fetching subjects for:', {grade: gradeStr, strand, semester, 'school_year': 'ANY (filtering by semester only)'});
  
  const params = new URLSearchParams({action:'list', grade_level: gradeStr, strand, semester, sy});
  console.log('API URL:', 'api/subject_offerings.php?'+params.toString());
  
  const res = await fetch('api/subject_offerings.php?'+params.toString());
  const d = await res.json();
  const offeredSubjects = (d && d.success && Array.isArray(d.items)) ? d.items : [];
  
  console.log('API Response:', d);
  console.log('Offered subjects for student:', offeredSubjects);
  
  // Get teacher's assigned subjects
  const teacherSubjectsRes = await fetch('api/get_teacher_subjects.php');
  const teacherSubjectsData = await teacherSubjectsRes.json();
  const teacherSubjects = teacherSubjectsData.success ? teacherSubjectsData.subjects : [];
  
  console.log('Teacher assigned subjects:', teacherSubjects);
  
  // If teacher has no assigned subjects, return all offered subjects (admin/fallback mode)
  if (teacherSubjects.length === 0) {
    console.log('Teacher has no assigned subjects, showing all offered subjects');
    return offeredSubjects;
  }
  
  // If no subjects are offered for this student's grade/strand/semester, return empty
  if (offeredSubjects.length === 0) {
    console.log('No subjects offered for this student grade/strand/semester');
    return [];
  }
  
  // Find intersection: subjects that are BOTH assigned to teacher AND offered for this student
  const offeredMap = new Map();
  offeredSubjects.forEach(s => {
    const key = s.name.toLowerCase().trim();
    offeredMap.set(key, s);
  });
  
  const intersection = [];
  teacherSubjects.forEach(ts => {
    const key = ts.subject_name.toLowerCase().trim();
    if (offeredMap.has(key)) {
      const offered = offeredMap.get(key);
      intersection.push({
        id: ts.id,
        name: ts.subject_name,
        code: offered.code || ''
      });
      console.log('✓ Match found:', ts.subject_name, '- Teacher can grade this subject');
    } else {
      console.log('✗ No match:', ts.subject_name, '- Not offered for this student');
    }
  });
  
  console.log('Final subjects (intersection):', intersection);
  
  // Return only subjects that match both criteria
  return intersection;
}

async function populateSubjectOptions(info, preselectSubject=null){
  const reqId = ++subjectOptionsRequestId;
  const sel = document.getElementById('subjectSelect');
  if (!sel) return;
  
  // Get term from BOTH the hidden combined field AND the visible dropdowns
  const termSel = document.getElementById('modalTermSelect');
  const yearSel = document.getElementById('modalSchoolYear');
  const termOnlySel = document.getElementById('modalTermOnly');
  
  // Build term value from visible dropdowns if hidden field is empty
  let termValue = termSel ? termSel.value : '';
  if (!termValue && yearSel && termOnlySel) {
    termValue = `${yearSel.value} ${termOnlySel.value}`;
    console.log('Built termValue from visible dropdowns:', termValue);
  }

  console.log('=== POPULATE SUBJECT OPTIONS ===');
  console.log('Hidden field (modalTermSelect):', termSel ? termSel.value : 'N/A');
  console.log('Year dropdown:', yearSel ? yearSel.value : 'N/A');
  console.log('Term dropdown:', termOnlySel ? termOnlySel.value : 'N/A');
  console.log('Final termValue used:', termValue);
  console.log('Grade:', info?.gradeLevelText, '| Program:', info?.program);

  // Clear current selection first to avoid showing invalid subjects
  sel.innerHTML = '<option value="">-- Select Subject --</option>';
  sel.value = '';

  // Preserve current selection if caller didn't provide one AND it's valid for the new term
  const preserved = preselectSubject ?? '';

  try{
    const items = await fetchOfferedSubjects(info?.gradeLevelText||'', info?.program||'', termValue);
    console.log('Fetched subjects:', items.length, 'items for term:', termValue);
    // Deduplicate by name+code
    const norm = (s)=> String(s||'').toLowerCase().replace(/\s+/g,' ').trim();
    const uniq = new Map();
    (items||[]).forEach(it=>{
      const key = `${norm(it.name)}|${norm(it.code)}`;
      if (!uniq.has(key)) uniq.set(key, it);
    });
    // If another request started after this one, abort applying results
    if (reqId !== subjectOptionsRequestId) return;
    sel.innerHTML = '<option value="">-- Select Subject --</option>';
    Array.from(uniq.values()).forEach(it=>{
      const label = it.code ? `${it.name} (${it.code})` : it.name;
      const o = document.createElement('option'); o.value = it.name; o.textContent = label; sel.appendChild(o);
    });
    // Restore selection if available
    if (preserved){
      if (!Array.from(sel.options).some(o=>o.value===preserved)){
        const o=document.createElement('option'); o.value=preserved; o.textContent=preserved; sel.appendChild(o);
      }
      sel.value = preserved;
    }
    // Attempt to prefill grade inputs after we know the current selection
    prefillModalInputsFromExisting();
  }catch(e){ console.error('Failed to load offered subjects', e); }
}
let currentStudentId = null;
let subjectOptionsRequestId = 0;

// Clear all grade input fields
function clearGradeInputs() {
  // Clear K-12 quarters
  const q1 = document.querySelector('input[name="first_quarter"]');
  const q2 = document.querySelector('input[name="second_quarter"]');
  const q3 = document.querySelector('input[name="third_quarter"]');
  const q4 = document.querySelector('input[name="fourth_quarter"]');
  if (q1) q1.value = '';
  if (q2) q2.value = '';
  if (q3) q3.value = '';
  if (q4) q4.value = '';
  
  // Clear College terms
  const prelimI = document.querySelector('input[name="prelim"]');
  const midI = document.querySelector('input[name="midterm"]');
  const finI = document.querySelector('input[name="finals"]');
  if (prelimI) prelimI.value = '';
  if (midI) midI.value = '';
  if (finI) finI.value = '';
}

// Prefill modal inputs based on current Subject + Term for current student
async function prefillModalInputsFromExisting(){
  try{
    const subjSel = document.getElementById('subjectSelect');
    const termSel = document.getElementById('modalTermSelect');
    if (!subjSel || !termSel) return;
    
    const subj = subjSel.value || '';
    const term = termSel.value || '';
    if (!subj || !term) return; // Don't prefill if subject or term not selected
    
    // Try from already loaded grades first
    let match = (allGrades||[]).find(g=> String(g.subject||'')===subj && String(g.school_year_term||'')===term);
    
    // If not found, fetch grades for the specific term and retry
    if (!match && currentStudentId && term){
      try{
        const resp = await fetch('api/get_student_grades.php', {
          method: 'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
          body: `student_id=${encodeURIComponent(currentStudentId)}&term=${encodeURIComponent(term)}`
        });
        const d = await resp.json();
        if (d && d.success && Array.isArray(d.grades)){
          match = d.grades.find(g=> String(g.subject||'')===subj && String(g.school_year_term||'')===term) || null;
        }
      }catch(e){ console.warn('Prefill fetch failed', e); }
    }
    
    // Determine grading system
    const gradingSystem = match?.grading_system || determineGradingSystem(currentStudentInfo.gradeLevelText);
    
    if (gradingSystem === 'K12') {
      // Pre-fill K-12 quarters
      const q1 = document.querySelector('input[name="first_quarter"]');
      const q2 = document.querySelector('input[name="second_quarter"]');
      const q3 = document.querySelector('input[name="third_quarter"]');
      const q4 = document.querySelector('input[name="fourth_quarter"]');
      if (q1) q1.value = match && match.first_quarter != null ? match.first_quarter : '';
      if (q2) q2.value = match && match.second_quarter != null ? match.second_quarter : '';
      if (q3) q3.value = match && match.third_quarter != null ? match.third_quarter : '';
      if (q4) q4.value = match && match.fourth_quarter != null ? match.fourth_quarter : '';
    } else {
      // Pre-fill College terms
      const prelimI = document.querySelector('input[name="prelim"]');
      const midI = document.querySelector('input[name="midterm"]');
      const finI = document.querySelector('input[name="finals"]');
      if (prelimI) prelimI.value = match && match.prelim != null ? match.prelim : '';
      if (midI) midI.value = match && match.midterm != null ? match.midterm : '';
      if (finI) finI.value = match && match.finals != null ? match.finals : '';
    }
    
    // Show a message if grades were found
    if (match) {
      console.log('Found existing grades for', subj, '-', term, '- Pre-filled for editing');
    }
  }catch(e){ console.error('prefillModalInputsFromExisting error', e); }
}

// Search students function
function searchStudents() {
    const searchInput = document.getElementById('studentSearchInput');
    const resultsDiv = document.getElementById('student-results');
    
    if (!searchInput || !resultsDiv) {
        console.error('Search elements not found');
        return;
    }
    
    const query = searchInput.value.trim();
    
    if (query.length < 2) {
        resultsDiv.innerHTML = `
            <div class="empty-state text-center py-12">
                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <p class="text-gray-400 text-sm">Please enter at least 2 characters to search</p>
            </div>
        `;
        return;
    }

    // Show loading spinner
    resultsDiv.innerHTML = `
        <div class="text-center py-12">
            <div class="spinner mx-auto mb-4"></div>
            <p class="text-gray-500 text-sm">Searching for students...</p>
        </div>
    `;

    // Make AJAX call to search students in database
    fetch('api/SearchStudent.php?query=' + encodeURIComponent(query))
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                resultsDiv.innerHTML = `
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 mx-auto text-red-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-red-500 font-medium">Error: ${data.error}</p>
                    </div>
                `;
                return;
            }

            const students = data.students || [];
            let resultsHTML = '';
            
            // Show message if teacher has no assigned section
            if (data.message) {
                resultsDiv.innerHTML = `
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg fade-in">
                        <div class="flex items-center gap-3">
                            <svg class="w-6 h-6 text-yellow-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <p class="text-yellow-800 font-medium">${data.message}</p>
                        </div>
                    </div>
                `;
                return;
            }
            
            if (students.length > 0) {
                resultsHTML = `<div class="space-y-2">`;
                students.forEach(student => {
                    const displayName = student.full_name || `${student.first_name} ${student.last_name}`;
                    const gradeLevel = student.year_section || student.grade_level || 'N/A';
                    const program = student.program || student.academic_track || '';
                    // Escape values for attribute context
                    const safeName = String(displayName).replace(/\\/g, "\\\\").replace(/'/g, "\\'");
                    const safeGL = String(gradeLevel).replace(/\\/g, "\\\\").replace(/'/g, "\\'");
                    const safeProg = String(program).replace(/\\/g, "\\\\").replace(/'/g, "\\'");
                    
                    resultsHTML += `
                        <div class="student-card bg-gray-50 hover:bg-green-50 p-4 rounded-lg cursor-pointer fade-in" 
                             onclick="viewStudentGrades('${student.id_number}', '${safeName}', '${safeGL}', '${safeProg}')">
                            <div class="flex justify-between items-center">
                                <div class="flex items-center gap-3">
                                    <div class="bg-green-100 p-2 rounded-full">
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-800">${displayName}</h3>
                                        <p class="text-sm text-gray-600">ID: ${student.id_number} • ${gradeLevel}</p>
                                        ${program ? `<p class="text-xs text-gray-500 mt-1"><span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded">${program}</span></p>` : ''}
                                    </div>
                                </div>
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </div>
                        </div>
                    `;
                });
                resultsHTML += `</div>`;
            } else {
                resultsHTML = `
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-gray-500 font-medium">No students found</p>
                        <p class="text-gray-400 text-sm mt-1">Try a different search term</p>
                    </div>
                `;
            }

            resultsDiv.innerHTML = resultsHTML;
        })
        .catch(error => {
            console.error('Search error:', error);
            resultsDiv.innerHTML = `
                <div class="text-center py-12">
                    <svg class="w-16 h-16 mx-auto text-red-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-red-500 font-medium">Search failed</p>
                    <p class="text-gray-400 text-sm mt-1">Please try again</p>
                </div>
            `;
        });
}


// View student grades
let currentStudentInfo = { gradeLevelText: null, program: null };

function viewStudentGrades(studentId, studentName, gradeLevelText = '', program = '') {
    currentStudentId = studentId;
    document.getElementById('student-name-display').textContent = studentName;
    document.getElementById('student-id-display').textContent = `ID: ${studentId} • Academic Performance Overview`;
    currentStudentInfo = { gradeLevelText, program };
    
    document.getElementById('student-search-section').classList.add('hidden');
    document.getElementById('student-grades-view').classList.remove('hidden');
    
    loadStudentGrades(studentId);
}

// Load student grades
function loadStudentGrades(studentId, selectedTerm = null) {
    console.log('Loading grades for student:', studentId, 'term:', selectedTerm);
    currentStudentId = studentId;
    
    // Prepare request body
    let requestBody = `student_id=${encodeURIComponent(studentId)}`;
    if (selectedTerm) {
        requestBody += `&term=${encodeURIComponent(selectedTerm)}`;
    }
    
    // Make AJAX call to get student grades from database
    fetch('api/get_student_grades.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: requestBody
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const grades = data.grades || [];
            const terms = data.terms || [];
            const selectedTerm = data.selected_term;
            
            // Populate term selector
            populateTermSelector(terms, selectedTerm);
            
            allGrades = grades; // Store for filtering
            let gradesHTML = '';
            
            if (grades.length > 0) {
                grades.forEach((grade, index) => {
                    // Determine grading system and calculate average
                    const gradingSystem = grade.grading_system || determineGradingSystem(currentStudentInfo.gradeLevelText);
                    const average = calculateAverage(grade);
                    const status = getGradeStatus(average);
                    const averageDisplay = average ? average.toFixed(2) : 'N/A';
                    
                    let gradeFieldsHTML = '';
                    
                    if (gradingSystem === 'K12') {
                        // K-12 Quarters Display
                        gradeFieldsHTML = `
                            <div class="grid grid-cols-4 gap-2 mb-3">
                                <div class="text-center">
                                    <p class="text-xs text-gray-500 uppercase font-medium mb-1">1ST QTR</p>
                                    <p class="text-lg font-bold text-gray-900">${grade.first_quarter || '-'}</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs text-gray-500 uppercase font-medium mb-1">2ND QTR</p>
                                    <p class="text-lg font-bold text-gray-900">${grade.second_quarter || '-'}</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs text-gray-500 uppercase font-medium mb-1">3RD QTR</p>
                                    <p class="text-lg font-bold text-gray-900">${grade.third_quarter || '-'}</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs text-gray-500 uppercase font-medium mb-1">4TH QTR</p>
                                    <p class="text-lg font-bold text-gray-900">${grade.fourth_quarter || '-'}</p>
                                </div>
                            </div>
                        `;
                    } else {
                        // College Terms Display
                        gradeFieldsHTML = `
                            <div class="grid grid-cols-3 gap-2 mb-3">
                                <div class="text-center">
                                    <p class="text-xs text-gray-500 uppercase font-medium mb-1">PRELIM</p>
                                    <p class="text-lg font-bold text-gray-900">${grade.prelim || '-'}</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs text-gray-500 uppercase font-medium mb-1">MIDTERM</p>
                                    <p class="text-lg font-bold text-gray-900">${grade.midterm || '-'}</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs text-gray-500 uppercase font-medium mb-1">FINALS</p>
                                    <p class="text-lg font-bold text-gray-900">${grade.finals || '-'}</p>
                                </div>
                            </div>
                        `;
                    }
                    
                    gradesHTML += `
                        <div class="bg-white rounded-2xl card-shadow p-5 hover:shadow-xl transition-all fade-in border-l-4 ${status.color === 'text-green-600' ? 'border-green-500' : status.color === 'text-red-600' ? 'border-red-500' : 'border-gray-300'}">
                            <div class="mb-4 flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <div class="bg-blue-100 p-2 rounded-lg">
                                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-bold text-gray-800">${grade.subject || 'Unknown Subject'}</h3>
                                            <p class="text-xs text-gray-500">${gradingSystem === 'K12' ? '📚 K-12 System' : '🎓 College System'}</p>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-600 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                        ${grade.teacher_name || 'Not assigned'}
                                    </p>
                                </div>
                                <div class="flex gap-1">
                                    <button onclick="editGrade(${index})" class="p-2 text-blue-500 hover:bg-blue-50 rounded-lg transition-colors" title="Edit Grade">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    <button onclick="deleteGrade(${grade.id})" class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Delete Grade">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="bg-gray-50 rounded-lg p-3 mb-3">
                                ${gradeFieldsHTML}
                            </div>
                            
                            <div class="flex justify-between items-center pt-3 border-t border-gray-200">
                                <div>
                                    <p class="text-xs text-gray-500 mb-1">Final Average</p>
                                    <p class="text-2xl font-bold ${status.color}">${averageDisplay}%</p>
                                </div>
                                <div class="text-right">
                                    <span class="px-3 py-1.5 rounded-full text-sm font-semibold ${status.bgColor} ${status.color}">${status.text}</span>
                                </div>
                            </div>
                        </div>
                    `;
                });
            } else {
                gradesHTML = `
                    <div class="col-span-full">
                        <div class="bg-white rounded-2xl card-shadow p-12 text-center">
                            <div class="bg-gray-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-700 mb-2">No Grades Yet</h3>
                            <p class="text-gray-500 mb-6">This student doesn't have any grades for the selected term.</p>
                            <button onclick="showAddGradeModal()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium inline-flex items-center gap-2 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Add First Grade
                            </button>
                        </div>
                    </div>
                `;
            }
            
            document.getElementById('grades-container').innerHTML = gradesHTML;
        } else {
            document.getElementById('grades-container').innerHTML = '<div class="text-center py-8"><p class="text-red-500">Error loading grades: ' + (data.message || 'Unknown error') + '</p></div>';
        }
    })
    .catch(error => {
        console.error('Error loading grades:', error);
        document.getElementById('grades-container').innerHTML = '<div class="text-center py-8"><p class="text-red-500">Failed to load grades. Please try again.</p></div>';
    });
}

// Edit grade function (called by edit button)
function editGrade(index) {
    if (allGrades && allGrades[index]) {
        editGradeModal(allGrades[index]);
    }
}

// Enhanced grade editing modal function
function editGradeModal(grade) {
    // Clear form first
    document.getElementById('addGradeForm').reset();
    
    // Determine grading system and show appropriate fields
    const gradingSystem = grade.grading_system || determineGradingSystem(currentStudentInfo.gradeLevelText);
    document.getElementById('modal-grading-system').value = gradingSystem;
    
    if (gradingSystem === 'K12') {
        document.getElementById('k12-grades').classList.remove('hidden');
        document.getElementById('college-grades').classList.add('hidden');
        // Pre-fill K-12 grades
        document.querySelector('input[name="first_quarter"]').value = grade.first_quarter || '';
        document.querySelector('input[name="second_quarter"]').value = grade.second_quarter || '';
        document.querySelector('input[name="third_quarter"]').value = grade.third_quarter || '';
        document.querySelector('input[name="fourth_quarter"]').value = grade.fourth_quarter || '';
    } else {
        document.getElementById('k12-grades').classList.add('hidden');
        document.getElementById('college-grades').classList.remove('hidden');
        // Pre-fill College grades
        document.querySelector('input[name="prelim"]').value = grade.prelim || '';
        document.querySelector('input[name="midterm"]').value = grade.midterm || '';
        document.querySelector('input[name="finals"]').value = grade.finals || '';
    }
    
    // Populate the modal with grade data
    // Populate subjects based on current student, then select existing subject
    populateSubjectOptions(currentStudentInfo, grade.subject);
    
    // Ensure term change listener is active
    const termSelect = document.getElementById('modalTermSelect');
    if (termSelect && !termSelect.hasAttribute('data-listener-added')) {
        termSelect.addEventListener('change', function() {
            populateSubjectOptions(currentStudentInfo, grade.subject);
        });
        termSelect.setAttribute('data-listener-added', 'true');
    }
    
    // Set term select for edit
    (function(){
        const sel = document.getElementById('modalTermSelect');
        if (!sel) return;
        const term = (grade.school_year_term || '').trim();
        if (!term) { sel.value = ''; return; }
        let found = false;
        Array.from(sel.options).forEach(o => { if ((o.value||'') === term) found = true; });
        if (!found) {
            const opt = document.createElement('option'); opt.value = term; opt.textContent = term; sel.appendChild(opt);
        }
        sel.value = term;
    })();
    
    document.getElementById('modal-student-id').value = currentStudentId;
    
    // Change modal title and button text for editing
    document.querySelector('#addGradeModal h3').textContent = 'Edit Grade';
    document.querySelector('#addGradeModal button[type="submit"]').textContent = 'Update Grade';
    
    // Make Subject and School Year & Term readonly in EDIT mode
    // Use a simpler approach: just make them readonly, not disabled
    const subjSel = document.getElementById('subjectSelect');
    const yearSel = document.getElementById('modalSchoolYear');
    const termOnlySel = document.getElementById('modalTermOnly');
    
    if (subjSel) {
        subjSel.style.backgroundColor = '#f0f0f0';
        subjSel.style.cursor = 'not-allowed';
        subjSel.setAttribute('readonly', 'readonly');
        subjSel.style.pointerEvents = 'none';
    }
    if (yearSel) {
        yearSel.style.backgroundColor = '#f0f0f0';
        yearSel.style.cursor = 'not-allowed';
        yearSel.style.pointerEvents = 'none';
    }
    if (termOnlySel) {
        termOnlySel.style.backgroundColor = '#f0f0f0';
        termOnlySel.style.cursor = 'not-allowed';
        termOnlySel.style.pointerEvents = 'none';
    }
    
    // Show the modal
    document.getElementById('addGradeModal').classList.remove('hidden');
}

// View grade details function
function viewGradeDetails(gradeId) {
    alert(`Viewing detailed breakdown for grade ID: ${gradeId}\n\nThis feature will show:\n- Assignment breakdowns\n- Attendance records\n- Performance trends\n- Teacher comments`);
}

// Delete grade variables
let gradeToDelete = null;

// Show delete confirmation modal
function deleteGrade(gradeId) {
    gradeToDelete = gradeId;
    document.getElementById('deleteConfirmModal').classList.remove('hidden');
}

// Hide delete confirmation modal
function hideDeleteModal() {
    document.getElementById('deleteConfirmModal').classList.add('hidden');
    gradeToDelete = null;
}

// Confirm delete action
function confirmDelete() {
    if (gradeToDelete) {
        // Make AJAX call to delete the grade
        const formData = new FormData();
        formData.append('grade_id', gradeToDelete);
        
        fetch('delete_grade.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccessMessage(data.message);
                // Reload the grades for this student
                if (currentStudentId) {
                    loadStudentGrades(currentStudentId);
                    refreshTermSelector();
                }
            } else {
                showSuccessMessage('Error: ' + (data.message || 'Failed to delete grade'));
            }
        })
        .catch(error => {
            console.error('Error deleting grade:', error);
            showSuccessMessage('Failed to delete grade. Please try again.');
        })
        .finally(() => {
            hideDeleteModal();
        });
    }
}

// Show search section
function showSearchSection() {
    document.getElementById('student-search-section').classList.remove('hidden');
    document.getElementById('student-grades-view').classList.add('hidden');
    currentStudentId = null;
}

// Show add grade modal
function showAddGradeModal() {
    if (currentStudentId) {
        // Reset form for new grade
        document.getElementById('addGradeForm').reset();
        document.getElementById('modal-student-id').value = currentStudentId;
        
        // Determine grading system and show appropriate fields
        const gradingSystem = determineGradingSystem(currentStudentInfo.gradeLevelText);
        document.getElementById('modal-grading-system').value = gradingSystem;
        
        if (gradingSystem === 'K12') {
            document.getElementById('k12-grades').classList.remove('hidden');
            document.getElementById('college-grades').classList.add('hidden');
        } else {
            document.getElementById('k12-grades').classList.add('hidden');
            document.getElementById('college-grades').classList.remove('hidden');
        }
        
        // Populate subjects for this student's level/track
        populateSubjectOptions(currentStudentInfo);
        
        // Add term change listener if not already added
        const termSelect = document.getElementById('modalTermSelect');
        if (termSelect && !termSelect.hasAttribute('data-listener-added')) {
            termSelect.addEventListener('change', function() {
                populateSubjectOptions(currentStudentInfo);
            });
            termSelect.setAttribute('data-listener-added', 'true');
        }
        
        // Don't call getLatestTermAndPopulate() - we want to default to current year
        // Immediately default to current year and 1st Term
        setTimeout(()=>{
          try{
            const yearSel = document.getElementById('modalSchoolYear');
            const termOnlySel = document.getElementById('modalTermOnly');
            const combinedSel = document.getElementById('modalTermSelect');
            
            // Calculate current academic year (August boundary)
            const now = new Date();
            const month = now.getMonth() + 1; // 1..12
            const startYear = (month >= 8) ? now.getFullYear() : (now.getFullYear() - 1);
            const curSY = `${startYear}-${startYear+1}`;
            
            // Ensure current year option exists in BOTH dropdowns
            if (yearSel) {
              const hasCur = Array.from(yearSel.options).some(o=> (o.value||'') === curSY);
              if (!hasCur){ 
                const o=document.createElement('option'); 
                o.value=curSY; 
                o.textContent=curSY; 
                yearSel.insertBefore(o, yearSel.firstChild); 
              }
              // Set to current year
              yearSel.value = curSY;
            }
            
            // Also ensure the combined hidden select has the current year options
            if (combinedSel) {
              const term1 = `${curSY} 1st Term`;
              const term2 = `${curSY} 2nd Term`;
              const hasTerm1 = Array.from(combinedSel.options).some(o=> o.value === term1);
              const hasTerm2 = Array.from(combinedSel.options).some(o=> o.value === term2);
              if (!hasTerm1) {
                const o = document.createElement('option');
                o.value = term1;
                o.textContent = term1;
                combinedSel.insertBefore(o, combinedSel.firstChild);
              }
              if (!hasTerm2) {
                const o = document.createElement('option');
                o.value = term2;
                o.textContent = term2;
                combinedSel.insertBefore(o, combinedSel.firstChild);
              }
            }
            
            if (termOnlySel){ termOnlySel.value = '1st Term'; }
            if (yearSel && termOnlySel && combinedSel){
              combinedSel.value = `${yearSel.value} ${termOnlySel.value}`;
            }
            populateSubjectOptions(currentStudentInfo);
            // Ensure listeners to sync term and prefill values
            if (yearSel && !yearSel.hasAttribute('data-sync')){
              yearSel.addEventListener('change', ()=>{
                combinedSel.value = `${yearSel.value} ${termOnlySel.value}`;
                // Clear grade inputs when term changes
                clearGradeInputs();
                populateSubjectOptions(currentStudentInfo);
              });
              yearSel.setAttribute('data-sync','1');
            }
            if (termOnlySel && !termOnlySel.hasAttribute('data-sync')){
              termOnlySel.addEventListener('change', ()=>{
                const newCombinedValue = `${yearSel.value} ${termOnlySel.value}`;
                combinedSel.value = newCombinedValue;
                console.log('Term changed! New combined value:', newCombinedValue);
                console.log('Combined select value after update:', combinedSel.value);
                console.log('Term only value:', termOnlySel.value);
                // Clear grade inputs when term changes
                clearGradeInputs();
                // Use setTimeout to ensure the DOM has updated before fetching subjects
                setTimeout(() => {
                  console.log('About to populate subjects, combined value is:', combinedSel.value);
                  populateSubjectOptions(currentStudentInfo);
                }, 10);
              });
              termOnlySel.setAttribute('data-sync','1');
            }
            const subjSel = document.getElementById('subjectSelect');
            if (subjSel && !subjSel.hasAttribute('data-prefill-listener')){
              subjSel.addEventListener('change', ()=>{
                // Clear inputs first, then prefill if grades exist
                clearGradeInputs();
                prefillModalInputsFromExisting();
              });
              subjSel.setAttribute('data-prefill-listener','1');
            }
          }catch(e){ console.error('Default to 1st term failed', e); }
        }, 0);
        
        // Reset modal title and button text for adding
        document.querySelector('#addGradeModal h3').textContent = 'Add New Grade';
        document.querySelector('#addGradeModal button[type="submit"]').textContent = 'Save Grade';

        // Ensure fields are ENABLED and VISIBLE in ADD mode
        const subjSel = document.getElementById('subjectSelect');
        const yearSel2 = document.getElementById('modalSchoolYear');
        const termOnlySel2 = document.getElementById('modalTermOnly');
        
        // Restore normal state
        if (subjSel) {
            subjSel.disabled = false;
            subjSel.style.backgroundColor = '';
            subjSel.style.cursor = '';
            subjSel.style.pointerEvents = 'auto';
            subjSel.removeAttribute('readonly');
        }
        if (yearSel2) {
            yearSel2.disabled = false;
            yearSel2.style.backgroundColor = '';
            yearSel2.style.cursor = '';
            yearSel2.style.pointerEvents = 'auto';
        }
        if (termOnlySel2) {
            termOnlySel2.disabled = false;
            termOnlySel2.style.backgroundColor = '';
            termOnlySel2.style.cursor = '';
            termOnlySel2.style.pointerEvents = 'auto';
        }
        if (yearSel2) yearSel2.disabled = false;
        if (termOnlySel2) termOnlySel2.disabled = false;
        
        document.getElementById('addGradeModal').classList.remove('hidden');
    } else {
        alert('Please select a student first.');
    }
}

  // Get latest term from database and populate the field
  function getLatestTermAndPopulate() {
    fetch('api/get_latest_term.php', {
        method: 'GET'
    })
    .then(response => response.json())
    .then(data => {
        const sel = document.getElementById('modalTermSelect');
        if (!sel) return;
        if (data.success && data.latest_term) {
            const latest = String(data.latest_term);
            // Add latest if missing
            if (!Array.from(sel.options).some(o=>o.value===latest)){
                const opt = document.createElement('option'); opt.value = latest; opt.textContent = latest; sel.appendChild(opt);
            }
            // Build list of all terms from database only (don't compute next term)
            let terms = Array.from(sel.options).map(o=>o.value).filter(v=>v);
            
            // Sort: by start year desc, then 2nd before 1st
            terms = Array.from(new Set(terms)).sort((a,b)=>{
                const pa = a.match(/^(\d{4})-(\d{4})\s+(\d)/); const pb = b.match(/^(\d{4})-(\d{4})\s+(\d)/);
                if (!pa || !pb) return 0;
                const ya = parseInt(pa[1],10), yb = parseInt(pb[1],10);
                if (yb !== ya) return yb - ya;
                const ta = parseInt(pa[3],10), tb = parseInt(pb[3],10);
                return tb - ta; // 2 before 1
            });
            // Rebuild options from existing terms only
            sel.innerHTML = '';
            terms.forEach(t=>{ const o=document.createElement('option'); o.value=t; o.textContent=t; sel.appendChild(o); });
            // Select latest by default
            sel.value = latest;
            // Also set split controls if present
            const yMatch = latest.match(/^(\d{4}-\d{4})\s+(\d(?:st|nd|rd|th)\s+Term)$/i);
            const yearSel = document.getElementById('modalSchoolYear');
            const termOnlySel = document.getElementById('modalTermOnly');
            if (yMatch && yearSel && termOnlySel){
              // Determine current academic SY (August boundary)
              const now = new Date();
              const month = now.getMonth() + 1; // 1..12
              const startYear = (month >= 8) ? now.getFullYear() : (now.getFullYear() - 1);
              const curSY = `${startYear}-${startYear+1}`;
              // Ensure current SY option exists
              const hasCur = Array.from(yearSel.options).some(o=> (o.value||'') === curSY);
              if (!hasCur){ 
                const o=document.createElement('option'); 
                o.value=curSY; 
                o.textContent=curSY; 
                yearSel.insertBefore(o, yearSel.firstChild); 
              }
              // ALWAYS select current SY (2025-2026) and default to 1st Term
              yearSel.value = curSY;
              termOnlySel.value = '1st Term';
              // Keep combined hidden in sync and refresh subjects
              document.getElementById('modalTermSelect').value = `${yearSel.value} ${termOnlySel.value}`;
              try { populateSubjectOptions(currentStudentInfo); } catch(_) {}
            }
            // Ensure current calendar year SY exists (auto-generate)
            ensureSchoolYearOptions();
        }
    })
    .catch(error => {
        console.error('Error getting latest term:', error);
        // Fallback: select the first option if available
        const sel = document.getElementById('modalTermSelect');
        if (sel) {
            if (sel.options.length > 0) sel.value = sel.options[0].value;
        }
    });
}

// Refresh term selector with latest terms from database
function refreshTermSelector() {
    return fetch('api/get_student_grades.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `student_id=${encodeURIComponent(currentStudentId)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.terms) {
            const terms = data.terms;
            const selectedTerm = data.selected_term;
            populateTermSelector(terms, selectedTerm);
        }
    })
    .catch(error => {
        console.error('Error refreshing terms:', error);
    });
}

// Hide add grade modal
function hideAddGradeModal() {
    document.getElementById('addGradeModal').classList.add('hidden');
    document.getElementById('addGradeForm').reset();
}

// Handle form submission
document.getElementById('addGradeForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    // Debug: Log form data
    console.log('=== SUBMITTING GRADE ===');
    console.log('Grading system:', formData.get('grading_system'));
    console.log('Student ID:', formData.get('student_id'));
    console.log('Subject:', formData.get('subject'));
    console.log('School Year & Term from form:', formData.get('school_year_term'));
    
    // Also log the actual dropdown values
    const yearSel = document.getElementById('modalSchoolYear');
    const termSel = document.getElementById('modalTermOnly');
    const combinedSel = document.getElementById('modalTermSelect');
    console.log('Year dropdown value:', yearSel ? yearSel.value : 'N/A');
    console.log('Term dropdown value:', termSel ? termSel.value : 'N/A');
    console.log('Combined hidden field value:', combinedSel ? combinedSel.value : 'N/A');
    
    // Submit to backend
    fetch('ManageGrades.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            showSuccessMessage(data.message);
            
            hideAddGradeModal();
            
            // Get the term that was just used for the grade
            const savedTerm = formData.get('school_year_term');
            console.log('Grade saved with term:', savedTerm);
            
            // Reload grades for current student and refresh terms
            if (currentStudentId) {
                // First refresh the term selector to include any new terms
                refreshTermSelector().then(() => {
                    // After refresh, update the term selector to show the term we just added
                    const termSelector = document.getElementById('termSelector');
                    if (termSelector && savedTerm) {
                        console.log('Available terms in selector:', Array.from(termSelector.options).map(o => o.value));
                        // Check if this term exists in the dropdown
                        const termExists = Array.from(termSelector.options).some(opt => opt.value === savedTerm);
                        console.log('Does saved term exist in dropdown?', termExists);
                        if (termExists) {
                            termSelector.value = savedTerm;
                            console.log('Switched term selector to:', savedTerm);
                            // Trigger change event to reload grades
                            termSelector.dispatchEvent(new Event('change'));
                        } else {
                            console.log('Term not found in dropdown, reloading with saved term anyway');
                            loadStudentGrades(currentStudentId, savedTerm);
                        }
                    } else {
                        // No term selector or saved term, just reload
                        loadStudentGrades(currentStudentId);
                    }
                }).catch(err => {
                    console.error('Error refreshing term selector:', err);
                    // Fallback: just reload grades
                    loadStudentGrades(currentStudentId);
                });
            }
        } else {
            alert('Error: ' + (data.message || 'Failed to save grade'));
        }
    })
    .catch(error => {
        console.error('Error saving grade:', error);
        alert('Failed to save grade. Please try again.');
    });
});

// Show success message function
function showSuccessMessage(message) {
    const successDiv = document.getElementById('successMessage');
    const successText = document.getElementById('successText');
    
    successText.textContent = message;
    successDiv.classList.remove('hidden');
    
    // Auto-hide after 3 seconds
    setTimeout(() => {
        successDiv.classList.add('hidden');
    }, 3000);
}

// Populate term selector with dynamic data
function populateTermSelector(terms, selectedTerm) {
    const termSelector = document.getElementById('termSelector');
    termSelector.innerHTML = '';
    
    if (terms.length === 0) {
        termSelector.innerHTML = '<option value="">No terms available</option>';
        return;
    }
    
    terms.forEach(term => {
        const option = document.createElement('option');
        option.value = term;
        option.textContent = term;
        if (term === selectedTerm) {
            option.selected = true;
        }
        termSelector.appendChild(option);
    });
}

// Load grades by selected term
function loadGradesByTerm() {
    if (currentStudentId) {
        const selectedTerm = document.getElementById('termSelector').value;
        loadStudentGrades(currentStudentId, selectedTerm);
    }
}

// Store all grades for filtering
let allGrades = [];

// Handle back navigation based on current view
function handleBackNavigation() {
    const studentGradesView = document.getElementById('student-grades-view');
    const searchSection = document.getElementById('search-section');
    
    // If we're viewing student grades, go back to search
    if (!studentGradesView.classList.contains('hidden')) {
        showSearchSection();
    } else {
        // Otherwise use browser back
        window.history.back();
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing search functionality...');
    
    // Test if elements exist
    const searchInput = document.getElementById('studentSearchInput');
    const searchResults = document.getElementById('student-results');
    
    if (searchInput && searchResults) {
        console.log('Search elements found successfully');
        
        // Search on Enter key
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchStudents();
            }
        });
        
        // Auto-search as user types (with debounce)
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const query = this.value.trim();
                if (query.length >= 2) {
                    searchStudents();
                } else if (query.length === 0) {
                    searchResults.innerHTML = '<p class="text-gray-400 text-center py-4">Enter a student name or ID to search...</p>';
                }
            }, 300);
        });
    } else {
        console.error('Search elements not found on page load');
    }
    // Ensure Add New Grade button opens the modal even if inline onclick is interfered with
    const addBtn = document.getElementById('addGradeBtn');
    if (addBtn) {
        addBtn.addEventListener('click', function(e){
            e.preventDefault();
            showAddGradeModal();
        });
    }

    // Wire split year/term controls to combined field and subject options
    const yearSel = document.getElementById('modalSchoolYear');
    const termOnlySel = document.getElementById('modalTermOnly');
    const combinedSel = document.getElementById('modalTermSelect');
    function syncCombined(){
      if (combinedSel && yearSel && termOnlySel){
        combinedSel.value = `${yearSel.value} ${termOnlySel.value}`;
        // Refresh offered subjects for the selected term
        populateSubjectOptions(currentStudentInfo);
      }
    }
    if (yearSel) yearSel.addEventListener('change', syncCombined);
    if (termOnlySel) termOnlySel.addEventListener('change', syncCombined);

    // Ensure current school year option exists even if DB has not created it yet
    function ensureSchoolYearOptions(){
      const ys = document.getElementById('modalSchoolYear');
      if (!ys) return;
      
      // Calculate current academic year (starts in August)
      const now = new Date();
      const month = now.getMonth() + 1; // 1-12
      const year = now.getFullYear();
      
      // If we're in August or later, use current year as start year
      // Otherwise, use previous year as start year
      const startYear = (month >= 8) ? year : (year - 1);
      const label = `${startYear}-${startYear+1}`;
      
      // Only add if it doesn't exist
      const exists = Array.from(ys.options).some(o=>o.value === label);
      if (!exists){
        const opt = document.createElement('option');
        opt.value = label; 
        opt.textContent = label;
        // Add to the end of the list, not the beginning
        ys.appendChild(opt);
      }
    }
    
    // Only call this once when page loads
    if (!window.schoolYearOptionsEnsured) {
      ensureSchoolYearOptions();
      window.schoolYearOptionsEnsured = true;
    }
});
</script>

</body>
</html>
