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
  <title>Cashier Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      20%, 60% { transform: translateX(-3px); }
      40%, 80% { transform: translateX(3px); }
    }
    .shake { animation: shake 0.3s; }
  </style>
</head>
<body class="bg-gray-100 font-sans min-h-screen">



  <!-- ✅ Hidden RFID input -->
  <input type="text" id="rfidInput" autofocus class="absolute opacity-0">

  <!-- Header -->
  <div class="bg-white p-4 border-b shadow-sm flex items-center justify-between relative">
    <div class="flex items-center gap-4">
      <!-- Burger Menu -->
      <div class="relative">
        <button id="menuBtn" class="text-2xl cursor-pointer">&#9776;</button>
        <!-- Dropdown -->
        <div id="menuDropdown" class="absolute left-0 mt-2 w-40 bg-white border rounded-lg shadow-lg hidden">
          <a href="logout.php" class="block px-4 py-2 text-black-500 hover:bg-gray-100">Logout</a>
        </div>
      </div>
      <h1 class="text-gray-900 text-sm">Cashier Dashboard</h1>
    </div>
  </div>

  <!-- ✅ Search Bar -->
<div class="bg-white px-6 py-3 border-b">
  <div class="flex gap-2 items-center">
    <div class="relative">
      <span class="absolute inset-y-0 left-0 flex items-center pl-2 pointer-events-none">
        <svg xmlns="http://www.w3.org/2000/svg" 
             class="h-4 w-4 text-gray-600" 
             fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 1110.5 3a7.5 7.5 0 016.15 13.65z" />
        </svg>
      </span>

      <!-- 🔎 Search Input -->
      <input
        type="text"
        id="searchInput"
        placeholder="Search by name or ID..."
        class="w-64 border border-gray-300 rounded pl-8 pr-3 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-black"
        onkeydown="if(event.key==='Enter'){ event.preventDefault(); searchStudent(); }"
      />
    </div>

    <!-- 🔎 Search Button -->
    <button
      id="searchBtn"
      onclick="searchStudent()"
      class="bg-black text-white px-3 py-1 rounded text-sm transition-colors duration-300"
    >Search</button>

    <p id="searchError" class="text-red-600 text-sm ml-3"></p>
  </div>

  <!-- 🔎 Search results -->
  <div id="searchResults" class="mt-3 space-y-1"></div>
</div>


  <!-- Tabs -->
  <div class="bg-white px-6 py-3 border-b flex justify-start gap-6">
    <button onclick="showTab('balance')" class="tab-btn font-medium border-b-2 border-black">Student Balance</button>
    <button onclick="showTab('history')" class="tab-btn text-gray-600 hover:text-black">Transaction History</button>
  </div>

  <!-- Content -->
  <div class="p-6 flex flex-col lg:flex-row gap-6">
    <!-- Student Info -->
    <div id="student-info" class="bg-white rounded-lg p-4 w-full lg:w-1/4 shadow">
      <p class="text-sm text-gray-500 italic">Scan RFID or use search to display student info...</p>
    </div>

    <!-- Tabs content -->
    <div class="w-full lg:w-3/4 space-y-4">
      <!-- Balance Tab -->
      <div id="tab-balance">
        <p class="text-sm text-gray-500 italic">No balance data yet.</p>
      </div>
      <!-- History Tab -->
      <div id="tab-history" class="hidden">
        <p class="text-sm text-gray-500 italic">No transaction history loaded.</p>
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
      document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('border-black', 'font-medium', 'text-black'));
      if (tab === 'balance') {
        document.getElementById('tab-balance').classList.remove('hidden');
        document.querySelectorAll('.tab-btn')[0].classList.add('border-black', 'font-medium', 'text-black');
      } else {
        document.getElementById('tab-history').classList.remove('hidden');
        document.querySelectorAll('.tab-btn')[1].classList.add('border-black', 'font-medium', 'text-black');
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
        showError('Please enter a name or ID');
        return;
      }
      fetch(`SearchBalance.php?query=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(data => {
          if (data.error) {
            showError(data.error);
            return;
          }
          const students = data.students || [];
          if (students.length === 0) {
            showError(`No student found for "${query}"`);
            return;
          }
          if (students.length === 1) {
            const s = students[0];
            if (s.rfid_uid) {
              handleRFID(s.rfid_uid);
              searchResults.innerHTML = '';
            } else {
              renderSearchResults(students, true);
              showError('Student has no RFID on file. Please scan or update RFID.');
            }
            return;
          }
          renderSearchResults(students, true);
        })
        .catch(() => {
          showError("Failed to fetch student data");
        });
    }

    // ===== FETCH BALANCE & HISTORY =====
    function handleRFID(rfid) {
  fetch(`GetBalance.php?rfid_uid=${encodeURIComponent(rfid)}`)
    .then(res => res.json())
    .then(data => {
      // ✅ Always render student info (never "Unknown Student")
      document.getElementById('student-info').innerHTML = `
        <div class="flex items-center mb-3">
          <div class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center text-xl">👤</div>
          <div class="ml-3 font-medium">${data.full_name || ''}</div>
        </div>
        <p class="text-sm text-gray-600">ID: ${data.id_number || ''}</p>
        <p class="text-sm text-gray-600">Program: ${data.program || ''}</p>
        <p class="text-sm text-gray-600">Year & Section: ${data.year_section || ''}</p>
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

      document.getElementById('tab-balance').innerHTML = `
  <div class="flex justify-between items-center">
    <label class="font-medium">${term}</label>
  </div>
  <div class="overflow-x-auto mt-4">
    <table class="min-w-full bg-white rounded shadow text-sm table-fixed">
      <thead class="bg-black text-white">
        <tr>
          <th class="px-4 py-2 w-12 text-center">#</th>
          <th class="px-4 py-2 w-48">Fee Type</th>
          <th class="px-4 py-2 w-32 text-right">Amount Due</th>
          <th class="px-4 py-2 w-32 text-right">Paid</th>
          <th class="px-4 py-2 w-32 text-right">Balance</th>
        </tr>
      </thead>
      <tbody class="text-gray-800">
        <tr class="text-center">
          <td class="px-4 py-2">1</td>
          <td class="px-4 py-2 text-left">Tuition Fee</td>
          <td class="px-4 py-2 text-right">₱${tuition_fee.toFixed(2)}</td>
          <td class="px-4 py-2 text-right">₱${tuition_paid.toFixed(2)}</td>
          <td class="px-4 py-2 text-right">₱${(tuition_fee - tuition_paid).toFixed(2)}</td>
        </tr>
        <tr class="text-center">
          <td class="px-4 py-2">2</td>
          <td class="px-4 py-2 text-left">Other Fees</td>
          <td class="px-4 py-2 text-right">₱${other_fees.toFixed(2)}</td>
          <td class="px-4 py-2 text-right">₱${other_paid.toFixed(2)}</td>
          <td class="px-4 py-2 text-right">₱${(other_fees - other_paid).toFixed(2)}</td>
        </tr>
        <tr class="text-center">
          <td class="px-4 py-2">3</td>
          <td class="px-4 py-2 text-left">Student Fees</td>
          <td class="px-4 py-2 text-right">₱${student_fees.toFixed(2)}</td>
          <td class="px-4 py-2 text-right">₱${student_paid.toFixed(2)}</td>
          <td class="px-4 py-2 text-right">₱${(student_fees - student_paid).toFixed(2)}</td>
        </tr>
      </tbody>
    </table>
    <p class="text-right text-sm mt-2 font-medium">Total: ₱${gross_total.toFixed(2)}</p>

    <!-- ✅ Add Fee Section -->
    <div class="mt-4 border-t pt-4">
      <h3 class="text-sm font-medium mb-2">Add New Fee</h3>
      <div class="flex flex-col gap-2">
        <input type="text" id="feeType" placeholder="Fee Type" 
          class="border rounded px-3 py-1 text-sm w-60"/>
        <input type="number" id="feeAmount" placeholder="Amount Due" 
          class="border rounded px-3 py-1 text-sm w-60"/>
        <input type="number" id="feePaid" placeholder="Amount Paid" 
          class="border rounded px-3 py-1 text-sm w-60"/>
        <button onclick="addFee('${rfid}')" 
          class="bg-black text-white px-4 py-2 rounded text-sm hover:bg-gray-800 w-32">
          Add Fee
        </button>
      </div>
    </div>
  </div>
`;


      // ✅ History stays empty if none
      let historyHTML = '';
      if (data.history && data.history.length > 0) {
        data.history.forEach((row, index) => {
          historyHTML += `
            <tr>
              <td class="px-4 py-2">${index + 1}</td>
              <td class="px-4 py-2">${row.date}</td>
              <td class="px-4 py-2">Tuition fee</td>
              <td class="px-4 py-2">₱${row.amount}</td>
              <td class="px-4 py-2">Cash</td>
            </tr>
          `;
        });
      } else {
        historyHTML = `<tr><td colspan="5" class="px-4 py-2">No history available</td></tr>`;
      }

      document.getElementById('tab-history').innerHTML = `
        <div class="overflow-x-auto">
          <table class="min-w-full bg-white rounded shadow text-sm">
            <thead class="bg-black text-white">
              <tr>
                <th class="px-4 py-2">#</th>
                <th class="px-4 py-2">Date</th>
                <th class="px-4 py-2">Description</th>
                <th class="px-4 py-2">Amount</th>
                <th class="px-4 py-2">Method</th>
              </tr>
            </thead>
            <tbody class="text-gray-800 text-center">
              ${historyHTML}
            </tbody>
          </table>
        </div>
      `;

      rfidInput.value = '';
      focusRFID();
    })
    .catch(err => {
      console.error(err);
      rfidInput.value = '';
      focusRFID();
    });
}


    // ====== AUTOCOMPLETE SEARCH ======
function addFee(rfid) {
  const feeType = document.getElementById('feeType').value.trim();
  const feeAmount = document.getElementById('feeAmount').value.trim();
  const feePaid = document.getElementById('feePaid').value.trim();

  if (!feeType || !feeAmount) {
    alert("Please enter fee type and amount due.");
    return;
  }

  fetch("AddFee.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `rfid_uid=${encodeURIComponent(rfid)}&fee_type=${encodeURIComponent(feeType)}&amount=${encodeURIComponent(feeAmount)}&paid=${encodeURIComponent(feePaid)}`
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert("Fee added successfully!");
      handleRFID(rfid); // ✅ refresh student balance
    } else {
      alert("Failed to add fee: " + (data.error || "Unknown error"));
    }
  })
  .catch(err => {
    console.error(err);
    alert("Error adding fee");
  });
}

// debounce helper (wait before firing fetch)
function debounce(func, delay) {
  let timeout;
  return function(...args) {
    clearTimeout(timeout);
    timeout = setTimeout(() => func.apply(this, args), delay);
  };
}

// hook input event for live search
const searchInput = document.getElementById('searchInput');
searchInput.addEventListener('input', debounce(() => {
  const query = searchInput.value.trim();
  if (!query) {
    searchResults.innerHTML = ''; // clear results if empty
    return;
  }

  fetch(`SearchBalance.php?query=${encodeURIComponent(query)}`)
    .then(res => res.json())
    .then(data => {
      if (data.error) {
        searchResults.innerHTML = `<p class="text-sm text-red-500">${data.error}</p>`;
        return;
      }

      const students = data.students || [];
      if (students.length === 0) {
        searchResults.innerHTML = `<p class="text-sm text-gray-500 italic">No matches for "${query}"</p>`;
        return;
      }

      // ✅ show results live while typing
      renderSearchResults(students, true);
    })
    .catch(() => {
      searchResults.innerHTML = `<p class="text-sm text-red-500">Failed to fetch</p>`;
    });
}, 300)); // 300ms delay before triggering search


    // ✅ Focus RFID when page loads
    window.onload = focusRFID;
  </script>

</body>
</html>