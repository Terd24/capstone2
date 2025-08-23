<?php
session_start();

// ✅ Require cashier role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'cashier') {
    header("Location: ../StudentLogin/login.php");
    exit;
}

// ✅ Prevent caching (para hindi bumalik gamit ang Back button)
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
</head>
<body class="bg-gray-100 font-sans min-h-screen">

  <!-- ✅ Hidden RFID input -->
  <input 
    type="text" 
    id="rfidInput" 
    autofocus 
    class="absolute opacity-0"
  >

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
      <h1 class="text-gray-1000 text-sm">Cashier Dashboard</h1>
    </div>
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
      <p class="text-sm text-gray-500 italic">Scan an RFID to display student info...</p>
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
    // ✅ Dropdown toggle
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

    // ✅ RFID Logic (from old working version)
    const rfidInput = document.getElementById('rfidInput');

    function focusRFID() {
      if (document.activeElement !== rfidInput) {
        rfidInput.focus({ preventScroll: true });
      }
    }

    document.addEventListener('keydown', (e) => {
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

    function handleRFID(rfid) {
      fetch(`GetBalance.php?rfid_uid=${encodeURIComponent(rfid)}`)
        .then(res => res.json())
        .then(data => {
          if (data.error) {
            document.getElementById('student-info').innerHTML = `<p class="text-red-500">${data.error}</p>`;
            document.getElementById('tab-balance').innerHTML = `<p class="text-sm text-gray-500 italic">No balance data yet.</p>`;
            document.getElementById('tab-history').innerHTML = `<p class="text-sm text-gray-500 italic">No transaction history loaded.</p>`;
          } else {
            // ✅ Student Info
            document.getElementById('student-info').innerHTML = `
              <div class="flex items-center mb-3">
                <div class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center text-xl">👤</div>
                <div class="ml-3 font-medium">${data.full_name || 'Unknown Student'}</div>
              </div>
              <p class="text-sm text-gray-600">ID: ${data.id_number}</p>
              <p class="text-sm text-gray-600">Program: ${data.program || '-'}</p>
              <p class="text-sm text-gray-600">Year & Section: ${data.year_section || '-'}</p>
            `;

            // ✅ Balance Tab
            document.getElementById('tab-balance').innerHTML = `
              <div class="flex justify-between items-center">
                <label class="font-medium">${data.school_year_term}</label>
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
                      <td class="px-4 py-2 text-right">₱${Number(data.tuition_fee).toFixed(2)}</td>
                      <td class="px-4 py-2 text-right">₱${Number(data.tuition_paid ?? 0).toFixed(2)}</td>
                      <td class="px-4 py-2 text-right">₱${(Number(data.tuition_fee) - Number(data.tuition_paid ?? 0)).toFixed(2)}</td>
                    </tr>
                    <tr class="text-center">
                      <td class="px-4 py-2">2</td>
                      <td class="px-4 py-2 text-left">Other Fees</td>
                      <td class="px-4 py-2 text-right">₱${Number(data.other_fees).toFixed(2)}</td>
                      <td class="px-4 py-2 text-right">₱${Number(data.other_paid ?? 0).toFixed(2)}</td>
                      <td class="px-4 py-2 text-right">₱${(Number(data.other_fees) - Number(data.other_paid ?? 0)).toFixed(2)}</td>
                    </tr>
                    <tr class="text-center">
                      <td class="px-4 py-2">3</td>
                      <td class="px-4 py-2 text-left">Student Fees</td>
                      <td class="px-4 py-2 text-right">₱${Number(data.student_fees).toFixed(2)}</td>
                      <td class="px-4 py-2 text-right">₱${Number(data.student_paid ?? 0).toFixed(2)}</td>
                      <td class="px-4 py-2 text-right">₱${(Number(data.student_fees) - Number(data.student_paid ?? 0)).toFixed(2)}</td>
                    </tr>
                  </tbody>
                </table>
                <p class="text-right text-sm mt-2 font-medium">Total: ₱${Number(data.gross_total).toFixed(2)}</p>
              </div>
            `;

            // ✅ History Tab
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
          }

          // Reset for next scan
          rfidInput.value = '';
          focusRFID();
        })
        .catch(err => {
          console.error(err);
          rfidInput.value = '';
          focusRFID();
        });
    }

    // ✅ Focus RFID when page loads
    window.onload = focusRFID;
  </script>

</body>
</html>
