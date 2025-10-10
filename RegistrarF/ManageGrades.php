<?php
session_start();
include("../StudentLogin/db_conn.php");

// Require registrar login
if (!isset($_SESSION['registrar_id'])) {
    header("Location: ../StudentLogin/login.php");
    exit;
}

// Handle form submission for adding/updating grades
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_number = $_POST['student_id'];
    $subject = $_POST['subject'];
    $school_year_term = !empty($_POST['school_year_term']) ? $_POST['school_year_term'] : '2024-2025 2nd Term'; // Use custom term or default
    $prelim = $_POST['prelim'] ?: null;
    $midterm = $_POST['midterm'] ?: null;
    $pre_finals = $_POST['prefinals'] ?: null;
    $finals = $_POST['finals'] ?: null;
    $teacher_name = $_POST['teacher'];
    
    // Check if grade record exists
    $check_stmt = $conn->prepare("SELECT id FROM grades_record WHERE id_number = ? AND subject = ? AND school_year_term = ?");
    $check_stmt->bind_param("sss", $id_number, $subject, $school_year_term);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing record
        $update_stmt = $conn->prepare("UPDATE grades_record SET prelim = ?, midterm = ?, pre_finals = ?, finals = ?, teacher_name = ? WHERE id_number = ? AND subject = ? AND school_year_term = ?");
        $update_stmt->bind_param("ddddssss", $prelim, $midterm, $pre_finals, $finals, $teacher_name, $id_number, $subject, $school_year_term);
        $update_stmt->execute();
        $success_msg = "Grade updated successfully!";
    } else {
        // Insert new record
        $insert_stmt = $conn->prepare("INSERT INTO grades_record (id_number, subject, school_year_term, prelim, midterm, pre_finals, finals, teacher_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $insert_stmt->bind_param("sssdddds", $id_number, $subject, $school_year_term, $prelim, $midterm, $pre_finals, $finals, $teacher_name);
        $insert_stmt->execute();
        $success_msg = "Grade added successfully!";
    }
    
    // Return JSON response for AJAX
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => $success_msg]);
    exit;
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
        <div class="bg-white rounded-xl card-shadow p-4">
            <h2 class="text-lg font-bold text-gray-800 mb-3">Search Students</h2>
            <div class="flex gap-3 mb-3">
                <div class="flex-1">
                    <input type="text" id="studentSearchInput" placeholder="Search by student name or ID..." 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm">
                </div>
                <button onclick="searchStudents()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium text-sm">
                    Search
                </button>
            </div>
            <div id="student-results" class="space-y-2 max-h-60 overflow-y-auto">
                <p class="text-gray-400 text-center py-3 text-sm">Enter a student name or ID to search...</p>
            </div>
        </div>
    </div>

    <!-- Individual Student Grades View (Hidden by default) -->
    <div id="student-grades-view" class="hidden">

        <!-- Student Header -->
        <div class="bg-white rounded-2xl card-shadow p-6 mb-6">
            <!-- Success Message -->
            <div id="successMessage" class="hidden mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
                <span id="successText"></span>
            </div>
            
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800" id="student-name-display">Student Grades</h2>
                    <p class="text-gray-600" id="student-id-display">Academic Performance Overview</p>
                </div>
                <button id="addGradeBtn" type="button" onclick="showAddGradeModal()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg relative z-10">
                    Add New Grade
                </button>
            </div>
            
            <!-- Term Selector -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">School Year & Term:</label>
                <select id="termSelector" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500" onchange="loadGradesByTerm()">
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
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                    <select id="subjectSelect" name="subject" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                        <option value="">-- Select Subject --</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Teacher</label>
                    <select id="teacherSelect" name="teacher" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
                        <option value="">-- Select Teacher --</option>
                        <?php if ($teachers_result && $teachers_result->num_rows > 0): ?>
                            <?php while ($t = $teachers_result->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($t['full_name']) ?>" data-name="<?= htmlspecialchars($t['full_name']) ?>">
                                    <?= htmlspecialchars($t['full_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <option value="" disabled>No teachers found</option>
                        <?php endif; ?>
                    </select>
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
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prelim</label>
                        <input type="number" name="prelim" min="0" max="100" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 no-spinner">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Midterm</label>
                        <input type="number" name="midterm" min="0" max="100" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 no-spinner">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pre-Finals</label>
                        <input type="number" name="prefinals" min="0" max="100" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 no-spinner">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Finals</label>
                        <input type="number" name="finals" min="0" max="100" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 no-spinner">
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
  return /2nd/i.test(t) ? '2nd' : '1st';
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
  return '';
}

async function fetchOfferedSubjects(gradeLevelText, program, termValue){
  // Pass the grade level text as-is so it supports values like 'Kinder 1', 'Kinder 2', 'Grade 12', 'Year 4'
  const gradeStr = String(gradeLevelText||'');
  const strand = mapStrandFromProgram(program);
  const semester = getSemesterFromTermString(termValue);
  const sy = termValue || '';
  const params = new URLSearchParams({action:'list', grade_level: gradeStr, strand, semester, sy});
  const res = await fetch('api/subject_offerings.php?'+params.toString());
  const d = await res.json();
  if (d && d.success && Array.isArray(d.items)) return d.items; // [{id,name,code}]
  return [];
}

async function populateSubjectOptions(info, preselectSubject=null){
  const sel = document.getElementById('subjectSelect');
  if (!sel) return;
  const termSel = document.getElementById('modalTermSelect');
  const termValue = termSel ? termSel.value : '';

  sel.innerHTML = '<option value="">-- Select Subject --</option>';
  try{
    const items = await fetchOfferedSubjects(info?.gradeLevelText||'', info?.program||'', termValue);
    items.forEach(it=>{
      const label = it.code ? `${it.name} (${it.code})` : it.name;
      const o = document.createElement('option'); o.value = it.name; o.textContent = label; sel.appendChild(o);
    });
    if (preselectSubject){
      if (!Array.from(sel.options).some(o=>o.value===preselectSubject)){
        const o=document.createElement('option'); o.value=preselectSubject; o.textContent=preselectSubject; sel.appendChild(o);
      }
      sel.value = preselectSubject;
    }
  }catch(e){ console.error('Failed to load offered subjects', e); }
}
let currentStudentId = null;

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
        resultsDiv.innerHTML = '<p class="text-gray-500 text-center py-4">Please enter at least 2 characters to search</p>';
        return;
    }

    // Show loading message
    resultsDiv.innerHTML = '<p class="text-gray-500 text-center py-4">Searching...</p>';

    // Make AJAX call to search students in database
    fetch('SearchStudent.php?query=' + encodeURIComponent(query))
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                resultsDiv.innerHTML = '<p class="text-red-500 text-center py-4">Error: ' + data.error + '</p>';
                return;
            }

            const students = data.students || [];
            let resultsHTML = '';
            
            if (students.length > 0) {
                students.forEach(student => {
                    const displayName = student.full_name || `${student.first_name} ${student.last_name}`;
                    const gradeLevel = student.year_section || student.grade_level || 'N/A';
                    const program = student.program || '';
                    // Escape values for attribute context
                    const safeName = String(displayName).replace(/\\/g, "\\\\").replace(/'/g, "\\'");
                    const safeGL = String(gradeLevel).replace(/\\/g, "\\\\").replace(/'/g, "\\'");
                    const safeProg = String(program).replace(/\\/g, "\\\\").replace(/'/g, "\\'");
                    
                    resultsHTML += `
                        <div class="bg-gray-50 hover:bg-gray-100 p-4 rounded-lg cursor-pointer transition-colors" 
                             onclick="viewStudentGrades('${student.id_number}', '${safeName}', '${safeGL}', '${safeProg}')">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h3 class="font-medium text-gray-800">${displayName}</h3>
                                    <p class="text-sm text-gray-600">ID: ${student.id_number} • ${gradeLevel}</p>
                                    ${program ? `<p class="text-xs text-gray-500">${program}</p>` : ''}
                                </div>
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </div>
                        </div>
                    `;
                });
            } else {
                resultsHTML = '<p class="text-gray-500 text-center py-4">No students found</p>';
            }

            resultsDiv.innerHTML = resultsHTML;
        })
        .catch(error => {
            console.error('Search error:', error);
            resultsDiv.innerHTML = '<p class="text-red-500 text-center py-4">Search failed. Please try again.</p>';
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
    fetch('get_student_grades.php', {
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
                    // Calculate average if all grades are present
                    let average = null;
                    let status = 'Incomplete';
                    
                    if (grade.prelim && grade.midterm && grade.pre_finals && grade.finals) {
                        average = (parseFloat(grade.prelim) + parseFloat(grade.midterm) + parseFloat(grade.pre_finals) + parseFloat(grade.finals)) / 4;
                        status = average >= 90 ? 'Excellent' : average >= 75 ? 'Passing' : 'Failing';
                    }
                    
                    const statusColor = average >= 90 ? 'text-green-600' : average >= 75 ? 'text-emerald-600' : 'text-red-600';
                    const statusBg = average >= 90 ? 'bg-green-100' : average >= 75 ? 'bg-emerald-100' : 'bg-red-100';
                    const averageDisplay = average ? average.toFixed(1) : 'N/A';
                    
                    gradesHTML += `
                        <div class="bg-white rounded-2xl card-shadow p-4 hover:shadow-xl transition-shadow">
                            <div class="mb-3 flex justify-between items-start">
                                <div>
                                    <h3 class="text-base font-bold text-gray-800">${grade.subject || 'Unknown Subject'}</h3>
                                    <p class="text-xs text-gray-600">Teacher: ${grade.teacher_name || 'Not assigned'}</p>
                                </div>
                                <div class="flex gap-2">
                                    <button onclick="editGrade(${index})" class="text-blue-500 hover:text-blue-700 text-sm font-medium">
                                        Edit
                                    </button>
                                    <button onclick="deleteGrade(${grade.id})" class="text-red-500 hover:text-red-700 text-sm font-medium">
                                        Delete
                                    </button>
                                </div>
                            </div>
                            <div class="grid grid-cols-4 gap-2 mb-3">
                                <div class="text-center">
                                    <p class="text-xs text-gray-500 uppercase font-medium mb-1">PRELIM</p>
                                    <p class="text-lg font-bold text-gray-900">${grade.prelim || '-'}</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs text-gray-500 uppercase font-medium mb-1">MIDTERM</p>
                                    <p class="text-lg font-bold text-gray-900">${grade.midterm || '-'}</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs text-gray-500 uppercase font-medium mb-1">PRE-FINALS</p>
                                    <p class="text-lg font-bold text-gray-900">${grade.pre_finals || '-'}</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs text-gray-500 uppercase font-medium mb-1">FINALS</p>
                                    <p class="text-lg font-bold text-gray-900">${grade.finals || '-'}</p>
                                </div>
                            </div>
                            
                            <div class="mt-3 pt-3 border-t flex justify-between items-center">
                                <div>
                                    <p class="text-xs text-gray-500 mb-1">Current Average:</p>
                                    <p class="text-lg font-bold ${statusColor}">${averageDisplay}%</p>
                                </div>
                                <div class="text-right">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium ${statusBg} ${statusColor}">${status}</span>
                                </div>
                            </div>
                        </div>
                    `;
                });
            } else {
                gradesHTML = '<div class="text-center py-8"><p class="text-gray-500">No grades found for this student.</p></div>';
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
    // Set teacher dropdown selection by name
    (function(){
        const sel = document.getElementById('teacherSelect');
        if (!sel) return;
        const targetName = (grade.teacher_name || grade.teacher || '').trim();
        let matched = false;
        Array.from(sel.options).forEach(opt => {
            if ((opt.dataset && (opt.dataset.name||'').trim()) === targetName) {
                sel.value = opt.value;
                matched = true;
            }
        });
        if (!matched) sel.value = '';
    })();
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
    document.querySelector('input[name="prelim"]').value = grade.prelim || '';
    document.querySelector('input[name="midterm"]').value = grade.midterm || '';
    document.querySelector('input[name="prefinals"]').value = grade.pre_finals || '';
    document.querySelector('input[name="finals"]').value = grade.finals || '';
    document.getElementById('modal-student-id').value = currentStudentId;
    
    // Change modal title and button text for editing
    document.querySelector('#addGradeModal h3').textContent = 'Edit Grade';
    document.querySelector('#addGradeModal button[type="submit"]').textContent = 'Update Grade';
    
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
        
        // Get latest term and populate the field
        getLatestTermAndPopulate();
        
        // Reset modal title and button text for adding
        document.querySelector('#addGradeModal h3').textContent = 'Add New Grade';
        document.querySelector('#addGradeModal button[type="submit"]').textContent = 'Save Grade';
        
        document.getElementById('addGradeModal').classList.remove('hidden');
    } else {
        alert('Please select a student first.');
    }
}

  // Get latest term from database and populate the field
  function getLatestTermAndPopulate() {
    fetch('get_latest_term.php', {
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
            // Build list of all terms
            let terms = Array.from(sel.options).map(o=>o.value).filter(v=>v);
            // Compute next (advance) term
            const m = latest.match(/^(\d{4})-(\d{4})\s+(\d)(st|nd|rd|th)\s+Term$/i);
            if (m){
                let y1 = parseInt(m[1],10), y2 = parseInt(m[2],10);
                let nth = parseInt(m[3],10);
                let next;
                if (nth === 1){ next = `${y1}-${y2} 2nd Term`; }
                else { next = `${y1+1}-${y2+1} 1st Term`; }
                if (!terms.includes(next)) terms.push(next);
            }
            // Sort: by start year desc, then 2nd before 1st
            terms = Array.from(new Set(terms)).sort((a,b)=>{
                const pa = a.match(/^(\d{4})-(\d{4})\s+(\d)/); const pb = b.match(/^(\d{4})-(\d{4})\s+(\d)/);
                if (!pa || !pb) return 0;
                const ya = parseInt(pa[1],10), yb = parseInt(pb[1],10);
                if (yb !== ya) return yb - ya;
                const ta = parseInt(pa[3],10), tb = parseInt(pb[3],10);
                return tb - ta; // 2 before 1
            });
            // Rebuild options (advance term will naturally be first when latest is 1st Term; if latest is 2nd Term, next year's 1st comes first)
            sel.innerHTML = '';
            terms.forEach(t=>{ const o=document.createElement('option'); o.value=t; o.textContent=t; sel.appendChild(o); });
            // Select latest by default
            sel.value = latest;
            // Also set split controls if present
            const yMatch = latest.match(/^(\d{4}-\d{4})\s+(\d(?:st|nd|rd|th)\s+Term)$/i);
            const yearSel = document.getElementById('modalSchoolYear');
            const termOnlySel = document.getElementById('modalTermOnly');
            if (yMatch && yearSel && termOnlySel){
              yearSel.value = yMatch[1];
              termOnlySel.value = (yMatch[2].startsWith('2') ? '2nd Term' : '1st Term');
              // Keep combined hidden in sync
              document.getElementById('modalTermSelect').value = `${yearSel.value} ${termOnlySel.value}`;
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
    fetch('get_student_grades.php', {
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
            
            // Reload grades for current student and refresh terms
            if (currentStudentId) {
                loadStudentGrades(currentStudentId);
                // Refresh term selector to include any new terms
                refreshTermSelector();
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
      const now = new Date();
      const current = now.getFullYear();
      const label = `${current}-${current+1}`; // e.g., 2026-2027
      const exists = Array.from(ys.options).some(o=>o.value === label);
      if (!exists){
        const opt = document.createElement('option');
        opt.value = label; opt.textContent = label;
        ys.insertBefore(opt, ys.firstChild);
      }
    }
    ensureSchoolYearOptions();
});
</script>

</body>
</html>
