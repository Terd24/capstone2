<?php
session_start();
include("../StudentLogin/db_conn.php");

// Prevent access if not logged in
if (!isset($_SESSION['registrar_id'])) {
    header("Location: ../StudentLogin/login.html");
    exit;
}

// Prevent browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Fetch all requests
$result = $conn->query("SELECT * FROM document_requests ORDER BY date_requested DESC");

// Fetch only top 3 recent requests
$recent = $conn->query("SELECT * FROM document_requests ORDER BY date_requested DESC LIMIT 3");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Registrar Dashboard</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans min-h-screen">

<!-- RFID Form -->
<form id="rfidForm" method="get" action="ViewStudentInfo.php">
    <input type="hidden" id="rfid_input" name="student_id" autocomplete="off">
</form>

<!-- Header -->
<div class="bg-white p-4 flex items-center justify-between shadow-sm relative">
    <h1 class="text-lg font-semibold text-gray-700 flex items-center gap-4">
        Registrar Dashboard
    </h1>

    <!-- Burger / Dropdown -->
    <div class="relative">
        <button id="menuBtn" class="p-2 rounded hover:bg-gray-100 focus:outline-none">☰</button>
        <div id="dropdownMenu" class="hidden absolute right-0 mt-2 w-40 bg-white border rounded shadow-md">
            <a href="logout.php" class="block px-4 py-2 text-black-500 hover:bg-gray-100">Logout</a>
        </div>
    </div>
</div>

<!-- Search Bar -->
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
      <input
        type="text"
        id="searchInput"
        placeholder="Search by name or ID..."
        class="w-64 border border-gray-400 rounded pl-8 pr-3 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-black"
      />
    </div>

    <p id="searchError" class="text-red-600 text-sm ml-3"></p>
  </div>

  <!-- Search results -->
  <div id="searchResults" class="mt-3 space-y-1"></div>
</div>

<!-- Content Layout -->
<div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- LEFT SIDE -->
    <div class="space-y-6">
        <!-- Registrar Info -->
        <div class="bg-white card p-6 shadow">
            <div class="flex items-center mb-4">
                <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center text-2xl">👤</div>
                <div class="ml-3">
                    <div class="font-medium text-gray-700"><?= htmlspecialchars($_SESSION['registrar_name'] ?? 'Employee Name') ?></div>
                    <p class="text-xs text-gray-500">ID: <?= htmlspecialchars($_SESSION['registrar_id']) ?></p>
                </div>
            </div>
        </div>

        <!-- Recent Student Requests -->
        <div class="bg-white card p-6 shadow">
            <div class="flex justify-between items-center mb-2 cursor-pointer" id="recentToggle">
                <h2 class="text-lg font-semibold">Recent Student Requests</h2>
                <span id="recentArrow" class="transform transition-transform"><svg id="recentArrow"
                            xmlns="http://www.w3.org/2000/svg"
                            class="w-5 h-5 transform transition-transform"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </span>
            </div>
            <div id="recentContent" class="hidden">
            <?php if ($recent && $recent->num_rows > 0): ?>
                <?php while ($row = $recent->fetch_assoc()): ?>
                    <div class="bg-gray-50 p-4 rounded border mb-3">
                        <p class="font-medium text-gray-700"><?= htmlspecialchars($row['document_type']) ?></p>
                        <p class="text-xs text-gray-500">By: <?= htmlspecialchars($row['student_name']) ?> (<?= htmlspecialchars($row['student_id']) ?>)</p>
                        <div class="flex justify-between items-center mt-2">
                            <span class="text-xs text-gray-400"><?= htmlspecialchars($row['purpose'] ?: 'Document request.') ?></span>
                            <a href="ViewStudentInfo.php?student_id=<?= urlencode($row['student_id']) ?>&type=requested" class="text-sm text-blue-600 hover:underline">More</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="bg-gray-50 p-4 rounded text-gray-500">No recent requests.</div>
            <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- RIGHT SIDE -->
    <div class="md:col-span-2">
        <div class="bg-white card p-6 shadow">
            <!-- Filter Dropdown -->
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold">All Student Requests</h2>
                <div>
                    <label for="statusFilter" class="text-sm text-gray-700 mr-2">Filter by status:</label>
                    <select id="statusFilter" class="border border-gray-400 rounded px-2 py-1 text-sm">
                        <option value="all">All</option>
                        <option value="Pending">Pending</option>
                        <option value="Ready to Claim">Ready to Claim</option>
                        <option value="Claimed">Claimed</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full bg-white rounded shadow-md text-sm">
                    <thead class="bg-black text-white text-left">
                        <tr>
                            <th class="px-4 py-2">Student No</th>
                            <th class="px-4 py-2">Document Name</th>
                            <th class="px-4 py-2">Date Submitted</th>
                            <th class="px-4 py-2">Claimed At</th>
                            <th class="px-4 py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-800">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($r = $result->fetch_assoc()): ?>
                                <tr class="<?= ($r['status'] === 'Pending') ? '' : 'bg-gray-100' ?>">
                                    <td class="px-4 py-2"><?= htmlspecialchars($r['student_id']) ?></td>
                                    <td class="px-4 py-2"><?= htmlspecialchars($r['document_type']) ?></td>
                                    <td class="px-4 py-2"><?= htmlspecialchars($r['date_requested']) ?></td>
                                    <td class="px-4 py-2">
                                        <?= ($r['status'] === 'Claimed' && $r['date_claimed']) ? htmlspecialchars($r['date_claimed']) : '---' ?>
                                    </td>
                                    <td class="px-4 py-2">
                                        <?php if ($r['status'] === 'Pending'): ?>
                                            <span class="text-yellow-600 font-medium">Pending</span>
                                        <?php elseif ($r['status'] === 'Ready to Claim' || $r['status'] === 'Ready for Claiming'): ?>
                                            <span class="text-green-600 font-medium">Ready to Claim</span>
                                        <?php elseif ($r['status'] === 'Claimed'): ?>
                                            <span class="text-blue-600 font-medium">Claimed</span>
                                        <?php else: ?>
                                            <?= htmlspecialchars($r['status']) ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-gray-500">No requests found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// RFID Scanner Logic
let rfidBuffer = "";
let lastKeyTime = Date.now();
const rfidInput = document.getElementById("rfid_input");
const rfidForm = document.getElementById("rfidForm");

document.addEventListener('keydown', (e) => {
    const currentTime = Date.now();
    if (currentTime - lastKeyTime > 100) rfidBuffer = "";
    lastKeyTime = currentTime;

    if (document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'TEXTAREA') return;

    if (e.key === 'Enter') {
        if (rfidBuffer.length >= 5) {
            rfidInput.value = rfidBuffer.trim();
            rfidForm.submit();
        }
        rfidBuffer = "";
        e.preventDefault();
    } else if (e.key.length === 1) {
        rfidBuffer += e.key;
    }
});

// Dropdown toggle
const menuBtn = document.getElementById("menuBtn");
const dropdownMenu = document.getElementById("dropdownMenu");
menuBtn.addEventListener("click", () => dropdownMenu.classList.toggle("hidden"));
document.addEventListener("click", (e) => {
    if (!menuBtn.contains(e.target) && !dropdownMenu.contains(e.target)) dropdownMenu.classList.add("hidden");
});

// ===== TOGGLE RECENT REQUESTS =====
const recentToggle = document.getElementById("recentToggle");
const recentContent = document.getElementById("recentContent");
const recentArrow = document.getElementById("recentArrow");

recentToggle.addEventListener("click", () => {
    recentContent.classList.toggle("hidden");
    recentArrow.classList.toggle("rotate-180");
});

// ===== LIVE SEARCH WITH PAGINATION =====
const searchInput = document.getElementById('searchInput');
const searchResults = document.getElementById('searchResults');
const searchError = document.getElementById('searchError');
let searchTimeout;
let allSearchResults = [];
let shownCount = 0;

function clearError() { searchError.textContent = ''; }
function showError(msg) { searchError.textContent = msg; }

function renderSearchResults(list, reset = true) {
    if (reset) {
        allSearchResults = list;
        shownCount = 0;
        searchResults.innerHTML = '';
    }

    if (!allSearchResults || allSearchResults.length === 0) {
        searchResults.innerHTML = '<p class="text-sm text-gray-500 italic">No students found.</p>';
        return;
    }

    const increment = 3;
    const nextCount = Math.min(shownCount + increment, allSearchResults.length);
    const slice = allSearchResults.slice(shownCount, nextCount);
    shownCount = nextCount;

    const items = slice.map(s => `
        <div onclick="window.location.href='ViewStudentInfo.php?student_id=${encodeURIComponent(s.id_number)}&type=requested'"
             class="flex justify-between items-center border rounded px-3 py-2 bg-white hover:bg-gray-100 cursor-pointer transition">
            <div class="text-sm">
                <div class="font-medium">${s.full_name}</div>
                <div class="text-gray-600">ID: ${s.id_number} • ${s.program} • ${s.year_section}</div>
            </div>
        </div>
    `).join('');

    searchResults.innerHTML += items;

    if (shownCount < allSearchResults.length) {
        if (!document.getElementById('viewMoreBtn')) {
            const btn = document.createElement('button');
            btn.id = 'viewMoreBtn';
            btn.innerHTML = '▼ View More';
            btn.className = 'mt-3 block w-full bg-blue-600 text-white rounded px-3 py-2 text-sm font-medium hover:bg-blue-700 transition';
            btn.onclick = (e) => { e.preventDefault(); renderSearchResults([], false); };
            searchResults.appendChild(btn);
        }
    } else {
        const btn = document.getElementById('viewMoreBtn');
        if (btn) btn.remove();
    }
}

function fetchStudents(query) {
    fetch(`SearchStudent.php?query=${encodeURIComponent(query)}`)
    .then(res => res.json())
    .then(data => {
        if(data.error) { searchResults.innerHTML=''; showError(data.error); return; }
        const students = data.students || [];
        renderSearchResults(students, true);
    })
    .catch(()=>{ searchResults.innerHTML=''; showError('Failed to fetch student data'); });
}

searchInput.addEventListener('input', (e) => {
    const query = e.target.value.trim();
    clearError();
    clearTimeout(searchTimeout);
    if(query.length < 1) {
        searchResults.innerHTML = '';
        return;
    }
    searchTimeout = setTimeout(() => {
        if(searchInput.value.trim().length < 1) {
            searchResults.innerHTML = '';
            return;
        }
        fetchStudents(query);
    }, 300);
});

searchInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        const query = searchInput.value.trim();
        clearTimeout(searchTimeout);
        if(query.length > 0) fetchStudents(query);
        else searchResults.innerHTML = '';
    }
});

// ===== STATUS FILTER FOR ALL STUDENT REQUESTS =====
const statusFilter = document.getElementById('statusFilter');
const allRows = Array.from(document.querySelectorAll('table tbody tr'));

statusFilter.addEventListener('change', () => {
    const value = statusFilter.value;
    allRows.forEach(row => {
        const statusCell = row.cells[4].textContent.trim();
        if (value === 'all' || statusCell === value || 
            (value === 'Ready to Claim' && statusCell === 'Ready for Claiming')) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
</script>
</body>
</html>
