<?php
session_start();

// ✅ Redirect if not guidance
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'guidance') {
    header("Location: ../StudentLogin/login.php");
    exit;
}

// ✅ Prevent caching (so back button after logout doesn’t show dashboard)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Guidance Dashboard - Cornerstone College Inc.</title>
  <link rel="icon" href="../images/LogoCCI.png" type="image/png">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .school-gradient { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #1e40af 100%); }
    .card-shadow { box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
  </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen font-sans">

<!-- Header with School Branding -->
<header class="bg-[#0B2C62] text-white shadow-lg">
  <div class="container mx-auto px-6 py-4">
    <div class="flex justify-between items-center">
      <div class="flex items-center space-x-4">
        <img src="../images/LogoCCI.png" alt="Cornerstone College Inc." class="h-12 w-12 rounded-full bg-white p-1">
        <div>
          <h1 class="text-xl font-bold">Cornerstone College Inc.</h1>
          <p class="text-blue-200 text-sm">Guidance Portal</p>
        </div>
      </div>
      
      <div class="flex items-center space-x-4">
        <div class="text-right">
          <p class="text-sm text-blue-200">Welcome,</p>
          <p class="font-semibold"><?= htmlspecialchars($_SESSION['guidance_name'] ?? 'Guidance Counselor') ?></p>
        </div>
        <div class="relative z-50">
          <button id="burgerBtn" class="bg-white bg-opacity-20 hover:bg-opacity-30 p-2 rounded-lg transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
          </button>
          <div id="burgerMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg z-50 text-gray-800">
            <a href="logout.php" class="block px-4 py-3 hover:bg-gray-100 rounded-lg">
              <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
              </svg>
              Logout
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</header>

<!-- Search Bar -->
<div class="bg-white shadow-sm border-b">
  <div class="container mx-auto px-6 py-4">
    <div class="flex gap-4 items-center">
      <div class="relative flex-1 max-w-md">
        <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
          <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </span>
        <input
          type="text"
          id="searchInput"
          placeholder="Search students by name or ID..."
          autocomplete="off"
          class="w-full border border-gray-300 rounded-xl pl-10 pr-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
          aria-describedby="searchError"
        />
      </div>
      <button onclick="searchStudents()" class="bg-[#0B2C62] hover:bg-blue-900 text-white px-6 py-3 rounded-xl font-medium transition-colors">
        Search
      </button>
      <p id="searchError" class="text-red-600 text-sm"></p>
    </div>
  </div>
</div>


<!-- Success Notification -->
<div id="notif" class="fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg hidden z-50">
  <div class="flex items-center space-x-2">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
    </svg>
    <span>Saved successfully</span>
  </div>
</div>

<!-- Main Content -->
<div class="container mx-auto px-6 py-8">
  <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
    
    <!-- Guidance Profile -->
    <div class="lg:col-span-1">
      <div class="bg-white rounded-2xl card-shadow p-6">
        <div class="text-center">
          <div class="w-20 h-20 mx-auto bg-gray-400 rounded-full flex items-center justify-center mb-4">
            <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
            </svg>
          </div>
          <h3 class="text-lg font-bold text-gray-800"><?= htmlspecialchars($_SESSION['guidance_name'] ?? 'Guidance Counselor') ?></h3>
          <p class="text-gray-600 text-sm">ID: <?= htmlspecialchars($_SESSION['id_number'] ?? 'N/A') ?></p>
        </div>
        
        <div class="mt-6 pt-6 border-t">
          <div class="space-y-3 text-sm">
            <div>
              <span class="text-gray-500 font-medium">Role:</span>
              <p class="text-gray-800">Guidance Counselor</p>
            </div>
            <div>
              <span class="text-gray-500 font-medium">Department:</span>
              <p class="text-gray-800">Student Affairs</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Student List -->
    <div class="lg:col-span-3">
      <div id="studentList" class="space-y-6">
        <div class="bg-white rounded-2xl card-shadow p-8 text-center text-gray-500">
          <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
          </svg>
          <p class="text-sm italic">Scan RFID card or search to load student guidance records</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Violation Form Modal -->
  <div id="formView" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl card-shadow p-8 max-w-lg w-full mx-4">
      <div class="flex items-center mb-6">
        <button onclick="closeForm()" class="mr-4 p-2 hover:bg-gray-100 rounded-lg transition">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
          </svg>
        </button>
        <h3 class="text-xl font-bold text-gray-800">Add Violation Record</h3>
      </div>

      <div class="space-y-4">
        <div>
          <label class="block mb-2 text-sm font-medium text-gray-700">Date of Violation:</label>
          <input type="date" id="violationDate" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
        </div>

        <div>
          <label class="block mb-2 text-sm font-medium text-gray-700">Violation Type:</label>
          <select id="violationType" class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"></select>
        </div>

        <button onclick="saveViolation()" class="w-full bg-[#0B2C62] hover:bg-blue-900 text-white py-3 rounded-xl font-medium transition-colors">
          Save Violation Record
        </button>
      </div>
    </div>
  </div>
</div>
<script>
  // ===== BURGER MENU =====
  const burgerBtn = document.getElementById("burgerBtn");
  const burgerMenu = document.getElementById("burgerMenu");

  burgerBtn.addEventListener("click", () => burgerMenu.classList.toggle("hidden"));
  document.addEventListener("click", (e) => {
    if (!burgerBtn.contains(e.target) && !burgerMenu.contains(e.target)) burgerMenu.classList.add("hidden");
  });

  // ===== PREVENT BACK BUTTON AFTER LOGOUT =====
  window.addEventListener("pageshow", function(event) {
    if (event.persisted || (performance.navigation.type === 2)) window.location.reload();
  });

  // ===== STUDENT RECORD SYSTEM =====
  let students = [];
  let selectedStudentIndex = null;
  const searchInput = document.getElementById('searchInput');
  const searchError = document.getElementById('searchError');
  const violationTypeSelect = document.getElementById('violationType');
  const notif = document.getElementById("notif"); // ⭐ Notification div

  // Violation master list
  const violationOptions = [
    "Dress Code Violation",
    "Late Arrival",
    "Misconduct"
  ];

  function renderStudents() {
    const list = document.getElementById('studentList');
    if (!students.length) {
      list.innerHTML = `
        <div class="bg-white rounded-2xl card-shadow p-8 text-center text-gray-500">
          <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
          </svg>
          <p class="text-sm italic">Scan RFID card or search to load student guidance records</p>
        </div>
      `;
      return;
    }
    list.innerHTML = students.map((student, i) => {
      const inEdit = student.editMode || false;
      const initials = student.name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
      return `
      <div class="bg-white rounded-2xl card-shadow p-6 student-card" data-index="${i}">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Student Profile -->
          <div class="text-center lg:border-r lg:pr-6">
            <div class="w-20 h-20 mx-auto bg-gray-400 rounded-full flex items-center justify-center mb-4">
              <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
              </svg>
            </div>
            <h4 class="font-bold text-lg text-gray-800 mb-2">${student.name}</h4>
            <div class="space-y-1 text-sm text-gray-600">
              <p><span class="font-medium">ID:</span> ${student.id}</p>
              <p><span class="font-medium">Program:</span> ${student.program}</p>
              <p><span class="font-medium">Year & Section:</span> ${student.section}</p>
            </div>
          </div>
          
          <!-- Guidance Records -->
          <div class="lg:col-span-2">
            <div class="flex justify-between items-center mb-4">
              <h3 class="font-semibold text-lg text-gray-800 flex items-center gap-2">
                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Guidance Records
              </h3>
              <button onclick="toggleEdit(${i})" class="text-blue-600 hover:text-blue-700 text-sm font-medium transition-colors">
                ${inEdit ? "Cancel" : "Edit Records"}
              </button>
            </div>
            
            <div class="bg-gray-50 rounded-lg p-4 mb-4 min-h-[120px]">
              ${inEdit ? renderEditableViolations(student, i) : renderReadOnlyViolations(student)}
            </div>
            
            <div class="flex gap-3">
              ${inEdit ? `
                <button onclick="deleteSelected(${i})" class="bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors">
                  Delete Selected
                </button>
                <button onclick="saveEdited(${i})" class="bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors">
                  Save Changes
                </button>
              ` : `
                <button onclick="openForm(${i})" class="bg-[#0B2C62] hover:bg-blue-900 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors">
                  Add Violation Record
                </button>
              `}
            </div>
          </div>
        </div>
      </div>
    `;
    }).join('');
  }

  function renderReadOnlyViolations(student) {
    if (!student.violations.length) {
      return `
        <div class="text-center py-4">
          <svg class="w-12 h-12 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <p class="text-gray-500 text-sm">No violation records found</p>
        </div>
      `;
    }
    
    return `
      <div class="space-y-2">
        ${student.violations.map(v => `
          <div class="bg-white rounded-lg p-3 border border-gray-200 shadow-sm">
            <p class="text-sm text-gray-700">${v}</p>
          </div>
        `).join('')}
      </div>
    `;
  }

  function renderEditableViolations(student, i) {
    if (!student.violations.length) {
      return `
        <div class="text-center py-4">
          <p class="text-gray-500 text-sm">No records to edit</p>
        </div>
      `;
    }
    return `
      <div class="space-y-2">
        ${student.violations.map((v, idx) => `
          <label class="flex items-center gap-3 bg-white rounded-lg p-3 border hover:bg-blue-50 transition-colors cursor-pointer">
            <input type="checkbox" data-violation-index="${idx}" class="violation-checkbox-${i} w-4 h-4 text-blue-600 rounded focus:ring-blue-500" />
            <span class="text-sm text-gray-700">${v}</span>
          </label>
        `).join('')}
      </div>
    `;
  }

  function toggleEdit(i) {
    students[i].editMode = !students[i].editMode;
    renderStudents();
  }

  function deleteSelected(i) {
    const checkboxes = document.querySelectorAll(`.violation-checkbox-${i}:checked`);
    const indexes = Array.from(checkboxes).map(cb => parseInt(cb.dataset.violationIndex));
    students[i].violations = students[i].violations.filter((_, idx) => !indexes.includes(idx));
    renderStudents();
    students[i].editMode = true; // stay in edit mode
  }

  function saveEdited(i) {
    const student = students[i];
    const studentId = student.id;

    const formData = new FormData();
    formData.append("id_number", studentId);
    formData.append("violations", JSON.stringify(student.violations));

    fetch("UpdateViolations.php", { method: "POST", body: formData })
    .then(res => res.json())
    .then(data => {
      if (data.error) {
        alert("Server error: " + data.error);
        return;
      }

      // ⭐ Show notification
      notif.classList.remove("hidden");
      setTimeout(() => notif.classList.add("hidden"), 2000);

      // Exit edit mode
      student.editMode = false;
      renderStudents();
    })
    .catch(err => alert("Fetch error: " + err));
  }

  function openForm(i) {
    selectedStudentIndex = i;
    updateViolationDropdown();

    const today = new Date().toISOString().split("T")[0];
    document.getElementById("violationDate").value = today;

    document.getElementById('formView').classList.remove('hidden');
  }

  function closeForm() {
    selectedStudentIndex = null;
    document.getElementById('formView').classList.add('hidden');
  }

  // ===== ADD WARNING LEVEL =====
  function getWarningLevel(student, violationType) {
    const count = student.violations.filter(v => v.startsWith(violationType)).length;
    if (count === 0) return "1st Offense";
    if (count === 1) return "2nd Offense";
    if (count === 2) return "3rd Offense";
    return (count + 1) + "th Offense";
  }

  function updateViolationDropdown() {
    violationTypeSelect.innerHTML = "";
    if (selectedStudentIndex === null) return;
    const student = students[selectedStudentIndex];

    violationOptions.forEach(type => {
      const warning = getWarningLevel(student, type);
      const option = document.createElement("option");
      option.value = type;
      option.textContent = `${type} - ${warning}`;
      violationTypeSelect.appendChild(option);
    });
  }

  function saveViolation() {
    const date = document.getElementById('violationDate').value;
    const type = violationTypeSelect.value;
    if (!date || !type) return alert("Fill all fields");
    if (selectedStudentIndex === null) return alert("No student selected");

    const student = students[selectedStudentIndex];
    const studentId = student.id;

    const formData = new FormData();
    formData.append('id_number', studentId);
    formData.append('violation_date', date);
    formData.append('violation_type', type);

    fetch('AddViolation.php', { method: 'POST', body: formData })
      .then(res => res.json())
      .then(data => {
        if (data.error) return alert(data.error);

        const cleanType = type.split(" - ")[0];
        const warning = getWarningLevel(student, cleanType);
        const newViolation = `${cleanType} - ${warning} (${new Date(date).toLocaleDateString('en-US', { 
            year: 'numeric', month: 'long', day: 'numeric' 
          })})`;

        student.violations.push(newViolation);
        updateViolationDropdown();
        closeForm();
        renderStudents();
      })
      .catch(() => alert("Failed to save violation"));
  }

  function clearError() { searchError.textContent = ''; }
  function showError(msg) { searchError.textContent = msg; }

  function renderSearchResults(list) {
    students = list.map(s => ({
      id: s.id_number,
      name: s.full_name,
      program: s.program,
      section: s.year_section,
      violations: s.guidance_records.map(r => `${r.remarks.trim()} (${new Date(r.record_date).toLocaleDateString()})`),
      editMode: false
    }));
    renderStudents();
  }

  // ===== LIVE SEARCH =====
  let searchTimeout;
  searchInput.addEventListener('input', () => {
    const query = searchInput.value.trim();
    clearError();
    clearTimeout(searchTimeout);
    if (!query.length) {
      students = [];
      renderStudents();
      return;
    }
    searchTimeout = setTimeout(() => fetchStudents(query), 300);
  });

  function searchStudents() {
    const query = searchInput.value.trim();
    if (!query) { showError('Please enter a name or ID to search'); return; }
    fetchStudents(query);
  }

  function fetchStudents(query) {
    clearError();
    loadStudentData(query);
  }

  // ===== RFID input listener =====
  let buffer = '', timer;
  window.addEventListener('keydown', e => {
    if (timer) clearTimeout(timer);
    if (e.key === 'Enter') {
      if (buffer) loadStudentData(buffer.trim());
      buffer = '';
    } else if (/[\w\d]/.test(e.key)) buffer += e.key;
    timer = setTimeout(() => buffer = '', 500);
  });

  function loadStudentData(id) {
    clearError();
    fetch(`GetRecord.php?rfid_uid=${encodeURIComponent(id)}`)
      .then(r => r.json())
      .then(data => {
        if (data.error) {
          fetch(`SearchStudent.php?query=${encodeURIComponent(id)}`)
            .then(res => res.json())
            .then(data => {
              if (data.error || !data.students || !data.students.length) {
                students = []; renderStudents(); showError(`No student found for "${id}"`); return;
              }
              renderSearchResults(data.students);
            })
            .catch(() => { students = []; renderStudents(); showError("Failed to fetch student data"); });
          return;
        }
        students = [{
          id: data.student.id_number,
          name: data.student.full_name,
          program: data.student.program,
          section: data.student.year_section,
          violations: data.guidance_records.map(r => `${r.remarks.trim()} (${new Date(r.record_date).toLocaleDateString()})`),
          editMode: false
        }];
        renderStudents();
      })
      .catch(() => { students = []; renderStudents(); showError("Failed to load student data"); });
  }

  renderStudents();
</script>

</body>
</html>
