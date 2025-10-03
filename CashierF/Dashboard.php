<?php
session_start();

// ✅ Require cashier role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'cashier') {
    header("Location: ../StudentLogin/login.php");
    exit;
}

// ✅ Prevent caching (so back button after logout doesn't show dashboard)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
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
  <!-- QR Scanner Library -->
  <script src="https://unpkg.com/html5-qrcode" defer></script>
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
    /* Mirror the QR camera preview */
    #qrReader video {
      transform: scaleX(-1);
      -webkit-transform: scaleX(-1);
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
                class="bg-[#0B2C62] hover:bg-blue-900 text-white px-6 py-3 rounded-xl font-medium transition-colors">
          Manage Fee Types
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
      </div>
      <div id="searchError" class="mt-2 text-red-600 text-sm"></div>
      <div id="searchResults" class="mt-4 space-y-2"></div>
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
      <!-- Inline error message for invalid scans -->
      <p id="qrInlineError" class="mt-2 text-red-600 text-sm hidden"></p>
      <p id="qrStatus" class="mt-3 text-sm text-gray-600">Point your camera at the QR code. The code should contain the RFID number.</p>
      <div class="flex gap-3 pt-4">
        <button onclick="switchQRScannerCamera()" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 rounded-lg">Switch Camera</button>
        <button onclick="closeQRScanner()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2 rounded-lg">Close</button>
      </div>
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
            <input type="hidden" id="editAmountDueHidden" name="amount_due">
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
                      class="absolute right-2 top-2 bottom-2 bg-[#0B2C62] hover:bg-blue-900 text-white px-3 rounded text-sm">
                Match
              </button>
            </div>
          </div>
          
          <div id="paymentMethodDiv" class="hidden">
            <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
            <div class="space-y-2">
              <select id="editPaymentMethod" name="payment_method" 
                      class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                      onchange="handleEditPaymentMethodChange(this)">
                <option value="Cash">Cash</option>
                <option value="GCash">GCash</option>
                <option value="Bank Transfer">Bank Transfer</option>
                <option value="Check">Check</option>
                <option value="Credit Card">Credit Card</option>
                <option value="Other">Other (Manual Input)</option>
              </select>
              <input type="text" id="editManualPaymentInput" placeholder="Enter payment method" 
                     class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent hidden">
            </div>
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
      <!-- MESSAGES MOVED TO TOP FOR VISIBILITY -->
      <div id="feeTypeError" class="hidden mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg text-sm"></div>
      <div id="feeTypeSuccess" class="hidden mb-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded-lg text-sm"></div>
      
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
            <button type="submit" class="bg-[#0B2C62] hover:bg-blue-900 text-white px-6 py-2 rounded-lg font-medium transition-colors">
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
    </div>
  </div>

  <!-- Delete Confirmation Modal (matches Registrar style) -->
  <div id="feeTypeDeleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[60]">
    <div class="bg-white rounded-2xl p-6 w-full max-w-sm mx-4">
      <div class="text-center">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
          <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
          </svg>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">Delete Fee Type</h3>
        <p class="text-sm text-gray-500 mb-6">Are you sure you want to delete <span id="feeTypeDeleteName" class="font-medium text-gray-900"></span>? This action cannot be undone.</p>
        <div class="flex gap-3">
          <button onclick="hideFeeTypeDeleteModal()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg font-medium">Cancel</button>
          <button onclick="confirmFeeTypeDelete()" class="flex-1 bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg font-medium">Delete</button>
        </div>
      </div>
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
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Academic Year</label>
                <select id="schoolYear" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                  <option value="">Select Year</option>
                  <!-- Years will be populated dynamically by JavaScript -->
                </select>
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Semester</label>
                <select id="schoolSemester" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                  <option value="">Select Semester</option>
                  <option value="1st Semester">1st Semester</option>
                  <option value="2nd Semester">2nd Semester</option>
                </select>
              </div>
            </div>
            <input type="hidden" id="schoolTerm" name="school_year_term">
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Student Fees</label>
            <div id="feeItemsContainer" class="space-y-3">
              <div class="space-y-2">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Fee Type</label>
                  <select id="mainFeeTypeSelect" onchange="handleFeeTypeChange(this)" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
                    <option value="">Select Fee Type...</option>
                  </select>
                </div>
                <div class="flex gap-2">
                  <input type="number" placeholder="Amount Due" step="0.01" min="0" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm" onchange="checkMainFormPayment(this)" oninput="checkMainFormPayment(this)">
                  <input type="number" placeholder="Paid" step="0.01" min="0" class="flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm" onchange="checkMainFormPayment(this)" oninput="checkMainFormPayment(this)">
                </div>
                <div id="addBalancePaymentMethodDiv" class="hidden">
                  <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                  <div class="space-y-2">
                    <select id="addBalancePaymentMethod" name="payment_method" 
                            class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            onchange="handleAddBalancePaymentMethodChange(this)">
                      <option value="Cash">Cash</option>
                      <option value="GCash">GCash</option>
                      <option value="Bank Transfer">Bank Transfer</option>
                      <option value="Check">Check</option>
                      <option value="Credit Card">Credit Card</option>
                      <option value="Other">Other (Manual Input)</option>
                    </select>
                    <input type="text" id="addBalanceManualPaymentInput" placeholder="Enter payment method" 
                           class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent hidden">
                  </div>
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

    // ===== QR SCANNER (html5-qrcode) =====
    let qrScanner = null;
    let qrCameraIds = [];
    let qrCameraIndex = 0;
    let qrStarting = false;

    // Inline error helper for the scanner modal
    function setQRInlineError(message) {
      const el = document.getElementById('qrInlineError');
      if (!el) return;
      if (message) {
        el.textContent = message;
        el.classList.remove('hidden');
      } else {
        el.textContent = '';
        el.classList.add('hidden');
      }
    }

    // Toggle mirror based on the active track's facingMode (front/user vs environment)
    function setMirrorFromCurrentStream() {
      try {
        const readerEl = document.getElementById('qrReader');
        const video = readerEl ? readerEl.querySelector('video') : null;
        const stream = video && video.srcObject ? video.srcObject : null;
        const tracks = stream ? stream.getVideoTracks() : [];
        const settings = tracks.length ? (tracks[0].getSettings ? tracks[0].getSettings() : {}) : {};
        const fm = (settings.facingMode || '').toString().toLowerCase();
        // Front cameras usually report 'user' or contain 'front'
        if (fm.includes('user') || fm.includes('front') || fm.includes('selfie')) {
          readerEl && readerEl.classList.add('mirror-video');
        } else {
          readerEl && readerEl.classList.remove('mirror-video');
        }
      } catch (_) {}
    }

    function ensureHtml5QrcodeReady() {
      return new Promise((resolve) => {
        if (window.Html5Qrcode) return resolve();
        const check = setInterval(() => {
          if (window.Html5Qrcode) { clearInterval(check); resolve(); }
        }, 100);
      });
    }

    async function showQRScanner() {
      try {
        const modal = document.getElementById('qrScannerModal');
        if (modal) modal.classList.remove('hidden');
        document.getElementById('qrStatus').textContent = 'Initializing camera...';
        setQRInlineError('');

        await ensureHtml5QrcodeReady();

        // Get cameras once
        if (!qrCameraIds.length) {
          const devices = await Html5Qrcode.getCameras();
          qrCameraIds = (devices || []).map(d => d.id);
          if (!qrCameraIds.length) {
            document.getElementById('qrStatus').textContent = 'No camera found.';
            return;
          }
        }

        await startQRScannerWithCamera(qrCameraIds[qrCameraIndex]);
      } catch (e) {
        console.error('QR init error:', e);
        const status = document.getElementById('qrStatus');
        if (status) status.textContent = 'Failed to start camera: ' + (e && e.message ? e.message : e);
      }
    }

    async function startQRScannerWithCamera(cameraId) {
      if (qrStarting) return; // prevent double start
      qrStarting = true;
      try {
        // Stop any existing instance
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
          async (decodedText, decodedResult) => {
            // Expect QR to contain the RFID UID directly
            const text = (decodedText || '').trim();
            if (!text) return;
            // Validate first so we can show error INSIDE the modal instead of closing it
            try {
              const res = await fetch(`SearchStudent.php?query=${encodeURIComponent(text)}`);
              const data = await res.json();
              const students = (data && data.students) ? data.students : [];
              // Accept if we can find at least one student whose rfid_uid matches exactly
              const match = students.find(s => (s.rfid_uid || '').toString() === text);
              if (match) {
                setQRInlineError('');
                closeQRScanner();
                handleRFID(text);
              } else {
                setQRInlineError('Invalid QR/RFID. Please scan a registered student QR.');
              }
            } catch (e) {
              setQRInlineError('Scan check failed. Please try again.');
            }
          },
          (errorMessage) => {
            // per-frame decode errors are noisy; show minimal status
            const el = document.getElementById('qrStatus');
            if (el) el.textContent = 'Scanning...';
          }
        );
        // Apply mirror a moment after video attaches
        setTimeout(setMirrorFromCurrentStream, 150);
        const status = document.getElementById('qrStatus');
        if (status) status.textContent = 'Scanning...';
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
      // Return focus to RFID field for normal flow
      focusRFID();
    }

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

        // If history is empty (e.g., after adding a payment), proactively fetch
        const body = document.getElementById('historyBody');
        const rows = body ? body.querySelectorAll('tr').length : 0;
        if (rows === 0 && window.currentStudentRFID) {
          // Reset pagination and load fresh history
          window.historyOffset = 0;
          window.historyHasMore = true;
          window.historyLoading = false;
          handleRFID(window.currentStudentRFID);
        }
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
      searchBtn.classList.add('bg-[#0B2C62]');
    }
    function showError(msg) {
      searchError.textContent = msg;
      searchBtn.classList.remove('bg-[#0B2C62]');
      searchBtn.classList.add('bg-red-600', 'shake');
      setTimeout(() => {
        searchBtn.classList.remove('shake');
        searchBtn.classList.add('bg-[#0B2C62]');
      }, 600);
    }

    // Show a top-level error for invalid RFID/QR scans
    function showRFIDError(message) {
      if (!message) return;
      const el = document.getElementById('searchError');
      if (el) {
        el.textContent = message;
      }
      // brief visual feedback on the Search button
      searchBtn.classList.remove('bg-[#0B2C62]');
      searchBtn.classList.add('bg-red-600');
      setTimeout(() => {
        searchBtn.classList.remove('bg-red-600');
        searchBtn.classList.add('bg-[#0B2C62]');
      }, 1200);
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

    // ===== Helper functions to support Add Balance modal =====
    // Cache for fee types loaded from server
    window.cachedFeeTypes = window.cachedFeeTypes || null;

    async function loadFeeTypes() {
      try {
        if (window.cachedFeeTypes) return window.cachedFeeTypes;
        const res = await fetch('ManageFeeTypes.php');
        const data = await res.json();
        if (data && data.success && Array.isArray(data.data)) {
          window.cachedFeeTypes = data.data;
          return window.cachedFeeTypes;
        }
        window.cachedFeeTypes = [];
        return [];
      } catch (e) {
        console.error('Failed to load fee types:', e);
        window.cachedFeeTypes = [];
        return [];
      }
    }

    function updateFeeTypeDropdowns() {
      const select = document.getElementById('mainFeeTypeSelect');
      if (!select) return;
      // Clear existing (keep first placeholder)
      while (select.options.length > 1) select.remove(1);
      const types = Array.isArray(window.cachedFeeTypes) ? window.cachedFeeTypes : [];
      types.forEach(ft => {
        const opt = document.createElement('option');
        opt.value = ft.id || ft.fee_name;
        opt.textContent = `${ft.fee_name}${ft.default_amount ? ` (₱${Number(ft.default_amount).toLocaleString('en-PH', {minimumFractionDigits:2})})` : ''}`;
        opt.setAttribute('data-name', ft.fee_name);
        opt.setAttribute('data-amount', ft.default_amount || 0);
        select.appendChild(opt);
      });
      // Optionally add a custom item entry in the future
      // const custom = document.createElement('option');
      // custom.value = 'custom'; custom.textContent = 'Custom Fee Type...';
      // select.appendChild(custom);
    }

    // ===== FEE TYPE MODAL HANDLERS =====
    function renderFeeTypesList(types) {
      const list = document.getElementById('feeTypesList');
      if (!list) return;
      if (!Array.isArray(types) || types.length === 0) {
        list.innerHTML = '<div class="text-center py-4 text-gray-500">No fee types yet.</div>';
        return;
      }
      list.innerHTML = types.map(t => {
        const id = t.id;
        const name = t.fee_name;
        const amt = Number(t.default_amount || 0);
        const safeAttrName = String(name).replace(/&/g, '&amp;').replace(/"/g, '&quot;');
        return `
          <div id="fee-row-${id}" class="flex items-center justify-between border rounded-lg px-4 py-3 bg-white">
            <div class="flex-1">
              <div class="font-medium text-gray-800">${name}</div>
              <div class="text-sm text-gray-600">Default: ₱${amt.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</div>
            </div>
            <div class="flex items-center gap-2">
              <button class="px-3 py-1 text-xs rounded bg-[#0B2C62] hover:bg-blue-900 text-white" onclick="startEditFeeType(${id})">Edit</button>
              <button class="px-3 py-1 text-xs rounded bg-red-500 hover:bg-red-600 text-white" onclick="openFeeTypeDeleteFromBtn(this)" data-id="${id}" data-name="${safeAttrName}">Delete</button>
            </div>
          </div>`;
      }).join('');
    }

    async function showFeeTypeModal() {
      // Reset messages
      const err = document.getElementById('feeTypeError');
      const ok = document.getElementById('feeTypeSuccess');
      if (err) { err.classList.add('hidden'); err.textContent = ''; }
      if (ok) { ok.classList.add('hidden'); ok.textContent = ''; }

      // Load and render list
      const types = await loadFeeTypes();
      renderFeeTypesList(types);

      // Show modal
      const modal = document.getElementById('feeTypeModal');
      if (modal) modal.classList.remove('hidden');
    }

    function closeFeeTypeModal() {
      const modal = document.getElementById('feeTypeModal');
      if (modal) modal.classList.add('hidden');
      focusRFID();
    }

    // Smoothly scroll the fee type modal content to the top (for messages visibility)
    function scrollFeeTypeModalTop() {
      const container = document.querySelector('#feeTypeModal > div');
      if (container && typeof container.scrollTo === 'function') {
        container.scrollTo({ top: 0, behavior: 'smooth' });
      } else if (container) {
        container.scrollTop = 0;
      }
    }

    // ===== Delete Confirmation (Registrar-style) =====
    let feeTypeToDelete = null;

    function openFeeTypeDeleteModal(id, name) {
      feeTypeToDelete = id;
      const nameSpan = document.getElementById('feeTypeDeleteName');
      if (nameSpan) nameSpan.textContent = name || 'this fee type';
      const modal = document.getElementById('feeTypeDeleteModal');
      if (modal) modal.classList.remove('hidden');
    }

    function hideFeeTypeDeleteModal() {
      const modal = document.getElementById('feeTypeDeleteModal');
      if (modal) modal.classList.add('hidden');
      feeTypeToDelete = null;
    }

    function confirmFeeTypeDelete() {
      if (feeTypeToDelete == null) return;
      deleteFeeType(feeTypeToDelete);
      hideFeeTypeDeleteModal();
    }

    // Convenience: open modal from button with data attributes
    function openFeeTypeDeleteFromBtn(btn) {
      if (!btn) return;
      const id = btn.getAttribute('data-id');
      const name = btn.getAttribute('data-name') || '';
      openFeeTypeDeleteModal(id, name);
    }

    // Add new fee type
    function submitFeeType(ev) {
      if (ev) ev.preventDefault();
      const nameEl = document.getElementById('feeTypeName');
      const amtEl = document.getElementById('feeTypeAmount');
      const err = document.getElementById('feeTypeError');
      const ok = document.getElementById('feeTypeSuccess');
      if (err) { err.classList.add('hidden'); err.textContent = ''; }
      if (ok) { ok.classList.add('hidden'); ok.textContent = ''; }

      const name = (nameEl?.value || '').trim();
      const amt = parseFloat(amtEl?.value || '0');
      if (!name) { if (err){err.textContent='Fee name is required'; err.classList.remove('hidden');} return; }
      if (amt < 0) { if (err){err.textContent='Default amount must be non-negative'; err.classList.remove('hidden');} return; }

      const fd = new FormData();
      fd.append('action', 'add');
      fd.append('fee_name', name);
      fd.append('default_amount', String(amt));

      fetch('ManageFeeTypes.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(async data => {
          if (!data.success) { if (err){err.textContent = data.message || 'Failed to add fee type'; err.classList.remove('hidden'); scrollFeeTypeModalTop();} return; }
          if (ok) { ok.textContent = data.message || 'Fee type added'; ok.classList.remove('hidden'); }
          // Invalidate cache and refresh UI/dropdowns
          window.cachedFeeTypes = null;
          const types = await loadFeeTypes();
          renderFeeTypesList(types);
          updateFeeTypeDropdowns();
          // Clear inputs
          if (nameEl) nameEl.value = '';
          if (amtEl) amtEl.value = '';
          scrollFeeTypeModalTop();
        })
        .catch(() => { if (err){err.textContent='Network error while adding fee type'; err.classList.remove('hidden'); scrollFeeTypeModalTop();} });
    }

    // Inline edit helpers
    function startEditFeeType(id) {
      const row = document.getElementById(`fee-row-${id}`);
      if (!row) return;
      const current = window.cachedFeeTypes?.find(t => String(t.id) === String(id));
      const name = current ? current.fee_name : '';
      const amt = current ? Number(current.default_amount || 0) : 0;
      row.innerHTML = `
        <div class="flex-1 flex items-center gap-3">
          <input id="edit-name-${id}" type="text" class="border rounded px-3 py-2 w-1/2" value="${name}">
          <input id="edit-amt-${id}" type="number" step="0.01" min="0" class="border rounded px-3 py-2 w-40" value="${amt.toFixed(2)}">
        </div>
        <div class="flex items-center gap-2">
          <button class="px-3 py-1 text-xs rounded bg-[#0B2C62] hover:bg-blue-900 text-white" onclick="saveEditFeeType(${id})">Save</button>
          <button class="px-3 py-1 text-xs rounded bg-gray-300 hover:bg-gray-400 text-gray-800" onclick="cancelEditFeeType(${id})">Cancel</button>
        </div>`;
    }

    function cancelEditFeeType(id) {
      // Re-render from cache
      renderFeeTypesList(window.cachedFeeTypes || []);
    }

    function saveEditFeeType(id) {
      const nameEl = document.getElementById(`edit-name-${id}`);
      const amtEl = document.getElementById(`edit-amt-${id}`);
      const err = document.getElementById('feeTypeError');
      const ok = document.getElementById('feeTypeSuccess');
      if (err) { err.classList.add('hidden'); err.textContent = ''; }
      if (ok) { ok.classList.add('hidden'); ok.textContent = ''; }
      const name = (nameEl?.value || '').trim();
      const amt = parseFloat(amtEl?.value || '0');
      if (!name) { if (err){err.textContent='Fee name is required'; err.classList.remove('hidden');} return; }
      if (amt < 0) { if (err){err.textContent='Default amount must be non-negative'; err.classList.remove('hidden');} return; }

      const fd = new FormData();
      fd.append('action', 'edit');
      fd.append('id', String(id));
      fd.append('fee_name', name);
      fd.append('default_amount', String(amt));

      fetch('ManageFeeTypes.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(async data => {
          if (!data.success) { if (err){err.textContent = data.message || 'Failed to update fee type'; err.classList.remove('hidden'); scrollFeeTypeModalTop();} return; }
          if (ok) { ok.textContent = data.message || 'Fee type updated'; ok.classList.remove('hidden'); }
          window.cachedFeeTypes = null;
          const types = await loadFeeTypes();
          renderFeeTypesList(types);
          updateFeeTypeDropdowns();
          scrollFeeTypeModalTop();
        })
        .catch(() => { if (err){err.textContent='Network error while updating fee type'; err.classList.remove('hidden'); scrollFeeTypeModalTop();} });
    }

    function deleteFeeType(id) {
      const err = document.getElementById('feeTypeError');
      const ok = document.getElementById('feeTypeSuccess');
      const fd = new FormData();
      fd.append('action', 'delete');
      fd.append('id', String(id));
      fetch('ManageFeeTypes.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(async data => {
          if (!data.success) { if (err){err.textContent = data.message || 'Failed to delete fee type'; err.classList.remove('hidden'); scrollFeeTypeModalTop();} return; }
          if (ok) { ok.textContent = data.message || 'Fee type deleted'; ok.classList.remove('hidden'); }
          window.cachedFeeTypes = null;
          const types = await loadFeeTypes();
          renderFeeTypesList(types);
          updateFeeTypeDropdowns();
          scrollFeeTypeModalTop();
        })
        .catch(() => { if (err){err.textContent='Network error while deleting fee type'; err.classList.remove('hidden'); scrollFeeTypeModalTop();} });
    }

    function handleFeeTypeChange(el) {
      // When fee type changes, if there is a default amount, prefill Amount Due
      try {
        const row = el.closest('.space-y-2');
        const amount = el.selectedOptions && el.selectedOptions[0] ? Number(el.selectedOptions[0].getAttribute('data-amount') || 0) : 0;
        if (row) {
          const nums = row.querySelectorAll('input[type="number"]');
          if (nums && nums[0]) {
            if (!nums[0].value || Number(nums[0].value) === 0) {
              nums[0].value = amount ? amount.toFixed(2) : '';
            }
          }
        }
      } catch (e) {
        console.warn('handleFeeTypeChange error:', e);
      }
    }

    function matchAllAmountsDue() {
      // Sets Paid equal to Amount Due for all items in the Add Balance form
      const container = document.getElementById('feeItemsContainer');
      if (!container) return;
      container.querySelectorAll('.space-y-2').forEach(row => {
        const nums = row.querySelectorAll('input[type="number"]');
        if (nums.length >= 2) {
          const due = parseFloat(nums[0].value) || 0;
          nums[1].value = due > 0 ? due.toFixed(2) : '';
          // Update payment method visibility for both main and dynamic rows
          if (row.parentElement && row.parentElement.id === 'feeItemsContainer') {
            // main first row check
            checkMainFormPayment(nums[1]);
          } else {
            checkAddBalancePayment(nums[1]);
          }
        }
      });
    }

    // ===== FETCH BALANCE & HISTORY =====
    function handleRFID(rfid) {
      console.log('handleRFID called with:', rfid);
      // Store RFID globally for refresh purposes
      window.currentStudentRFID = rfid;
      
  // Reset history pagination state
  window.historyLimit = 10;
  window.historyOffset = 0;
  window.historyHasMore = false;
  window.historyLoading = false;
  // Read filters if present (use globals as fallback before UI renders)
  const startInput = document.getElementById('historyStartDate');
  const endInput = document.getElementById('historyEndDate');
  const startVal = (startInput && startInput.value) ? startInput.value : (window.historyStartDate || '');
  const endVal = (endInput && endInput.value) ? endInput.value : (window.historyEndDate || '');
  const startParam = startVal ? `&start_date=${encodeURIComponent(startVal)}` : '';
  const endParam = endVal ? `&end_date=${encodeURIComponent(endVal)}` : '';

  fetch(`GetBalance.php?rfid_uid=${encodeURIComponent(rfid)}&limit=${window.historyLimit}&offset=0${startParam}${endParam}`)
    .then(res => {
      console.log('GetBalance response status:', res.status);
      return res.json();
    })
    .then(data => {
      console.log('GetBalance response data:', data);
      // Guard: show error and stop if RFID is not registered/invalid
      if (!data || data.error || !data.id_number || !data.full_name) {
        showRFIDError('RFID not found or not registered. Please scan a valid student QR/RFID.');
        clearRFIDAndFocus();
        return;
      }
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
                      class="w-full text-left bg-white border border-gray-300 rounded-lg px-4 py-3 text-gray-800 hover:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent flex items-center justify-between transition-all">
                <span class="font-medium">${defaultTerm}</span>
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
              </button>
              <div id="termDropdown" class="hidden absolute top-full left-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-10 min-w-64">
                <div class="p-2 text-sm text-gray-500 border-b">Loading available terms...</div>
              </div>
            </div>
            <div class="flex gap-2">
              <button onclick="showAddBalanceFormAndRefresh('${data.id_number}', '${data.full_name}')" 
                      class="bg-[#0B2C62] hover:bg-blue-900 text-white px-3 py-1 rounded text-sm">
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
              <button onclick="showAddBalanceFormAndRefresh('${data.id_number}', '${data.full_name}')" 
                      class="bg-[#0B2C62] hover:bg-blue-900 text-white px-3 py-1 rounded text-sm">
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
                ${data.fee_items && data.fee_items.length > 0 ? data.fee_items.filter(fee => {
                  const amountDue = parseFloat(fee.amount || 0);
                  const paid = parseFloat(fee.paid || 0);
                  const isPaid = amountDue <= paid;
                  // Show unpaid items OR recently paid items (marked with temporary flag)
                  return amountDue > paid || (isPaid && window.temporarilyVisibleFees && window.temporarilyVisibleFees.has(fee.id));
                }).map((fee, index) => {
                  const amountDue = parseFloat(fee.amount || 0);
                  const paid = parseFloat(fee.paid || 0);
                  const balance = amountDue - paid;
                  const isPaid = balance <= 0;
                  
                  return `
                  <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors ${isPaid ? 'bg-green-50' : ''}">
                    <td class="px-4 py-3 text-center font-medium">${index + 1}</td>
                    <td class="px-4 py-3 font-medium">${fee.fee_type} ${isPaid ? '<span class="text-xs text-green-600 font-semibold">(PAID)</span>' : ''}</td>
                    <td class="px-4 py-3 text-right font-semibold">₱${amountDue.toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                    <td class="px-4 py-3 text-right ${paid > 0 ? 'text-green-600 font-semibold' : 'text-gray-500'}">₱${paid.toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                    <td class="px-4 py-3 text-right font-bold ${isPaid ? 'text-green-600' : 'text-red-600'}">₱${Math.abs(balance).toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                    <td class="px-4 py-3 text-center">
                      ${isPaid ? '<span class="text-green-600 text-xs font-semibold">✓ PAID</span>' : `<button onclick="editFeePayment(${fee.id}, '${fee.fee_type}', ${amountDue}, ${paid})" class="bg-[#0B2C62] hover:bg-blue-900 text-white px-2 py-1 rounded text-xs font-medium transition-colors">Edit</button>`}
                    </td>
                  </tr>
                  `;
                }).join('') : '<tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No unpaid fees</td></tr>'}
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


      // Use the reusable history display function
      updateHistoryDisplay(data);

      clearRFIDAndFocus();
    })
    .catch(err => {
      console.error(err);
      clearRFIDAndFocus();
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
    function resetAddBalanceForm() {
      // Reset the main fee type dropdown
      document.getElementById('mainFeeTypeSelect').selectedIndex = 0;
      
      // Clear amount inputs
      const amountInputs = document.querySelectorAll('#feeItemsContainer input[type="number"]');
      amountInputs.forEach(input => input.value = '');
      
      // Reset payment method dropdown and hide manual input
      document.getElementById('addBalancePaymentMethod').selectedIndex = 0;
      document.getElementById('addBalanceManualPaymentInput').value = '';
      document.getElementById('addBalanceManualPaymentInput').classList.add('hidden');
      document.getElementById('addBalancePaymentMethodDiv').classList.add('hidden');
      
      // Hide error/success messages
      document.getElementById('balanceError').classList.add('hidden');
      document.getElementById('balanceSuccess').classList.add('hidden');
    }

    function showAddBalanceForm(studentId, studentName) {
      // Reset form completely
      resetAddBalanceForm();
      
      // Populate the form with student data
      document.getElementById('studentDisplay').value = studentName;
      document.getElementById('studentId').value = studentId;
      
      // Ensure academic years dropdown is populated before setting defaults
      if (typeof populateAcademicYears === 'function') {
        populateAcademicYears();
      }

      // Get latest term from database and set as default
      fetch('GetLatestTerm.php')
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            setSchoolYearAndSemester(data.latest_term);
          } else {
            // Fallback to current year 1st semester
            const currentYear = new Date().getFullYear();
            const nextYear = currentYear + 1;
            setSchoolYearAndSemester(`${currentYear}-${nextYear} 1st Semester`);
          }
          // Update hidden composed field after setting values
          if (typeof updateSchoolTermHidden === 'function') {
            updateSchoolTermHidden();
          }
        })
        .catch(err => {
          console.error('Error getting latest term:', err);
          // Fallback to current year 1st semester
          const currentYear = new Date().getFullYear();
          const nextYear = currentYear + 1;
          setSchoolYearAndSemester(`${currentYear}-${nextYear} 1st Semester`);
          if (typeof updateSchoolTermHidden === 'function') {
            updateSchoolTermHidden();
          }
        });
      
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

    // Function to check payment method for main form
    function checkMainFormPayment(input) {
      const container = document.getElementById('feeItemsContainer');
      const mainFeeItem = container.querySelector('.space-y-2');
      const amountInputs = mainFeeItem.querySelectorAll('input[type="number"]');
      const amountDue = parseFloat(amountInputs[0].value) || 0;
      const paid = parseFloat(amountInputs[1].value) || 0;
      const paymentMethodDiv = document.getElementById('addBalancePaymentMethodDiv');
      
      console.log('checkMainFormPayment - Amount Due:', amountDue, 'Paid:', paid);
      
      if (paid > 0 && paid >= amountDue && amountDue > 0) {
        console.log('Showing main payment method section');
        paymentMethodDiv.classList.remove('hidden');
      } else {
        console.log('Hiding main payment method section');
        paymentMethodDiv.classList.add('hidden');
      }
    }

    // Function to check payment method for dynamic fee items
    function checkAddBalancePayment(input) {
      const row = input.closest('.space-y-2');
      const amountInputs = row.querySelectorAll('input[type="number"]');
      const amountDue = parseFloat(amountInputs[0].value) || 0;
      const paid = parseFloat(amountInputs[1].value) || 0;
      const paymentMethodSection = row.querySelector('.payment-method-section');
      
      console.log('checkAddBalancePayment (dynamic) - Amount Due:', amountDue, 'Paid:', paid);
      
      if (paid > 0 && paid >= amountDue && amountDue > 0) {
        console.log('Showing dynamic payment method section');
        paymentMethodSection.classList.remove('hidden');
      } else {
        console.log('Hiding dynamic payment method section');
        paymentMethodSection.classList.add('hidden');
      }
    }

    // Function to handle payment method dropdown change in Add Balance form (copied from Edit Payment logic)
    function handleAddBalancePaymentMethodChange(select) {
      const manualInput = document.getElementById('addBalanceManualPaymentInput');
      
      if (select.value === 'Other') {
        manualInput.classList.remove('hidden');
        manualInput.required = true;
        manualInput.name = 'payment_method';
        select.name = '';
      } else {
        manualInput.classList.add('hidden');
        manualInput.required = false;
        manualInput.value = '';
        manualInput.name = '';
        select.name = 'payment_method';
      }
    }

    // Function to handle payment method dropdown change for dynamic fee items
    function handleDynamicPaymentMethodChange(select) {
      const row = select.closest('.space-y-2');
      const manualInput = row.querySelector('.manual-payment-input');
      
      if (select.value === 'Other') {
        manualInput.classList.remove('hidden');
        manualInput.required = true;
      } else {
        manualInput.classList.add('hidden');
        manualInput.required = false;
        manualInput.value = '';
      }
    }

    // Function to handle payment method dropdown change in Edit Payment modal
    function handleEditPaymentMethodChange(select) {
      const manualInput = document.getElementById('editManualPaymentInput');
      
      if (select.value === 'Other') {
        manualInput.classList.remove('hidden');
        manualInput.required = true;
        manualInput.name = 'payment_method';
        select.name = '';
      } else {
        manualInput.classList.add('hidden');
        manualInput.required = false;
        manualInput.value = '';
        manualInput.name = '';
        select.name = 'payment_method';
      }
    }

    // ===== Minimal handlers to enable Edit modal =====
    function editFeePayment(feeId, feeType, amountDue, paid) {
      // Populate fields
      const idEl = document.getElementById('editFeeId');
      const typeEl = document.getElementById('editFeeType');
      const dueEl = document.getElementById('editAmountDue');
      const paidEl = document.getElementById('editPaidAmount');
      if (!idEl || !typeEl || !dueEl || !paidEl) return;
      idEl.value = feeId;
      typeEl.value = feeType;
      dueEl.value = Number(amountDue || 0).toFixed(2);
      paidEl.value = Number(paid || 0).toFixed(2);

      // Reset method controls and messages
      const methodSel = document.getElementById('editPaymentMethod');
      if (methodSel) methodSel.value = 'Cash';
      const manual = document.getElementById('editManualPaymentInput');
      if (manual) { manual.classList.add('hidden'); manual.required = false; manual.value = ''; }
      const err = document.getElementById('editPaymentError');
      if (err) { err.classList.add('hidden'); err.textContent = ''; }
      const ok = document.getElementById('editPaymentSuccess');
      if (ok) { ok.classList.add('hidden'); ok.textContent = ''; }

      // Show or hide payment method based on amounts
      checkPaymentAmount();

      // Open modal
      const modal = document.getElementById('editPaymentModal');
      if (modal) modal.classList.remove('hidden');
    }

    function checkPaymentAmount() {
      const due = parseFloat((document.getElementById('editAmountDue')?.value) || '0');
      const paid = parseFloat((document.getElementById('editPaidAmount')?.value) || '0');
      const div = document.getElementById('paymentMethodDiv');
      if (!div) return;
      if (paid > 0 && paid >= due && due > 0) {
        div.classList.remove('hidden');
      } else {
        div.classList.add('hidden');
      }
    }

    function matchEditAmountDue() {
      const dueEl = document.getElementById('editAmountDue');
      const paidEl = document.getElementById('editPaidAmount');
      if (!dueEl || !paidEl) return;
      const due = parseFloat(dueEl.value || '0');
      paidEl.value = due > 0 ? due.toFixed(2) : '0.00';
      checkPaymentAmount();
    }

    function closeEditPaymentModal() {
      const modal = document.getElementById('editPaymentModal');
      if (modal) modal.classList.add('hidden');
      focusRFID();
    }

    function submitPaymentEdit(ev) {
      if (ev) ev.preventDefault();
      const feeId = parseInt((document.getElementById('editFeeId')?.value) || '0', 10);
      const paid = parseFloat((document.getElementById('editPaidAmount')?.value) || '0');
      const due = parseFloat((document.getElementById('editAmountDue')?.value) || '0');
      let method = (document.getElementById('editPaymentMethod')?.value) || 'Cash';
      const manual = document.getElementById('editManualPaymentInput');
      if (method === 'Other' && manual && manual.value.trim()) method = manual.value.trim();

      const err = document.getElementById('editPaymentError');
      if (err) { err.classList.add('hidden'); err.textContent = ''; }

      if (!feeId || feeId <= 0) { if (err){err.textContent='Invalid fee item'; err.classList.remove('hidden');} return; }
      if (paid < 0) { if (err){err.textContent='Paid amount cannot be negative'; err.classList.remove('hidden');} return; }
      if (paid > due) { if (err){err.textContent=`Paid cannot exceed Amount Due (₱${due.toFixed(2)})`; err.classList.remove('hidden');} return; }

      const fd = new FormData();
      fd.append('fee_id', String(feeId));
      fd.append('paid_amount', String(paid));
      fd.append('payment_method', method);

      fetch('UpdatePayment.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
          if (!data.success) { if (err){err.textContent = data.message || 'Failed to update payment'; err.classList.remove('hidden');} return; }
          closeEditPaymentModal();
          if (window.currentStudentRFID) handleRFID(window.currentStudentRFID);
          if (data.or_number) { try { window.open(`Receipt.php?or=${encodeURIComponent(data.or_number)}`, '_blank'); } catch(_){} }
        })
        .catch(() => { if (err){err.textContent='Network error. Please try again.'; err.classList.remove('hidden');} });
    }

    function submitBalance(event) {
      if (event) event.preventDefault();
      
      console.log('Submit balance called');
      
      const form = document.getElementById('addBalanceForm');
      const formData = new FormData(form);
      
      // Collect fee items with correct structure
      const feeItems = [];
      const feeItemsContainer = document.getElementById('feeItemsContainer');
      const feeItemRows = feeItemsContainer.querySelectorAll('.space-y-2');
      
      console.log('Found fee item rows:', feeItemRows.length);
      
      feeItemRows.forEach(row => {
        const selectElement = row.querySelector('select');
        const textInput = row.querySelector('input[type="text"]');
        const amountInputs = row.querySelectorAll('input[type="number"]');
        const amountDueInput = amountInputs[0]; // First number input is amount due
        const paidInput = amountInputs[1]; // Second number input is paid amount
        
        let feeTypeName = '';
        let paymentMethod = 'Cash'; // Default payment method
        
        // Determine fee type name
        if (selectElement && selectElement.value && selectElement.value !== 'custom') {
          const selectedOption = selectElement.options[selectElement.selectedIndex];
          feeTypeName = selectedOption.dataset.name || selectedOption.textContent.split(' (₱')[0];
        } else if (textInput && textInput.style.display !== 'none' && textInput.value.trim()) {
          feeTypeName = textInput.value.trim();
        }
        
        // Get payment method if payment section is visible (check both main form and dynamic sections)
        const paymentMethodDiv = document.getElementById('addBalancePaymentMethodDiv');
        const paymentMethodSection = row.querySelector('.payment-method-section');
        
        if (paymentMethodDiv && !paymentMethodDiv.classList.contains('hidden')) {
          // Main form payment method
          const paymentSelect = document.getElementById('addBalancePaymentMethod');
          const manualInput = document.getElementById('addBalanceManualPaymentInput');
          
          if (paymentSelect.value === 'Other' && manualInput && manualInput.value.trim()) {
            paymentMethod = manualInput.value.trim();
          } else {
            paymentMethod = paymentSelect.value;
          }
        } else if (paymentMethodSection && !paymentMethodSection.classList.contains('hidden')) {
          // Dynamic fee item payment method
          const paymentSelect = paymentMethodSection.querySelector('select');
          const manualInput = paymentMethodSection.querySelector('.manual-payment-input');
          
          if (paymentSelect.value === 'Other' && manualInput && manualInput.value.trim()) {
            paymentMethod = manualInput.value.trim();
          } else {
            paymentMethod = paymentSelect.value;
          }
        }
        
        if (feeTypeName && amountDueInput && amountDueInput.value) {
          const feeItem = {
            fee_type: feeTypeName,
            amount_due: parseFloat(amountDueInput.value) || 0,
            paid: parseFloat(paidInput ? paidInput.value : 0) || 0,
            payment_method: paymentMethod
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
          
          // If any ORs were created for fully paid items, open their receipts
          if (Array.isArray(data.or_numbers) && data.or_numbers.length > 0) {
            data.or_numbers.forEach((or, idx) => {
              setTimeout(() => {
                window.open(`Receipt.php?or=${encodeURIComponent(or)}`, '_blank');
              }, idx * 300);
            });
          }
          
          // Mark newly added fees as temporarily visible if they are fully paid
          if (data.added_fees) {
            window.temporarilyVisibleFees = window.temporarilyVisibleFees || new Set();
            data.added_fees.forEach(fee => {
              const amountDue = parseFloat(fee.amount_due || 0);
              const paid = parseFloat(fee.paid || 0);
              if (paid >= amountDue && fee.id) {
                window.temporarilyVisibleFees.add(fee.id);
              }
            });
          }
          
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

    // ... rest of your code ...

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
          
          // If an OR was generated, open the receipt in a new tab
          if (data.or_number) {
            window.open(`Receipt.php?or=${encodeURIComponent(data.or_number)}`, '_blank');
          }
          
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

    // ... rest of your code ...

    function submitPaymentEdit(event) {
      if (event) event.preventDefault();
      
      const form = document.getElementById('editPaymentForm');
      const formData = new FormData(form);
      
      // Debug: Log form data
      console.log('Form data being sent:');
      for (let [key, value] of formData.entries()) {
        console.log(key + ': ' + value);
      }
      
      fetch('UpdatePayment.php', {
        method: 'POST',
        body: formData
      })
      .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then(data => {
        console.log('Response data:', data);
        if (data.success) {
          closeEditPaymentModal();
          
          // If an OR was generated, open the receipt in a new tab
          if (data.or_number) {
            window.open(`Receipt.php?or=${encodeURIComponent(data.or_number)}`, '_blank');
          }
          
          // Update the UI immediately for paid fees
          const feeId = parseInt(formData.get('fee_id'));
          const paidAmount = parseFloat(formData.get('paid_amount'));
          const amountDue = parseFloat(formData.get('amount_due'));
          
          if (paidAmount >= amountDue) {
            // Mark this fee as temporarily visible and update UI immediately
            window.temporarilyVisibleFees = window.temporarilyVisibleFees || new Set();
            window.temporarilyVisibleFees.add(feeId);
            
            console.log('Calling updateBalanceRowToPaid for fee ID:', feeId, 'with amount:', paidAmount);
            // Update the specific row in the balance table immediately
            updateBalanceRowToPaid(feeId, paidAmount);
          } else {
            console.log('Payment not full amount - paid:', paidAmount, 'due:', amountDue);
          }
          
          // Don't refresh immediately - let the UI update show first
          // Refresh after a short delay to update totals
          setTimeout(() => {
            if (window.currentStudentRFID) {
              handleRFID(window.currentStudentRFID);
            }
          }, 500);
        } else {
          // Show error in modal instead of alert
          const errorDiv = document.getElementById('editPaymentError');
          errorDiv.textContent = data.message || 'Failed to update payment';
          errorDiv.classList.remove('hidden');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        // Show error in modal instead of alert
        const errorDiv = document.getElementById('editPaymentError');
        errorDiv.textContent = 'An error occurred while updating the payment';
        errorDiv.classList.remove('hidden');
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
      dropdown.innerHTML = '<div class="p-2 text-sm text-gray-500 border-b">Loading available terms...</div>';
      
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
      
      // Reset pagination and include filters
      window.historyOffset = 0; window.historyHasMore = false; window.historyLoading = false;
      const startInput = document.getElementById('historyStartDate');
      const endInput = document.getElementById('historyEndDate');
      const startVal = (startInput && startInput.value) ? startInput.value : (window.historyStartDate || '');
      const endVal = (endInput && endInput.value) ? endInput.value : (window.historyEndDate || '');
      const startParam = startVal ? `&start_date=${encodeURIComponent(startVal)}` : '';
      const endParam = endVal ? `&end_date=${encodeURIComponent(endVal)}` : '';
      fetch(`GetBalance.php?rfid_uid=${window.currentStudentRFID}&term=${encodeURIComponent(term)}&limit=${window.historyLimit}&offset=0${startParam}${endParam}`)
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
                      class="w-full text-left bg-white border border-gray-300 rounded-lg px-4 py-3 text-gray-800 hover:border-blue-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent flex items-center justify-between transition-all">
                <span class="font-medium">${defaultTerm}</span>
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
              </button>
              <div id="termDropdown" class="hidden absolute top-full left-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg z-10 min-w-64">
                <div class="p-2 text-sm text-gray-500 border-b">Loading available terms...</div>
              </div>
            </div>
            <div class="flex gap-2">
              <button onclick="showAddBalanceFormAndRefresh('${data.id_number}', '${data.full_name}')" 
                      class="bg-[#0B2C62] hover:bg-blue-900 text-white px-3 py-1 rounded text-sm">
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
                ${data.fee_items && data.fee_items.length > 0 ? data.fee_items.filter(fee => {
                  const amountDue = parseFloat(fee.amount || 0);
                  const paid = parseFloat(fee.paid || 0);
                  const isPaid = amountDue <= paid;
                  // Show unpaid items OR recently paid items (marked with temporary flag)
                  return amountDue > paid || (isPaid && window.temporarilyVisibleFees && window.temporarilyVisibleFees.has(fee.id));
                }).map((fee, index) => {
                  const amountDue = parseFloat(fee.amount || 0);
                  const paid = parseFloat(fee.paid || 0);
                  const balance = amountDue - paid;
                  const isPaid = balance <= 0;
                  
                  return `
                  <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors ${isPaid ? 'bg-green-50' : ''}">
                    <td class="px-4 py-3 text-center font-medium">${index + 1}</td>
                    <td class="px-4 py-3 font-medium">${fee.fee_type} ${isPaid ? '<span class="text-xs text-green-600 font-semibold">(PAID)</span>' : ''}</td>
                    <td class="px-4 py-3 text-right font-semibold">₱${amountDue.toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                    <td class="px-4 py-3 text-right ${paid > 0 ? 'text-green-600 font-semibold' : 'text-gray-500'}">₱${paid.toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                    <td class="px-4 py-3 text-right font-bold ${isPaid ? 'text-green-600' : 'text-red-600'}">₱${Math.abs(balance).toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                    <td class="px-4 py-3 text-center">
                      ${isPaid ? '<span class="text-green-600 text-xs font-semibold">✓ PAID</span>' : `<button onclick=\"editFeePayment(${fee.id}, '${fee.fee_type}', ${amountDue}, ${paid})\" class=\"bg-[#0B2C62] hover:bg-blue-900 text-white px-2 py-1 rounded text-xs font-medium transition-colors\">Edit</button>`}
                    </td>
                  </tr>
                  `;
                }).join('') : '<tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No unpaid fees</td></tr>'}
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
              <button onclick="showAddBalanceFormAndRefresh('${data.id_number}', '${data.full_name}')" 
                      class="bg-[#0B2C62] hover:bg-blue-900 text-white px-3 py-1 rounded text-sm">
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
                ${data.fee_items && data.fee_items.length > 0 ? data.fee_items.filter(fee => {
                  const amountDue = parseFloat(fee.amount || 0);
                  const paid = parseFloat(fee.paid || 0);
                  const isPaid = amountDue <= paid;
                  // Show unpaid items OR recently paid items (marked with temporary flag)
                  return amountDue > paid || (isPaid && window.temporarilyVisibleFees && window.temporarilyVisibleFees.has(fee.id));
                }).map((fee, index) => {
                  const amountDue = parseFloat(fee.amount || 0);
                  const paid = parseFloat(fee.paid || 0);
                  const balance = amountDue - paid;
                  const isPaid = balance <= 0;
                  
                  return `
                  <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors ${isPaid ? 'bg-green-50' : ''}">
                    <td class="px-4 py-3 text-center font-medium">${index + 1}</td>
                    <td class="px-4 py-3 font-medium">${fee.fee_type} ${isPaid ? '<span class="text-xs text-green-600 font-semibold">(PAID)</span>' : ''}</td>
                    <td class="px-4 py-3 text-right font-semibold">₱${amountDue.toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                    <td class="px-4 py-3 text-right ${paid > 0 ? 'text-green-600 font-semibold' : 'text-gray-500'}">₱${paid.toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                    <td class="px-4 py-3 text-right font-bold ${isPaid ? 'text-green-600' : 'text-red-600'}">₱${Math.abs(balance).toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                    <td class="px-4 py-3 text-center">
                      ${isPaid ? '<span class="text-green-600 text-xs font-semibold">✓ PAID</span>' : `<button onclick="editFeePayment(${fee.id}, '${fee.fee_type}', ${amountDue}, ${paid})" class="bg-[#0B2C62] hover:bg-blue-900 text-white px-2 py-1 rounded text-xs font-medium transition-colors">Edit</button>`}
                    </td>
                  </tr>
                  `;
                }).join('') : '<tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No unpaid fees</td></tr>'}
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
      // Track current term for subsequent paginated requests
      window.currentHistoryTerm = (data.school_year_term && data.school_year_term !== 'No balance record') ? data.school_year_term : null;

      // Build filter toolbar
      const filterBar = `
        <div class="flex flex-wrap items-end gap-4 mb-4">
          <div>
            <label class="block text-sm text-gray-700 mb-1">Start Date</label>
            <input type="date" id="historyStartDate" value="${window.historyStartDate || ''}" class="border rounded px-3 py-2 text-base" />
          </div>
          <div>
            <label class="block text-sm text-gray-700 mb-1">End Date</label>
            <input type="date" id="historyEndDate" value="${window.historyEndDate || ''}" class="border rounded px-3 py-2 text-base" />
          </div>
          <div class="flex items-end gap-2 pb-1">
            <button id="applyHistoryFiltersBtn" class="bg-[#0B2C62] hover:bg-blue-900 text-white px-4 py-2 rounded text-base">Apply</button>
            <button id="clearHistoryFiltersBtn" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded text-base">Clear</button>
          </div>
        </div>`;

      // Container and table shell
      document.getElementById('tab-history').innerHTML = `
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center">
            <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center mr-3">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
              </svg>
            </div>
            <div>
              <h2 class="text-xl font-bold text-gray-800">Transaction History</h2>
              <p class="text-sm text-gray-600">${data.school_year_term || 'All Terms'}</p>
            </div>
          </div>
        </div>
        ${filterBar}
        <div id="historyContainer" class="rounded-lg border border-gray-200 overflow-y-auto" style="max-height: 420px;">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-900">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">#</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Date</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">Description</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-white uppercase tracking-wider">Amount</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">Method</th>
                <th class="px-4 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody id="historyBody" class="bg-white divide-y divide-gray-200"></tbody>
          </table>
          <div id="historyLoadingRow" class="hidden px-4 py-3 text-sm text-gray-500">Loading more...</div>
          <div id="historySentinel" class="w-full h-6"></div>
        </div>
      `;

      // Render first page rows
      renderHistoryRows(data.history || [], false);

      // Set pagination state
      window.historyOffset = data.offset || 0;
      window.historyHasMore = !!data.has_more;

      // Wire up filters
      const applyBtn = document.getElementById('applyHistoryFiltersBtn');
      if (applyBtn) applyBtn.onclick = applyHistoryFilters;
      const clearBtn = document.getElementById('clearHistoryFiltersBtn');
      if (clearBtn) clearBtn.onclick = clearHistoryFilters;

      // Ensure inputs show the current global values even after re-render
      const sInp = document.getElementById('historyStartDate');
      const eInp = document.getElementById('historyEndDate');
      if (sInp && (window.historyStartDate || '')) sInp.value = window.historyStartDate || '';
      if (eInp && (window.historyEndDate || '')) eInp.value = window.historyEndDate || '';

      // Setup infinite scrolling
      setupHistoryInfiniteScroll();

      // If no rows came back (some datasets start at later offsets), proactively load once
      const initialRows = Array.isArray(data.history) ? data.history.length : 0;
      if (initialRows === 0) {
        // ensure we attempt another page
        if (typeof data.has_more === 'undefined') window.historyHasMore = true;
        // kick one manual load to populate
        loadMoreHistory();

        // Fallback: if still empty shortly after, fetch without term filter (all terms)
        setTimeout(async () => {
          const body = document.getElementById('historyBody');
          if (!body) return;
          const rowsNow = body.querySelectorAll('tr').length;
          if (rowsNow === 0 && window.currentStudentRFID) {
            try {
              const startInput = document.getElementById('historyStartDate');
              const endInput = document.getElementById('historyEndDate');
              const startParam = startInput && startInput.value ? `&start_date=${encodeURIComponent(startInput.value)}` : '';
              const endParam = endInput && endInput.value ? `&end_date=${encodeURIComponent(endInput.value)}` : '';
              const url = `GetBalance.php?rfid_uid=${encodeURIComponent(window.currentStudentRFID)}&limit=${window.historyLimit || 20}&offset=0${startParam}${endParam}`;
              const res = await fetch(url);
              const allData = await res.json();
              if (Array.isArray(allData.history) && allData.history.length) {
                window.historyOffset = allData.offset || allData.history.length;
                window.historyHasMore = !!allData.has_more;
                renderHistoryRows(allData.history, true);
              }
            } catch (_) {}
          }
        }, 700);
      }
    }

    function renderHistoryRows(rows, append = true) {
      const tbody = document.getElementById('historyBody');
      if (!tbody) return;
      const existingCount = append ? tbody.querySelectorAll('tr').length : 0;
      const chunk = rows.map((row, idx) => {
        const formattedDate = new Date(row.date).toLocaleDateString('en-US', { month: '2-digit', day: '2-digit', year: '2-digit' });
        const orSafe = row.or_number ? encodeURIComponent(row.or_number) : '';
        const seq = existingCount + idx + 1;
        return `
          <tr class="${seq % 2 === 1 ? 'bg-white' : 'bg-gray-50'}">
            <td class="px-4 py-3 text-sm text-gray-900">${seq}</td>
            <td class="px-4 py-3 text-sm text-gray-900">${formattedDate}</td>
            <td class="px-4 py-3 text-sm text-gray-900">${row.fee_type || 'Payment'}</td>
            <td class="px-4 py-3 text-sm font-semibold text-gray-900 text-right">₱${parseFloat(row.amount).toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
            <td class="px-4 py-3 text-sm text-gray-600 text-center">${row.payment_method || 'Cash'}</td>
            <td class="px-4 py-3 text-sm text-center">${row.or_number ? `<a href="Receipt.php?or=${orSafe}" target="_blank" class="inline-block bg-[#0B2C62] hover:bg-blue-900 text-white px-3 py-1 rounded text-xs">Print Receipt</a>` : '<span class="text-gray-400 text-xs">No OR</span>'}</td>
          </tr>`;
      }).join('');

      if (append) {
        tbody.insertAdjacentHTML('beforeend', chunk);
      } else {
        tbody.innerHTML = chunk;
      }
    }

    function setupHistoryInfiniteScroll() {
      const sentinel = document.getElementById('historySentinel');
      const container = document.getElementById('historyContainer');
      if (!sentinel || !container) return;

      if (window.historyObserver) {
        window.historyObserver.disconnect();
      }

      window.historyObserver = new IntersectionObserver(async (entries) => {
        for (const entry of entries) {
          if (entry.isIntersecting) {
            await loadMoreHistory();
          }
        }
      }, { root: container, rootMargin: '0px 0px 200px 0px' });
      window.historyObserver.observe(sentinel);
    }

    async function loadMoreHistory() {
      if (!window.historyHasMore || window.historyLoading) return;
      if (!window.currentStudentRFID) return;
      window.historyLoading = true;
      const loadingRow = document.getElementById('historyLoadingRow');
      if (loadingRow) loadingRow.classList.remove('hidden');

      try {
        const startInput = document.getElementById('historyStartDate');
        const endInput = document.getElementById('historyEndDate');
        const startParam = startInput && startInput.value ? `&start_date=${encodeURIComponent(startInput.value)}` : '';
        const endParam = endInput && endInput.value ? `&end_date=${encodeURIComponent(endInput.value)}` : '';
        const termParam = window.currentHistoryTerm ? `&term=${encodeURIComponent(window.currentHistoryTerm)}` : '';
        const url = `GetBalance.php?rfid_uid=${encodeURIComponent(window.currentStudentRFID)}${termParam}&limit=${window.historyLimit}&offset=${window.historyOffset}${startParam}${endParam}`;

        const res = await fetch(url);
        const data = await res.json();
        if (Array.isArray(data.history)) {
          renderHistoryRows(data.history, true);
          window.historyOffset = data.offset || (window.historyOffset + data.history.length);
          window.historyHasMore = !!data.has_more;
        } else {
          window.historyHasMore = false;
        }
      } catch (e) {
        console.error('Load more history failed:', e);
        window.historyHasMore = false;
      } finally {
        window.historyLoading = false;
        if (loadingRow) loadingRow.classList.add('hidden');
      }
    }

    function applyHistoryFilters() {
      if (!window.currentStudentRFID) return;
      // Save to globals so values persist after re-render
      const s = document.getElementById('historyStartDate');
      const e = document.getElementById('historyEndDate');
      window.historyStartDate = s && s.value ? s.value : '';
      window.historyEndDate = e && e.value ? e.value : '';
      // Reset and reload via handleRFID which respects filters
      handleRFID(window.currentStudentRFID);
    }

    function clearHistoryFilters() {
      // Reset globals and UI then reload
      window.historyStartDate = '';
      window.historyEndDate = '';
      const s = document.getElementById('historyStartDate');
      const e = document.getElementById('historyEndDate');
      if (s) s.value = '';
      if (e) e.value = '';
      if (window.currentStudentRFID) handleRFID(window.currentStudentRFID);
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
    
    // Function to set school year and semester dropdowns from combined string
    function setSchoolYearAndSemester(termString) {
      // Parse the term string (e.g., "2025-2026 2nd Semester")
      const parts = termString.split(' ');
      if (parts.length >= 2) {
        const year = parts[0];
        const semester = parts.slice(1).join(' ');
        
        // Set the dropdowns
        document.getElementById('schoolYear').value = year;
        document.getElementById('schoolSemester').value = semester;
        
        // Update the hidden field
        updateSchoolTermHidden();
      }
    }
    
    // Function to update the hidden field when dropdowns change
    function updateSchoolTermHidden() {
      const year = document.getElementById('schoolYear').value;
      const semester = document.getElementById('schoolSemester').value;
      
      if (year && semester) {
        document.getElementById('schoolTerm').value = `${year} ${semester}`;
      } else {
        document.getElementById('schoolTerm').value = '';
      }
    }
    
    // Function to populate academic years dynamically
    function populateAcademicYears() {
      const currentYear = new Date().getFullYear();
      const startYear = currentYear - 2; // Show 2 years back
      const endYear = currentYear + 2;   // Show 1 year forward
      
      const schoolYearSelect = document.getElementById('schoolYear');
      
      // Clear existing options except the first one
      while (schoolYearSelect.children.length > 1) {
        schoolYearSelect.removeChild(schoolYearSelect.lastChild);
      }
      
      // Generate academic years
      for (let year = startYear; year <= endYear; year++) {
        const nextYear = year + 1;
        const academicYear = `${year}-${nextYear}`;
        
        const option = document.createElement('option');
        option.value = academicYear;
        option.textContent = academicYear;
        schoolYearSelect.appendChild(option);
      }
    }
    
    // Add event listeners to the dropdowns
    document.addEventListener('DOMContentLoaded', function() {
      // Populate years when page loads
      populateAcademicYears();
      
      document.getElementById('schoolYear').addEventListener('change', updateSchoolTermHidden);
      document.getElementById('schoolSemester').addEventListener('change', updateSchoolTermHidden);
    });
    
    // Utility function to clear RFID input and refocus
    function clearRFIDAndFocus() {
      rfidInput.value = '';
      focusRFID();
    }
    
    // Clear student data function
    function clearStudentData() {
      // Clear student info
      document.getElementById('student-info').innerHTML = `
        <div class="text-center text-gray-500">
          <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
          </svg>
          <p class="text-lg font-medium">No Student Selected</p>
          <p class="text-sm">Scan RFID or search for a student</p>
        </div>
      `;
      
      // Clear balance tab
      document.getElementById('tab-balance').innerHTML = `
        <div class="text-center py-12">
          <div class="w-16 h-16 mx-auto bg-gray-100 rounded-lg flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-gray-800 mb-2">No Balance Information</h3>
          <p class="text-sm text-gray-500">Select a student to view their balance details</p>
        </div>
      `;
      
      // Clear history tab
      document.getElementById('tab-history').innerHTML = `
        <div class="text-center py-12">
          <div class="w-16 h-16 mx-auto bg-gray-100 rounded-lg flex items-center justify-center mb-4">
            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-gray-800 mb-2">No Transaction History</h3>
          <p class="text-sm text-gray-500">Select a student to view their payment history</p>
        </div>
      `;
      
      // Clear search results and input
      document.getElementById('searchResults').innerHTML = '';
      document.getElementById('searchInput').value = '';
      // Clear stored RFID
      window.currentStudentRFID = null;
    }
    
    // Focus RFID when page loads
    window.onload = focusRFID;
    
    // ===== PREVENT BACK BUTTON AFTER LOGOUT =====
    window.addEventListener("pageshow", function(event) {
      if (event.persisted || (performance.navigation.type === 2)) window.location.reload();
    });
  </script>

</body>
</html>