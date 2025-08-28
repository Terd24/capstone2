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
  <title>Guidance Record System</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800 min-h-screen p-6">

<div class="max-w-4xl mx-auto mb-6">
  <!-- Header -->
  <div class="flex items-center justify-between mb-4 relative">
    <h2 class="text-2xl font-semibold">Guidance Dashboard</h2>

    <!-- Burger Menu -->
    <div class="relative z-50">
      <button id="burgerBtn" class="text-2xl focus:outline-none">&#9776;</button>
      <div id="burgerMenu" 
           class="hidden absolute right-0 mt-2 w-40 bg-white border rounded shadow-lg z-50">
        <a href="logout.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Logout</a>
      </div>
    </div>
  </div>

  <!-- Search Bar -->
  <div class="flex gap-1 items-center relative">
    <input
      type="text"
      id="searchInput"
      placeholder="Search by name or ID..."
      autocomplete="off"
      class="flex-grow border border-gray-300 rounded px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black"
      aria-describedby="searchError"
    />
  </div>
  <p id="searchError" class="text-red-600 text-sm mt-1 min-h-[1.25rem]"></p>
</div>


<!--Notification-->
<div id="notif" class="bg-green-400 text-white px-3 py-1 rounded shadow hidden mt-1 w-fit" style="margin-left: 1034px;">
  Saved successfully
</div>

<!-- Student List -->
<div id="studentList" class="max-w-4xl mx-auto space-y-4">
  <p class="text-center text-gray-500">No student data loaded. Tap RFID card or search to load a student.</p>
</div>

<!-- Violation Form -->
<div id="formView" class="hidden max-w-md mx-auto mt-10 p-6 border rounded-md shadow bg-white">
  <button onclick="closeForm()" class="mb-4 text-2xl">&#8592;</button>

  <div class="mb-4">
    <label class="block mb-1 text-sm">Date of Violation:</label>
    <input type="date" id="violationDate" class="w-full border px-2 py-1 rounded text-sm" />
  </div>

  <div class="mb-4">
    <label class="block mb-1 text-sm">Violation Type:</label>
    <select id="violationType" class="w-full border px-2 py-1 rounded text-sm"></select>
  </div>

  <button onclick="saveViolation()" class="bg-black text-white px-4 py-2 rounded text-sm">Save</button>
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
      list.innerHTML = '<p class="text-center text-gray-500">No student data loaded. Tap RFID card or search to load a student.</p>';
      return;
    }
    list.innerHTML = students.map((student, i) => {
      const inEdit = student.editMode || false;
      return `
      <div class="flex gap-4 p-4 border rounded-md shadow bg-white student-card" data-index="${i}">
        <div class="w-1/3 text-center border-r pr-4">
          <div class="text-3xl">👤</div>
          <p class="text-sm mt-2">Name: ${student.name}</p>
          <p class="text-sm">ID: ${student.id}</p>
          <p class="text-sm">Program: ${student.program}</p>
          <p class="text-sm">Year & Section: ${student.section}</p>
        </div>
        <div class="w-2/3 pl-4">
          <div class="flex justify-between items-center">
            <h3 class="font-semibold text-md">Student Guidance Record</h3>
            <button onclick="toggleEdit(${i})" class="text-blue-600 text-sm">${inEdit ? "Cancel" : "Edit"}</button>
          </div>
          <div class="mt-2 text-sm">
            ${inEdit ? renderEditableViolations(student, i) : renderReadOnlyViolations(student)}
          </div>
          <div class="mt-4 flex gap-2">
            ${inEdit ? `
              <button onclick="deleteSelected(${i})" class="bg-red-600 text-white py-1 px-3 rounded text-sm">Delete</button>
              <button onclick="saveEdited(${i})" class="bg-green-600 text-white py-1 px-3 rounded text-sm">Save</button>
            ` : `
              <button onclick="openForm(${i})" class="bg-black text-white py-1 px-4 rounded text-sm">Add Student Violation</button>
            `}
          </div>
        </div>
      </div>
    `;
    }).join('');
  }

  function renderReadOnlyViolations(student) {
    return `
      <ul class="list-disc list-inside">
        ${student.violations.length ? student.violations.map(v => `<li>${v}</li>`).join('') : '<li>No records yet</li>'}
      </ul>`;
  }

  function renderEditableViolations(student, i) {
    if (!student.violations.length) return "<p>No records yet</p>";
    return student.violations.map((v, idx) => `
      <label class="flex items-center gap-2">
        <input type="checkbox" data-violation-index="${idx}" class="violation-checkbox-${i}" />
        <span>${v}</span>
      </label>
    `).join('');
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
    document.getElementById('studentList').classList.add('hidden');
  }

  function closeForm() {
    selectedStudentIndex = null;
    document.getElementById('formView').classList.add('hidden');
    document.getElementById('studentList').classList.remove('hidden');
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

  const searchBtn = document.createElement('button');
  searchBtn.textContent = 'Search';
  searchBtn.className = 'ml-2 bg-black text-white px-4 py-2 rounded text-sm';
  searchInput.parentNode.appendChild(searchBtn);

  searchBtn.addEventListener('click', () => {
    const query = searchInput.value.trim();
    if (!query) { showError('Please enter a name or ID to search'); return; }
    fetchStudents(query);
  });

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
