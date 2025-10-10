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
        <button onclick="window.location.href='registrardashboard.php'" class="bg-white bg-opacity-20 hover:bg-opacity-30 p-2 rounded-lg transition">
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

      <!-- CTA -->
      <div class="mt-4 flex justify-end">
        <button id="goManageBtn" class="bg-[#0B2C62] hover:bg-blue-900 text-white px-5 py-2.5 rounded-lg disabled:opacity-50">Manage Subjects</button>
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
          <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div class="md:col-span-2">
              <label class="text-sm text-gray-600">New Subject Name</label>
              <input id="smName" type="text" class="w-full border rounded-lg px-3 py-2" placeholder="e.g., Applied Economics"/>
            </div>
            <div>
              <label class="text-sm text-gray-600">Code</label>
              <input id="smCode" type="text" class="w-full border rounded-lg px-3 py-2" placeholder="e.g., APEC12"/>
            </div>
          </div>
          <div class="flex gap-3">
            <button id="smAddBtn" class="bg-[#0B2C62] hover:bg-blue-900 text-white px-4 py-2 rounded-lg">Add Subject</button>
            <button id="smRefreshBtn" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg">Refresh</button>
          </div>

          <!-- Assigned list (filtered by top Strand/Sem when advanced) -->
          <div id="smAssignListBlock" class="hidden">
            <div class="text-sm text-gray-600 mb-2">Assigned for this selection</div>
            <div id="smAssignList" class="rounded-xl border divide-y bg-white"></div>
          </div>

          <div class="flex items-center justify-between">
            <h3 class="font-semibold text-gray-800">All Subjects</h3>
            <span class="hidden"></span>
          </div>
          <div id="smList" class="max-h-[420px] overflow-y-auto divide-y rounded-xl border bg-white"></div>

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
          <input id="subCode" type="text" class="w-full border rounded-lg px-3 py-2" placeholder="e.g., ENG11" />
        </div>
        <div>
          <label class="text-sm text-gray-600">Subject Name</label>
          <input id="subName" type="text" class="w-full border rounded-lg px-3 py-2" placeholder="e.g., Purposive Communication" />
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

  <script>
    const apiSubjects = 'api/subjects.php';
    const apiOfferings = 'api/subject_offerings.php';

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
    const API_SUBJECTS = 'api/subjects.php';

    // Subject Manager modal elements
    const subjectManagerModal = document.getElementById('subjectManagerModal');
    const smName = document.getElementById('smName');
    const smCode = document.getElementById('smCode');
    const smAddBtn = document.getElementById('smAddBtn');
    const smRefreshBtn = document.getElementById('smRefreshBtn');
    const smList = document.getElementById('smList');
    const smSearch = document.getElementById('smSearch');
    const smCloseBtn = document.getElementById('smCloseBtn');
    const closeSubjectManagerBtn = document.getElementById('closeSubjectManagerBtn');
    const modalTitle = document.getElementById('modalTitle');

    // Assignment controls
    // Modal strand/semester controls removed; rely on top-level selectors
    const smTopControls = null;
    const smTopStrand = null;
    const smTopStrandWrap = null;
    const smTopSem = null;
    const smAssignListBlock = document.getElementById('smAssignListBlock');
    const smAssignList = document.getElementById('smAssignList');
    const API_OFFERINGS = 'api/subject_offerings.php';

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

    function setGroup(g){ currentGroup = g; renderTabStyles(); populateLevels(); populateTrack(); updateSemVisibility(); updateGoState(); }

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
    if (selLevel) selLevel.addEventListener('change', updateGoState);
    if (selTrack) selTrack.addEventListener('change', updateGoState);
    if (selSem) selSem.addEventListener('change', updateGoState);

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
      loadSubjectManagerList();
      // Hide Assigned list section permanently
      const advanced = /^(Grade 11|Grade 12|1st Year|2nd Year|3rd Year|4th Year)$/i.test(currentManageLevel);
      if (smAssignListBlock) smAssignListBlock.classList.add('hidden');
    }

    function closeSubjectManager(){ subjectManagerModal.classList.add('hidden'); }

    async function loadSubjectManagerList(){
      try{
        if (!currentManageLevel){ smList.innerHTML = '<div class="p-4 text-gray-500 text-sm">Select a level.</div>'; return; }
        const isBasic = /^(Kinder\s*1|Kinder\s*2|Grade\s*(?:[1-9]|10))$/i.test(currentManageLevel);
        const q = smSearch ? (smSearch.value || '').trim().toLowerCase() : '';
        const buildUrl = (sem)=>{
          const params = new URLSearchParams({action:'list', grade_level: currentManageLevel, semester: sem});
          if (!isBasic){
            const track = selTrack ? (selTrack.value||'') : '';
            if (track) params.set('strand', track);
          }
          return `${API_OFFERINGS}?${params.toString()}`;
        };
        let items = [];
        if (isBasic){
          const [r1,r2] = await Promise.all([fetch(buildUrl('1st')), fetch(buildUrl('2nd'))]);
          const [t1,t2] = await Promise.all([r1.text(), r2.text()]);
          let d1={}, d2={};
          try{ d1 = JSON.parse(t1);}catch(e){ console.error('Offerings 1st resp:', t1); throw e; }
          try{ d2 = JSON.parse(t2);}catch(e){ console.error('Offerings 2nd resp:', t2); throw e; }
          const map = new Map();
          (d1.items||[]).forEach(o=> map.set(o.subject_id, o));
          (d2.items||[]).forEach(o=> map.set(o.subject_id, o));
          items = Array.from(map.values());
        } else {
          const semVal = selSem ? (selSem.value||'1st') : '1st';
          const r = await fetch(buildUrl(semVal));
          const txt = await r.text();
          let d={};
          try{ d = JSON.parse(txt);}catch(e){ console.error('Offerings resp:', txt); throw e; }
          items = d.items || [];
        }
        if (q){ items = items.filter(o=> (o.name||'').toLowerCase().includes(q) || (o.code||'').toLowerCase().includes(q)); }
        if (!items.length){ smList.innerHTML = '<div class="p-4 text-gray-500 text-sm">No subjects assigned for this selection.</div>'; return; }
        smList.innerHTML = items.map(o=>`
          <div class="p-3 flex items-center justify-between">
            <div>
              <div class="font-medium text-gray-800">${escapeHtml(o.name||'')}</div>
              <div class="text-xs text-gray-500">${o.code? escapeHtml(o.code):''}</div>
            </div>
            <div class="flex gap-2">
              <button class="px-3 py-1 text-sm rounded-lg bg-gray-200 hover:bg-gray-300" onclick='openSubjectModal({id:${o.subject_id}, code:${JSON.stringify(o.code||'')}, name:${JSON.stringify(o.name||'')}})'>Edit</button>
              <button class="px-3 py-1 text-sm rounded-lg bg-red-600 text-white hover:bg-red-700" onclick='smRemoveAssign(${o.id})'>Remove</button>
            </div>
          </div>`).join('');
      }catch(e){ console.error(e); smList.innerHTML = '<div class="p-4 text-red-600 text-sm">Failed to load assigned subjects.</div>'; }
    }

    async function smAdd(){
      const name = (smName.value||'').trim();
      const code = (smCode.value||'').trim();
      if(!name) return toast('Enter subject name', false);
      try{
        const res = await fetch(API_SUBJECTS,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'create', name, code})});
        const d = await res.json();
        if(!d.success){ toast(d.message||'Failed to add subject', false); return; }
        // Auto-assign to the current selection so it appears in Manage Grades
        try{
          const lvl = String(currentManageLevel||'');
          if (d.id && lvl){
            const isBasic = /^(Kinder\s*1|Kinder\s*2|Grade\s*(?:[1-9]|10))$/i.test(lvl);
            if (isBasic){
              const p1 = fetch(API_OFFERINGS,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'assign', subject_id:d.id, grade_level:lvl, strand:null, semester:'1st', sy:null})});
              const p2 = fetch(API_OFFERINGS,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'assign', subject_id:d.id, grade_level:lvl, strand:null, semester:'2nd', sy:null})});
              await Promise.all([p1,p2]);
            } else {
              const strandVal = selTrack ? (selTrack.value||null) : null;
              const semVal = selSem ? (selSem.value||'1st') : '1st';
              await fetch(API_OFFERINGS,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'assign', subject_id:d.id, grade_level:lvl, strand: strandVal, semester: semVal, sy:null})});
            }
          }
        }catch(e){ console.error('Auto-assign failed', e); }
        toast('Subject added');
        smName.value=''; smCode.value='';
        loadSubjectManagerList();
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
    if (smRefreshBtn) smRefreshBtn.addEventListener('click', loadSubjectManagerList);
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

    function toast(msg, ok=true){
      const t = document.getElementById('toast');
      t.textContent = msg; t.className = `fixed top-4 right-4 ${ok? 'bg-emerald-600':'bg-red-600'} text-white px-4 py-2 rounded-lg`;
      t.classList.remove('hidden');
      setTimeout(()=>t.classList.add('hidden'), 2000);
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
      try{
        const res = await fetch(API_OFFERINGS,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'remove', id})});
        const d = await res.json();
        if (d.success){ toast('Removed'); loadAssignList(); }
        else toast(d.message||'Failed', false);
      }catch(e){ console.error(e); toast('Error', false); }
    }

    // React to top-level controls changes
    if (selTrack) selTrack.addEventListener('change', loadAssignList);
    if (selSem) selSem.addEventListener('change', loadAssignList);

    function openSubjectModal(data){
      subjectModal.classList.remove('hidden');
      subError.classList.add('hidden');
      if(data){
        editingId = data.id; subjectModalTitle.textContent = 'Edit Subject';
        subCode.value = data.code; subName.value = data.name;
      } else {
        editingId = null; subjectModalTitle.textContent = 'Add Subject';
        subCode.value=''; subName.value='';
      }
    }
    function closeSubjectModal(){ subjectModal.classList.add('hidden'); }
    addSubjectBtn.onclick = ()=>openSubjectModal();

    saveSubjectBtn.onclick = async ()=>{
      const code = subCode.value.trim();
      const name = subName.value.trim();
      if(!name){ subError.textContent='Name is required'; subError.classList.remove('hidden'); return; }
      const payload = editingId ? {action:'update', id: editingId, code, name} : {action:'create', code, name};
      const res = await fetch(apiSubjects,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)});
      const d = await res.json();
      if(d.success){ closeSubjectModal(); loadSubjects(); toast(d.message); refreshAssignChoices(); if(isFilterReady()) loadOfferings(); }
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
      if(!confirm('Delete this subject?')) return;
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
      if(!confirm('Remove this subject from the selected offering?')) return;
      const res = await fetch(apiOfferings,{method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({action:'remove', id})});
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
    })();

    // init
    loadSubjects();
  </script>
</body>
</html>
