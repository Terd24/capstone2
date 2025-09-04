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
    .card-shadow { box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
    
    /* Remove number input arrows */
    input[type="number"]::-webkit-outer-spin-button,
    input[type="number"]::-webkit-inner-spin-button {
      -webkit-appearance: none;
      margin: 0;
    }
    input[type="number"] {
      -moz-appearance: textfield;
    }
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
        <button onclick="showFeeTypeModal()" 
                class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-xl font-medium transition-colors">
          Manage Fee Types
        </button>
      </div>
      <div id="searchError" class="mt-2 text-red-600 text-sm"></div>
      <div id="searchResults" class="mt-4 space-y-2"></div>
    </div>
  </div>

  <!-- Tabs -->
  <div class="bg-white border-b">
    <div class="container mx-auto px-6 py-4">
      <div class="flex gap-8">
        <button onclick="showTab('balance')" class="tab-btn font-semibold text-blue-600 border-b-2 border-blue-600 pb-2">Student Balance</button>
        <button onclick="showTab('history')" class="tab-btn text-gray-600 hover:text-blue-600 pb-2 transition-colors border-b-2 border-transparent">Transaction History</button>
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

  <!-- Edit Payment Modal -->
  <div id="editPaymentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-bold text-gray-800">Edit Payment</h3>
        <button onclick="closeEditPaymentModal()" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
      
      <form id="editPaymentForm" onsubmit="submitPaymentEdit(event); return false;">
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Fee Type</label>
            <input type="text" id="editFeeType" readonly class="w-full border border-gray-300 rounded-lg px-4 py-3 bg-gray-50 text-gray-600">
            <input type="hidden" id="editFeeId" name="fee_id">
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Amount Due</label>
            <input type="number" id="editAmountDue" readonly step="0.01" class="w-full border border-gray-300 rounded-lg px-4 py-3 bg-gray-50 text-gray-600">
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Paid Amount</label>
            <div class="relative">
              <input type="number" id="editPaidAmount" name="paid_amount" step="0.01" min="0" 
                     class="w-full border border-gray-300 rounded-lg px-4 py-3 pr-20 focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                     onchange="checkPaymentAmount()" oninput="checkPaymentAmount()" required>
              <button type="button" onclick="matchEditAmountDue()" 
                      class="absolute right-2 top-2 bottom-2 bg-blue-500 hover:bg-blue-600 text-white px-3 rounded text-sm">
                Match
              </button>
            </div>
          </div>
          
          <div id="paymentMethodDiv" class="hidden">
            <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
            <select id="editPaymentMethod" name="payment_method" 
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
              <option value="Cash">Cash</option>
              <option value="GCash">GCash</option>
              <option value="Bank Transfer">Bank Transfer</option>
              <option value="Check">Check</option>
              <option value="Credit Card">Credit Card</option>
            </select>
          </div>
        </div>
        
        <div id="editPaymentError" class="hidden mt-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg text-sm"></div>
        <div id="editPaymentSuccess" class="hidden mt-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded-lg text-sm"></div>
        
        <div class="flex gap-3 mt-6">
          <button type="button" onclick="closeEditPaymentModal()" 
                  class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-3 rounded-xl font-medium transition-colors">
            Cancel
          </button>
          <button type="submit" id="submitEditPaymentBtn"
                  class="flex-1 bg-[#0B2C62] hover:bg-blue-900 text-white px-6 py-3 rounded-xl font-medium transition-colors">
            Update Payment
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Fee Type Modal -->
  <div id="feeTypeModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-8 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-bold text-gray-800">Manage Fee Types</h3>
        <button onclick="closeFeeTypeModal()" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
      
      <!-- Add New Fee Type Form -->
      <div class="mb-6 p-4 bg-gray-50 rounded-lg">
        <h4 class="text-lg font-semibold mb-4">Add New Fee Type</h4>
        <form id="addFeeTypeForm" onsubmit="submitFeeType(event); return false;">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Fee Name</label>
              <input type="text" id="feeTypeName" name="fee_name" placeholder="e.g., Laboratory Fee" 
                     class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Default Amount</label>
              <input type="number" id="feeTypeAmount" name="default_amount" step="0.01" min="0" placeholder="0.00"
                     class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
            </div>
          </div>
          <div class="flex gap-3 mt-4">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
              Add Fee Type
            </button>
          </div>
        </form>
      </div>
      
      <!-- Existing Fee Types List -->
      <div>
        <h4 class="text-lg font-semibold mb-4">Existing Fee Types</h4>
        <div id="feeTypesList" class="space-y-2">
          <div class="text-center py-4 text-gray-500">Loading fee types...</div>
        </div>
      </div>
      
      <div id="feeTypeError" class="hidden mt-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg text-sm"></div>
      <div id="feeTypeSuccess" class="hidden mt-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded-lg text-sm"></div>
    </div>
  </div>

  <!-- Add Balance Modal -->
  <div id="addBalanceModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4">
      <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-bold text-gray-800">Add Student Balance</h3>
        <button onclick="closeAddBalanceModal()" class="text-gray-400 hover:text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>
      
      <form id="addBalanceForm" onsubmit="submitBalance(event); return false;">
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Student</label>
            <input type="text" id="studentDisplay" readonly class="w-full border border-gray-300 rounded-lg px-4 py-3 bg-gray-50 text-gray-600">
            <input type="hidden" id="studentId" name="id_number">
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">School Year & Term</label>
            <input type="text" id="schoolTerm" name="school_year_term" placeholder="e.g., 2024-2025 1st Semester" 
                   class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Student Fees</label>
            <div id="feeItemsContainer" class="space-y-3">
              <div class="space-y-2">
                <div class="flex gap-2">
                  <select onchange="handleFeeTypeChange(this)" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                    <option value="">Select Fee Type...</option>
                  </select>
                </div>
                <div class="flex gap-2">
                  <input type="number" placeholder="Amount Due" step="0.01" min="0" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm">
                  <input type="number" placeholder="Paid" step="0.01" min="0" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
              </div>
            </div>
            <button type="button" onclick="matchAllAmountsDue()" class="mt-2 bg-[#0B2C62] hover:bg-blue-900 text-white px-4 py-2 rounded-lg text-sm">Match Amount Due</button>
          </div>
        </div>
        
        <div id="balanceError" class="hidden mt-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg text-sm"></div>
        <div id="balanceSuccess" class="hidden mt-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded-lg text-sm"></div>
        
        <div class="flex gap-3 mt-6">
          <button type="button" onclick="closeAddBalanceModal()" 
                  class="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-3 rounded-xl font-medium transition-colors">
            Cancel
          </button>
          <button type="submit" id="submitBalanceBtn"
                  class="flex-1 bg-[#0B2C62] hover:bg-blue-900 text-white px-6 py-3 rounded-xl font-medium transition-colors">
            Add Balance
          </button>
        </div>
      </form>
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
        btn.classList.add('text-gray-600', 'border-transparent');
      });
      if (tab === 'balance') {
        document.getElementById('tab-balance').classList.remove('hidden');
        document.querySelectorAll('.tab-btn')[0].classList.add('border-blue-600', 'font-semibold', 'text-blue-600');
        document.querySelectorAll('.tab-btn')[0].classList.remove('text-gray-600', 'border-transparent');
      } else {
        document.getElementById('tab-history').classList.remove('hidden');
        document.querySelectorAll('.tab-btn')[1].classList.add('border-blue-600', 'font-semibold', 'text-blue-600');
        document.querySelectorAll('.tab-btn')[1].classList.remove('text-gray-600', 'border-transparent');
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
      console.log('searchStudent function called');
      clearError();
      searchResults.innerHTML = '';
      const query = document.getElementById('searchInput').value.trim();
      console.log('Search query:', query);
      
      if (!query) {
        showError('Please enter a search term');
        return;
      }
      
      console.log('Making fetch request to SearchStudent.php');
      fetch(`SearchStudent.php?query=${encodeURIComponent(query)}`)
        .then(res => {
          console.log('Response status:', res.status);
          return res.json();
        })
        .then(data => {
          console.log('Response data:', data);
          if (data.error) {
            showError(data.error);
            return;
          }
          
          const students = data.students || [];
          console.log('Students found:', students.length);
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
      console.log('handleRFID called with:', rfid);
      // Store RFID globally for refresh purposes
      window.currentStudentRFID = rfid;
      
  fetch(`GetBalance.php?rfid_uid=${encodeURIComponent(rfid)}`)
    .then(res => {
      console.log('GetBalance response status:', res.status);
      return res.json();
    })
    .then(data => {
      console.log('GetBalance response data:', data);
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
          <button onclick="clearStudentData()" class="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            Close
          </button>
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

      if (data.school_year_term === "No balance record") {
        // Set default term for new balance
        const currentYear = new Date().getFullYear();
        const nextYear = currentYear + 1;
        const defaultTerm = `${currentYear}-${nextYear} 1st Semester`;
        
        document.getElementById('tab-balance').innerHTML = `
          <div class="flex items-center justify-between mb-4">
            <div class="relative">
              <button id="termSelector" onclick="toggleTermDropdown('${data.id_number}')" 
                      class="text-lg font-semibold text-gray-800 hover:text-blue-600 flex items-center gap-2 transition-colors">
                ${defaultTerm}
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
              </button>
              <div id="termDropdown" class="hidden absolute top-full left-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-10 min-w-64">
                <div class="p-2 text-sm text-gray-500 border-b">Loading available terms...</div>
              </div>
            </div>
            <div class="flex gap-2">
              <button onclick="showAddBalanceFormAndRefresh('${data.id_number}', '${data.full_name}')" 
                      class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">
                Add More Fees
              </button>
            </div>
          </div>
          
          <div class="bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm">
            <table class="min-w-full">
              <thead class="bg-gradient-to-r from-gray-800 to-gray-900 text-white">
                <tr>
                  <th class="px-4 py-3 text-center text-sm font-semibold">#</th>
                  <th class="px-4 py-3 text-left text-sm font-semibold">Fee Type</th>
                  <th class="px-4 py-3 text-right text-sm font-semibold">Amount Due</th>
                  <th class="px-4 py-3 text-right text-sm font-semibold">Paid</th>
                  <th class="px-4 py-3 text-right text-sm font-semibold">Balance</th>
                  <th class="px-4 py-3 text-center text-sm font-semibold">Action</th>
                </tr>
              </thead>
              <tbody class="text-gray-800 text-sm">
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No fee items found</td></tr>
              </tbody>
            </table>
            
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-t-2 border-blue-200">
              <div class="flex justify-between items-center">
                <div class="text-sm text-gray-600">
                  <span class="font-medium">Total Due:</span> ₱0.00 | 
                  <span class="font-medium">Total Paid:</span> ₱0.00
                </div>
                <div class="text-right">
                  <span class="text-sm text-gray-600 font-medium">Remaining Balance:</span>
                  <div class="text-xl font-bold text-green-600">
                    ₱0.00
                  </div>
                </div>
              </div>
            </div>
          </div>
        `;
      } else {
        // Calculate paid amounts from payments
        const totalPaid = data.history ? data.history.reduce((sum, payment) => sum + parseFloat(payment.amount || 0), 0) : 0;
        const remainingBalance = gross_total - totalPaid;
        
        document.getElementById('tab-balance').innerHTML = `
          <div class="flex items-center justify-between mb-4">
            <div class="relative">
              <button id="termSelector" onclick="toggleTermDropdown('${data.id_number}')" 
                      class="text-lg font-semibold text-gray-800 hover:text-blue-600 flex items-center gap-2 transition-colors">
                ${data.school_year_term}
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
              </button>
              <div id="termDropdown" class="hidden absolute top-full left-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-10 min-w-64">
                <div class="p-2 text-sm text-gray-500 border-b">Loading available terms...</div>
              </div>
            </div>
            <div class="flex gap-2">
              <button onclick="showAddBalanceForm('${data.id_number}', '${data.full_name}')" 
                      class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">
                Add More Fees
              </button>
              <button onclick="showPaymentSchedule('${data.id_number}', '${data.school_year_term}')" 
                      class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm">
                Payment Schedule
              </button>
            </div>
          </div>
          
          <div class="bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm">
            <table class="min-w-full">
              <thead class="bg-gradient-to-r from-gray-800 to-gray-900 text-white">
                <tr>
                  <th class="px-4 py-3 text-center text-sm font-semibold">#</th>
                  <th class="px-4 py-3 text-left text-sm font-semibold">Fee Type</th>
                  <th class="px-4 py-3 text-right text-sm font-semibold">Amount Due</th>
                  <th class="px-4 py-3 text-right text-sm font-semibold">Paid</th>
                  <th class="px-4 py-3 text-right text-sm font-semibold">Balance</th>
                  <th class="px-4 py-3 text-center text-sm font-semibold">Action</th>
                </tr>
              </thead>
              <tbody class="text-gray-800 text-sm">
                ${data.fee_items && data.fee_items.length > 0 ? data.fee_items.map((fee, index) => {
                  const amountDue = parseFloat(fee.amount || 0);
                  const paid = parseFloat(fee.paid || 0);
                  const balance = amountDue - paid;
                  return `
                  <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 text-center font-medium">${index + 1}</td>
                    <td class="px-4 py-3 font-medium">${fee.fee_type}</td>
                    <td class="px-4 py-3 text-right font-semibold">₱${amountDue.toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                    <td class="px-4 py-3 text-right ${paid > 0 ? 'text-green-600 font-semibold' : 'text-gray-500'}">₱${paid.toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                    <td class="px-4 py-3 text-right font-bold ${balance > 0 ? 'text-red-600' : 'text-green-600'}">₱${balance.toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                    <td class="px-4 py-3 text-center">
                      <button onclick="editFeePayment(${fee.id}, '${fee.fee_type}', ${amountDue}, ${paid})" 
                              class="bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded text-xs font-medium transition-colors">
                        Edit
                      </button>
                    </td>
                  </tr>
                  `;
                }).join('') : '<tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No fee items found</td></tr>'}
              </tbody>
            </table>
            
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-t-2 border-blue-200">
              <div class="flex justify-between items-center">
                <div class="text-sm text-gray-600">
                  <span class="font-medium">Total Due:</span> ₱${data.gross_total.toLocaleString('en-PH', {minimumFractionDigits: 2})} | 
                  <span class="font-medium">Total Paid:</span> ₱${data.total_paid.toLocaleString('en-PH', {minimumFractionDigits: 2})}
                </div>
                <div class="text-right">
                  <span class="text-sm text-gray-600 font-medium">Remaining Balance:</span>
                  <div class="text-xl font-bold ${data.remaining_balance > 0 ? 'text-red-600' : 'text-green-600'}">
                    ₱${data.remaining_balance.toLocaleString('en-PH', {minimumFractionDigits: 2})}
                  </div>
                </div>
              </div>
            </div>
          </div>
        `;
      }


      // ✅ Enhanced History UI - Table Format
      if (data.history && data.history.length > 0) {
        let historyHTML = '';
        data.history.forEach((row, index) => {
          const formattedDate = new Date(row.date).toLocaleDateString('en-US', {
            month: '2-digit',
            day: '2-digit',
            year: '2-digit'
          });
          
          historyHTML += `
            <tr class="${index % 2 === 0 ? 'bg-white' : 'bg-gray-50'}">
              <td class="px-4 py-3 text-sm text-gray-900">${index + 1}</td>
              <td class="px-4 py-3 text-sm text-gray-900">${formattedDate}</td>
              <td class="px-4 py-3 text-sm text-gray-900">${row.fee_type || 'Payment'}</td>
              <td class="px-4 py-3 text-sm font-semibold text-gray-900">₱${parseFloat(row.amount).toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
              <td class="px-4 py-3 text-sm text-gray-600">${row.payment_method || 'Cash'}</td>
            </tr>
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
          <div class="overflow-hidden rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-900">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">#</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Date</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Description</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Amount</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Method</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                ${historyHTML}
              </tbody>
            </table>
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


    // ===== ADD BALANCE MODAL FUNCTIONS =====
    function showAddBalanceForm(idNumber, fullName, currentData = null) {
      document.getElementById('studentId').value = idNumber;
      document.getElementById('studentDisplay').value = fullName + ' (' + idNumber + ')';
      document.getElementById('addBalanceModal').classList.remove('hidden');
      
      // Clear form first
      document.getElementById('addBalanceForm').reset();
      document.getElementById('studentId').value = idNumber;
      document.getElementById('studentDisplay').value = fullName + ' (' + idNumber + ')';
      
      // Pre-populate with current data if updating
      if (currentData) {
        document.getElementById('schoolTerm').value = currentData.school_year_term || '';
        document.getElementById('tuitionFee').value = currentData.tuition_fee || '';
        document.getElementById('otherFees').value = currentData.other_fees || '';
        document.getElementById('studentFees').value = currentData.student_fees || '';
      } else {
        // Set default current academic year for new balances
        const currentYear = new Date().getFullYear();
        const nextYear = currentYear + 1;
        document.getElementById('schoolTerm').value = `${currentYear}-${nextYear} 1st Semester`;
      }
      
      // Hide error/success messages
      document.getElementById('balanceError').classList.add('hidden');
      document.getElementById('balanceSuccess').classList.add('hidden');
    }

    function showAddBalanceForm(studentId, studentName) {
      // Populate the form with student data
      document.getElementById('studentDisplay').value = studentName;
      document.getElementById('studentId').value = studentId;
      
      // Set default term
      const currentYear = new Date().getFullYear();
      const nextYear = currentYear + 1;
      document.getElementById('schoolTerm').value = `${currentYear}-${nextYear} 1st Semester`;
      
      // Reset form messages
      document.getElementById('balanceError').classList.add('hidden');
      document.getElementById('balanceSuccess').classList.add('hidden');
      
      // Load fee types and update dropdowns
      loadFeeTypes().then(() => {
        updateFeeTypeDropdowns();
      });
      
      // Show modal
      document.getElementById('addBalanceModal').classList.remove('hidden');
    }
    
    function showAddBalanceFormAndRefresh(studentId, studentName) {
      // Show the add balance form
      showAddBalanceForm(studentId, studentName);
      
      // Store flag to refresh after adding balance
      window.shouldRefreshAfterBalance = true;
    }

    function closeAddBalanceModal() {
      document.getElementById('addBalanceModal').classList.add('hidden');
      focusRFID();
    }

    function submitBalance(event) {
      if (event) event.preventDefault();
      
      console.log('Submit balance called');
      
      const form = document.getElementById('addBalanceForm');
      const formData = new FormData(form);
      
      // Collect fee items with new structure
      const feeItems = [];
      const feeItemsContainer = document.getElementById('feeItemsContainer');
      const feeItemRows = feeItemsContainer.querySelectorAll('.grid.grid-cols-3');
      
      console.log('Found fee item rows:', feeItemRows.length);
      
      feeItemRows.forEach(row => {
        const selectElement = row.querySelector('select');
        const textInput = row.querySelector('input[type="text"]');
        const amountDueInput = row.querySelector('input[type="number"]:nth-of-type(1)');
        const paidInput = row.querySelector('input[type="number"]:nth-of-type(2)');
        
        let feeTypeName = '';
        
        // Determine fee type name
        if (selectElement && selectElement.value && selectElement.value !== 'custom') {
          const selectedOption = selectElement.options[selectElement.selectedIndex];
          feeTypeName = selectedOption.dataset.name || selectedOption.textContent.split(' (₱')[0];
        } else if (textInput && textInput.style.display !== 'none' && textInput.value.trim()) {
          feeTypeName = textInput.value.trim();
        }
        
        if (feeTypeName && amountDueInput && amountDueInput.value) {
          const feeItem = {
            fee_type: feeTypeName,
            amount_due: parseFloat(amountDueInput.value) || 0,
            paid: parseFloat(paidInput.value) || 0
          };
          feeItems.push(feeItem);
          console.log('Added fee item:', feeItem);
        }
      });
      
      console.log('Total fee items:', feeItems.length);
      
      // Validate that at least one fee item exists
      if (feeItems.length === 0) {
        const errorDiv = document.getElementById('balanceError');
        errorDiv.textContent = 'Please add at least one fee item';
        errorDiv.classList.remove('hidden');
        return;
      }
      
      // Add fee items to form data
      formData.append('fee_items', JSON.stringify(feeItems));
      console.log('Fee items JSON:', JSON.stringify(feeItems));
      
      const submitBtn = document.getElementById('submitBalanceBtn');
      const errorDiv = document.getElementById('balanceError');
      const successDiv = document.getElementById('balanceSuccess');
      
      // Reset messages
      errorDiv.classList.add('hidden');
      successDiv.classList.add('hidden');
      
      // Disable submit button
      submitBtn.disabled = true;
      submitBtn.textContent = 'Processing...';
      
      fetch('AddBalance.php', {
        method: 'POST',
        body: formData
      })
      .then(response => {
        console.log('Response status:', response.status);
        return response.json();
      })
      .then(data => {
        console.log('Response data:', data);
        if (data.success) {
          successDiv.textContent = data.message;
          successDiv.classList.remove('hidden');
          
          // Store current student RFID globally for refresh
          let currentStudentRFID = window.currentStudentRFID;
          
          // Close modal immediately
          closeAddBalanceModal();
          
          // Refresh student data if we have the RFID
          if (currentStudentRFID) {
            console.log('Refreshing with stored RFID:', currentStudentRFID);
            handleRFID(currentStudentRFID);
          } else {
            // Try to get RFID from current display
            const studentInfo = document.getElementById('student-info');
            if (studentInfo && studentInfo.innerHTML.trim()) {
              const idMatch = studentInfo.innerHTML.match(/ID:\s*([A-Z0-9]+)/);
              if (idMatch) {
                const idNumber = idMatch[1];
                
                fetch(`SearchStudent.php?query=${idNumber}`)
                  .then(res => res.json())
                  .then(searchData => {
                    if (searchData.students && searchData.students.length > 0) {
                      const student = searchData.students[0];
                      if (student.rfid_uid) {
                        console.log('Found RFID, refreshing:', student.rfid_uid);
                        handleRFID(student.rfid_uid);
                      }
                    }
                  })
                  .catch(err => console.error('Refresh error:', err));
              }
            }
          }
        } else {
          errorDiv.textContent = data.message || 'An error occurred';
          errorDiv.classList.remove('hidden');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        errorDiv.textContent = 'An error occurred. Please try again.';
        errorDiv.classList.remove('hidden');
      })
      .finally(() => {
        // Re-enable submit button
        submitBtn.disabled = false;
        submitBtn.textContent = 'Add Balance';
      });
    }

    // ===== FEE ITEMS MANAGEMENT =====
    function addFeeItem() {
      const container = document.getElementById('feeItemsContainer');
      const newItem = document.createElement('div');
      newItem.className = 'space-y-2 p-3 bg-gray-50 rounded-lg relative';
      newItem.innerHTML = `
        <div class="space-y-2">
          <select class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white" onchange="handleFeeTypeSelection(this)">
            <option value="">Select Fee Type...</option>
          </select>
          <div class="flex gap-2">
            <input type="number" placeholder="Amount Due" step="0.01" min="0" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <input type="number" placeholder="Paid" step="0.01" min="0" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm">
          </div>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="absolute -top-2 -right-2 bg-red-600 hover:bg-red-700 text-white w-6 h-6 rounded-full text-xs flex items-center justify-center">×</button>
      `;
      container.appendChild(newItem);
      updateFeeTypeDropdowns();
    }

    function updateFeeTypeDropdowns() {
      const dropdowns = document.querySelectorAll('#feeItemsContainer select');
      dropdowns.forEach(dropdown => {
        const currentValue = dropdown.value;
        dropdown.innerHTML = '<option value="">Select Fee Type...</option>';
        
        if (allFeeTypes && allFeeTypes.length > 0) {
          allFeeTypes.forEach(feeType => {
            const option = document.createElement('option');
            option.value = feeType.id;
            option.textContent = `${feeType.fee_name} (₱${parseFloat(feeType.default_amount).toLocaleString('en-PH', {minimumFractionDigits: 2})})`;
            option.dataset.amount = feeType.default_amount;
            option.dataset.name = feeType.fee_name;
            dropdown.appendChild(option);
          });
        }
        
        dropdown.value = currentValue;
      });
    }

    function handleFeeTypeChange(selectElement) {
      console.log('handleFeeTypeChange called');
      const container = selectElement.closest('.space-y-2');
      const amountInput = container.querySelector('input[placeholder="Amount Due"]');
      
      console.log('Selected value:', selectElement.value);
      console.log('Amount input found:', amountInput);
      
      if (selectElement.value === '') {
        if (amountInput) amountInput.value = '';
      } else {
        const selectedOption = selectElement.options[selectElement.selectedIndex];
        console.log('Selected option:', selectedOption);
        console.log('Option dataset:', selectedOption ? selectedOption.dataset : 'none');
        
        if (selectedOption && selectedOption.dataset && selectedOption.dataset.amount) {
          const amount = parseFloat(selectedOption.dataset.amount).toFixed(2);
          console.log('Setting amount to:', amount);
          if (amountInput) amountInput.value = amount;
        } else {
          if (amountInput) amountInput.value = '';
        }
      }
    }

    function matchAmountDue(button) {
      const container = button.closest('.space-y-2');
      const amountDueInput = container.querySelector('input[placeholder="Amount Due"]');
      const paidInput = container.querySelector('input[placeholder="Paid"]');
      
      if (amountDueInput && paidInput && amountDueInput.value) {
        paidInput.value = amountDueInput.value;
      }
    }

    function matchEditAmountDue() {
      const amountDue = document.getElementById('editAmountDue').value;
      const paidInput = document.getElementById('editPaidAmount');
      
      if (amountDue && paidInput) {
        paidInput.value = amountDue;
        checkPaymentAmount(); // Trigger payment method check
      }
    }

    function matchAllAmountsDue() {
      const container = document.getElementById('feeItemsContainer');
      const feeItems = container.querySelectorAll('.space-y-2');
      
      feeItems.forEach(item => {
        const amountDueInput = item.querySelector('input[placeholder="Amount Due"]');
        const paidInput = item.querySelector('input[placeholder="Paid"]');
        
        if (amountDueInput && paidInput && amountDueInput.value) {
          paidInput.value = amountDueInput.value;
        }
      });
    }

    function editFeePayment(feeId, feeType, amountDue, currentPaid) {
      // Populate the edit form
      document.getElementById('editFeeId').value = feeId;
      document.getElementById('editFeeType').value = feeType;
      document.getElementById('editAmountDue').value = amountDue.toFixed(2);
      document.getElementById('editPaidAmount').value = currentPaid.toFixed(2);
      
      // Reset messages
      document.getElementById('editPaymentError').classList.add('hidden');
      document.getElementById('editPaymentSuccess').classList.add('hidden');
      
      // Check if payment method should be shown
      checkPaymentAmount();
      
      // Show modal
      document.getElementById('editPaymentModal').classList.remove('hidden');
    }
    
    function checkPaymentAmount() {
      const paidAmount = parseFloat(document.getElementById('editPaidAmount').value) || 0;
      const amountDue = parseFloat(document.getElementById('editAmountDue').value) || 0;
      const paymentMethodDiv = document.getElementById('paymentMethodDiv');
      const paymentMethodSelect = document.getElementById('editPaymentMethod');
      
      // Show payment method field if paid amount equals amount due
      if (Math.abs(paidAmount - amountDue) < 0.01 && paidAmount > 0) {
        paymentMethodDiv.classList.remove('hidden');
        paymentMethodSelect.setAttribute('required', 'required');
      } else {
        paymentMethodDiv.classList.add('hidden');
        paymentMethodSelect.removeAttribute('required');
      }
    }

    function closeEditPaymentModal() {
      document.getElementById('editPaymentModal').classList.add('hidden');
      focusRFID();
    }

    function submitPaymentEdit(event) {
      if (event) event.preventDefault();
      
      const form = document.getElementById('editPaymentForm');
      const formData = new FormData(form);
      
      const submitBtn = document.getElementById('submitEditPaymentBtn');
      const errorDiv = document.getElementById('editPaymentError');
      const successDiv = document.getElementById('editPaymentSuccess');
      
      // Reset messages
      errorDiv.classList.add('hidden');
      successDiv.classList.add('hidden');
      
      // Disable submit button
      submitBtn.disabled = true;
      submitBtn.textContent = 'Updating...';
      
      fetch('UpdatePayment.php', {
        method: 'POST',
        body: formData
      })
      .then(response => {
        console.log('Response status:', response.status);
        return response.json();
      })
      .then(data => {
        console.log('Response data:', data);
        if (data.success) {
          successDiv.textContent = data.message;
          successDiv.classList.remove('hidden');
          
          // Close modal immediately and refresh data
          closeEditPaymentModal();
          
          // Use stored RFID to refresh immediately
          if (window.currentStudentRFID) {
            console.log('Refreshing after payment edit with RFID:', window.currentStudentRFID);
            handleRFID(window.currentStudentRFID);
          }
        } else {
          errorDiv.textContent = data.message || 'An error occurred';
          errorDiv.classList.remove('hidden');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        errorDiv.textContent = 'An error occurred. Please try again.';
        errorDiv.classList.remove('hidden');
      })
      .finally(() => {
        // Re-enable submit button
        submitBtn.disabled = false;
        submitBtn.textContent = 'Update Payment';
      });
    }

    // ===== FEE TYPE MANAGEMENT =====
    let allFeeTypes = [];
    
    function showFeeTypeModal() {
      document.getElementById('feeTypeModal').classList.remove('hidden');
      loadFeeTypes();
      
      // Reset form
      document.getElementById('addFeeTypeForm').reset();
      document.getElementById('feeTypeError').classList.add('hidden');
      document.getElementById('feeTypeSuccess').classList.add('hidden');
    }
    
    function closeFeeTypeModal() {
      document.getElementById('feeTypeModal').classList.add('hidden');
      focusRFID();
    }
    
    function loadFeeTypes() {
      return fetch('ManageFeeTypes.php')
        .then(response => response.json())
        .then(data => {
          console.log('LoadFeeTypes response:', data);
          if (data.success && data.data) {
            allFeeTypes = data.data;
            renderFeeTypesList(data.data);
            if (typeof updateFeeTypeDropdowns === 'function') {
              updateFeeTypeDropdowns();
            }
          } else {
            document.getElementById('feeTypesList').innerHTML = '<div class="text-center py-4 text-red-500">Failed to load fee types</div>';
          }
          return data;
        })
        .catch(error => {
          console.error('Error loading fee types:', error);
          document.getElementById('feeTypesList').innerHTML = '<div class="text-center py-4 text-red-500">Error loading fee types</div>';
          throw error;
        });
    }
    
    function renderFeeTypesList(feeTypes) {
      const container = document.getElementById('feeTypesList');
      
      if (!feeTypes || feeTypes.length === 0) {
        container.innerHTML = '<p class="text-gray-500 text-center py-4">No fee types found</p>';
        return;
      }
      
      let html = '<div class="space-y-2">';
      feeTypes.forEach(feeType => {
        html += `
          <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
            <div>
              <div class="font-medium">${feeType.fee_name}</div>
              <div class="text-sm text-gray-600">₱${parseFloat(feeType.default_amount).toFixed(2)}</div>
            </div>
            <div class="flex gap-2">
              <button onclick="editFeeType(${feeType.id}, '${feeType.fee_name}', ${feeType.default_amount})" 
                      class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm">
                Edit
              </button>
              <button onclick="deleteFeeType(${feeType.id}, '${feeType.fee_name}')" 
                      class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">
                Delete
              </button>
            </div>
          </div>
        `;
      });
      html += '</div>';
      
      container.innerHTML = html;
    }
    
    function submitFeeType(event) {
      if (event) event.preventDefault();
      
      const form = document.getElementById('addFeeTypeForm');
      const formData = new FormData(form);
      
      // Check if this is an edit operation
      const editId = document.getElementById('editFeeTypeId');
      if (editId && editId.value) {
        formData.append('action', 'edit');
        formData.append('id', editId.value);
      } else {
        formData.append('action', 'add');
      }
      
      console.log('Submitting fee type:', {
        action: formData.get('action'),
        fee_name: formData.get('fee_name'),
        default_amount: formData.get('default_amount'),
        id: formData.get('id')
      });
      
      fetch('ManageFeeTypes.php', {
        method: 'POST',
        body: formData
      })
      .then(response => {
        console.log('Response status:', response.status);
        return response.text();
      })
      .then(text => {
        console.log('Raw response:', text);
        try {
          const data = JSON.parse(text);
          if (data.success) {
            // Reset form
            form.reset();
            if (editId) editId.value = '';
            
            // Reset button text
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.textContent = 'Add Fee Type';
            
            // Show success message
            alert('Fee type saved successfully!');
            
            // Reload fee types list
            loadFeeTypes();
            
            // Update dropdowns in add balance modal
            if (typeof updateFeeTypeDropdowns === 'function') {
              updateFeeTypeDropdowns();
            }
          } else {
            alert('Error: ' + (data.message || 'Failed to save fee type'));
          }
        } catch (e) {
          console.error('JSON parse error:', e);
          alert('Server error: ' + text);
        }
      })
      .catch(error => {
        console.error('Fetch error:', error);
        alert('Network error saving fee type');
      });
    }
    
    function editFeeType(id, name, amount) {
      // Populate form with existing data
      document.getElementById('feeTypeName').value = name;
      document.getElementById('feeTypeAmount').value = amount;
      
      // Add hidden field for edit ID if it doesn't exist
      let editIdField = document.getElementById('editFeeTypeId');
      if (!editIdField) {
        editIdField = document.createElement('input');
        editIdField.type = 'hidden';
        editIdField.id = 'editFeeTypeId';
        editIdField.name = 'edit_id';
        document.getElementById('addFeeTypeForm').appendChild(editIdField);
      }
      editIdField.value = id;
      
      // Change button text
      const submitBtn = document.querySelector('#addFeeTypeForm button[type="submit"]');
      submitBtn.textContent = 'Update Fee Type';
    }
    
    function deleteFeeType(id, name) {
      if (!confirm(`Are you sure you want to delete "${name}"?`)) {
        return;
      }
      
      const formData = new FormData();
      formData.append('action', 'delete');
      formData.append('id', id);
      
      fetch('ManageFeeTypes.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert('Fee type deleted successfully!');
          
          // Reload fee types list
          loadFeeTypes();
          
          // Update dropdowns in add balance modal
          updateFeeTypeDropdowns();
        } else {
          alert('Error: ' + (data.message || 'Failed to delete fee type'));
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Error deleting fee type');
      });
    }
    

    // Term dropdown functionality
    let currentStudentId = null;
    
    function toggleTermDropdown(studentId) {
      currentStudentId = studentId;
      const dropdown = document.getElementById('termDropdown');
      
      if (dropdown.classList.contains('hidden')) {
        // Show dropdown and load terms
        dropdown.classList.remove('hidden');
        loadAvailableTerms(studentId);
      } else {
        dropdown.classList.add('hidden');
      }
    }
    
    function loadAvailableTerms(studentId) {
      const dropdown = document.getElementById('termDropdown');
      dropdown.innerHTML = '<div class="p-2 text-sm text-gray-500">Loading available terms...</div>';
      
      fetch(`GetAvailableTerms.php?id_number=${encodeURIComponent(studentId)}`)
        .then(response => response.json())
        .then(data => {
          if (data.success && data.terms && data.terms.length > 0) {
            let termsHTML = '';
            data.terms.forEach(term => {
              termsHTML += `
                <button onclick="switchToTerm('${studentId}', '${term}')" 
                        class="w-full text-left px-3 py-2 hover:bg-gray-100 text-sm transition-colors">
                  ${term}
                </button>
              `;
            });
            dropdown.innerHTML = termsHTML;
          } else {
            dropdown.innerHTML = '<div class="p-2 text-sm text-gray-500">No other terms available</div>';
          }
        })
        .catch(error => {
          console.error('Error loading terms:', error);
          dropdown.innerHTML = '<div class="p-2 text-sm text-red-500">Error loading terms</div>';
        });
    }
    
    function switchToTerm(studentId, term) {
      // Hide dropdown
      document.getElementById('termDropdown').classList.add('hidden');
      
      // Fetch balance data for the selected term
      fetch(`GetBalance.php?rfid_uid=${window.currentStudentRFID}&term=${encodeURIComponent(term)}`)
        .then(response => response.json())
        .then(data => {
          console.log('Term switch data:', data);
          // Update student info
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
            <button onclick="clearStudentData()" class="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
              Close
            </button>
          </div>
          `;
          
          // Update balance display
          updateBalanceDisplay(data);
          
          // Update history display
          updateHistoryDisplay(data);
        })
        .catch(error => {
          console.error('Error switching term:', error);
        });
    }
    
    function updateBalanceDisplay(data) {
      if (data.school_year_term === "No balance record") {
        // Set default term for new balance
        const currentYear = new Date().getFullYear();
        const nextYear = currentYear + 1;
        const defaultTerm = `${currentYear}-${nextYear} 1st Semester`;
        
        document.getElementById('tab-balance').innerHTML = `
          <div class="flex items-center justify-between mb-4">
            <div class="relative">
              <button id="termSelector" onclick="toggleTermDropdown('${data.id_number}')" 
                      class="text-lg font-semibold text-gray-800 hover:text-blue-600 flex items-center gap-2 transition-colors">
                ${defaultTerm}
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
              </button>
              <div id="termDropdown" class="hidden absolute top-full left-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-10 min-w-64">
                <div class="p-2 text-sm text-gray-500 border-b">Loading available terms...</div>
              </div>
            </div>
            <div class="flex gap-2">
              <button onclick="showAddBalanceFormAndRefresh('${data.id_number}', '${data.full_name}')" 
                      class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">
                Add More Fees
              </button>
            </div>
          </div>
          
          <div class="bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm">
            <table class="min-w-full">
              <thead class="bg-gradient-to-r from-gray-800 to-gray-900 text-white">
                <tr>
                  <th class="px-4 py-3 text-center text-sm font-semibold">#</th>
                  <th class="px-4 py-3 text-left text-sm font-semibold">Fee Type</th>
                  <th class="px-4 py-3 text-right text-sm font-semibold">Amount Due</th>
                  <th class="px-4 py-3 text-right text-sm font-semibold">Paid</th>
                  <th class="px-4 py-3 text-right text-sm font-semibold">Balance</th>
                  <th class="px-4 py-3 text-center text-sm font-semibold">Action</th>
                </tr>
              </thead>
              <tbody class="text-gray-800 text-sm">
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No fee items found</td></tr>
              </tbody>
            </table>
            
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-t-2 border-blue-200">
              <div class="flex justify-between items-center">
                <div class="text-sm text-gray-600">
                  <span class="font-medium">Total Due:</span> ₱0.00 | 
                  <span class="font-medium">Total Paid:</span> ₱0.00
                </div>
                <div class="text-right">
                  <span class="text-sm text-gray-600 font-medium">Remaining Balance:</span>
                  <div class="text-xl font-bold text-green-600">
                    ₱0.00
                  </div>
                </div>
              </div>
            </div>
          </div>
        `;
      } else {
        // Display balance data for selected term
        document.getElementById('tab-balance').innerHTML = `
          <div class="flex items-center justify-between mb-4">
            <div class="relative">
              <button id="termSelector" onclick="toggleTermDropdown('${data.id_number}')" 
                      class="text-lg font-semibold text-gray-800 hover:text-blue-600 flex items-center gap-2 transition-colors">
                ${data.school_year_term}
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
              </button>
              <div id="termDropdown" class="hidden absolute top-full left-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-10 min-w-64">
                <div class="p-2 text-sm text-gray-500 border-b">Loading available terms...</div>
              </div>
            </div>
            <div class="flex gap-2">
              <button onclick="showAddBalanceForm('${data.id_number}', '${data.full_name}')" 
                      class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">
                Add More Fees
              </button>
              <button onclick="showPaymentSchedule('${data.id_number}', '${data.school_year_term}')" 
                      class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm">
                Payment Schedule
              </button>
            </div>
          </div>
          
          <div class="bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm">
            <table class="min-w-full">
              <thead class="bg-gradient-to-r from-gray-800 to-gray-900 text-white">
                <tr>
                  <th class="px-4 py-3 text-center text-sm font-semibold">#</th>
                  <th class="px-4 py-3 text-left text-sm font-semibold">Fee Type</th>
                  <th class="px-4 py-3 text-right text-sm font-semibold">Amount Due</th>
                  <th class="px-4 py-3 text-right text-sm font-semibold">Paid</th>
                  <th class="px-4 py-3 text-right text-sm font-semibold">Balance</th>
                  <th class="px-4 py-3 text-center text-sm font-semibold">Action</th>
                </tr>
              </thead>
              <tbody class="text-gray-800 text-sm">
                ${data.fee_items && data.fee_items.length > 0 ? data.fee_items.map((fee, index) => {
                  const amountDue = parseFloat(fee.amount || 0);
                  const paid = parseFloat(fee.paid || 0);
                  const balance = amountDue - paid;
                  return `
                  <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 text-center font-medium">${index + 1}</td>
                    <td class="px-4 py-3 font-medium">${fee.fee_type}</td>
                    <td class="px-4 py-3 text-right font-semibold">₱${amountDue.toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                    <td class="px-4 py-3 text-right ${paid > 0 ? 'text-green-600 font-semibold' : 'text-gray-500'}">₱${paid.toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                    <td class="px-4 py-3 text-right font-bold ${balance > 0 ? 'text-red-600' : 'text-green-600'}">₱${balance.toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                    <td class="px-4 py-3 text-center">
                      <button onclick="editFeePayment(${fee.id}, '${fee.fee_type}', ${amountDue}, ${paid})" 
                              class="bg-yellow-500 hover:bg-yellow-600 text-white px-2 py-1 rounded text-xs font-medium transition-colors">
                        Edit
                      </button>
                    </td>
                  </tr>
                  `;
                }).join('') : '<tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No fee items found</td></tr>'}
              </tbody>
            </table>
            
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-t-2 border-blue-200">
              <div class="flex justify-between items-center">
                <div class="text-sm text-gray-600">
                  <span class="font-medium">Total Due:</span> ₱${data.gross_total.toLocaleString('en-PH', {minimumFractionDigits: 2})} | 
                  <span class="font-medium">Total Paid:</span> ₱${data.total_paid.toLocaleString('en-PH', {minimumFractionDigits: 2})}
                </div>
                <div class="text-right">
                  <span class="text-sm text-gray-600 font-medium">Remaining Balance:</span>
                  <div class="text-xl font-bold ${data.remaining_balance > 0 ? 'text-red-600' : 'text-green-600'}">
                    ₱${data.remaining_balance.toLocaleString('en-PH', {minimumFractionDigits: 2})}
                  </div>
                </div>
              </div>
            </div>
          </div>
        `;
      }
    }
    
    function updateHistoryDisplay(data) {
      if (data.history && data.history.length > 0) {
        let historyHTML = '';
        data.history.forEach((row, index) => {
          const formattedDate = new Date(row.date).toLocaleDateString('en-US', {
            month: '2-digit',
            day: '2-digit',
            year: '2-digit'
          });
          
          historyHTML += `
            <tr class="${index % 2 === 0 ? 'bg-white' : 'bg-gray-50'}">
              <td class="px-4 py-3 text-sm text-gray-900">${index + 1}</td>
              <td class="px-4 py-3 text-sm text-gray-900">${formattedDate}</td>
              <td class="px-4 py-3 text-sm text-gray-900">${row.fee_type || 'Payment'}</td>
              <td class="px-4 py-3 text-sm font-semibold text-gray-900">₱${parseFloat(row.amount).toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
              <td class="px-4 py-3 text-sm text-gray-600">${row.payment_method || 'Cash'}</td>
            </tr>
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
          <div class="overflow-hidden rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-900">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">#</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Date</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Description</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Amount</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Method</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                ${historyHTML}
              </tbody>
            </table>
          </div>
        `;
      } else {
        document.getElementById('tab-history').innerHTML = `
          <div class="text-center py-12">
            <div class="w-16 h-16 mx-auto bg-gray-100 rounded-lg flex items-center justify-center mb-4">
              <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">No Transaction History</h3>
            <p class="text-sm text-gray-500">This student has no payment records on file</p>
          </div>
        `;
      }
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', (event) => {
      const dropdown = document.getElementById('termDropdown');
      const termSelector = document.getElementById('termSelector');
      
      if (dropdown && termSelector && 
          !dropdown.contains(event.target) && 
          !termSelector.contains(event.target)) {
        dropdown.classList.add('hidden');
      }
    });
    
    // Clear student data function
    function clearStudentData() {
      // Clear student info
      document.getElementById('student-info').innerHTML = `
        <div class="text-center text-gray-500">
          <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
          </svg>
          <p class="text-sm italic">Scan RFID or search to display student information</p>
        </div>
      `;
      
      // Clear balance tab
      document.getElementById('tab-balance').innerHTML = `
        <div class="text-center text-gray-500 py-12">
          <div class="w-16 h-16 mx-auto school-gradient rounded-lg flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-gray-800 mb-2">No Balance Data</h3>
          <p class="text-sm text-gray-500">Search for a student or scan RFID to view balance information</p>
        </div>
      `;
      
      // Clear history tab
      document.getElementById('tab-history').innerHTML = `
        <div class="text-center text-gray-500 py-12">
          <div class="w-16 h-16 mx-auto school-gradient rounded-lg flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-gray-800 mb-2">No Transaction History</h3>
          <p class="text-sm text-gray-500">Search for a student or scan RFID to view transaction history</p>
        </div>
      `;
      
      // Clear search results and input
      document.getElementById('searchResults').innerHTML = '';
      document.getElementById('searchInput').value = '';
      
      // Clear stored RFID
      window.currentStudentRFID = null;
      
      // Focus back to RFID input
      focusRFID();
    }
    
    // Focus RFID when page loads
    window.onload = focusRFID;
  </script>

</body>
</html>