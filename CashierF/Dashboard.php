<?php
session_start();

// ✅ Require cashier role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'cashier') {
    header("Location: ../StudentLogin/login.php");
    exit;
}

// ✅ Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Cashier Dashboard - Cornerstone College Inc.</title>
  <link rel="icon" href="../images/LogoCCI.png" type="image/png">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      20%, 60% { transform: translateX(-3px); }
      40%, 80% { transform: translateX(3px); }
    }
    .shake { animation: shake 0.3s; }
    .school-gradient { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #1e40af 100%); }
    .card-shadow { box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
  </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen font-sans">



  <!-- ✅ Hidden RFID input -->
  <input type="text" id="rfidInput" autofocus class="absolute opacity-0">

  <!-- Header with School Branding -->
  <header class="bg-[#0B2C62] text-white shadow-lg">
    <div class="container mx-auto px-6 py-4">
      <div class="flex justify-between items-center">
      <div class="flex items-center space-x-4">
        <div class="text-left">
          <p class="text-sm text-blue-200">Welcome,</p>
          <p class="font-semibold"><?= htmlspecialchars($_SESSION['cashier_name'] ?? 'Cashier') ?></p>
        </div>
      </div>
      
      <div class="flex items-center space-x-4">
        <img src="../images/LogoCCI.png" alt="Cornerstone College Inc." class="h-12 w-12 rounded-full bg-white p-1">
        <div class="text-right">
          <h1 class="text-xl font-bold">Cornerstone College Inc.</h1>
          <p class="text-blue-200 text-sm">Registrar Portal</p>
        </div>
          <div class="relative">
            <button id="menuBtn" class="bg-white bg-opacity-20 hover:bg-opacity-30 p-2 rounded-lg transition">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
              </svg>
            </button>
            <div id="menuDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg z-50 text-gray-800">
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
            class="w-full border border-gray-300 rounded-xl pl-10 pr-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
            onkeydown="if(event.key==='Enter'){ event.preventDefault(); searchStudent(); }"
          />
        </div>
        <button
          id="searchBtn"
          onclick="searchStudent()"
          class="bg-[#0B2C62] hover:bg-blue-900 text-white px-6 py-3 rounded-xl font-medium transition-colors"
        >Search</button>
        <p id="searchError" class="text-red-600 text-sm"></p>
      </div>
      <div id="searchResults" class="mt-4 space-y-2"></div>
    </div>
  </div>

  <!-- Tabs -->
  <div class="bg-white border-b">
    <div class="container mx-auto px-6 py-4">
      <div class="flex gap-8">
        <button onclick="showTab('balance')" class="tab-btn font-semibold text-blue-600 border-b-2 border-blue-600 pb-2">Student Balance</button>
        <button onclick="showTab('history')" class="tab-btn text-gray-600 hover:text-blue-600 pb-2 transition-colors">Transaction History</button>
      </div>
    </div>
  </div>

  <!-- Content -->
  <div class="container mx-auto px-6 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
      
      <!-- Cashier Profile -->
      <div class="lg:col-span-1">
        <div class="bg-white rounded-2xl card-shadow p-6">
          <div class="text-center">
            <div class="w-20 h-20 mx-auto bg-gray-400 rounded-full flex items-center justify-center mb-4">
              <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
              </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-800"><?= htmlspecialchars($_SESSION['cashier_name'] ?? 'Cashier') ?></h3>
            <p class="text-gray-600 text-sm">ID: <?= htmlspecialchars($_SESSION['id_number'] ?? 'N/A') ?></p>
          </div>
          
          <div class="mt-6 pt-6 border-t">
            <div class="space-y-3 text-sm">
              <div>
                <span class="text-gray-500 font-medium">Role:</span>
                <p class="text-gray-800">Cashier</p>
              </div>
              <div>
                <span class="text-gray-500 font-medium">Department:</span>
                <p class="text-gray-800">Finance Office</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <div class="lg:col-span-3 space-y-6">
        <!-- Student Info -->
        <div id="student-info" class="bg-white rounded-2xl card-shadow p-6">
          <div class="text-center text-gray-500">
            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
            <p class="text-sm italic">Scan RFID or search to display student information</p>
          </div>
        </div>

        <!-- Balance Tab -->
        <div id="tab-balance" class="bg-white rounded-2xl card-shadow p-6">
          <div class="text-center text-gray-500 py-12">
            <div class="w-16 h-16 mx-auto school-gradient rounded-lg flex items-center justify-center mb-4">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">No Balance Data</h3>
            <p class="text-sm text-gray-500">Search for a student or scan RFID to view balance information</p>
          </div>
        </div>
        
        <!-- History Tab -->
        <div id="tab-history" class="hidden bg-white rounded-2xl card-shadow p-6">
          <div class="text-center text-gray-500 py-12">
            <div class="w-16 h-16 mx-auto school-gradient rounded-lg flex items-center justify-center mb-4">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">No Transaction History</h3>
            <p class="text-sm text-gray-500">Search for a student or scan RFID to view transaction history</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    // ===== DROPDOWN MENU =====
    const menuBtn = document.getElementById("menuBtn");
    const menuDropdown = document.getElementById("menuDropdown");
    menuBtn.addEventListener("click", () => {
      menuDropdown.classList.toggle("hidden");
    });
    document.addEventListener("click", (e) => {
      if (!menuBtn.contains(e.target) && !menuDropdown.contains(e.target)) {
        menuDropdown.classList.add("hidden");
      }
    });

    // ===== RFID LOGIC =====
    const rfidInput = document.getElementById('rfidInput');
    function focusRFID() {
      if (document.activeElement !== rfidInput) {
        rfidInput.focus({ preventScroll: true });
      }
    }

    // ✅ FIXED: let Ctrl/Meta shortcuts work (copy, paste, select all, etc.)
    document.addEventListener('keydown', (e) => {
      if (e.ctrlKey || e.metaKey) return; // skip forcing RFID on shortcuts
      if (!['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) {
        focusRFID();
      }
    });

    rfidInput.addEventListener('change', () => {
      const rfid = rfidInput.value.trim();
      if (!rfid) return;
      handleRFID(rfid);
    });

    function showTab(tab) {
      document.getElementById('tab-balance').classList.add('hidden');
      document.getElementById('tab-history').classList.add('hidden');
      document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('border-blue-600', 'font-semibold', 'text-blue-600');
        btn.classList.add('text-gray-600');
      });
      if (tab === 'balance') {
        document.getElementById('tab-balance').classList.remove('hidden');
        document.querySelectorAll('.tab-btn')[0].classList.add('border-blue-600', 'font-semibold', 'text-blue-600');
        document.querySelectorAll('.tab-btn')[0].classList.remove('text-gray-600');
      } else {
        document.getElementById('tab-history').classList.remove('hidden');
        document.querySelectorAll('.tab-btn')[1].classList.add('border-blue-600', 'font-semibold', 'text-blue-600');
        document.querySelectorAll('.tab-btn')[1].classList.remove('text-gray-600');
      }
      focusRFID(); 
    }

    // ====== SEARCH LOGIC ======
    const searchBtn = document.getElementById('searchBtn');
    const searchError = document.getElementById('searchError');
    const searchResults = document.getElementById('searchResults');

    function clearError() {
      searchError.textContent = '';
      searchBtn.classList.remove('bg-red-600', 'shake');
      searchBtn.classList.add('bg-blue-600');
    }
    function showError(msg) {
      searchError.textContent = msg;
      searchBtn.classList.remove('bg-blue-600');
      searchBtn.classList.add('bg-red-600', 'shake');
      setTimeout(() => {
        searchBtn.classList.remove('shake');
        searchBtn.classList.add('bg-blue-600');
      }, 600);
    }

    let allSearchResults = [];   // store all results globally
let shownCount = 0;          // how many are currently shown

function renderSearchResults(list, reset = true) {
  if (reset) {
    allSearchResults = list;
    shownCount = 0;
    searchResults.innerHTML = ''; // reset container
  }

  if (!allSearchResults || allSearchResults.length === 0) {
    searchResults.innerHTML = '<p class="text-sm text-gray-500 italic">No students found.</p>';
    return;
  }

  // determine how many to show (3 first, then +10 per click)
  const increment = reset ? 3 : 10;
  const nextCount = Math.min(shownCount + increment, allSearchResults.length);

  // slice the portion we need to render
  const slice = allSearchResults.slice(shownCount, nextCount);
  shownCount = nextCount;

  // render each student card
const items = slice.map(s => {
  const id = s.id_number || '';
  const name = s.full_name || '';
  const prog = s.program || '';
  const sec = s.year_section || '';
  const rfid = s.rfid_uid || '';
  const note = rfid ? '' : '<span class="text-xs text-red-600 ml-2">(no RFID on file)</span>';

  if (!rfid) {
    // show non-clickable card if no RFID
    return `
      <div class="flex justify-between items-center border rounded px-3 py-2 bg-white opacity-50 cursor-not-allowed">
        <div class="text-sm">
          <div class="font-medium">${name}</div>
          <div class="text-gray-600">ID: ${id} • ${prog} • ${sec} ${note}</div>
        </div>
      </div>
    `;
  }

  // ✅ clickable card if has RFID
  return `
    <div onclick="handleRFID('${rfid.replace(/'/g, "\\'")}'); document.getElementById('searchResults').innerHTML='';"
         class="flex justify-between items-center border rounded px-3 py-2 bg-white hover:bg-gray-100 cursor-pointer transition">
      <div class="text-sm">
        <div class="font-medium">${name}</div>
        <div class="text-gray-600">ID: ${id} • ${prog} • ${sec}</div>
      </div>
    </div>
  `;
}).join('');


  // append to results
  searchResults.innerHTML += `
    <div class="max-w-2xl space-y-1">${items}</div>
  `;

  // show "View More" button if not all students are shown
  if (shownCount < allSearchResults.length) {
    if (!document.getElementById('viewMoreBtn')) {
      searchResults.innerHTML += `
        <div class="mt-2">
          <button id="viewMoreBtn" onclick="renderSearchResults([], false)" class="text-sm px-3 py-2">
            View More
          </button>
        </div>
      `;
    }
  } else {
    const btn = document.getElementById('viewMoreBtn');
    if (btn) btn.remove();
  }
}


    function searchStudent() {
      clearError();
      searchResults.innerHTML = '';
      const query = document.getElementById('searchInput').value.trim();
      if (!query) {
        showError('Please enter a search term');
        return;
      }
      
      fetch(`SearchStudent.php?query=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(data => {
          if (data.error) {
            showError(data.error);
            return;
          }
          
          const students = data.students || [];
          if (students.length === 1) {
            const s = students[0];
            if (s.rfid_uid) {
              handleRFID(s.rfid_uid);
              searchResults.innerHTML = '';
            } else {
              renderSearchResults(students, true);
            }
          } else {
            renderSearchResults(students, true);
          }
        })
        .catch(err => {
          console.error('Search error:', err);
          showError('Search failed. Please try again.');
        });
    }

    // ===== FETCH BALANCE & HISTORY =====
    function handleRFID(rfid) {
  fetch(`GetBalance.php?rfid_uid=${encodeURIComponent(rfid)}`)
    .then(res => res.json())
    .then(data => {
      // ✅ Always render student info with simple gray avatar
      document.getElementById('student-info').innerHTML = `
        <div class="flex items-center space-x-4">
          <div class="w-16 h-16 bg-gray-400 rounded-full flex items-center justify-center">
            <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
            </svg>
          </div>
          <div class="flex-1">
            <h3 class="text-xl font-bold text-gray-800">${data.full_name || 'Unknown Student'}</h3>
            <div class="space-y-1 text-sm text-gray-600">
              <p><span class="font-medium">ID:</span> ${data.id_number || 'N/A'}</p>
              <p><span class="font-medium">Program:</span> ${data.program || 'N/A'}</p>
              <p><span class="font-medium">Year & Section:</span> ${data.year_section || 'N/A'}</p>
            </div>
          </div>
        </div>
      `;

      // ✅ If no balance record, just fill in zeros
      const tuition_fee = Number(data.tuition_fee ?? 0);
      const tuition_paid = Number(data.tuition_paid ?? 0);
      const other_fees = Number(data.other_fees ?? 0);
      const other_paid = Number(data.other_paid ?? 0);
      const student_fees = Number(data.student_fees ?? 0);
      const student_paid = Number(data.student_paid ?? 0);
      const gross_total = Number(data.gross_total ?? 0);
      const term = data.school_year_term || "No balance record";

      if (term === "No balance record") {
        document.getElementById('tab-balance').innerHTML = `
          <div class="text-center py-12">
            <div class="w-16 h-16 mx-auto bg-blue-500 rounded-lg flex items-center justify-center mb-4">
              <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/>
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">No Balance Record</h3>
            <p class="text-sm text-gray-500">This student has no balance information on file</p>
          </div>
        `;
      } else {
        document.getElementById('tab-balance').innerHTML = `
          <div class="flex items-center mb-6">
            <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center mr-3">
              <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/>
              </svg>
            </div>
            <h2 class="text-xl font-bold text-gray-800">Balance Information</h2>
          </div>
          
          <div class="bg-blue-50 rounded-lg p-4 mb-6">
            <p class="text-sm font-medium text-blue-800">Academic Term: ${term}</p>
          </div>
          
          <div class="overflow-x-auto">
            <table class="min-w-full bg-white rounded-lg shadow-sm border border-gray-200">
              <thead class="bg-gray-800 text-white">
                <tr>
                  <th class="px-4 py-3 text-center text-sm font-semibold">#</th>
                  <th class="px-4 py-3 text-left text-sm font-semibold">Fee Type</th>
                  <th class="px-4 py-3 text-right text-sm font-semibold">Amount Due</th>
                  <th class="px-4 py-3 text-right text-sm font-semibold">Paid</th>
                  <th class="px-4 py-3 text-right text-sm font-semibold">Balance</th>
                </tr>
              </thead>
              <tbody class="text-gray-800">
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                  <td class="px-4 py-3 text-center">1</td>
                  <td class="px-4 py-3 font-medium">Tuition Fee</td>
                  <td class="px-4 py-3 text-right">₱${tuition_fee.toFixed(2)}</td>
                  <td class="px-4 py-3 text-right">₱${tuition_paid.toFixed(2)}</td>
                  <td class="px-4 py-3 text-right font-semibold">₱${(tuition_fee - tuition_paid).toFixed(2)}</td>
                </tr>
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                  <td class="px-4 py-3 text-center">2</td>
                  <td class="px-4 py-3 font-medium">Other Fees</td>
                  <td class="px-4 py-3 text-right">₱${other_fees.toFixed(2)}</td>
                  <td class="px-4 py-3 text-right">₱${other_paid.toFixed(2)}</td>
                  <td class="px-4 py-3 text-right font-semibold">₱${(other_fees - other_paid).toFixed(2)}</td>
                </tr>
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                  <td class="px-4 py-3 text-center">3</td>
                  <td class="px-4 py-3 font-medium">Student Fees</td>
                  <td class="px-4 py-3 text-right">₱${student_fees.toFixed(2)}</td>
                  <td class="px-4 py-3 text-right">₱${student_paid.toFixed(2)}</td>
                  <td class="px-4 py-3 text-right font-semibold">₱${(student_fees - student_paid).toFixed(2)}</td>
                </tr>
              </tbody>
            </table>
            
            <div class="mt-4 p-4 bg-gray-50 rounded-lg">
              <div class="flex justify-between items-center">
                <span class="text-lg font-bold text-gray-800">Total Outstanding Balance:</span>
                <span class="text-xl font-bold text-gray-900">₱${gross_total.toFixed(2)}</span>
              </div>
            </div>
          </div>
        `;
      }


      // ✅ Enhanced History UI
      if (data.history && data.history.length > 0) {
        let historyHTML = '';
        data.history.forEach((row, index) => {
          historyHTML += `
            <div class="border border-gray-200 rounded-lg p-4 mb-3">
              <div class="flex justify-between items-start mb-2">
                <div>
                  <p class="font-semibold text-gray-900">${row.date}</p>
                  <p class="text-sm text-gray-600">${row.description || 'Payment'}</p>
                </div>
                <span class="bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-sm font-medium">Paid</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Payment Method: ${row.method || 'Cash'}</span>
                <span class="font-semibold text-gray-900">₱${row.amount}</span>
              </div>
            </div>
          `;
        });
        
        document.getElementById('tab-history').innerHTML = `
          <div class="flex items-center mb-6">
            <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center mr-3">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
              </svg>
            </div>
            <h2 class="text-xl font-bold text-gray-800">Transaction History</h2>
          </div>
          <div class="space-y-3">
            ${historyHTML}
          </div>
        `;
      } else {
        document.getElementById('tab-history').innerHTML = `
          <div class="text-center py-12">
            <div class="w-16 h-16 mx-auto school-gradient rounded-lg flex items-center justify-center mb-4">
              <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">No Transaction History</h3>
            <p class="text-sm text-gray-500">This student has no payment records on file</p>
          </div>
        `;
      }

      rfidInput.value = '';
      focusRFID();
    })
    .catch(err => {
      console.error(err);
      rfidInput.value = '';
      focusRFID();
    });
}


    // ====== LIVE SEARCH FUNCTIONALITY ======
    let searchTimeout;
    
    searchInput.addEventListener('input', () => {
      const query = searchInput.value.trim();
      clearError();
      clearTimeout(searchTimeout);
      if (!query.length) {
        searchResults.innerHTML = '';
        return;
      }
      searchTimeout = setTimeout(() => fetchStudents(query), 300);
    });

    function fetchStudents(query) {
      clearError();
      fetch(`SearchStudent.php?query=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(data => {
          if (data.error || !data.students || !data.students.length) {
            searchResults.innerHTML = `<p class="text-sm text-gray-500 italic">No matches for "${query}"</p>`;
            return;
          }
          renderSearchResults(data.students, true);
        })
        .catch(err => {
          console.error('Search error:', err);
          searchResults.innerHTML = `<p class="text-sm text-red-500">Failed to fetch student data</p>`;
        });
    }


    // Focus RFID when page loads
    window.onload = focusRFID;
  </script>

</body>
</html>