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

// Ensure column exists for read/unread
$conn->query("ALTER TABLE document_requests ADD COLUMN IF NOT EXISTS is_read TINYINT(1) DEFAULT 0");

// Fetch all requests
$result = $conn->query("SELECT * FROM document_requests ORDER BY date_requested DESC");

// Fetch only top 3 recent requests
$recent = $conn->query("SELECT * FROM document_requests ORDER BY date_requested ASC LIMIT 3");

// Helper function for PHP fallback time ago (optional)
function timeAgo($time) {
    $diff = time() - strtotime($time);
    if ($diff < 60) return $diff . " seconds ago";
    elseif ($diff < 3600) return floor($diff/60) . " minutes ago";
    elseif ($diff < 86400) return floor($diff/3600) . " hours ago";
    else return floor($diff/86400) . " days ago";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Registrar Dashboard - Cornerstone College Inc.</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="icon" type="image/png" href="../images/Logo.png">
<style>
  .school-gradient { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #1e40af 100%); }
  .card-shadow { box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
</style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen font-sans">
<!-- RFID Form -->
<form id="rfidForm" method="get" action="ViewStudentInfo.php">
    <input type="hidden" id="rfid_input" name="student_id" autocomplete="off">
</form>

<!-- Header with School Branding -->
<header class="bg-blue-600 text-white shadow-lg">
  <div class="container mx-auto px-6 py-4">
    <div class="flex justify-between items-center">
      <div class="flex items-center space-x-4">
        <div class="text-left">
          <p class="text-sm text-blue-200">Welcome,</p>
          <p class="font-semibold"><?= htmlspecialchars($_SESSION['registrar_name'] ?? 'Registrar') ?></p>
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
          <div id="dropdownMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg z-50 text-gray-800">
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
        />
      </div>
      <button onclick="fetchStudents(document.getElementById('searchInput').value.trim())" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-medium transition-colors">
        Search
      </button>
      <p id="searchError" class="text-red-600 text-sm"></p>
    </div>
    <div id="searchResults" class="mt-4 space-y-2"></div>
  </div>
</div>

<!-- Content Layout -->
<div class="container mx-auto px-6 py-8">
  <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
    <!-- LEFT SIDE - Registrar Profile -->
    <div class="lg:col-span-1">
        <!-- Registrar Profile -->
        <div class="bg-white rounded-2xl card-shadow p-6">
          <div class="text-center">
            <div class="w-20 h-20 mx-auto bg-gray-400 rounded-full flex items-center justify-center mb-4">
              <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
              </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-800"><?= htmlspecialchars($_SESSION['registrar_name'] ?? 'Registrar') ?></h3>
            <p class="text-gray-600 text-sm">ID: <?= htmlspecialchars($_SESSION['id_number'] ?? 'N/A') ?></p>
          </div>
          
          <div class="mt-6 pt-6 border-t">
            <div class="space-y-3 text-sm">
              <div>
                <span class="text-gray-500 font-medium">Role:</span>
                <p class="text-gray-800">Registrar</p>
              </div>
              <div>
                <span class="text-gray-500 font-medium">Department:</span>
                <p class="text-gray-800">Student Records</p>
              </div>
            </div>
            
            <div class="mt-4 pt-4 border-t">
              <button onclick="window.location.href='AccountList.php'" 
                      class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg font-medium transition-colors text-sm mb-2">
                Manage Accounts
              </button>
              <button onclick="window.location.href='ManageGrades.php'" 
                      class="w-full bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg font-medium transition-colors text-sm">
                Manage Grades
              </button>
            </div>
          </div>
        </div>
    </div>

    <!-- RIGHT SIDE - All Student Requests -->
    <div class="lg:col-span-3">
    <div class="space-y-6">
        <!-- Recent Student Requests -->
        <div class="bg-white rounded-2xl card-shadow p-6">
            <div class="flex justify-between items-center mb-4 cursor-pointer" id="recentToggle">
                <h2 class="text-lg font-semibold flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Recent Requests
                    <?php 
                    $unreadCount = $conn->query("SELECT COUNT(*) AS c FROM document_requests WHERE is_read=0")->fetch_assoc()['c'];
                    if($unreadCount > 0): ?>
                        <span id="notifBadge" class="bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full"><?= $unreadCount ?></span>
                    <?php endif; ?>
                </h2>
                <span id="recentArrow" class="transform transition-transform">
                    <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </span>
            </div>
            <div id="recentContent" class="hidden space-y-3">
            <?php if ($recent && $recent->num_rows > 0): ?>
                <?php while ($row = $recent->fetch_assoc()): ?>
                    <div class="bg-gray-50 p-4 rounded border mb-3 flex justify-between items-start" 
                         id="req-<?= $row['id'] ?>"
                         data-timestamp="<?= strtotime($row['date_requested']) ?>">
                        <div class="flex flex-col gap-1">
                            <p class="text-xs text-gray-400 font-medium time-ago">Just now</p>
                            <p class="font-medium text-gray-700"><?= htmlspecialchars($row['document_type']) ?></p>
                            <p class="text-xs text-gray-500">
                                By: <?= htmlspecialchars($row['student_name']) ?> (<?= htmlspecialchars($row['student_id']) ?>)
                            </p>
                            <span class="text-xs text-gray-400"><?= htmlspecialchars($row['purpose'] ?: 'Document request.') ?></span>
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            <?php if (!$row['is_read']): ?>
                                <span id="badge-<?= $row['id'] ?>" class="bg-red-600 text-white text-xs font-bold px-2 py-0.5 rounded-full">1</span>
                            <?php endif; ?>
                            <a href="ViewStudentInfo.php?student_id=<?= urlencode($row['student_id']) ?>&type=requested"
                               data-id="<?= $row['id'] ?>"
                               class="view-link text-sm text-blue-600 hover:underline">View</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="bg-gray-50 p-4 rounded text-gray-500">No recent requests.</div>
            <?php endif; ?>
            </div>
        </div>

        <div class="bg-white rounded-2xl card-shadow p-6">
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
                <table class="min-w-full bg-white rounded-xl overflow-hidden text-sm">
                    <thead class="bg-blue-600 text-white">
                        <tr>
                            <th class="px-6 py-4 text-left font-semibold">Student No</th>
                            <th class="px-6 py-4 text-left font-semibold">Document Name</th>
                            <th class="px-6 py-4 text-left font-semibold">Date Submitted</th>
                            <th class="px-6 py-4 text-left font-semibold">Claimed At</th>
                            <th class="px-6 py-4 text-left font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-800 divide-y divide-gray-200">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($r = $result->fetch_assoc()): ?>
                                <tr class="bg-white hover:bg-[#FBB917]/20 transition cursor-pointer" onclick="viewRequest('<?= htmlspecialchars($r['student_id']) ?>', '<?= htmlspecialchars($r['document_type']) ?>', '<?= $r['status'] ?>')">
                                    <td class="px-6 py-4 font-medium"><?= htmlspecialchars($r['student_id']) ?></td>
                                    <td class="px-6 py-4"><?= htmlspecialchars($r['document_type']) ?></td>
                                    <td class="px-6 py-4 text-gray-600"><?= htmlspecialchars($r['date_requested']) ?></td>
                                    <td class="px-6 py-4 text-gray-600">
                                        <?= ($r['status'] === 'Claimed' && $r['date_claimed']) ? htmlspecialchars($r['date_claimed']) : '---' ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($r['status'] === 'Pending'): ?>
                                            <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                                        <?php elseif ($r['status'] === 'Ready to Claim' || $r['status'] === 'Ready for Claiming'): ?>
                                            <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Ready to Claim</span>
                                        <?php elseif ($r['status'] === 'Claimed'): ?>
                                            <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Claimed</span>
                                        <?php else: ?>
                                            <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800"><?= htmlspecialchars($r['status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                    <svg class="w-12 h-12 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    No document requests found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// ===== RFID Scanner Logic =====
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

// ===== Dropdown toggle =====
const menuBtn = document.getElementById("menuBtn");
const dropdownMenu = document.getElementById("dropdownMenu");
menuBtn.addEventListener("click", () => dropdownMenu.classList.toggle("hidden"));
document.addEventListener("click", (e) => {
    if (!menuBtn.contains(e.target) && !dropdownMenu.contains(e.target)) dropdownMenu.classList.add("hidden");
});

// ===== Toggle Recent Requests =====
const recentToggle = document.getElementById("recentToggle");
const recentContent = document.getElementById("recentContent");
const recentArrow = document.getElementById("recentArrow");

recentToggle.addEventListener("click", () => {
    recentContent.classList.toggle("hidden");
    recentArrow.classList.toggle("rotate-180");
});

// ===== Live Time Ago Function =====
function updateTimeAgo() {
    const now = Date.now();
    document.querySelectorAll('#recentContent [data-timestamp]').forEach(el => {
        const timestamp = parseInt(el.getAttribute('data-timestamp')) * 1000; // convert to ms
        const diff = Math.floor((now - timestamp)/1000);

        let text = '';
        if (diff < 60) text = 'Just now';
        else if (diff < 3600) text = Math.floor(diff/60) + (Math.floor(diff/60) === 1 ? ' minute ago' : ' minutes ago');
        else if (diff < 86400) text = Math.floor(diff/3600) + (Math.floor(diff/3600) === 1 ? ' hour ago' : ' hours ago');
        else text = Math.floor(diff/86400) + (Math.floor(diff/86400) === 1 ? ' day ago' : ' days ago');

        const p = el.querySelector('.time-ago');
        if (p) p.textContent = text;
    });
}

// Run every 30 seconds
updateTimeAgo();
setInterval(updateTimeAgo, 30000);

// ===== Render Recent Requests with View More =====
let recentRequests = Array.from(document.querySelectorAll('#recentContent > div'));
let shownRecent = 3;

function renderRecent() {
    recentRequests.forEach((el, idx) => {
        el.style.display = idx < shownRecent ? '' : 'none';
    });

    if (shownRecent < recentRequests.length) {
        if (!document.getElementById('viewMoreRecent')) {
            const btn = document.createElement('button');
            btn.textContent = 'View More';
            btn.id = 'viewMoreRecent';
            btn.className = 'mt-2 text-sm px-3 py-1 bg-gray-200 rounded';
            btn.addEventListener('click', () => {
                shownRecent += 3;
                renderRecent();
            });
            document.getElementById('recentContent').appendChild(btn);
        }
    } else {
        const btn = document.getElementById('viewMoreRecent');
        if (btn) btn.remove();
    }
}
renderRecent();

// ===== Mark as Read when View clicked =====
document.querySelectorAll('.view-link').forEach(link => {
    link.addEventListener('click', () => {
        const id = link.getAttribute('data-id');
        fetch(`?id=${id}`).then(() => {
            const badge = document.getElementById(`badge-${id}`);
            if (badge) badge.remove();
            const notif = document.getElementById('notifBadge');
            if (notif) {
                let count = parseInt(notif.textContent);
                if (count > 1) notif.textContent = count - 1;
                else notif.remove();
            }
        });
    });
});

// ===== Live Search =====
const searchInput = document.getElementById('searchInput');
const searchResults = document.getElementById('searchResults');
const searchError = document.getElementById('searchError');
let searchTimeout, allSearchResults = [], shownCount = 0;

function clearError() { searchError.textContent = ''; }
function showError(msg) { searchError.textContent = msg; }

function renderSearchResults(list, reset = true) {
    if (reset) { allSearchResults = list; shownCount = 0; searchResults.innerHTML = ''; }
    if (!allSearchResults || allSearchResults.length === 0) {
        searchResults.innerHTML = '<p class="text-sm text-gray-500 italic">No students found.</p>'; return;
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

    if (shownCount < allSearchResults.length && !document.getElementById('viewMoreBtn')) {
        searchResults.innerHTML += `<div class="mt-2"><button id="viewMoreBtn" onclick="renderSearchResults([], false)" class="text-sm px-3 py-2">View More</button></div>`;
    } else {
        const btn = document.getElementById('viewMoreBtn'); if (btn) btn.remove();
    }
}

function fetchStudents(query) {
    fetch(`SearchStudent.php?query=${encodeURIComponent(query)}`)
    .then(res => res.json())
    .then(data => {
        if(data.error) { searchResults.innerHTML=''; showError(data.error); return; }
        renderSearchResults(data.students || [], true);
    })
    .catch((err)=>{ 
        console.error('Search error:', err);
        searchResults.innerHTML=''; 
        showError('Failed to fetch student data'); 
    });
}

searchInput.addEventListener('input', () => {
    const query = searchInput.value.trim();
    clearError();
    clearTimeout(searchTimeout);
    if(query.length < 1) { searchResults.innerHTML=''; return; }
    searchTimeout = setTimeout(() => { if(query.length>0) fetchStudents(query); }, 300);
});

searchInput.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); fetchStudents(searchInput.value.trim()); } });

// ===== Status Filter =====
const statusFilter = document.getElementById('statusFilter');
const allRows = Array.from(document.querySelectorAll('table tbody tr'));
statusFilter.addEventListener('change', () => {
    const value = statusFilter.value;
    allRows.forEach(row => {
        const statusCell = row.cells[4].textContent.trim();
        if (value === 'all' || statusCell === value || (value === 'Ready to Claim' && statusCell === 'Ready for Claiming')) {
            row.style.display = '';
        } else row.style.display = 'none';
    });

    renderStudents();

    // Add viewRequest function for clickable table rows
    function viewRequest(studentId, documentType, status) {
        alert(`Student: ${studentId}\nDocument: ${documentType}\nStatus: ${status}`);
        // You can customize this to navigate to a request detail page
    }
});
</script>
</body>
</html>

<?php
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("UPDATE document_requests SET is_read = 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    echo "ok";
}
?>
