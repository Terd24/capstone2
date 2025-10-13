<?php
session_start();
include("../StudentLogin/db_conn.php");

date_default_timezone_set('Asia/Manila');
if (!isset($_SESSION['registrar_id'])) {
    header("Location: ../StudentLogin/login.html");
    exit;
}

// Cache prevention
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Ensure tables exist (non-destructive)
$conn->query("CREATE TABLE IF NOT EXISTS subjects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(50) UNIQUE,
  name VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$conn->query("CREATE TABLE IF NOT EXISTS subject_offerings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  subject_id INT NOT NULL,
  grade_level VARCHAR(20) NOT NULL,
  strand VARCHAR(50) NULL,
  semester ENUM('1st','2nd') NOT NULL,
  school_year_term VARCHAR(50) NULL,
  active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_subject_offerings_subject FOREIGN KEY(subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
  UNIQUE KEY uniq_offer (subject_id, grade_level, strand, semester, school_year_term)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Manage Subjects - Registrar</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="icon" type="image/png" href="../images/Logo.png">
  <style>
    .school-gradient { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #1e40af 100%); }
    .card-shadow { box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
  </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
<header class="bg-[#0B2C62] text-white shadow-lg">
    <div class="container mx-auto px-6 py-4">
        <div class="flex justify-between items-center">
        <div class="flex items-center space-x-4">
        <button onclick="window.location.href='RegistrarDashboard.php'" class="bg-white bg-opacity-20 hover:bg-opacity-30 p-2 rounded-lg transition">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
          </svg>
        </button>
        <div>
                <h1 class="text-xl font-bold">Subject Management</h1>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <img src="../images/LogoCCI.png" alt="Cornerstone College Inc." class="h-12 w-12 rounded-full bg-white p-1">
                <div class="text-right">
                    <h1 class="text-xl font-bold">Cornerstone College Inc.</h1>
                    <p class="text-blue-200 text-sm">Subject Management System</p>
                </div>
            </div>
        </div>
    </div>
</header>

  <!-- Subjects by Level (Option A: Tabs + Single Manage CTA) -->
  <section id="levelGridSection" class="max-w-[1200px] mx-auto px-6 py-6">
    <div class="bg-white rounded-2xl card-shadow p-5 border border-gray-200">
      <!-- Tabs -->
      <div class="flex flex-wrap gap-2 mb-4" id="levelTabs">
        <button type="button" data-group="basic" class="tab-btn px-3 py-1.5 rounded-lg border bg-[#0B2C62] text-white">Basic Ed</button>
        <button type="button" data-group="shs" class="tab-btn px-3 py-1.5 rounded-lg border text-gray-700 bg-gray-50">Senior High</button>
        <button type="button" data-group="college" class="tab-btn px-3 py-1.5 rounded-lg border text-gray-700 bg-gray-50">College</button>
      </div>

      <!-- Section header + subtext -->
      <div class="mb-3">
        <h3 class="text-lg font-semibold text-gray-800">Select scope to manage subjects</h3>
        <p class="text-sm text-gray-500">Choose a level, then (if applicable) select a strand/course and term.</p>
      </div>

      <!-- Selector Row -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
        <div class="md:col-span-2">
          <label class="text-sm text-gray-600">Level</label>
          <select id="selLevel" class="w-full border rounded-lg px-3 py-2"></select>
        </div>
        <div id="selTrackWrap">
          <label class="text-sm text-gray-600">Strand / Course</label>
          <select id="selTrack" class="w-full border rounded-lg px-3 py-2"></select>
        </div>
        <div id="selSemWrap">
          <label class="text-sm text-gray-600">Semester</label>
          <select id="selSem" class="w-full border rounded-lg px-3 py-2">
            <option value="1st">1st Term</option>
            <option value="2nd">2nd Term</option>
          </select>
        </div>
      </div>

      

      <!-- Live Subjects Preview -->
      <div id="previewListBlock" class="mt-6">
        <div class="flex items-center justify-between mb-2">
          <h4 class="font-medium text-gray-800">Subjects for this selection</h4>
          <div class="flex items-center gap-2">
            <span class="text-xs text-gray-500" id="previewMeta"></span>
            <button id="previewAddToggle" class="text-sm px-3 py-1.5 rounded-lg bg-[#0B2C62] text-white hover:bg-blue-900">Add Subject</button>
          </div>
        </div>
        <!-- Inline Add Form -->
        <div id="previewAddForm" class="hidden mb-3">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div class="md:col-span-2">
              <input id="previewAddName" type="text" class="w-full border rounded-lg px-3 py-2" placeholder="e.g., Applied Economics" />
            </div>
            <div>
              <input id="previewAddCode" type="text" class="w-full border rounded-lg px-3 py-2" placeholder="e.g., APEC12" />
            </div>
          </div>
          <div class="mt-2 flex gap-2">
            <button id="previewAddBtn" class="px-4 py-2 rounded-lg bg-[#0B2C62] text-white hover:bg-blue-900">Add</button>
            <button id="previewAddCancel" class="px-4 py-2 rounded-lg bg-gray-200 hover:bg-gray-300">Cancel</button>
          </div>
        </div>
        <div id="previewList" class="border rounded-xl bg-white divide-y">
          <div class="p-3 text-gray-500 text-sm">Select Level (and Strand/Course & Term if applicable) to view subjects.</div>
        </div>
      </div>
    </div>
  </section>

  <!-- Hide legacy multi-panel for a cleaner UI in Option A -->
  <main id="manageMain" class="hidden max-w-[1800px] mx-auto px-2 py-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Subjects Master List -->
    <section id="masterListSection" class="lg:col-span-1 bg-white rounded-2xl card-shadow p-5">
      <div class="flex items-center justify-between mb-3">
        <h2 class="font-semibold text-gray-800">Subjects</h2>
        <button id="addSubjectBtn" class="bg-[#0B2C62] hover:bg-blue-900 text-white px-3 py-2 rounded-lg text-sm">Add Subject</button>
      </div>
      <div class="mb-3">
        <input id="subjectSearch" type="text" placeholder="Search subject..." class="w-full border rounded-lg px-3 py-2" />
      </div>
      <div id="subjectList" class="max-h-[520px] overflow-y-auto divide-y"></div>
    </section>

    <!-- Offerings Manager -->
    <section class="lg:col-span-2 bg-white rounded-2xl card-shadow p-5">
      <h2 class="font-semibold text-gray-800 mb-3">Offerings by Grade/Strand/Semester</h2>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
        <div>
          <label class="text-sm text-gray-600">Grade Level</label>
          <select id="gradeLevel" class="w-full border rounded-lg px-3 py-2">
            <option value="">-- Select Grade Level --</option>
            <option>Kinder 1</option>
            <option>Kinder 2</option>
            <option>Grade 1</option>
            <option>Grade 2</option>
            <option>Grade 3</option>
            <option>Grade 4</option>
            <option>Grade 5</option>
            <option>Grade 6</option>
            <option>Grade 7</option>
            <option>Grade 8</option>
            <option>Grade 9</option>
            <option>Grade 10</option>
            <option>Grade 11</option>
            <option>Grade 12</option>
            <option>1st Year</option>
            <option>2nd Year</option>
            <option>3rd Year</option>
            <option>4th Year</option>
          </select>
        </div>
        <div>
          <label class="text-sm text-gray-600">Strand / Track</label>
          <select id="strand" class="w-full border rounded-lg px-3 py-2">
            <option value="">-- Any / Not Applicable --</option>
            <option>ABM</option>
            <option>STEM</option>
            <option>HUMSS</option>
            <option>GAS</option>
            <option>TVL-ICT</option>
            <option>TVL-HE</option>
            <option>SPORTS</option>
            <option>BPED</option>
            <option>BECED</option>
          </select>
        </div>
        <div>
          <label class="text-sm text-gray-600">Semester</label>
          <select id="semester" class="w-full border rounded-lg px-3 py-2">
            <option value="1st">1st Term</option>
            <option value="2nd">2nd Term</option>
          </select>
        </div>
        <div>
          <label class="text-sm text-gray-600">School Year / Term (optional)</label>
          <input id="syterm" type="text" placeholder="e.g., 2025-2026 1st" class="w-full border rounded-lg px-3 py-2" />
        </div>
      </div>

      <div class="flex items-center justify-between mb-2">
        <h3 class="font-medium text-gray-700">Assigned Subjects</h3>
        <div class="text-sm text-gray-500">Tip: filter first, then add/remove subjects</div>
      </div>
      <div id="filterChips" class="flex flex-wrap gap-2 mb-2"></div>
      <div id="assignedSubjects" class="border rounded-xl p-3 min-h-[120px]">
        <div class="text-gray-500 text-sm">Select filters to view assigned subjects...</div>
      </div>

      <div class="mt-4 flex gap-3 flex-wrap">
        <button id="assignSubjectBtn" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg disabled:opacity-50" disabled>Assign Subject to Selection</button>
        <button id="addAndAssignBtn" class="bg-[#0B2C62] hover:bg-blue-900 text-white px-4 py-2 rounded-lg disabled:opacity-50" disabled>Add & Assign New Subject</button>
      </div>

      <!-- Simple mode inline add -->
      <div id="simpleInlineAdd" class="hidden mt-3">
        <div class="flex gap-2 items-end">
          <div class="flex-1">
            <label class="text-sm text-gray-600">New Subject Name</label>
            <input id="simpleSubjectName" type="text" class="w-full border rounded-lg px-3 py-2" placeholder="e.g., Applied Economics" />
          </div>
          <div>
            <label class="text-sm text-gray-600">Code (optional)</label>
            <input id="simpleSubjectCode" type="text" class="w-40 border rounded-lg px-3 py-2" placeholder="e.g., APEC12" />
          </div>
          <button id="simpleAddBtn" class="bg-[#0B2C62] hover:bg-blue-900 text-white px-4 py-2 rounded-lg">Add</button>
        </div>
      </div>

      <div class="mt-6">
        <h3 class="font-medium text-gray-700 mb-2">Quick Select</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <p class="text-sm text-gray-600 mb-1">Grade Levels</p>
            <div id="quickGrades" class="flex flex-wrap gap-2"></div>
          </div>
          <div>
            <p class="text-sm text-gray-600 mb-1">Strands / Tracks</p>
            <div id="quickStrands" class="flex flex-wrap gap-2"></div>
          </div>
          <div>
            <p class="text-sm text-gray-600 mb-1">College Courses</p>
            <div id="quickCourses" class="flex flex-wrap gap-2"></div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- Subject Manager Modal -->
  <div id="subjectManagerModal" class="fixed inset-0 hidden z-50">
    <div class="absolute inset-0 bg-black/40"></div>
    <div class="absolute inset-0 flex items-start justify-center pt-16 px-4">
      <div class="w-full max-w-3xl bg-white rounded-2xl shadow-2xl overflow-hidden">
        <div class="bg-[#0B2C62] text-white px-5 py-3 flex items-center justify-between">
          <div class="font-semibold" id="modalTitle">Manage Subjects</div>
          <button id="closeSubjectManagerBtn" class="hover:bg-white/10 rounded p-1" aria-label="Close">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
        <div class="p-5 space-y-5">
          <!-- In-modal Year Level + Strand/Course + Semester controls -->
          <div id="smTopControls" class="grid grid-cols-1 md:grid-cols-3 gap-3 hidden">
            <div id="smTopYearWrap">
              <label class="text-sm text-gray-600">Year Level</label>
              <select id="smTopYear" class="w-full border rounded-lg px-3 py-2"></select>
            </div>
            <div id="smTopStrandWrap">
              <label class="text-sm text-gray-600">Strand / Course</label>
              <select id="smTopStrand" class="w-full border rounded-lg px-3 py-2"></select>
            </div>
            <div id="smTopSemWrap">
              <label class="text-sm text-gray-600">Semester</label>
              <select id="smTopSem" class="w-full border rounded-lg px-3 py-2">
                <option value="1st">1st Term</option>
                <option value="2nd">2nd Term</option>
              </select>
            </div>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div class="md:col-span-2">
              <label class="text-sm text-gray-600">New Subject Name</label>
              <input id="smName" type="text" required class="w-full border rounded-lg px-3 py-2" placeholder="e.g., Applied Economics"/>
            </div>
            <div>
              <label class="text-sm text-gray-600">Code</label>
              <input id="smCode" type="text" required class="w-full border rounded-lg px-3 py-2" placeholder="e.g., APEC12"/>
            </div>
          </div>
          <div class="flex gap-3">
            <button id="smAddBtn" class="bg-[#0B2C62] hover:bg-blue-900 text-white px-4 py-2 rounded-lg">Add Subject</button>
          </div>

          <!-- Assigned list (filtered by top Strand/Sem when advanced) -->
          <div id="smAssignListBlock" class="hidden">
            <div class="text-sm text-gray-600 mb-2">Assigned for this selection</div>
            <div id="smAssignList" class="rounded-xl border divide-y bg-white"></div>
          </div>

          <div class="flex items-center justify-between hidden">
            <h3 class="font-semibold text-gray-800">All Subjects</h3>
            <span class="hidden"></span>
          </div>
          <div id="smList" class="hidden max-h-[420px] overflow-y-auto divide-y rounded-xl border bg-white"></div>

          <div class="text-right">
            <button id="smCloseBtn" class="px-4 py-2 rounded-lg bg-gray-200 hover:bg-gray-300">Close</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Add/Edit Subject Modal -->
  <div id="subjectModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md">
      <div class="flex items-center justify-between mb-3">
        <h3 id="subjectModalTitle" class="font-semibold">Add Subject</h3>
        <button onclick="closeSubjectModal()" class="text-gray-500">✕</button>
      </div>
      <div class="space-y-3">
        <div>
          <label class="text-sm text-gray-600">Subject Code</label>
          <input id="subCode" type="text" required class="w-full border rounded-lg px-3 py-2" placeholder="e.g., ENG11" />
        </div>
        <div>
          <label class="text-sm text-gray-600">Subject Name</label>
          <input id="subName" type="text" required class="w-full border rounded-lg px-3 py-2" placeholder="e.g., Purposive Communication" />
        </div>
        <div id="subError" class="hidden text-sm text-red-600"></div>
        <div class="flex gap-2 justify-end">
          <button class="px-3 py-2 rounded-lg bg-gray-200" onclick="closeSubjectModal()">Cancel</button>
          <button id="saveSubjectBtn" class="px-3 py-2 rounded-lg bg-[#0B2C62] text-white">Save</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Assign Modal -->
  <div id="assignModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-6 w-full max-w-lg">
      <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold">Assign Subject</h3>
        <button onclick="closeAssignModal()" class="text-gray-500">✕</button>
      </div>
      <div>
        <label class="text-sm text-gray-600">Choose subject to assign</label>
        <select id="assignSubjectSelect" class="w-full border rounded-lg px-3 py-2"></select>
      </div>
      <div class="flex gap-2 justify-end mt-4">
        <button class="px-3 py-2 rounded-lg bg-gray-200" onclick="closeAssignModal()">Cancel</button>
        <button id="confirmAssignBtn" class="px-3 py-2 rounded-lg bg-emerald-600 text-white">Assign</button>
      </div>
    </div>
  </div>

  <div id="toast" class="fixed top-4 right-4 hidden bg-gray-900 text-white px-4 py-2 rounded-lg"></div>

  <!-- Reusable Confirmation Modal -->
  <div id="confirmModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[60]">
    <div class="bg-white rounded-2xl p-6 w-full max-w-sm mx-4">
      <div class="text-center">
        <div id="confirmIconWrap" class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 mb-4">
          <svg id="confirmIconSvg" class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z" />
          </svg>
        </div>
        <h3 id="confirmTitle" class="text-lg font-bold text-gray-800 mb-1">Confirm Action</h3>
        <p id="confirmMessage" class="text-sm text-gray-600 mb-5">Are you sure?</p>
        <div class="flex gap-3">
          <button id="confirmCancelBtn" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg font-medium">Cancel</button>
          <button id="confirmOkBtn" class="flex-1 bg-[#0B2C62] hover:bg-blue-900 text-white py-2 px-4 rounded-lg font-medium">Confirm</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    const apiSubjects = '../EmployeePortal/api/subjects.php';
    const apiOfferings = '../EmployeePortal/api/subject_offerings.php';

    const subjectList = document.getElementById('subjectList');
    const subjectSearch = document.getElementById('subjectSearch');
    const addSubjectBtn = document.getElementById('addSubjectBtn');

    const subjectModal = document.getElementById('subjectModal');
    const subjectModalTitle = document.getElementById('subjectModalTitle');
    const subCode = document.getElementById('subCode');
    const subName = document.getElementById('subName');
    const subError = document.getElementById('subError');
    const saveSubjectBtn = document.getElementById('saveSubjectBtn');

    const gradeLevel = document.getElementById('gradeLevel');
    const strand = document.getElementById('strand');
    const semester = document.getElementById('semester');
    const syterm = document.getElementById('syterm');
    const assignedSubjects = document.getElementById('assignedSubjects');
    const assignSubjectBtn = document.getElementById('assignSubjectBtn');
    const addAndAssignBtn = document.getElementById('addAndAssignBtn');
    const masterListSection = document.getElementById('masterListSection');
    const simpleInlineAdd = document.getElementById('simpleInlineAdd');
    const simpleSubjectName = document.getElementById('simpleSubjectName');
    const simpleSubjectCode = document.getElementById('simpleSubjectCode');
    const simpleAddBtn = document.getElementById('simpleAddBtn');
    const assignModal = document.getElementById('assignModal');
    const assignSubjectSelect = document.getElementById('assignSubjectSelect');
    const confirmAssignBtn = document.getElementById('confirmAssignBtn');

    // Level grid references (for grid mode)
    const levelGridSection = document.getElementById('levelGridSection');
    const manageMain = document.getElementById('manageMain');
    const gridStrand = document.getElementById('gridStrand');
    const gridSemester = document.getElementById('gridSemester');
    const gridSyterm = document.getElementById('gridSyterm');
    const levelCards = document.getElementById('levelCards');
    const API_SUBJECTS = '../EmployeePortal/api/subjects.php';

    // Subject Manager modal elements
    const subjectManagerModal = document.getElementById('subjectManagerModal');
    const smName = document.getElementById('smName');
    const smCode = document.getElementById('smCode');
    const smAddBtn = document.getElementById('smAddBtn');
    const smList = document.getElementById('smList');
    const smSearch = document.getElementById('smSearch');
    const smCloseBtn = document.getElementById('smCloseBtn');
    const closeSubjectManagerBtn = document.getElementById('closeSubjectManagerBtn');
    const modalTitle = document.getElementById('modalTitle');
    // Confirmation modal elements
    const confirmModal = document.getElementById('confirmModal');
    const confirmMessage = document.getElementById('confirmMessage');
    const confirmOkBtn = document.getElementById('confirmOkBtn');
    const confirmCancelBtn = document.getElementById('confirmCancelBtn');

    // Reusable confirmation helper
    function showConfirm(message, detailsHtml, theme='info', titleText){
      return new Promise(resolve=>{
        if(!confirmModal || !confirmOkBtn || !confirmCancelBtn){
          // Fallback to native confirm if modal not found
          resolve(window.confirm(message||'Are you sure?'));
          return;
        }
        // Set main message
        confirmMessage.textContent = '';
        const base = document.createElement('div');
        const p = document.createElement('p');
        p.className = 'mb-2';
        p.textContent = message || 'Are you sure?';
        base.appendChild(p);
        if (detailsHtml){
          const wrap = document.createElement('div');
          wrap.className = 'text-sm text-gray-700';
          wrap.innerHTML = detailsHtml;
          base.appendChild(wrap);
        }
        confirmMessage.innerHTML = base.innerHTML;
        // Theme the icon and confirm button
        try{
          const iconWrap = document.getElementById('confirmIconWrap');
          const iconSvg = document.getElementById('confirmIconSvg');
          const titleEl = document.getElementById('confirmTitle');
          if (titleEl && titleText) titleEl.textContent = titleText;
          if (theme==='danger'){
            if (iconWrap) iconWrap.className = 'mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4';
            if (iconSvg){
              iconSvg.className = 'h-6 w-6';
              iconSvg.setAttribute('stroke', '#dc2626');
              // Warning triangle glyph (Heroicons outline style)
              iconSvg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />';
            }
            if (confirmOkBtn) confirmOkBtn.className = 'flex-1 bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg font-medium';
          } else {
            if (iconWrap) iconWrap.className = 'mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 mb-4';
            if (iconSvg){
              iconSvg.className = 'h-6 w-6';
              iconSvg.setAttribute('stroke', '#2563eb');
              // Info circle glyph
              iconSvg.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z" />';
            }
            if (confirmOkBtn) confirmOkBtn.className = 'flex-1 bg-[#0B2C62] hover:bg-blue-900 text-white py-2 px-4 rounded-lg font-medium';
          }
        }catch(_){ }
        confirmModal.classList.remove('hidden');
        const cleanup = ()=>{
          confirmOkBtn.removeEventListener('click', onOk);
          confirmCancelBtn.removeEventListener('click', onCancel);
          document.removeEventListener('keydown', onKey);
          confirmModal.classList.add('hidden');
        };
        const onOk = ()=>{ cleanup(); resolve(true); };
        const onCancel = ()=>{ cleanup(); resolve(false); };
        const onKey = (e)=>{ if(e.key==='Escape'){ onCancel(); } };
        confirmOkBtn.addEventListener('click', onOk, { once:true });
        confirmCancelBtn.addEventListener('click', onCancel, { once:true });
        document.addEventListener('keydown', onKey, { once:true });
      });
    }

    // Enable/disable Add button in modal based on inputs (no inline errors)
    function updateSmAddState(){
      try{
        const hasName = !!(smName && smName.value.trim());
        const hasCode = !!(smCode && smCode.value.trim());
        if (smAddBtn){
          smAddBtn.disabled = !(hasName && hasCode);
          smAddBtn.classList.toggle('opacity-50', smAddBtn.disabled);
          smAddBtn.title = smAddBtn.disabled ? 'Please input subject name and code' : '';
        }
      }catch(_){ }
    }
    if (smName) smName.addEventListener('input', updateSmAddState);
    if (smCode) smCode.addEventListener('input', updateSmAddState);

    // Assignment controls (in-modal for SHS/College)
    const smTopControls = document.getElementById('smTopControls');
    const smTopYear = document.getElementById('smTopYear');
    const smTopYearWrap = document.getElementById('smTopYearWrap');
    const smTopStrand = document.getElementById('smTopStrand');
    const smTopStrandWrap = document.getElementById('smTopStrandWrap');
    const smTopSem = document.getElementById('smTopSem');
    const smTopSemWrap = document.getElementById('smTopSemWrap');
    const smAssignListBlock = document.getElementById('smAssignListBlock');
    const smAssignList = document.getElementById('smAssignList');
    const API_OFFERINGS = '../EmployeePortal/api/subject_offerings.php';

    let currentManageLevel = '';

    // ===== Option A: Tabs + Single Manage CTA =====
    const levelTabs = document.getElementById('levelTabs');
    const selLevel = document.getElementById('selLevel');
    const selTrackWrap = document.getElementById('selTrackWrap');
    const selTrack = document.getElementById('selTrack');
    const selSem = document.getElementById('selSem');
    const selSemWrap = document.getElementById('selSemWrap');
    const goManageBtn = document.getElementById('goManageBtn');

    const basicLevels = ['Kinder 1','Kinder 2', ...Array.from({length:10}, (_,i)=>`Grade ${i+1}`)];
    const shsLevels = ['Grade 11','Grade 12'];
    const collegeLevels = ['1st Year','2nd Year','3rd Year','4th Year'];
    const shsStrands = [
      { value: 'ABM',     label: 'ABM (Accountancy, Business & Management)' },
      { value: 'GAS',     label: 'GAS (General Academic Strand)' },
      { value: 'TVL-HE',  label: 'HE (Home Economics)' },
      { value: 'HUMSS',   label: 'HUMSS (Humanities & Social Sciences)' },
      { value: 'TVL-ICT', label: 'ICT (Information and Communications Technology)' },
      { value: 'SPORTS',  label: 'SPORTS' },
      { value: 'STEM',    label: 'STEM (Science, Technology, Engineering & Mathematics)' }
    ];
    const collegeCourses = [
      { value: 'BPED', label: 'BPED (Bachelor of Physical Education)' },
      { value: 'BECED', label: 'BECED (Bachelor of Early Childhood Education)' }
    ];

    let currentGroup = 'basic'; // default focus is Basic Ed

    function renderTabStyles(){
      if (!levelTabs) return;
      levelTabs.querySelectorAll('.tab-btn').forEach(btn=>{
        const active = btn.getAttribute('data-group') === currentGroup;
        btn.className = 'tab-btn px-3 py-1.5 rounded-lg border ' + (active ? 'bg-[#0B2C62] text-white' : 'text-gray-700 bg-gray-50');
      });
    }

    function populateLevels(){
      if (!selLevel) return;
      if (currentGroup==='basic'){
        const kinder = ['Kinder 1','Kinder 2'];
        const gs = Array.from({length:6}, (_,i)=>`Grade ${i+1}`); // 1-6
        const jhs = ['Grade 7','Grade 8','Grade 9','Grade 10'];
        selLevel.innerHTML = `
          <option value="">-- Select Level --</option>
          <optgroup label="Kindergarten">${kinder.map(v=>`<option>${v}</option>`).join('')}</optgroup>
          <optgroup label="Grade School">${gs.map(v=>`<option>${v}</option>`).join('')}</optgroup>
          <optgroup label="Junior High School">${jhs.map(v=>`<option>${v}</option>`).join('')}</optgroup>
        `;
      } else if (currentGroup==='shs'){
        const items = shsLevels; // Grade 11, Grade 12
        selLevel.innerHTML = '<option value="">-- Select Level --</option>' +
          `<optgroup label="Senior High">${items.map(v=>`<option>${v}</option>`).join('')}</optgroup>`;
      } else {
        const items = collegeLevels; // 1st-4th Year
        selLevel.innerHTML = '<option value="">-- Select Level --</option>' +
          `<optgroup label="College">${items.map(v=>`<option>${v}</option>`).join('')}</optgroup>`;
      }
    }

    function populateTrack(){
      if (!selTrack || !selTrackWrap) return;
      if (currentGroup==='shs'){
        selTrackWrap.classList.remove('hidden');
        selTrack.innerHTML = '<option value="">-- Select Strand --</option>' + shsStrands.map(s=>`<option value="${s.value}">${s.label}</option>`).join('');
      } else if (currentGroup==='college'){
        selTrackWrap.classList.remove('hidden');
        selTrack.innerHTML = '<option value="">-- Select Course --</option>' + collegeCourses.map(c=>`<option value="${c.value}">${c.label}</option>`).join('');
      } else {
        selTrackWrap.classList.add('hidden');
        selTrack.innerHTML = '';
      }
    }

    function updateSemVisibility(){
      if (!selSemWrap) return;
      // Hide semester for Basic Ed; show for SHS/College
      if (currentGroup==='basic') selSemWrap.classList.add('hidden');
      else selSemWrap.classList.remove('hidden');
    }

    // Helper: close inline add form in preview
    function closeInlineAddPreview(){
      try{
        const form = document.getElementById('previewAddForm');
        const nameEl = document.getElementById('previewAddName');
        const codeEl = document.getElementById('previewAddCode');
        if (form) form.classList.add('hidden');
        if (nameEl) nameEl.value = '';
        if (codeEl) codeEl.value = '';
      }catch(_){ }
    }

    function setGroup(g){
      currentGroup = g;
      renderTabStyles();
      populateLevels();
      populateTrack();
      updateSemVisibility();
      updateGoState();
      closeInlineAddPreview();
      try{ loadPreview(); }catch(_){ }
    }

    function updateGoState(){
      if (!goManageBtn) return;
      const lvlOk = selLevel && selLevel.value;
      const trackNeeded = (currentGroup==='shs' || currentGroup==='college');
      const trackOk = !trackNeeded || (selTrack && selTrack.value);
      const semNeeded = (currentGroup==='shs' || currentGroup==='college');
      const semOk = !semNeeded || (selSem && selSem.value);
      goManageBtn.disabled = !(lvlOk && trackOk && semOk);
    }

    if (levelTabs){
      levelTabs.addEventListener('click', (e)=>{
        const btn = e.target.closest('[data-group]');
        if (!btn) return;
        setGroup(btn.getAttribute('data-group'));
      });
    }
    if (selLevel) selLevel.addEventListener('change', ()=>{ updateGoState(); closeInlineAddPreview(); loadPreview(); });
    if (selTrack) selTrack.addEventListener('change', ()=>{ updateGoState(); closeInlineAddPreview(); loadPreview(); });
    if (selSem) selSem.addEventListener('change', ()=>{ updateGoState(); closeInlineAddPreview(); loadPreview(); });

    if (goManageBtn){
      goManageBtn.addEventListener('click', ()=>{
        const levelVal = selLevel ? selLevel.value : '';
        const trackVal = selTrack ? selTrack.value : '';
        if (!levelVal) return toast('Please select a level', false);
        if ((currentGroup==='shs' || currentGroup==='college') && !trackVal) return toast('Please select a strand/course', false);
        openSubjectManager(levelVal);
      });
    }

    // Initial render
    setGroup('basic');

    function openSubjectManager(level){
      currentManageLevel = level || '';
      modalTitle.textContent = level ? `Manage Subjects • ${level}` : 'Manage Subjects';
      subjectManagerModal.classList.remove('hidden');
      smName.value = '';
      smCode.value = '';
      if (smSearch) smSearch.value = '';
      updateSmAddState();
      // Configure in-modal controls (now shown for all groups with dynamic visibility)
      if (smTopControls){
        smTopControls.classList.remove('hidden');
        const isSHS = /^(Grade\s*11|Grade\s*12)$/i.test(currentManageLevel);
        const isCollege = /^(1st Year|2nd Year|3rd Year|4th Year)$/i.test(currentManageLevel);
        // Populate Year Level choices per group
        if (smTopYear){
          if (isSHS){
            smTopYear.innerHTML = ['Grade 11','Grade 12'].map(v=>`<option>${v}</option>`).join('');
          } else if (isCollege){
            smTopYear.innerHTML = ['1st Year','2nd Year','3rd Year','4th Year'].map(v=>`<option>${v}</option>`).join('');
          } else {
            const kinder = ['Kinder 1','Kinder 2'];
            const gs = Array.from({length:6}, (_,i)=>`Grade ${i+1}`);
            const jhs = ['Grade 7','Grade 8','Grade 9','Grade 10'];
            smTopYear.innerHTML = [...kinder, ...gs, ...jhs].map(v=>`<option>${v}</option>`).join('');
          }
          // Default to the level used to open the modal
          if (currentManageLevel){ smTopYear.value = currentManageLevel; }
        }
        // Always hide Year Level control per request
        if (smTopYearWrap) smTopYearWrap.classList.add('hidden');
        // Always hide Strand/Course and Semester in the modal (use main selectors outside the modal)
        if (smTopStrandWrap){ smTopStrandWrap.classList.add('hidden'); if (smTopStrand) smTopStrand.innerHTML = ''; }
        if (smTopSemWrap){ smTopSemWrap.classList.add('hidden'); }
      }
      loadSubjectManagerList();
      // Hide Assigned list section permanently
      if (smAssignListBlock) smAssignListBlock.classList.add('hidden');
    }

    function closeSubjectManager(){ subjectManagerModal.classList.add('hidden'); }

    function getSelectedYearLevel(){
      // Prefer in-modal Year Level only if its wrap is visible; otherwise use the modal's currentManageLevel
      if (smTopControls && smTopYear && smTopYearWrap && !smTopControls.classList.contains('hidden') && !smTopYearWrap.classList.contains('hidden') && smTopYear.value){
        return smTopYear.value;
      }
      return currentManageLevel || '';
    }

    async function loadSubjectManagerList(){
      // Per request: hide and do not load the 'All Subjects' list in the modal
      try{ if (smList){ smList.innerHTML = ''; } }catch(_){ }
      return;
    }

    async function smAdd(){
      const name = (smName.value||'').trim();
      const code = (smCode.value||'').trim();
      if(!name || !code){ updateSmAddState(); return; }
      const lvl = getSelectedYearLevel ? (getSelectedYearLevel()||'') : (currentManageLevel||'');
      // Determine strand/course and semester (prefer in-modal controls if visible)
      const isAdvanced = /^(Grade\s*11|Grade\s*12|1st Year|2nd Year|3rd Year|4th Year)$/i.test(String(lvl||''));
      const strandVal = (isAdvanced && smTopStrand && !smTopStrandWrap.classList.contains('hidden'))
        ? (smTopStrand.value||'')
        : (selTrack ? (selTrack.value||'') : '');
      const semVal = (isAdvanced && smTopSem && !smTopSemWrap.classList.contains('hidden'))
        ? (smTopSem.value||'')
        : (selSem ? (selSem.value||'') : '');
      const strandLabel = (()=>{
        if (!strandVal) return '';
        const m = (strandVal+'' ).toUpperCase();
        return m;
      })();
      const details = `
        <div class='bg-gray-50 rounded-lg p-3 text-left text-sm space-y-1'>
          <div><span class='font-medium text-gray-600'>Subject:</span> <span class='text-gray-900'>${escapeHtml(name)}</span></div>
          <div><span class='font-medium text-gray-600'>Code:</span> <span class='text-gray-900'>${escapeHtml(code)}</span></div>
          <div><span class='font-medium text-gray-600'>Grade Level:</span> <span class='text-gray-900'>${escapeHtml(lvl)}</span></div>
          ${isAdvanced && strandVal ? `<div><span class='font-medium text-gray-600'>Strand / Course:</span> <span class='text-gray-900'>${escapeHtml(strandLabel)}</span></div>` : ''}
          ${isAdvanced && semVal ? `<div><span class='font-medium text-gray-600'>Semester:</span> <span class='text-gray-900'>${escapeHtml(semVal)}</span></div>` : ''}
        </div>`;
      if(!(await showConfirm('Add this subject to the current selection?', details))) return;
      try{
        let subjectId = null;
        // 1) Check for duplicate code FIRST so overwrite prompt always shows when code is already in use
        if (code){
          try{
            const rByCode = await fetch(`${API_SUBJECTS}?action=list&q=${encodeURIComponent(code)}`);
            const dByCode = await rByCode.json();
            const items2 = dByCode.items||[];
            const dup = items2.find(s=> (s.code||'').toLowerCase() === code.toLowerCase());
            if (dup){
              const det = `
                <div class='bg-yellow-50 rounded-lg p-3 text-left text-sm space-y-1'>
                  <div class='text-yellow-800 mb-1'>A subject with this code already exists. Overwrite it with your new details?</div>
                  <div><span class='font-medium text-gray-600'>Existing Subject:</span> <span class='text-gray-900'>${escapeHtml(dup.name||'')}</span></div>
                  <div><span class='font-medium text-gray-600'>Existing Code:</span> <span class='text-gray-900'>${escapeHtml(dup.code||'')}</span></div>
                  <hr class='my-2'>
                  <div><span class='font-medium text-gray-600'>New Subject:</span> <span class='text-gray-900'>${escapeHtml(name)}</span></div>
                  <div><span class='font-medium text-gray-600'>New Code:</span> <span class='text-gray-900'>${escapeHtml(code)}</span></div>
                </div>`;
              const okOverwrite = await showConfirm('Code already in use. Overwrite the existing subject?', det);
              if (!okOverwrite) return; // abort if user cancels
              // Overwrite existing subject with the new name/code
              try{
                const up = await fetch(API_SUBJECTS,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'update', id: dup.id, name, code})});
                const upRes = await up.json();
                if (!upRes.success){ toast(upRes.message||'Failed to overwrite subject', false); return; }
              }catch(e){ console.error(e); toast('Failed to overwrite subject', false); return; }
              subjectId = dup.id;
              toast('Subject overwritten');
            }
          }catch(_){ /* ignore */ }
        }
        // 2) If not set via dup code, try to reuse by exact same name (and optional code)
        if (!subjectId){
          try{
            const rList = await fetch(`${API_SUBJECTS}?action=list&q=${encodeURIComponent(name)}`);
            const dList = await rList.json();
            const items = dList.items||[];
            const found = items.find(s=> s && s.name && s.name.trim().toLowerCase()===name.toLowerCase() && ((code? (s.code||'')===''+code : true)));
            if (found) subjectId = found.id;
          }catch(_){ /* ignore list failures */ }
        }

        // 3) Create if still not found
        if (!subjectId){
          const res = await fetch(API_SUBJECTS,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'create', name, code})});
          const d = await res.json();
          if(!d.success){ toast(d.message||'Failed to add subject', false); return; }
          subjectId = d.id;
        }

        // 4) Assign to current selection
        const lvl = String(getSelectedYearLevel()||'');
        if (subjectId && lvl){
          const isBasic = /^(Kinder\s*1|Kinder\s*2|Grade\s*(?:[1-9]|10))$/i.test(lvl);
          if (isBasic){
            // Assign to both terms (unique key prevents duplicates)
            const syVal = syterm ? (syterm.value||null) : null;
            const p1 = fetch(API_OFFERINGS,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'assign', subject_id:subjectId, grade_level:lvl, strand:null, semester:'1st', sy: syVal})});
            const p2 = fetch(API_OFFERINGS,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'assign', subject_id:subjectId, grade_level:lvl, strand:null, semester:'2nd', sy: syVal})});
            // Also create generic (no SY) offerings for compatibility if SY provided
            const p1g = syVal ? fetch(API_OFFERINGS,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'assign', subject_id:subjectId, grade_level:lvl, strand:null, semester:'1st', sy: null})}) : Promise.resolve();
            const p2g = syVal ? fetch(API_OFFERINGS,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'assign', subject_id:subjectId, grade_level:lvl, strand:null, semester:'2nd', sy: null})}) : Promise.resolve();
            await Promise.all([p1,p2,p1g,p2g]);
          } else {
            // Use in-modal controls only if their wraps are visible; otherwise use main selectors
            const strandVal = (smTopStrand && smTopStrandWrap && !smTopStrandWrap.classList.contains('hidden'))
              ? (smTopStrand.value||null)
              : (selTrack ? (selTrack.value||null) : null);
            const semVal = (smTopSem && smTopSemWrap && !smTopSemWrap.classList.contains('hidden'))
              ? (smTopSem.value||'1st')
              : (selSem ? (selSem.value||'1st') : '1st');
            const syVal = syterm ? (syterm.value||null) : null;
            await fetch(API_OFFERINGS,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'assign', subject_id:subjectId, grade_level:lvl, strand: strandVal, semester: semVal, sy: syVal})});
            // Also create generic (no SY) offering for compatibility if SY provided
            if (syVal){
              await fetch(API_OFFERINGS,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'assign', subject_id:subjectId, grade_level:lvl, strand: strandVal, semester: semVal, sy: null})});
            }
          }
        }

        // 4) Close modal and refresh preview
        toast('Subject added');
        smName.value=''; smCode.value='';
        closeSubjectManager();
        try{ loadPreview(); }catch(_){ }
      }catch(e){ console.error(e); toast('Error adding subject', false); }
    }

    window.smStartEdit = function(id, name, code){
      const row = Array.from(smList.children).find(el=> el.innerHTML.includes(`smStartEdit(${id},`));
      if(!row) return;
      row.innerHTML = `
        <div class="w-full grid grid-cols-1 md:grid-cols-3 gap-2 items-center">
          <input id="smEditName${id}" class="border rounded-lg px-3 py-2 md:col-span-2" value="${escapeHtml(name||'')}">
          <input id="smEditCode${id}" class="border rounded-lg px-3 py-2" value="${escapeHtml(code||'')}">
        </div>
        <div class="flex gap-2 mt-2 md:mt-0">
          <button class="px-3 py-1 text-sm rounded-lg bg-[#0B2C62] text-white hover:bg-blue-900" onclick="smSave(${id})">Save</button>
          <button class="px-3 py-1 text-sm rounded-lg bg-gray-200 hover:bg-gray-300" onclick="loadSubjectManagerList()">Cancel</button>
        </div>`;
    }

    window.smSave = async function(id){
      const name = document.getElementById(`smEditName${id}`).value.trim();
      const code = document.getElementById(`smEditCode${id}`).value.trim();
      if(!name) return toast('Name required', false);
      try{
        const res = await fetch(API_SUBJECTS,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'update', id, name, code})});
        const d = await res.json();
        if(d.success){ toast('Updated'); loadSubjectManagerList(); }
        else toast(d.message||'Update failed', false);
      }catch(e){ console.error(e); toast('Error updating', false); }
    }

    window.smDelete = async function(id){
      if(!confirm('Delete this subject?')) return;
      try{
        const res = await fetch(API_SUBJECTS,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'delete', id})});
        const d = await res.json();
        if(d.success){ toast('Deleted'); loadSubjectManagerList(); }
        else toast(d.message||'Delete failed', false);
      }catch(e){ console.error(e); toast('Error deleting', false); }
    }

    // Wire up modal controls
    if (smAddBtn) smAddBtn.addEventListener('click', smAdd);
    if (smCloseBtn) smCloseBtn.addEventListener('click', closeSubjectManager);
    if (closeSubjectManagerBtn) closeSubjectManagerBtn.addEventListener('click', closeSubjectManager);
    if (smSearch) smSearch.addEventListener('input', ()=>{ loadSubjectManagerList(); });

    function bindManageButtons(){
      document.querySelectorAll('.manage-level-btn').forEach(btn=>{
        btn.addEventListener('click', ()=>{
          const level = btn.getAttribute('data-level') || '';
          openSubjectManager(level);
        });
      });
    }

    function renderLevelCards(){
      if (!levelCards) return;
      const levels = [
        'Kinder 1','Kinder 2',
        'Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6',
        'Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12',
        '1st Year','2nd Year','3rd Year','4th Year'
      ];
      levelCards.innerHTML = levels.map(l=>`
        <div class="bg-white rounded-2xl p-6 card-shadow flex flex-col justify-between border border-gray-100 hover:-translate-y-0.5 transition min-h-[170px]">
          <div>
            <h3 class="text-xl font-semibold text-gray-800 mb-1">${l}</h3>
            <p class="text-xs text-gray-500">Click to manage subjects for this level</p>
          </div>
          <button data-level="${l}" class="mt-4 manage-level-btn bg-[#0B2C62] hover:bg-blue-900 text-white w-full py-3.5 rounded-xl">Manage Subjects</button>
        </div>`).join('');
      bindManageButtons();
    }

    // Bind buttons for server-rendered cards
    bindManageButtons();

    let editingId = null;
    let prevEditName = '';
    let prevEditCode = '';

    function toast(msg, ok=true){
      const t = document.getElementById('toast');
      t.textContent = msg;
      // Top-right pill, lighter color, long and noticeable
      const bg = ok ? 'bg-emerald-500' : 'bg-red-500';
      t.className = `fixed top-6 right-6 ${bg} text-white px-6 py-3 rounded-full shadow-lg transition-opacity duration-300 whitespace-nowrap max-w-[60vw]`;
      t.classList.remove('hidden');
      // Click to dismiss early
      t.onclick = ()=> t.classList.add('hidden');
      // Show longer (7 seconds)
      clearTimeout(window.__toastTimer);
      window.__toastTimer = setTimeout(()=>t.classList.add('hidden'), 7000);
    }

    // ===== Advanced helpers =====
    function isAdvancedLevel(level){
      return /^(Grade 11|Grade 12|1st Year|2nd Year|3rd Year|4th Year)$/i.test(String(level||''));
    }

    async function loadAssignList(){
      if (!isAdvancedLevel(currentManageLevel)) return;
      const params = new URLSearchParams({
        action: 'list',
        grade_level: currentManageLevel,
        strand: selTrack ? (selTrack.value||'') : '',
        semester: selSem ? (selSem.value||'1st') : '1st',
        sy: ''
      });
      try{
        const res = await fetch(`${API_OFFERINGS}?${params.toString()}`);
        const d = await res.json();
        const items = d.items || [];
        if (!items.length){
          smAssignList.innerHTML = '<div class="p-3 text-sm text-gray-500">No assigned subjects for this selection.</div>';
          return;
        }
        smAssignList.innerHTML = items.map(o=>`
          <div class="p-3 flex items-center justify-between">
            <div>
              <div class="font-medium">${escapeHtml(o.name||'')}</div>
              <div class="text-xs text-gray-500">${o.code||''}</div>
            </div>
            <button class="px-3 py-1 text-sm rounded-lg bg-red-600 text-white hover:bg-red-700" onclick="smRemoveAssign(${o.id})">Remove</button>
          </div>
        `).join('');
      }catch(e){
        console.error(e);
        smAssignList.innerHTML = '<div class="p-3 text-sm text-red-600">Failed to load assigned list.</div>';
      }
    }

    window.smQuickAssign = async function(subject_id){
      if (!subject_id){ toast('Select a subject', false); return; }
      try{
        const lvl = String(currentManageLevel||'');
        const isBasic = /^(Kinder\s*1|Kinder\s*2|Grade\s*(?:[1-9]|10))$/i.test(lvl);
        if (isBasic){
          const p1 = fetch(API_OFFERINGS,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'assign', subject_id, grade_level:lvl, strand:null, semester:'1st', sy:null})});
          const p2 = fetch(API_OFFERINGS,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'assign', subject_id, grade_level:lvl, strand:null, semester:'2nd', sy:null})});
          await Promise.all([p1,p2]);
        } else {
          const strandVal = selTrack ? (selTrack.value||null) : null;
          const semVal = selSem ? (selSem.value||'1st') : '1st';
          await fetch(API_OFFERINGS,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'assign', subject_id, grade_level:lvl, strand: strandVal, semester: semVal, sy: null})});
        }
        toast('Assigned');
        // Refresh whichever list the user sees
        try{ loadAssignList(); }catch(_){}
        try{ loadSubjectManagerList(); }catch(_){}
      }catch(e){ console.error(e); toast('Error assigning', false); }
    }

    window.smRemoveAssign = async function(id){
      if(!(await showConfirm('Remove this subject from the selected offering?'))) return;
      try{
        const res = await fetch(API_OFFERINGS,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'remove', id, delete_orphan: true})});
        const d = await res.json();
        if (d.success){ toast('Removed'); loadAssignList(); try{ loadPreview(); }catch(_){ } }
        else toast(d.message||'Failed', false);
      }catch(e){ console.error(e); toast('Error', false); }
    }

    // React to top-level controls changes
    if (selTrack) selTrack.addEventListener('change', loadAssignList);
    if (selSem) selSem.addEventListener('change', loadAssignList);

    // React to in-modal Year Level / Strand / Semester changes
    if (typeof loadSubjectManagerList === 'function'){
      if (typeof smTopYear !== 'undefined' && smTopYear){
        smTopYear.addEventListener('change', ()=>{ try{ modalTitle.textContent = `Manage Subjects • ${getSelectedYearLevel()}`; loadSubjectManagerList(); }catch(_){ } });
      }
      if (typeof smTopStrand !== 'undefined' && smTopStrand){
        smTopStrand.addEventListener('change', ()=>{ try{ loadSubjectManagerList(); }catch(_){ } });
      }
      if (typeof smTopSem !== 'undefined' && smTopSem){
        smTopSem.addEventListener('change', ()=>{ try{ loadSubjectManagerList(); }catch(_){ } });
      }
    }

    function openSubjectModal(data){
      subjectModal.classList.remove('hidden');
      subError.classList.add('hidden');
      if(data){
        editingId = data.id; subjectModalTitle.textContent = 'Edit Subject';
        subCode.value = data.code; subName.value = data.name;
        prevEditName = data.name || '';
        prevEditCode = data.code || '';
      } else {
        editingId = null; subjectModalTitle.textContent = 'Add Subject';
        subCode.value=''; subName.value='';
        prevEditName = '';
        prevEditCode = '';
      }
    }
    function closeSubjectModal(){ subjectModal.classList.add('hidden'); }
    addSubjectBtn.onclick = ()=>openSubjectModal();

    saveSubjectBtn.onclick = async ()=>{
      const code = subCode.value.trim();
      const name = subName.value.trim();
      if(!name){ subError.textContent='Name is required'; subError.classList.remove('hidden'); subName.focus(); return; }
      if(!code){ subError.textContent='Code is required'; subError.classList.remove('hidden'); subCode.focus(); return; }
      const details = `
        <div class='bg-gray-50 rounded-lg p-3 text-left text-sm space-y-2'>
          <div>
            <div class='font-medium text-gray-600 mb-0.5'>Subject</div>
            <div class='text-gray-500'>Previous: <span class='text-gray-900'>${escapeHtml(prevEditName||'(none)')}</span></div>
            <div class='text-gray-500'>Current: <span class='text-gray-900'>${escapeHtml(name||'(none)')}</span></div>
          </div>
          <div>
            <div class='font-medium text-gray-600 mb-0.5'>Code</div>
            <div class='text-gray-500'>Previous: <span class='text-gray-900'>${escapeHtml(prevEditCode||'(none)')}</span></div>
            <div class='text-gray-500'>Current: <span class='text-gray-900'>${escapeHtml(code||'(none)')}</span></div>
          </div>
        </div>`;
      const msg = editingId ? 'Save changes to this subject?' : 'Create this subject?';
      const ok = await showConfirm(msg, details);
      if(!ok) return;
      const payload = editingId ? {action:'update', id: editingId, code, name} : {action:'create', code, name};
      const res = await fetch(apiSubjects,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
      const d = await res.json();
      if(d.success){ closeSubjectModal(); loadSubjects(); toast(d.message); refreshAssignChoices(); if(isFilterReady()) loadOfferings(); try{ loadPreview(); }catch(_){ } }
      else { subError.textContent=d.message||'Error'; subError.classList.remove('hidden'); }
    };

    async function loadSubjects(){
      const q = subjectSearch.value.trim();
      const res = await fetch(`${apiSubjects}?action=list&q=${encodeURIComponent(q)}`);
      const d = await res.json();
      subjectList.innerHTML = (d.items||[]).map(s=>`
        <div class="py-3 flex items-center justify-between">
          <div>
            <div class="font-medium">${escapeHtml(s.name)}</div>
            <div class="text-xs text-gray-500">${s.code?escapeHtml(s.code):''}</div>
          </div>
          <div class="space-x-2 text-sm">
            <button class="px-2 py-1 rounded bg-gray-200 hover:bg-gray-300" onclick='editSubject(${JSON.stringify(s)})'>Edit</button>
            <button class="px-2 py-1 rounded bg-red-600 text-white hover:bg-red-700" onclick='deleteSubject(${s.id})'>Delete</button>
          </div>
        </div>`).join('');
    }

    function editSubject(s){ openSubjectModal(s); }

    async function deleteSubject(id){
      if(!(await showConfirm('Delete this subject?'))) return;
      const res = await fetch(apiSubjects,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'delete', id})});
      const d = await res.json();
      if(d.success){ loadSubjects(); toast('Subject deleted'); refreshAssignChoices(); if(isFilterReady()) loadOfferings(); }
      else toast(d.message||'Delete failed', false);
    }

    subjectSearch.addEventListener('input', ()=>loadSubjects());

    function isFilterReady(){ return gradeLevel.value && semester.value; }

    function updateAssignButtons(){
      const ready = isFilterReady();
      if (assignSubjectBtn) assignSubjectBtn.disabled = !ready;
      if (addAndAssignBtn) addAndAssignBtn.disabled = !ready;
    }

    [gradeLevel, strand, semester, syterm].forEach(el=> el.addEventListener('change', ()=>{
      updateAssignButtons();
      if(isFilterReady()) loadOfferings();
      updateFilterChips();
    }));

    async function loadOfferings(){
      const params = new URLSearchParams({
        action:'list', grade_level: gradeLevel.value, strand: strand.value, semester: semester.value, sy: syterm.value
      });
      const res = await fetch(`${apiOfferings}?${params.toString()}`);
      const d = await res.json();
      if(!(d.items && d.items.length)){
        assignedSubjects.innerHTML = '<div class="text-gray-500 text-sm">No assigned subjects for this selection.</div>';
      } else {
        assignedSubjects.innerHTML = d.items.map(o=>`
          <div class="flex items-center justify-between p-3 border rounded-xl mb-2 bg-white/80">
            <div>
              <div class="font-medium">${escapeHtml(o.name)}</div>
              <div class="text-xs text-gray-500">${o.code||''}</div>
            </div>
            <div class="flex gap-2">
              <button class="text-sm px-3 py-1 rounded-lg bg-gray-200 hover:bg-gray-300" onclick='openSubjectModal({id:${o.subject_id}, code:${JSON.stringify(o.code||'')}, name:${JSON.stringify(o.name||'')}})'>Edit</button>
              <button class="text-sm px-3 py-1 rounded-lg bg-red-600 text-white hover:bg-red-700" onclick="removeOffering(${o.id})">Remove</button>
            </div>
          </div>`).join('');
      }
    }

    async function removeOffering(id){
      if(!(await showConfirm('Remove this subject from the selected offering?', null, 'danger'))) return;
      const res = await fetch(apiOfferings,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'remove', id, delete_orphan: true})});
      const d = await res.json();
      if(d.success){ loadOfferings(); toast('Removed'); }
      else toast(d.message||'Failed', false);
    }

    assignSubjectBtn.onclick = async ()=>{
      if(!isFilterReady()) return;
      await refreshAssignChoices();
      assignModal.classList.remove('hidden');
    };

    if (addAndAssignBtn){
      addAndAssignBtn.onclick = async ()=>{
        if(!isFilterReady()) return;
        const name = (prompt('Enter Subject Name:')||'').trim();
        if(!name) return;
        const code = (prompt('Enter Subject Code (optional):')||'').trim();
        try{
          const res = await fetch(apiSubjects,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'create', name, code})});
          const d = await res.json();
          if(!d.success){ toast(d.message||'Failed to create subject', false); return; }
          const payload = {action:'assign', subject_id: d.id, grade_level: gradeLevel.value, strand: strand.value||null, semester: semester.value, sy: syterm.value||null};
          const res2 = await fetch(apiOfferings,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
          const d2 = await res2.json();
          if(d2.success){ toast('Subject added & assigned'); loadSubjects(); loadOfferings(); }
          else toast(d2.message||'Assign failed', false);
        }catch(e){ console.error(e); toast('Error while adding & assigning', false); }
      };
    }

    function updateFilterChips(){
      const bar = document.getElementById('filterChips');
      if (!bar) return;
      const chips = [];
      if (gradeLevel && gradeLevel.value) chips.push(gradeLevel.value);
      if (strand && strand.value) chips.push(strand.value);
      if (semester && semester.value) chips.push(semester.value);
      if (syterm && syterm.value) chips.push(syterm.value);
      bar.innerHTML = chips.map(c=>`<span class="inline-flex items-center gap-1 text-xs px-2 py-1 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-100">${escapeHtml(c)}</span>`).join('');
    }

    // Simple mode inline add handler
    if (simpleAddBtn){
      simpleAddBtn.onclick = async ()=>{
        if(!isFilterReady()) return toast('Select grade and semester first', false);
        const name = (simpleSubjectName.value||'').trim();
        const code = (simpleSubjectCode.value||'').trim();
        if(!name) return toast('Enter subject name', false);
        try{
          const res = await fetch(apiSubjects,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'create', name, code})});
          const d = await res.json();
          if(!d.success){ toast(d.message||'Failed to create subject', false); return; }
          const payload = {action:'assign', subject_id: d.id, grade_level: gradeLevel.value, strand: strand.value||null, semester: semester.value, sy: syterm.value||null};
          const r2 = await fetch(apiOfferings,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
          const d2 = await r2.json();
          if(d2.success){ simpleSubjectName.value=''; simpleSubjectCode.value=''; loadOfferings(); toast('Added'); }
          else toast(d2.message||'Assign failed', false);
        }catch(e){ console.error(e); toast('Error while adding', false); }
      };
    }

    async function refreshAssignChoices(){
      const res = await fetch(`${apiSubjects}?action=list`);
      const d = await res.json();
      assignSubjectSelect.innerHTML = (d.items||[]).map(s=>`<option value="${s.id}">${escapeHtml(s.name)} ${s.code? '('+escapeHtml(s.code)+')':''}</option>`).join('');
    }

    function closeAssignModal(){ assignModal.classList.add('hidden'); }

    confirmAssignBtn.onclick = async ()=>{
      const subject_id = assignSubjectSelect.value;
      const payload = {action:'assign', subject_id, grade_level: gradeLevel.value, strand: strand.value||null, semester: semester.value, sy: syterm.value||null};
      const res = await fetch(apiOfferings,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
      const d = await res.json();
      if(d.success){ closeAssignModal(); loadOfferings(); toast('Assigned'); }
      else toast(d.message||'Failed', false);
    };

    function escapeHtml(s){ return (s||'').replace(/[&<>"]+/g, c=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;"}[c])); }

    // Quick Select chips
    const quickGrades = ['Kinder 1','Kinder 2','Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6','Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12','1st Year','2nd Year','3rd Year','4th Year'];
    const quickStrands = ['ABM','STEM','HUMSS','GAS','TVL-ICT','TVL-HE','SPORTS'];
    const quickCourses = ['BPED','BECED'];

    function renderChips(containerId, items, onClick){
      const el = document.getElementById(containerId);
      if(!el) return;
      el.innerHTML = '';
      items.forEach(v=>{
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'text-xs px-3 py-1 rounded-full border border-gray-300 hover:border-blue-600 hover:text-blue-700';
        b.textContent = v;
        b.addEventListener('click', ()=>onClick(v));
        el.appendChild(b);
      });
    }

    renderChips('quickGrades', quickGrades, (v)=>{ gradeLevel.value = v; updateAssignButtons(); loadOfferings(); });
    renderChips('quickStrands', quickStrands, (v)=>{ strand.value = v; updateAssignButtons(); if(isFilterReady()) loadOfferings(); });
    renderChips('quickCourses', quickCourses, (v)=>{ strand.value = v; updateAssignButtons(); if(isFilterReady()) loadOfferings(); });

    // Live Preview renderer under top selectors
    async function loadPreview(){
      const previewList = document.getElementById('previewList');
      const previewMeta = document.getElementById('previewMeta');
      if (!previewList) return;
      const level = selLevel ? selLevel.value : '';
      const needsTrack = (currentGroup==='shs' || currentGroup==='college');
      const needsSem = (currentGroup!=='basic');
      const track = selTrack ? selTrack.value : '';
      const sem = selSem ? selSem.value : '';
      // Determine readiness
      const ready = level && (!needsTrack || track) && (!needsSem || sem);
      if (!level){
        previewList.innerHTML = '<div class="p-3 text-gray-500 text-sm">Select Level to view subjects.</div>';
        if (previewMeta) previewMeta.textContent='';
        return;
      }
      try{
        let items = [];
        if (currentGroup==='basic'){
          const p1 = fetch(`${apiOfferings}?` + new URLSearchParams({action:'list', grade_level: level, semester:'1st'}));
          const p2 = fetch(`${apiOfferings}?` + new URLSearchParams({action:'list', grade_level: level, semester:'2nd'}));
          const [r1,r2] = await Promise.all([p1,p2]);
          const [d1,d2] = await Promise.all([r1.json(), r2.json()]);
          // Build a subject-centric map, collecting offering ids across terms
          const map = new Map(); // subject_id -> { subject_id, name, code, offering_ids: [], semester_label: 'both terms'|'1st'|'2nd' }
          const addItem = (o)=>{
            if (!o) return;
            const key = o.subject_id;
            if (!map.has(key)) map.set(key, { subject_id: o.subject_id, name: o.name, code: o.code, offering_ids: [], semester_label: '' });
            const entry = map.get(key);
            if (o.id && !entry.offering_ids.includes(o.id)) entry.offering_ids.push(o.id);
          };
          (d1.items||[]).forEach(addItem);
          (d2.items||[]).forEach(addItem);
          items = Array.from(map.values()).map(e=>{
            const lbl = e.offering_ids.length===2 ? 'both terms' : ( (d1.items||[]).some(x=>x.subject_id===e.subject_id) ? '1st' : '2nd');
            return { subject_id: e.subject_id, name: e.name, code: e.code, id: e.offering_ids[0], offering_ids: e.offering_ids, semester: lbl };
          });
          if (previewMeta) previewMeta.textContent = `${level} • both terms`;
        } else if (ready){
          const params = new URLSearchParams({action:'list', grade_level: level, strand: track, semester: sem});
          const r = await fetch(`${apiOfferings}?${params.toString()}`);
          const d = await r.json();
          items = d.items||[];
          if (previewMeta) previewMeta.textContent = `${level} • ${track} • ${sem}`;
        } else {
          previewList.innerHTML = '<div class="p-3 text-gray-500 text-sm">Select Strand/Course and Term to view subjects.</div>';
          if (previewMeta) previewMeta.textContent = level;
          return;
        }
        if (!items.length){
          previewList.innerHTML = '<div class="p-3 text-gray-500 text-sm">No subjects assigned for this selection.</div>';
          return;
        }
        previewList.innerHTML = items.map(o=>{
          const removeHandler = (currentGroup==='basic' && Array.isArray(o.offering_ids) && o.offering_ids.length)
            ? `previewRemoveOfferingMulti(${JSON.stringify(o.offering_ids)})`
            : `previewRemoveOffering(${o.id})`;
          const semBadge = (o.semester||'') ? `<span class=\"text-[11px] text-gray-400 mr-2\">${escapeHtml(o.semester||'')}</span>` : '';
          return `
          <div class=\"p-3 flex items-center justify-between\">
            <div>
              <div class=\"font-medium text-gray-800\">${escapeHtml(o.name||'')}</div>
              <div class=\"text-xs text-gray-500\">${o.code?escapeHtml(o.code):''}</div>
            </div>
            <div class=\"flex items-center gap-2\">
              ${semBadge}
              <button class=\"px-3 py-1 text-sm rounded-lg bg-gray-200 hover:bg-gray-300\" onclick='previewOpenEdit(${o.subject_id}, ${JSON.stringify(o.name||'')}, ${JSON.stringify(o.code||'')})'>Edit</button>
              <button class=\"px-3 py-1 text-sm rounded-lg bg-red-600 text-white hover:bg-red-700\" onclick='${removeHandler}'>Remove</button>
            </div>
          </div>`;
        }).join('');
      }catch(e){ console.error(e); if (previewList) previewList.innerHTML = '<div class="p-3 text-red-600 text-sm">Failed to load preview.</div>'; }
    }

    // Inline preview actions
    window.previewRemoveOffering = async function(id){
      if(!(await showConfirm('Remove this subject from the selected offering?', null, 'danger'))) return;
      try{
        const res = await fetch(apiOfferings,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'remove', id, delete_orphan: true})});
        const d = await res.json();
        if(d.success){ toast('Removed'); loadPreview(); }
        else toast(d.message||'Failed', false);
      }catch(e){ console.error(e); toast('Error', false); }
    }

    // Remove multiple offerings (e.g., Basic Ed both terms for the same subject)
    window.previewRemoveOfferingMulti = async function(ids){
      if(!(await showConfirm('Remove this subject from all related offerings?', null, 'danger'))) return;
      try{
        const arr = Array.isArray(ids)? ids: [];
        await Promise.all(arr.map(id=> fetch(apiOfferings,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'remove', id, delete_orphan: true})}) ));
        toast('Removed');
        loadPreview();
      }catch(e){ console.error(e); toast('Error', false); }
    }

    window.previewOpenEdit = function(subject_id, name, code){
      // Reuse subject modal
      openSubjectModal({id: subject_id, name, code});
    }

    // Inline Add handlers
    const previewAddToggle = document.getElementById('previewAddToggle');
    const previewAddForm = document.getElementById('previewAddForm');
    const previewAddName = document.getElementById('previewAddName');
    const previewAddCode = document.getElementById('previewAddCode');
    const previewAddBtn = document.getElementById('previewAddBtn');
    const previewAddCancel = document.getElementById('previewAddCancel');

    if (previewAddToggle){
      // Open the subject manager modal for adding subjects instead of inline form
      previewAddToggle.addEventListener('click', ()=>{
        if (!selLevel || !selLevel.value){ toast('Select a level first', false); return; }
        // Ensure inline form stays hidden
        if (previewAddForm) previewAddForm.classList.add('hidden');
        // Open modal pre-configured for current selection
        try {
          openSubjectManager(selLevel.value);
          // Focus the name input inside the modal
          setTimeout(()=>{ const el = document.getElementById('smName'); if (el) el.focus(); }, 50);
        } catch(e){ console.error(e); }
      });
    }
    if (previewAddCancel){ previewAddCancel.addEventListener('click', ()=>{ previewAddForm.classList.add('hidden'); }); }
    if (previewAddBtn){
      previewAddBtn.addEventListener('click', async ()=>{
        const name = (previewAddName.value||'').trim();
        const code = (previewAddCode.value||'').trim();
        const level = selLevel ? selLevel.value : '';
        const track = selTrack ? (selTrack.value||null) : null;
        const sem = selSem ? (selSem.value||'1st') : '1st';
        if (!name) return toast('Enter subject name', false);
        try{
          // Create subject
          const r1 = await fetch(apiSubjects,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'create', name, code})});
          const d1 = await r1.json();
          if(!d1.success){ toast(d1.message||'Failed to create subject', false); return; }
          // Assign based on selection
          if (currentGroup==='basic'){
            const p1 = fetch(apiOfferings,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'assign', subject_id:d1.id, grade_level:level, strand:null, semester:'1st', sy:null})});
            const p2 = fetch(apiOfferings,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'assign', subject_id:d1.id, grade_level:level, strand:null, semester:'2nd', sy:null})});
            await Promise.all([p1,p2]);
          } else {
            if (!track){ toast('Select a strand/course', false); return; }
            await fetch(apiOfferings,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'assign', subject_id:d1.id, grade_level:level, strand:track, semester:sem, sy:null})});
          }
          previewAddName.value=''; previewAddCode.value=''; previewAddForm.classList.add('hidden');
          toast('Subject added');
          loadPreview();
        }catch(e){ console.error(e); toast('Error while adding', false); }
      });
    }

    // Apply URL presets and simple mode
    (function(){
      const params = new URLSearchParams(window.location.search);
      const presetLevel = params.get('level');
      const presetStrand = params.get('strand');
      const presetSem = params.get('semester');
      const simple = params.get('simple');
      if (presetLevel) gradeLevel.value = presetLevel;
      if (presetStrand!==null && presetStrand!==undefined) strand.value = presetStrand;
      if (presetSem) semester.value = presetSem;
      if (simple==='1' && presetLevel){
        // Simple manage mode for a specific level
        if (masterListSection) masterListSection.style.display = 'none';
        if (simpleInlineAdd) simpleInlineAdd.classList.remove('hidden');
        if (levelGridSection) levelGridSection.classList.add('hidden');
        if (manageMain) manageMain.classList.remove('hidden');
        updateFilterChips();
      } else {
        // Grid mode
        if (levelGridSection) levelGridSection.classList.remove('hidden');
        if (manageMain) manageMain.classList.add('hidden');
        renderLevelCards();
        // Sync default grid filters with manage filters for consistency
        if (gridSemester && semester) { gridSemester.value = semester.value; }
      }
      updateAssignButtons();
      if (isFilterReady()) loadOfferings();
      try{ loadPreview(); }catch(_){ }
    })();

    // init
    loadSubjects();
  </script>
</body>
</html>
