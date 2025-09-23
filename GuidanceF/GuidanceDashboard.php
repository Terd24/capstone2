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
  <!-- QR Scanner Library -->
  <script src="https://unpkg.com/html5-qrcode" defer></script>
  <style>
    .school-gradient { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #1e40af 100%); }
    .card-shadow { box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
    .record-item:hover { transform: translateY(-2px); transition: all 0.3s ease; box-shadow: 0 8px 25px rgba(11, 44, 98, 0.15); }
    .record-item { cursor: pointer; transition: all 0.3s ease; border-left: 4px solid transparent; }
    .record-item:hover { border-left-color: #0B2C62; }
    /* Mirror video only when class is applied */
    .mirror-video video { transform: scaleX(-1); -webkit-transform: scaleX(-1); }
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
      <button onclick="showViolationTypeModal()" 
              class="bg-[#0B2C62] hover:bg-blue-900 text-white px-6 py-3 rounded-xl font-medium transition-colors">
        Manage Violations
      </button>
      <button id="scanQrBtn"
              onclick="showQRScanner()"
              class="inline-flex items-center gap-2 bg-[#0B2C62] hover:bg-blue-900 text-white px-6 py-3 rounded-xl font-medium transition-colors"
              aria-label="Scan QR">
        <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <path d="M6 4h-1a3 3 0 0 0-3 3v1" />
          <path d="M18 4h1a3 3 0 0 1 3 3v1" />
          <path d="M2 16v1a3 3 0 0 0 3 3h1" />
          <path d="M22 16v1a3 3 0 0 1-3 3h-1" />
          <rect x="3" y="11" width="18" height="2" rx="1" fill="currentColor" stroke="none" />
        </svg>
        <span>Scan</span>
      </button>
      <p id="searchError" class="text-red-600 text-sm"></p>
    </div>
  </div>
</div>

<!-- QR Scanner Modal -->
<div id="qrScannerModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[70]">
  <div class="bg-white rounded-2xl p-6 w-full max-w-md mx-4">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-bold text-gray-800">Scan Student QR</h3>
      <button onclick="closeQRScanner()" class="text-gray-500 hover:text-gray-700" aria-label="Close">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
      </button>
    </div>
    <div id="qrReader" class="w-full"></div>
    <!-- Inline error message shown for invalid scans -->
    <p id="qrInlineError" class="mt-2 text-red-600 text-sm hidden"></p>
    <p id="qrStatus" class="mt-3 text-sm text-gray-600">Point your camera at the student's RFID QR code.</p>
    <div class="flex gap-3 pt-4">
      <button onclick="switchQRScannerCamera()" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 rounded-lg">Switch Camera</button>
      <button onclick="closeQRScanner()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2 rounded-lg">Close</button>
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

<!-- Manage Violations Modal -->
<div id="violationTypeModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
  <div class="bg-white rounded-2xl p-8 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
    <div class="flex items-center justify-between mb-6">
      <h3 class="text-xl font-bold text-gray-800">Manage Violation Types</h3>
      <button onclick="closeViolationTypeModal()" class="text-gray-400 hover:text-gray-600">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
      </button>
    </div>
    
    <!-- Add/Edit Form -->
    <div class="bg-gray-50 rounded-xl p-6 mb-6">
      <h4 class="text-lg font-semibold mb-4" id="violationFormTitle">Add New Violation Type</h4>
      <form id="violationTypeForm" class="space-y-4">
        <input type="hidden" id="violationTypeId" value="">
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Violation Name</label>
          <input type="text" id="violationTypeName" 
                 class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                 placeholder="Enter violation name" required>
        </div>
        
        
        <div class="flex space-x-3">
          <button type="submit" class="bg-[#0B2C62] hover:bg-blue-900 text-white px-6 py-2 rounded-lg font-medium transition-colors">
            <span id="violationSubmitText">Add Violation Type</span>
          </button>
          <button type="button" onclick="resetViolationForm()" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg font-medium transition-colors">
            Cancel
          </button>
        </div>
      </form>
    </div>
    
    <!-- Violation Types List -->
    <div>
      <h4 class="text-lg font-semibold mb-4">Current Violation Types</h4>
      <div id="violationTypesList" class="space-y-3">
        <!-- Violation types will be loaded here -->
      </div>
    </div>
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

  <!-- Record Detail Modal -->
  <div id="recordModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden p-4">
    <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden border-2 border-[#0B2C62] transform transition-all scale-100">
      
      <!-- Header -->
      <div class="bg-[#0B2C62] text-white px-6 py-4">
        <div class="flex items-center justify-between">
          <h2 class="text-lg font-semibold">Guidance Record Details</h2>
          <div class="flex items-center space-x-2">
            <button onclick="confirmDeleteRecord()" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-lg text-sm font-medium transition">
              Delete
            </button>
            <button onclick="closeRecordModal()" class="text-white hover:text-gray-300 transition">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Content -->
      <div class="px-6 py-6">
        <div class="space-y-4">
          <!-- Student Information -->
          <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
            <h3 class="text-sm font-semibold text-gray-600 mb-3">Student Information</h3>
            <div class="space-y-2">
              <div>
                <span class="text-sm text-gray-500">Name:</span>
                <span id="modalStudentName" class="text-sm font-medium text-gray-800 ml-2"></span>
              </div>
              <div>
                <span class="text-sm text-gray-500">ID:</span>
                <span id="modalStudentId" class="text-sm font-medium text-gray-800 ml-2 font-mono"></span>
              </div>
            </div>
          </div>
          
          <!-- Violation Details -->
          <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
            <h3 class="text-sm font-semibold text-gray-600 mb-3">Violation Details</h3>
            <p id="modalViolation" class="text-sm text-gray-800"></p>
          </div>
          
          <!-- Date -->
          <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
            <h3 class="text-sm font-semibold text-gray-600 mb-3">Date of Incident</h3>
            <p id="modalDate" class="text-sm text-gray-800"></p>
          </div>
        </div>
        
        <!-- Close Button -->
        <div class="flex justify-end mt-6 pt-4 border-t border-gray-200">
          <button onclick="closeRecordModal()" class="px-4 py-2 border border-[#0B2C62] text-[#0B2C62] rounded-lg hover:bg-[#0B2C62] hover:text-white transition">
            Close
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[60] hidden p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 transform transition-all">
      <div class="p-6">
        <!-- Warning Icon -->
        <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
          <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
          </svg>
        </div>
        
        <!-- Title and Message -->
        <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">Delete Guidance Record</h3>
        <p class="text-gray-600 text-center mb-6">Are you sure you want to delete this guidance record? This action cannot be undone.</p>
        
        <!-- Action Buttons -->
        <div class="flex gap-3 justify-end">
          <button onclick="closeDeleteModal()" class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
            Cancel
          </button>
          <button onclick="proceedDeleteRecord()" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
            Delete Record
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Delete Violation Type Confirmation Modal -->
<div id="deleteViolationModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[60] hidden p-4">
  <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 transform transition-all">
    <div class="p-6">
      <!-- Warning Icon -->
      <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full mb-4">
        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
        </svg>
      </div>
      
      <!-- Title and Message -->
      <h3 class="text-lg font-semibold text-gray-900 text-center mb-2">Delete Violation Type</h3>
      <p class="text-gray-600 text-center mb-6" id="deleteViolationMessage">Are you sure you want to delete this violation type? This action cannot be undone.</p>
      
      <!-- Action Buttons -->
      <div class="flex gap-3 justify-end">
        <button onclick="closeDeleteViolationModal()" class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition">
          Cancel
        </button>
        <button onclick="proceedDeleteViolationType()" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition">
          Delete
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
  const violationOptions = [];

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
              <p class="text-gray-500 text-sm italic">Click on records to view details</p>
            </div>
            
            <div class="bg-gray-50 rounded-lg p-4 mb-4 min-h-[120px]">
              ${renderReadOnlyViolations(student)}
            </div>
            
            <div class="flex gap-3">
              <button onclick="openForm(${i})" class="bg-[#0B2C62] hover:bg-blue-900 text-white py-2 px-4 rounded-lg text-sm font-medium transition-colors">
                Add Violation Record
              </button>
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
        ${student.violations.map((v, idx) => {
          const parts = v.split(' (');
          const violation = parts[0];
          const date = parts[1] ? parts[1].replace(')', '') : '';
          return `
            <div class="bg-white rounded-lg p-3 border border-gray-200 shadow-sm record-item" onclick="openRecordModal('${student.id}', '${student.name}', '${violation.replace(/'/g, "\\'")}'${date ? `, '${date}'` : ', \'\''})">
              <div class="flex items-center justify-between">
                <p class="text-sm text-gray-700">${v}</p>
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
              </div>
            </div>
          `;
        }).join('')}
      </div>
    `;
  }


  // Modal functions
  let currentRecord = null;

  function openRecordModal(studentId, studentName, violation, date) {
    currentRecord = { studentId, studentName, violation, date };
    
    // Populate modal
    document.getElementById('modalStudentName').textContent = studentName;
    document.getElementById('modalStudentId').textContent = studentId;
    document.getElementById('modalViolation').textContent = violation;
    document.getElementById('modalDate').textContent = date || 'Date not available';
    
    // Show modal
    document.getElementById('recordModal').classList.remove('hidden');
  }

  function closeRecordModal() {
    document.getElementById('recordModal').classList.add('hidden');
    currentRecord = null;
  }

  function confirmDeleteRecord() {
    if (!currentRecord) return;
    document.getElementById('deleteModal').classList.remove('hidden');
  }

  function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
  }

  function proceedDeleteRecord() {
    if (!currentRecord) return;
    
    // Find the student and remove the violation
    const student = students.find(s => s.id === currentRecord.studentId);
    if (student) {
      const violationToRemove = `${currentRecord.violation}${currentRecord.date ? ` (${currentRecord.date})` : ''}`;
      student.violations = student.violations.filter(v => v !== violationToRemove);
      
      // Update database
      const formData = new FormData();
      formData.append("id_number", currentRecord.studentId);
      formData.append("violations", JSON.stringify(student.violations));

      fetch("UpdateViolations.php", { method: "POST", body: formData })
      .then(res => res.json())
      .then(data => {
        if (data.error) {
          alert("Server error: " + data.error);
          return;
        }

        // Show notification
        notif.classList.remove("hidden");
        setTimeout(() => notif.classList.add("hidden"), 2000);

        // Refresh display
        renderStudents();
        closeDeleteModal();
        closeRecordModal();
      })
      .catch(err => alert("Fetch error: " + err));
    }
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

    // Load violations from database if not already loaded
    if (violationTypes.length === 0) {
      loadViolationTypes();
      return;
    }

    violationTypes.forEach(violationType => {
      const warning = getWarningLevel(student, violationType.violation_name);
      const option = document.createElement("option");
      option.value = violationType.violation_name;
      option.textContent = `${violationType.violation_name} - ${warning}`;
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
      violations: s.guidance_records.map(r => `${r.remarks.trim()} (${new Date(r.record_date).toLocaleDateString()})`)
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
          violations: data.guidance_records.map(r => `${r.remarks.trim()} (${new Date(r.record_date).toLocaleDateString()})`)
        }];
        renderStudents();
      })
      .catch(() => { students = []; renderStudents(); showError("Failed to load student data"); });
  }

  renderStudents();
  
  // Load violation types on page load
  loadViolationTypes();

  // ===== VIOLATION OPTIONS =====
  // Violation options now loaded from database via violationTypes array
  let violationTypes = [];
  
  function showViolationTypeModal() {
    document.getElementById('violationTypeModal').classList.remove('hidden');
    loadViolationTypes();
  }
  
  // ===== QR SCANNER (html5-qrcode) FOR GUIDANCE =====
  let qrScanner = null;
  let qrCameraIds = [];
  let qrCameraIndex = 0;
  let qrStarting = false;

  function ensureHtml5QrcodeReady() {
    return new Promise((resolve) => {
      if (window.Html5Qrcode) return resolve();
      const check = setInterval(() => { if (window.Html5Qrcode) { clearInterval(check); resolve(); } }, 100);
    });
  }

  // Show/hide inline error just below the camera preview
  function setQRInlineError(message) {
    const el = document.getElementById('qrInlineError');
    if (!el) return;
    if (message) { el.textContent = message; el.classList.remove('hidden'); }
    else { el.textContent = ''; el.classList.add('hidden'); }
  }

  // Mirror preview only for front/user cameras
  function setMirrorFromCurrentStream() {
    try {
      const readerEl = document.getElementById('qrReader');
      const video = readerEl ? readerEl.querySelector('video') : null;
      const stream = video && video.srcObject ? video.srcObject : null;
      const tracks = stream ? stream.getVideoTracks() : [];
      const settings = tracks.length ? (tracks[0].getSettings ? tracks[0].getSettings() : {}) : {};
      const fm = (settings.facingMode || '').toString().toLowerCase();
      if (fm.includes('user') || fm.includes('front') || fm.includes('selfie')) readerEl && readerEl.classList.add('mirror-video');
      else readerEl && readerEl.classList.remove('mirror-video');
    } catch (_) {}
  }

  async function showQRScanner() {
    try {
      const modal = document.getElementById('qrScannerModal');
      if (modal) modal.classList.remove('hidden');
      const status = document.getElementById('qrStatus');
      if (status) status.textContent = "Initializing camera...";
      setQRInlineError('');
      await ensureHtml5QrcodeReady();

      if (!qrCameraIds.length) {
        const devices = await Html5Qrcode.getCameras();
        qrCameraIds = (devices || []).map(d => d.id);
        if (!qrCameraIds.length) { if (status) status.textContent = 'No camera found.'; return; }
      }

      await startQRScannerWithCamera(qrCameraIds[qrCameraIndex]);
    } catch (e) {
      console.error('QR init error:', e);
      const status = document.getElementById('qrStatus');
      if (status) status.textContent = 'Failed to start camera: ' + (e && e.message ? e.message : e);
    }
  }

  async function startQRScannerWithCamera(cameraId) {
    if (qrStarting) return;
    qrStarting = true;
    try {
      if (qrScanner) {
        try { await qrScanner.stop(); } catch(_){}
        try { await qrScanner.clear(); } catch(_){}
      }
      qrScanner = new Html5Qrcode('qrReader');
      const fps = 10;
      const qrbox = { width: 250, height: 250 };
      await qrScanner.start(
        { deviceId: { exact: cameraId } },
        { fps, qrbox },
        async (decodedText) => {
          const text = (decodedText || '').trim();
          if (!text) return;
          // RFID-only: validate via Guidance GetRecord first
          try {
            const res = await fetch(`GetRecord.php?rfid_uid=${encodeURIComponent(text)}`);
            const data = await res.json();
            if (data && !data.error && data.student && data.student.id_number) {
              setQRInlineError('');
              closeQRScanner();
              // Load student data into the dashboard (mirrors keyboard RFID flow)
              loadStudentData(text);
            } else {
              setQRInlineError('Invalid RFID. Please scan a registered student RFID.');
            }
          } catch (e) {
            setQRInlineError('Scan check failed. Please try again.');
          }
        },
        () => {
          const el = document.getElementById('qrStatus');
          if (el) el.textContent = 'Scanning...';
        }
      );
      setTimeout(setMirrorFromCurrentStream, 150);
      const status = document.getElementById('qrStatus'); if (status) status.textContent = 'Scanning...';
    } finally {
      qrStarting = false;
    }
  }

  async function switchQRScannerCamera() {
    if (!qrCameraIds.length) return;
    qrCameraIndex = (qrCameraIndex + 1) % qrCameraIds.length;
    await startQRScannerWithCamera(qrCameraIds[qrCameraIndex]);
  }

  async function closeQRScanner() {
    const modal = document.getElementById('qrScannerModal');
    if (modal) modal.classList.add('hidden');
    if (qrScanner) {
      try { await qrScanner.stop(); } catch(_){}
      try { await qrScanner.clear(); } catch(_){}
    }
  }

  function closeViolationTypeModal() {
    document.getElementById('violationTypeModal').classList.add('hidden');
    resetViolationForm();
  }
  
  function resetViolationForm() {
    document.getElementById('violationTypeForm').reset();
    document.getElementById('violationTypeId').value = '';
    document.getElementById('violationFormTitle').textContent = 'Add New Violation Type';
    document.getElementById('violationSubmitText').textContent = 'Add Violation Type';
  }
  
  function loadViolationTypes() {
    fetch('ManageViolations.php')
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          violationTypes = data.data;
          renderViolationTypes();
          updateViolationDropdown(); // Update the main form dropdown
        } else {
          alert('Error loading violation types: ' + data.message);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Failed to load violation types');
      });
  }
  
  function renderViolationTypes() {
    const container = document.getElementById('violationTypesList');
    
    if (violationTypes.length === 0) {
      container.innerHTML = '<p class="text-gray-500 text-center py-4">No violation types found</p>';
      return;
    }
    
    container.innerHTML = violationTypes.map(violation => {
      return `
        <div class="bg-white border border-gray-200 rounded-lg p-4">
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <h5 class="font-semibold text-gray-900 mb-2">${violation.violation_name}</h5>
              <p class="text-xs text-gray-400">Created: ${new Date(violation.created_at).toLocaleDateString()}</p>
            </div>
            <div class="flex space-x-2 ml-4">
              <button onclick="editViolationType(${violation.id})" 
                      class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                Edit
              </button>
              <button onclick="deleteViolationType(${violation.id})" 
                      class="text-red-600 hover:text-red-800 text-sm font-medium">
                Delete
              </button>
            </div>
          </div>
        </div>
      `;
    }).join('');
  }
  
  function editViolationType(id) {
    const violation = violationTypes.find(v => v.id === id);
    if (!violation) return;
    
    document.getElementById('violationTypeId').value = violation.id;
    document.getElementById('violationTypeName').value = violation.violation_name;
    
    document.getElementById('violationFormTitle').textContent = 'Edit Violation Type';
    document.getElementById('violationSubmitText').textContent = 'Update Violation Type';
  }
  
  let violationToDelete = null;
  
  function deleteViolationType(id) {
    const violation = violationTypes.find(v => v.id === id);
    if (!violation) return;
    
    violationToDelete = violation;
    document.getElementById('deleteViolationMessage').textContent = `Are you sure you want to delete "${violation.violation_name}"? This action cannot be undone.`;
    document.getElementById('deleteViolationModal').classList.remove('hidden');
  }
  
  function closeDeleteViolationModal() {
    document.getElementById('deleteViolationModal').classList.add('hidden');
    violationToDelete = null;
  }
  
  function proceedDeleteViolationType() {
    if (!violationToDelete) return;
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', violationToDelete.id);
    
    fetch('ManageViolations.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        notif.classList.remove("hidden");
        setTimeout(() => notif.classList.add("hidden"), 2000);
        loadViolationTypes();
        closeDeleteViolationModal();
      } else {
        alert('Error: ' + data.message);
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Failed to delete violation type');
    });
  }
  
  // Handle violation type form submission
  document.getElementById('violationTypeForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    const id = document.getElementById('violationTypeId').value;
    
    formData.append('action', id ? 'edit' : 'add');
    if (id) formData.append('id', id);
    formData.append('violation_name', document.getElementById('violationTypeName').value.trim());
    
    fetch('ManageViolations.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        notif.classList.remove("hidden");
        setTimeout(() => notif.classList.add("hidden"), 2000);
        resetViolationForm();
        loadViolationTypes();
      } else {
        alert('Error: ' + data.message);
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Failed to save violation type');
    });
  });
</script>

</body>
</html>
