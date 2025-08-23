<?php
session_start();

// redirect if not guidance
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'guidance') {
    header("Location: ../StudentLogin/login.php");
    exit;
}

// prevent caching (so back button after logout doesn’t show dashboard)
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
  <style>
    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      20%, 60% { transform: translateX(-5px); }
      40%, 80% { transform: translateX(5px); }
    }
    .shake {
      animation: shake 0.3s;
    }
  </style>
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
            <!-- ✅ make sure logout.php is inside the same folder as this file -->
            <a href="logout.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Logout</a>
          </div>
      </div>
    </div>

    <!-- Search Bar -->
    <div class="flex gap-2 items-center relative">
      <input
        type="text"
        id="searchInput"
        placeholder="Search by name or ID..."
        class="flex-grow border border-gray-300 rounded px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black"
        onkeydown="if(event.key==='Enter'){ event.preventDefault(); searchStudent(); }"
        aria-describedby="searchError"
      />
      <button id="searchBtn" onclick="searchStudent()" class="bg-black text-white px-4 py-2 rounded text-sm transition-colors duration-300">Search</button>
    </div>
    <p id="searchError" class="text-red-600 text-sm mt-1 min-h-[1.25rem]"></p>
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
      <select id="violationType" class="w-full border px-2 py-1 rounded text-sm">
        <option>Dress Code Violation</option>
        <option>Late Arrival</option>
        <option>Misconduct</option>
      </select>
    </div>

    <button onclick="saveViolation()" class="bg-black text-white px-4 py-2 rounded text-sm">Save</button>
  </div>

<script>
  // ===== BURGER MENU =====
  const burgerBtn = document.getElementById("burgerBtn");
  const burgerMenu = document.getElementById("burgerMenu");

  burgerBtn.addEventListener("click", () => {
    burgerMenu.classList.toggle("hidden");
  });

  document.addEventListener("click", (e) => {
    if (!burgerBtn.contains(e.target) && !burgerMenu.contains(e.target)) {
      burgerMenu.classList.add("hidden");
    }
  });

  // ===== PREVENT BACK BUTTON AFTER LOGOUT =====
  window.addEventListener("pageshow", function (event) {
    if (event.persisted || (performance.navigation.type === 2)) {
      window.location.reload();
    }
  });

  // ===== STUDENT RECORD SYSTEM =====
  let students = [];
  let selectedStudentIndex = null;

  const searchBtn = document.getElementById('searchBtn');
  const searchError = document.getElementById('searchError');

  function renderStudents() {
    const list = document.getElementById('studentList');
    if (students.length === 0) {
      list.innerHTML = '<p class="text-center text-gray-500">No student data loaded. Tap RFID card or search to load a student.</p>';
      return;
    }
    list.innerHTML = students.map((student, i) => `
      <div class="flex gap-4 p-4 border rounded-md shadow bg-white student-card" data-index="${i}">
        <div class="w-1/3 text-center border-r pr-4">
          <div class="text-3xl">👤</div>
          <p class="text-sm mt-2">ID: ${student.id}</p>
          <p class="text-sm">Name: ${student.name}</p>
          <p class="text-sm">Program: ${student.program}</p>
          <p class="text-sm">Year & Section: ${student.section}</p>
        </div>
        <div class="w-2/3 pl-4">
          <h3 class="font-semibold text-md">Student Guidance Record</h3>
          <ul class="mt-2 list-disc list-inside text-sm">
            ${student.violations.length ? student.violations.map(v => `<li>${v}</li>`).join('') : '<li>No records yet</li>'}
          </ul>
          <button onclick="openForm(${i})" class="mt-4 bg-black text-white py-1 px-4 rounded text-sm">Add Student Violation</button>
        </div>
      </div>
    `).join('');
  }

  function openForm(i) {
    selectedStudentIndex = i;
    document.getElementById('formView').classList.remove('hidden');
    document.getElementById('studentList').classList.add('hidden');
  }
  function closeForm() {
    selectedStudentIndex = null;
    document.getElementById('formView').classList.add('hidden');
    document.getElementById('studentList').classList.remove('hidden');
  }

  function saveViolation() {
    const date = document.getElementById('violationDate').value;
    const type = document.getElementById('violationType').value;
    if (!date || !type) return alert("Fill all fields");

    if (selectedStudentIndex === null) {
      alert("No student selected");
      return;
    }

    const studentId = students[selectedStudentIndex].id;

    const formData = new FormData();
    formData.append('id_number', studentId);
    formData.append('violation_date', date);
    formData.append('violation_type', type);

    fetch('AddViolation.php', {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.error) {
        alert(data.error);
        return;
      }
      const newViolation = `${type} on ${new Date(date).toLocaleDateString('en-GB')}`;
      students[selectedStudentIndex].violations.push(newViolation);

      document.getElementById('violationDate').value = '';
      document.getElementById('violationType').selectedIndex = 0;

      closeForm();
      renderStudents();
    })
    .catch(() => {
      alert("Failed to save violation");
    });
  }

  function clearError() {
    searchError.textContent = '';
    searchBtn.classList.remove('bg-red-600', 'shake');
    searchBtn.classList.add('bg-black');
  }

  function showError(msg) {
    searchError.textContent = msg;
    searchBtn.classList.remove('bg-black');
    searchBtn.classList.add('bg-red-600', 'shake');
    setTimeout(() => {
      searchBtn.classList.remove('shake');
      searchBtn.classList.add('bg-black');
    }, 600);
  }

  function searchStudent() {
    clearError();
    const query = document.getElementById('searchInput').value.trim();
    if (!query) {
      showError('Please enter a name or ID to search');
      return;
    }

    fetch(`SearchStudent.php?query=${encodeURIComponent(query)}`)
      .then(res => res.json())
      .then(data => {
        if (data.error) {
          alert(data.error);
          return;
        }

        if (!data.students || data.students.length === 0) {
          students = [];
          renderStudents();
          searchError.textContent = `No students found for "${query}".`;
          return;
        }

        students = data.students.map(s => ({
          id: s.id_number,
          name: s.full_name,
          program: s.program,
          section: s.year_section,
          violations: s.guidance_records.map(r => `${r.remarks.trim()} (${new Date(r.record_date).toLocaleDateString()})`)
        }));

        renderStudents();
      })
      .catch(() => {
        alert("Failed to fetch student data");
      });
  }

  // ===== RFID input listener =====
  let buffer = '';
  let timer;
  window.addEventListener('keydown', e => {
    if (timer) clearTimeout(timer);
    if (e.key === 'Enter') {
      if (buffer) loadStudentData(buffer.trim());
      buffer = '';
    } else if (/[\w\d]/.test(e.key)) {
      buffer += e.key;
    }
    timer = setTimeout(() => buffer = '', 500);
  });

  function loadStudentData(id) {
    clearError();
    fetch(`GetRecord.php?rfid_uid=${encodeURIComponent(id)}`)
      .then(r => r.json())
      .then(data => {
        if (data.error) {
          searchError.textContent = data.error;
          return;
        }
        students = [{
          id: data.student.id_number,
          name: data.student.full_name,
          program: data.student.program,
          section: data.student.year_section,
          violations: data.guidance_records.map(r => `${r.remarks.trim()} (${new Date(r.record_date).toLocaleDateString()})`)
        }];
        renderStudents();
      })
      .catch(() => {
        searchError.textContent = "Failed to load student data";
      });
  }

  renderStudents();
</script>

</body>
</html>
