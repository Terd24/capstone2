<?php
session_start();

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

include("../StudentLogin/db_conn.php");

// Require registrar login
if (!isset($_SESSION['registrar_id'])) {
    header("Location: ../StudentLogin/login.php");
    exit;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include("Accounts/add_account.php");
}

// Handle success message from add_student.php
$success_msg = $_SESSION['success_msg'] ?? '';
if ($success_msg) {
    unset($_SESSION['success_msg']);
}


// Get selected school year filter
$selected_school_year = $_GET['school_year'] ?? '';
// Default to current academic year if no filter specified
$current_year = (int)date('Y');
$current_sy = $current_year . '-' . ($current_year + 1);
if ($selected_school_year === '') { $selected_school_year = $current_sy; }

// Get all available school years for dropdown
$school_years = [];
$year_query = $conn->query("SELECT DISTINCT school_year FROM student_account WHERE deleted_at IS NULL AND school_year IS NOT NULL AND school_year != '' ORDER BY school_year DESC");
if ($year_query) {
    while ($year_row = $year_query->fetch_assoc()) {
        $school_years[] = $year_row['school_year'];
    }
}

// Ensure current school year is present in dropdown
if (!in_array($current_sy, $school_years, true)) {
    array_unshift($school_years, $current_sy);
}

// Build student-only list
$rows = [];

// Ensure soft delete columns exist
$conn->query("ALTER TABLE student_account ADD COLUMN IF NOT EXISTS deleted_at TIMESTAMP NULL DEFAULT NULL");
$conn->query("ALTER TABLE student_account ADD COLUMN IF NOT EXISTS deleted_by VARCHAR(255) NULL");
$conn->query("ALTER TABLE student_account ADD COLUMN IF NOT EXISTS deleted_reason TEXT NULL");

// Build query with school year filter
$where_clause = "WHERE deleted_at IS NULL";
$params = [];
$types = "";

if (!empty($selected_school_year)) {
    $where_clause .= " AND school_year = ?";
    $params[] = $selected_school_year;
    $types .= "s";
}

// Only show active students (not soft-deleted) with optional school year filter
if (!empty($params)) {
    $stmt = $conn->prepare("SELECT id_number, first_name, last_name, academic_track, grade_level, school_year FROM student_account $where_clause");
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $student_q = $stmt->get_result();
} else {
    $student_q = $conn->query("SELECT id_number, first_name, last_name, academic_track, grade_level, school_year FROM student_account $where_clause");
}

if ($student_q) {
    while ($s = $student_q->fetch_assoc()) {
        $rows[] = [
            'id_number' => $s['id_number'],
            'full_name' => $s['first_name'] . ' ' . $s['last_name'],
            'academic_track' => $s['academic_track'],
            'grade_level' => $s['grade_level'],
            'school_year' => $s['school_year'],
        ];
    }
}

// Define grade level order for sorting
function getGradeLevelOrder($grade_level) {
    $order = [
        'Kinder 1' => 1, 'Kinder 2' => 2, 'Kinder' => 3,
        'Grade 1' => 4, 'Grade 2' => 5, 'Grade 3' => 6, 'Grade 4' => 7, 'Grade 5' => 8, 'Grade 6' => 9,
        'Grade 7' => 10, 'Grade 8' => 11, 'Grade 9' => 12, 'Grade 10' => 13,
        'Grade 11' => 14, 'Grade 12' => 15,
        '1st Year' => 16, '2nd Year' => 17, '3rd Year' => 18, '4th Year' => 19
    ];
    return $order[$grade_level] ?? 999; // Unknown grades go to the end
}

// Sort by grade level first, then by full name within the same grade
usort($rows, function($a, $b) {
    $gradeOrderA = getGradeLevelOrder($a['grade_level']);
    $gradeOrderB = getGradeLevelOrder($b['grade_level']);
    
    if ($gradeOrderA === $gradeOrderB) {
        // Same grade level, sort by name
        return strcasecmp($a['full_name'], $b['full_name']);
    }
    
    // Different grade levels, sort by grade order
    return $gradeOrderA - $gradeOrderB;
});

$columns = ['No.', 'Student ID', 'Full Name', 'Academic Track', 'Grade Level'];
$total_accounts = count($rows);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Account List - CCI</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
<script>
// Test QR library loading
document.addEventListener('DOMContentLoaded', function() {
    // Clean URL - remove query parameters without refreshing
    if (window.location.search) {
        const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
        window.history.replaceState({}, document.title, cleanUrl);
    }
    
    console.log('QRious library loaded:', typeof QRious !== 'undefined');
    if (typeof QRious !== 'undefined') {
        console.log('QRious version available');
    } else {
        console.error('QRious library failed to load!');
    }
});

// Test function you can run in console
function testQRGeneration() {
    console.log('Testing QR generation...');
    const testCanvas = document.createElement('canvas');
    testCanvas.width = 150;
    testCanvas.height = 150;
    document.body.appendChild(testCanvas);
    
    if (typeof QRious !== 'undefined') {
        try {
            const qr = new QRious({
                element: testCanvas,
                value: '1234567890',
                size: 150
            });
            console.log('Test QR generation successful!');
            testCanvas.style.position = 'fixed';
            testCanvas.style.top = '10px';
            testCanvas.style.right = '10px';
            testCanvas.style.zIndex = '9999';
            testCanvas.style.border = '2px solid red';
            console.log('Test QR code displayed in top-right corner');
        } catch (error) {
            console.error('Test QR generation failed:', error);
        }
    } else {
        console.error('QRious library not available for test');
    }
}

// Populate school year selects on initial page load as a safety net
document.addEventListener('DOMContentLoaded', function(){
    try { setupSchoolYearOptions(); } catch (e) { console.warn('setupSchoolYearOptions init failed', e); }
});

// Define viewStudent function early so it's available when rows are clicked
window.viewStudent = async function(studentId) {
    console.log('viewStudent called with ID:', studentId);
    const overlay = document.getElementById('studentViewOverlay');
    const inner = document.getElementById('studentViewInner');
    
    if (!overlay || !inner) {
        console.error('Modal elements not found!', {overlay, inner});
        alert('Error: Modal not found. Please refresh the page.');
        return;
    }
    
    console.log('Modal elements found, opening...');
    
    // Clear previous content
    inner.innerHTML = '';
    
    // Show the modal
    overlay.classList.remove('hidden');
    overlay.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    console.log('Modal should be visible now');
    
    try {
        console.log('Fetching student data...');
        const res = await fetch(`Accounts/view_student.php?embed=1&id=${encodeURIComponent(studentId)}`, { 
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        
        const html = await res.text();
        console.log('Student data loaded, length:', html.length);
        
        inner.innerHTML = html;
        
        // Extract and execute only the script content (using eval in global scope)
        const scripts = inner.querySelectorAll('script');
        scripts.forEach((script) => {
            if (!script.src && script.textContent.trim()) {
                try {
                    // Use indirect eval to execute in global scope
                    (0, eval)(script.textContent);
                } catch (e) {
                    console.warn('Error executing script:', e);
                }
            }
        });
        
        // Trigger username setup with multiple attempts
        const triggerSetup = () => {
            if (typeof setupAutoUsername === 'function') {
                setupAutoUsername();
            }
            if (typeof setupAutoParentUsername === 'function') {
                setupAutoParentUsername();
            }
        };
        
        // Try multiple times with delays
        setTimeout(triggerSetup, 100);
        setTimeout(triggerSetup, 300);
        setTimeout(triggerSetup, 500);
        
        // Wire up handlers
        if (typeof setupStudentModalHandlers === 'function') {
            setupStudentModalHandlers(studentId);
        }
    } catch (e) {
        console.error('Error loading student:', e);
        inner.innerHTML = '<div class="p-6 text-center"><div class="text-red-600 text-lg font-semibold mb-2">Failed to load student information</div><div class="text-gray-600">' + e.message + '</div><button onclick="closeStudentModal()" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded">Close</button></div>';
    }
};

function closeStudentModal() {
    console.log('Closing modal...');
    const overlay = document.getElementById('studentViewOverlay');
    const inner = document.getElementById('studentViewInner');
    if (inner) inner.innerHTML = '';
    if (overlay) {
        overlay.classList.add('hidden');
        overlay.style.display = 'none';
    }
    document.body.style.overflow = '';
}
</script>
<style>
input[type=number]::-webkit-inner-spin-button,
input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
input[type=number] { -moz-appearance: textfield; }
/* HR-like inline error visuals for modal form */
.field-error { border-color: #dc2626 !important; background-color: #fef2f2; box-shadow: 0 0 0 1px rgba(220,38,38,0.12); }
.error-text { color: #dc2626; font-size: 0.75rem; margin-top: 0.25rem; display: flex; align-items: center; gap: 6px; }
.error-text::before { content: ''; display: none; }
</style>
</head>
<body class="bg-gradient-to-br from-[#f3f6fb] to-[#e6ecf7] font-sans min-h-screen text-gray-900">

<!-- Toast Notification -->
<div id="toast" class="fixed top-5 right-5 z-[9999] hidden">
  <div id="toastInner" class="px-4 py-3 rounded shadow-lg text-white"></div>
</div>

<!-- Approval/Rejection Notification -->
<div id="approvalNotification" class="fixed top-5 right-5 z-[9999] hidden">
  <div class="bg-white rounded-lg shadow-2xl p-6 max-w-md border-l-4 border-green-500">
    <div class="flex items-start">
      <div class="flex-shrink-0">
        <svg class="w-6 h-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
      </div>
      <div class="ml-3 flex-1">
        <h3 class="text-sm font-semibold text-gray-900" id="approvalTitle">Request Approved!</h3>
        <div class="mt-2 text-sm text-gray-600" id="approvalMessage"></div>
      </div>
      <button onclick="document.getElementById('approvalNotification').classList.add('hidden')" class="ml-4 text-gray-400 hover:text-gray-600">
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
        </svg>
      </button>
    </div>
  </div>
</div>

<!-- Approval/Rejection Notification -->
<div id="approvalNotification" class="fixed top-5 right-5 z-[9999] hidden">
  <div class="bg-white rounded-lg shadow-2xl p-6 max-w-md border-l-4 border-green-500">
    <div class="flex items-start">
      <div class="flex-shrink-0">
        <svg class="w-6 h-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
      </div>
      <div class="ml-3 flex-1">
        <h3 class="text-sm font-semibold text-gray-900" id="approvalTitle">Request Approved!</h3>
        <div class="mt-2 text-sm text-gray-600" id="approvalMessage"></div>
      </div>
      <button onclick="document.getElementById('approvalNotification').classList.add('hidden')" class="ml-4 text-gray-400 hover:text-gray-600">
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
        </svg>
      </button>
    </div>
  </div>
</div>

<header class="bg-[#0B2C62] text-white shadow-lg">
    <div class="container mx-auto px-6 py-4">
        <div class="flex justify-between items-center">
        <div class="flex items-center space-x-4">
        <button onclick="window.location.href='RegistrarDashboard.php'" class="bg-white bg-opacity-20 hover:bg-opacity-30 p-2 rounded-lg transition">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
          </svg>
        </button>
        <div>
                <h1 class="text-xl font-bold">Account Management</h1>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <img src="../images/LogoCCI.png" alt="Cornerstone College Inc." class="h-12 w-12 rounded-full bg-white p-1">
                <div class="text-right">
                    <h1 class="text-xl font-bold">Cornerstone College Inc.</h1>
                    <p class="text-blue-200 text-sm">Registrar Portal</p>
                </div>
                <a href="RegistrarDashboard.php" class="bg-white bg-opacity-20 hover:bg-opacity-30 p-2 rounded-lg transition" title="Home">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                  </svg>
                </a>
                <div class="relative">
                  <button id="menuBtn" class="bg-white bg-opacity-20 hover:bg-opacity-30 p-2 rounded-lg transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                  </button>
                  <div id="dropdownMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg z-50 text-gray-800">
                    <a href="javascript:void(0);" onclick="showLogoutConfirmation('logout.php');" class="block px-4 py-3 hover:bg-gray-100 rounded-lg">
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
<script src="../js/logout-confirm.js"></script>
<script>
// Menu dropdown toggle
const menuBtn = document.getElementById("menuBtn");
const dropdownMenu = document.getElementById("dropdownMenu");
if (menuBtn && dropdownMenu) {
  menuBtn.addEventListener("click", () => {
    dropdownMenu.classList.toggle("hidden");
  });
  document.addEventListener("click", (e) => {
    if (!menuBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
      dropdownMenu.classList.add("hidden");
    }
  });
}
</script>

<div class="max-w-7xl mx-auto mt-8 p-6">

    <!-- Header Section with Title and Account Count -->
    <div class="mb-6">
        <div class="flex items-center gap-4">
            <h2 class="text-2xl font-bold text-[#0B2C62]">Student Accounts</h2>
            <div class="flex items-center gap-2 bg-[#0B2C62] text-white px-4 py-2 rounded-lg shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zM21 10a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <span class="text-sm font-semibold">Total: <?= $total_accounts ?></span>
            </div>
        </div>
    </div>

    <!-- Top Controls -->
    <div class="mb-6 bg-white p-4 rounded-xl shadow-sm border border-[#0B2C62]/10">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            <!-- School Year -->
            <div>
                <label class="block font-medium text-[#0B2C62] text-sm mb-1">School Year</label>
                <select id="schoolYearFilter" onchange="filterBySchoolYear()" class="w-full border border-[#0B2C62]/30 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]">
                    <?php foreach ($school_years as $year): ?>
                        <option value="<?= htmlspecialchars($year) ?>" <?= $selected_school_year === $year ? 'selected' : '' ?>>
                            <?= htmlspecialchars($year) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Track -->
            <div>
                <label class="block font-medium text-[#0B2C62] text-sm mb-1">Track</label>
                <select id="trackFilter" class="w-full border border-[#0B2C62]/30 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]">
                    <option value="">All</option>
                    <option>Kinder</option>
                    <option>Elementary</option>
                    <option>Junior High School</option>
                    <option>Senior High School Strands</option>
                    <option>ABM</option>
                    <option>GAS</option>
                    <option>HE</option>
                    <option>HUMSS</option>
                    <option>ICT</option>
                    <option>SPORTS</option>
                    <option>STEM</option>
                    <option>College Courses</option>
                    <option>BPEd (Bachelor of Physical Education)</option>
                    <option>BECEd (Bachelor of Early Childhood Education)</option>
                </select>
            </div>

            <!-- Grade -->
            <div>
                <label class="block font-medium text-[#0B2C62] text-sm mb-1">Grade</label>
                <select id="gradeFilter" class="w-full border border-[#0B2C62]/30 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]">
                    <option value="">All</option>
                    <option>Kinder 1</option>
                    <option>Kinder 2</option>
                    <option>Grade 1</option>
                    <option>Grade 2</option>
                    <option>Grade 3</option>
                    <option>Grade 4</option>
                    <option>Grade 5</option>
                    <option>Grade 6</option>
                    <option>Grade 7</option>
                    <option>Grade 8</option>
                    <option>Grade 9</option>
                    <option>Grade 10</option>
                    <option>Grade 11</option>
                    <option>Grade 12</option>
                    <option>1st Year</option>
                    <option>2nd Year</option>
                    <option>3rd Year</option>
                    <option>4th Year</option>
                </select>
            </div>

            <!-- Search -->
            <div>
                <label class="block font-medium text-[#0B2C62] text-sm mb-1">Search</label>
                <input type="text" id="searchInput" placeholder="Search by name or ID..." class="w-full border border-[#0B2C62]/30 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62]"/>
            </div>

            <!-- Add Account -->
            <div class="flex md:justify-end">
                <button id="addAccountBtn" type="button" onclick="openAddAccountModal()" class="w-full md:w-auto px-4 py-2 bg-[#2F8D46] text-white rounded-lg shadow hover:bg-[#256f37] transition flex items-center gap-2 justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Add Account
                </button>
            </div>
        </div>
    </div>

    <!-- Accounts Table -->
    <div class="overflow-x-auto bg-white shadow-lg rounded-2xl p-4 border border-[#0B2C62]/20">
        <table class="min-w-full text-sm border-collapse">
            <thead class="bg-[#0B2C62] text-white">
                <tr>
                    <?php foreach($columns as $col): ?>
                        <th class="px-4 py-3 border text-left font-semibold"><?= $col ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody id="accountTable" class="divide-y divide-gray-200">
                <?php if (!empty($rows)): ?>
                    <?php foreach ($rows as $r): ?>
                        <tr class="hover:bg-[#FBB917]/20 transition cursor-pointer" data-student-id="<?= htmlspecialchars($r['id_number']) ?>" data-track="<?= htmlspecialchars($r['academic_track']) ?>" data-grade="<?= htmlspecialchars($r['grade_level']) ?>" onclick="viewStudent('<?= htmlspecialchars($r['id_number']) ?>')">
                            <td class="px-4 py-3 serial"></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($r['id_number']) ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($r['full_name']) ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($r['academic_track']) ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($r['grade_level']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?= count($columns) ?>" class="px-4 py-6 text-center text-gray-500 italic">No accounts found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <!-- Bottom Pagination (10 per page) -->
    <div id="paginationBar" class="mt-4 flex items-center justify-center gap-2 text-sm">
        <button id="prevPage" class="px-3 py-1 border rounded-lg text-[#0B2C62] hover:bg-[#0B2C62] hover:text-white disabled:opacity-40">Prev</button>
        <span id="pageInfo" class="px-2 text-gray-600">Page 1 of 1</span>
        <button id="nextPage" class="px-3 py-1 border rounded-lg text-[#0B2C62] hover:bg-[#0B2C62] hover:text-white disabled:opacity-40">Next</button>
    </div>
</div>

<!-- Success Notification -->
<?php if (!empty($success_msg)): ?>
<div id="notif" class="fixed top-4 right-4 bg-green-400 text-white px-4 py-2 rounded shadow-lg z-50 transform translate-x-full opacity-0 transition-all duration-300">
    <?= htmlspecialchars($success_msg) ?>
</div>
<?php endif; ?>

<!-- Student View Modal (embedded content) - MUST BE BEFORE add_account.php include -->
<div id="studentViewOverlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden" style="z-index: 9999; display: none;">
  <div class="relative w-full max-w-6xl max-h-[90vh] mx-auto overflow-hidden">
    <div class="absolute -top-10 right-0 flex gap-2">
      <button aria-label="Close" onclick="closeStudentModal()" class="bg-white text-gray-700 px-3 py-1 rounded-lg shadow hover:bg-gray-100">Close</button>
    </div>
    <div id="studentViewInner" class="w-full h-full overflow-auto bg-white rounded-2xl shadow-2xl border-2 border-[#0B2C62]"></div>
  </div>
</div>

<!-- Include Modal -->
<?php include("Accounts/add_account.php"); ?>

<script>
// Show cancel deletion request modal
function showCancelRequestModal(studentId) {
    const modal = document.createElement('div');
    modal.id = 'cancelRequestModal';
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[99999]';
    modal.innerHTML = `
        <div class="bg-white rounded-2xl p-6 max-w-md w-full mx-4">
            <div class="flex flex-col items-center text-center">
                <div class="w-12 h-12 rounded-full bg-orange-50 flex items-center justify-center mb-3">
                    <svg class="w-6 h-6 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-2">Cancel Deletion Request?</h3>
                <p class="text-gray-600">Are you sure you want to cancel the pending deletion request for this student?</p>
            </div>
            <div class="flex gap-3 mt-6">
                <button onclick="document.getElementById('cancelRequestModal').remove()" class="flex-1 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg font-medium transition">
                    No, Keep Request
                </button>
                <button onclick="confirmCancelStudentRequest('${studentId}')" class="flex-1 px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg font-medium transition">
                    Yes, Cancel Request
                </button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

// Confirm cancel deletion request
async function confirmCancelStudentRequest(studentId) {
    try {
        const response = await fetch('../AdminF/cancel_hr_deletion_request.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `target_id=${encodeURIComponent(studentId)}&request_type=student_deletion`
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('Deletion request cancelled successfully', 'success');
            
            // Remove the modal
            const modal = document.getElementById('cancelRequestModal');
            if (modal) modal.remove();
            
            // Close the student view modal
            closeStudentModal();
            
            // Remove pending status from the row
            updateRowPendingStatus(studentId, false);
        } else {
            showToast(data.message || 'Failed to cancel request', 'error');
        }
    } catch (error) {
        console.error('Error cancelling request:', error);
        showToast('An error occurred while cancelling the request', 'error');
    }
}

// Wire up the embedded Student View (no inline script execution needed)
async function setupStudentModalHandlers(studentId) {
    const overlay = document.getElementById('studentViewOverlay');
    const container = document.getElementById('studentViewInner');
    if (!container) return;

    const root = container; // embedded content root
    const editBtn = root.querySelector('#editBtn');
    const deleteBtn = root.querySelector('#deleteBtn');
    const saveBtn = root.querySelector('#saveBtn');
    const xBtn = root.querySelector('button[onclick*="closeModal"], button.text-2xl');
    const backLink = root.querySelector('a[href*="AccountList.php"]');
    const form = root.querySelector('#studentForm');
    const gradeLevel = root.querySelector('#gradeLevel');
    const academicTrack = root.querySelector('select[name="academic_track"]');
    
    // Check if this student has a pending deletion request
    let hasPendingDeletion = false;
    try {
        const response = await fetch('../AdminF/check_pending_requests.php');
        const data = await response.json();
        if (data.success && data.pending_requests) {
            hasPendingDeletion = data.pending_requests.some(request => {
                if (request.request_type === 'student_deletion') {
                    const targetData = request.target_data ? JSON.parse(request.target_data) : {};
                    return (targetData.id_number === studentId || request.target_id === studentId);
                }
                return false;
            });
        }
    } catch (error) {
        console.error('Error checking pending requests:', error);
    }
    
    // Modify buttons based on pending deletion status
    if (deleteBtn) {
        if (hasPendingDeletion) {
            // Show Cancel Deletion Request button
            deleteBtn.textContent = 'Cancel Deletion Request';
            deleteBtn.className = 'px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition';
            deleteBtn.onclick = (e) => {
                e.preventDefault();
                e.stopPropagation();
                showCancelRequestModal(studentId);
            };
        }
    }
    
    if (editBtn) {
        if (hasPendingDeletion) {
            // Hide Edit button when there's a pending deletion
            editBtn.classList.add('hidden');
        } else {
            editBtn.classList.remove('hidden');
        }
    }

    // Ensure form posts to the server endpoint that handles updates/deletes
    if (form) {
        form.action = `Accounts/view_student.php?id=${encodeURIComponent(studentId)}`;
    }

    // Initialize gender select from data-initial if not selected (embedded content scripts don't run)
    const genderSelect = root.querySelector('select[name="gender"]');
    if (genderSelect && !genderSelect.value) {
        const init = genderSelect.getAttribute('data-initial') || genderSelect.dataset.initial;
        if (init) genderSelect.value = init;
    }

    // Helper to update grade levels based on academic track
    // Helper to update grade levels based on academic track
    function updateGradeLevelsLocal() {
        if (!academicTrack || !gradeLevel) return;
        const gradeOptions = {
            "Pre-Elementary": ["Kinder"],
            "Elementary": ["Grade 1","Grade 2","Grade 3","Grade 4","Grade 5","Grade 6"],
            "Junior High School": ["Grade 7","Grade 8","Grade 9","Grade 10"],
            "Senior High School Strands": ["Grade 11","Grade 12"],

            // Allow direct strand selections
            "ABM": ["Grade 11","Grade 12"],
            "GAS": ["Grade 11","Grade 12"],
            "HE": ["Grade 11","Grade 12"],
            "HUMSS": ["Grade 11","Grade 12"],
            "ICT": ["Grade 11","Grade 12"],
            "SPORTS": ["Grade 11","Grade 12"],
            "STEM": ["Grade 11","Grade 12"],

            // College programs
            "College Courses": ["1st Year","2nd Year","3rd Year","4th Year"],
            "BPEd (Bachelor of Physical Education)": ["1st Year","2nd Year","3rd Year","4th Year"],
            "BECEd (Bachelor of Early Childhood Education)": ["1st Year","2nd Year","3rd Year","4th Year"]
        };
        const selectedTrack = academicTrack.value;
        const selectedGrade = gradeLevel.value;

        // Get the optgroup label (e.g., "College Courses")
        const optgroupLabel = selectedTrack ? academicTrack.options[academicTrack.selectedIndex].parentNode?.label : '';

        let levels = [];
        if (gradeOptions[optgroupLabel]) levels = gradeOptions[optgroupLabel];
        else if (gradeOptions[selectedTrack]) levels = gradeOptions[selectedTrack];

        gradeLevel.innerHTML = '<option value="">-- Select Grade Level --</option>';
        levels.forEach(level => {
            const opt = document.createElement('option');
            opt.value = level; opt.textContent = level;
            if (level === selectedGrade) opt.selected = true;
            gradeLevel.appendChild(opt);
        });
    }

    // Helpers to snapshot and restore original values within this embedded root
    function captureOriginalLocal() {
        const fields = root.querySelectorAll('.student-field');
        fields.forEach(f => {
            if (f.dataset.origCaptured === '1') return;
            if (f.tagName === 'SELECT' || f.tagName === 'TEXTAREA' || ['text','tel','number','date'].includes(f.type)) {
                f.dataset.originalValue = f.value;
            } else if (f.type === 'radio' || f.type === 'checkbox') {
                f.dataset.originalChecked = f.checked ? '1' : '0';
            }
            f.dataset.origCaptured = '1';
        });
    }
    function restoreOriginalLocal() {
        const fields = root.querySelectorAll('.student-field');
        fields.forEach(f => {
            if (f.tagName === 'SELECT' || f.tagName === 'TEXTAREA' || ['text','tel','number','date'].includes(f.type)) {
                if (f.dataset.originalValue !== undefined) f.value = f.dataset.originalValue;
            } else if (f.type === 'radio' || f.type === 'checkbox') {
                if (f.dataset.originalChecked !== undefined) f.checked = (f.dataset.originalChecked === '1');
            }
        });
    }

    // Implement toggleEdit locally
    function toggleEditLocal() {
        if (!editBtn || !saveBtn) return;
        const fields = root.querySelectorAll('.student-field');
        const delBtn = root.querySelector('#deleteBtn');
        const resetPasswordBtn = root.querySelector('#resetPasswordBtn');
        const resetParentPasswordBtn = root.querySelector('#resetParentPasswordBtn');
        const passwordField = root.querySelector('#studentPasswordField');
        const parentPasswordField = root.querySelector('#parentPasswordField');
        const isCancel = editBtn.textContent.trim() === 'Cancel';
        if (isCancel) {
            editBtn.textContent = 'Edit';
            editBtn.className = 'px-4 py-2 bg-[#2F8D46] text-white rounded-lg hover:bg-[#256f37] transition';
            saveBtn.classList.add('hidden');
            // Revert any unsaved changes
            restoreOriginalLocal();
            if (delBtn) delBtn.classList.remove('hidden');
            
            // Disable reset password buttons
            if (resetPasswordBtn) {
                resetPasswordBtn.disabled = true;
                resetPasswordBtn.className = 'px-4 py-2 bg-gray-400 text-white rounded-lg font-medium transition-colors cursor-not-allowed';
            }
            if (resetParentPasswordBtn) {
                resetParentPasswordBtn.disabled = true;
                resetParentPasswordBtn.className = 'px-4 py-2 bg-gray-400 text-white rounded-lg font-medium transition-colors cursor-not-allowed';
            }
            
            // Keep password fields disabled
            if (passwordField) {
                passwordField.readOnly = true;
                passwordField.disabled = true;
                passwordField.value = '';
            }
            if (parentPasswordField) {
                parentPasswordField.readOnly = true;
                parentPasswordField.disabled = true;
                parentPasswordField.value = '';
            }
            
            fields.forEach(field => {
                if (['TEXTAREA'].includes(field.tagName) || ['text','tel','number','date'].includes(field.type)) {
                    field.readOnly = true; field.classList.add('bg-gray-50'); field.classList.remove('bg-white');
                } else if (['radio','checkbox'].includes(field.type) || field.tagName === 'SELECT') {
                    field.disabled = true;
                }
            });
        } else {
            editBtn.textContent = 'Cancel';
            editBtn.className = 'px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition';
            saveBtn.classList.remove('hidden');
            // Snapshot current values once when entering edit mode
            captureOriginalLocal();
            if (delBtn) delBtn.classList.add('hidden');
            
            // Enable reset password buttons
            if (resetPasswordBtn) {
                resetPasswordBtn.disabled = false;
                resetPasswordBtn.className = 'px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg font-medium transition-colors cursor-pointer';
                console.log('Student reset button enabled in AccountList');
            }
            if (resetParentPasswordBtn) {
                resetParentPasswordBtn.disabled = false;
                resetParentPasswordBtn.className = 'px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg font-medium transition-colors cursor-pointer';
                console.log('Parent reset button enabled in AccountList');
            }
            
            // Keep password fields disabled (only reset buttons can fill them)
            if (passwordField) {
                passwordField.readOnly = true;
                passwordField.disabled = true;
            }
            if (parentPasswordField) {
                parentPasswordField.readOnly = true;
                parentPasswordField.disabled = true;
            }
            
            fields.forEach(field => {
                // Skip password fields - they should always stay disabled
                if (field.name === 'password' || field.name === 'parent_password' || field.name === 'username' || field.name === 'parent_username') {
                    return;
                }
                
                if (['TEXTAREA'].includes(field.tagName) || ['text','tel','number','date'].includes(field.type)) {
                    field.readOnly = false; field.classList.remove('bg-gray-50'); field.classList.add('bg-white');
                } else if (['radio','checkbox'].includes(field.type) || field.tagName === 'SELECT') {
                    field.disabled = false;
                }
            });
            updateGradeLevelsLocal();
        }
    }

    if (editBtn) {
        editBtn.onclick = (e) => { e.preventDefault(); e.stopPropagation(); toggleEditLocal(); };
    }

    if (deleteBtn && !hasPendingDeletion) {
        deleteBtn.onclick = (e) => {
            e.preventDefault(); e.stopPropagation();
            
            const idInput = root.querySelector('input[name="id_number"]');
            const idVal = idInput ? idInput.value : '';
            
            // Store student ID for the inline handlers
            window._deleteStudentId = idVal;
            
            // Build deletion request modal with reason input
            const c = document.createElement('div');
            c.id = 'deleteConfirmModal';
            c.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[99999]';
            c.style.pointerEvents = 'auto';
            c.innerHTML = `
                <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 overflow-hidden">
                    <div class="p-8">
                        <div class="mx-auto w-20 h-20 bg-orange-50 rounded-full flex items-center justify-center mb-6">
                            <svg class="w-10 h-10 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 mb-3 text-center">Request Student Deletion</h3>
                        <p class="text-gray-600 mb-4 text-center leading-relaxed">
                            This action requires Owner approval. Please provide a reason for deletion.
                        </p>
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Deletion Reason *</label>
                            <textarea 
                                id="deletionReasonInput"
                                rows="4"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent resize-none"
                                placeholder="Enter reason for deleting this student..."
                                required
                            ></textarea>
                            <p class="text-xs text-gray-500 mt-1">This will be sent to the Owner for approval</p>
                        </div>
                    </div>
                    <div class="px-8 pb-8 flex gap-3">
                        <button 
                            onclick="document.getElementById('deleteConfirmModal').remove(); return false;"
                            class="flex-1 px-6 py-3.5 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-xl font-semibold transition-all duration-200"
                            type="button"
                        >
                            Cancel
                        </button>
                        <button 
                            onclick="handleStudentDeleteConfirm(); return false;"
                            class="flex-1 px-6 py-3.5 bg-orange-600 hover:bg-orange-700 text-white rounded-xl font-semibold transition-all duration-200"
                            type="button"
                            id="confirmDeleteBtn"
                        >
                            Send Request
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(c);
            window._deleteModal = c;
        };
    }

    if (xBtn) {
        xBtn.onclick = (e) => { e.preventDefault(); e.stopPropagation(); closeStudentModal(); };
    }
    if (backLink) {
        backLink.onclick = (e) => { e.preventDefault(); e.stopPropagation(); closeStudentModal(); };
    }

    if (academicTrack) {
        academicTrack.addEventListener('change', updateGradeLevelsLocal);
    }
}

// Disable outside-click to close for the student modal (close only via X/Back)
document.addEventListener('click', function(e){
  const overlay = document.getElementById('studentViewOverlay');
  if (!overlay || overlay.classList.contains('hidden')) return;
  // Intentionally do nothing on outside click to prevent accidental closing
});

// Close on ESC
document.addEventListener('keydown', function(e){
  if(e.key === 'Escape') closeStudentModal();
});

window.viewParent = function(parentId) {
    console.log('Clicked parent ID:', parentId);
    console.log('Navigating to:', `Accounts/view_parent.php?id=${parentId}`);
    window.location.href = `Accounts/view_parent.php?id=${parentId}`;
};

window.viewAttendance = function(attendanceId) {
    console.log('Clicked attendance ID:', attendanceId);
    console.log('Navigating to:', `Accounts/view_attendance.php?id=${attendanceId}`);
    window.location.href = `Accounts/view_attendance.php?id=${attendanceId}`;
};

// Show success notification with animation
const notificationElement = document.getElementById("notif");
if (notificationElement) {
    // Show notification with slide-in effect
    setTimeout(() => {
        notificationElement.style.transform = 'translateX(0)';
        notificationElement.style.opacity = '1';
    }, 100);
    
    // Hide after 4 seconds with fade out
    setTimeout(() => {
        notificationElement.style.opacity = '0';
        notificationElement.style.transform = 'translateX(100px)';
        setTimeout(() => notificationElement.remove(), 300);
    }, 4000);
}



// Client-side pagination (10 per page) with search
const searchInput = document.getElementById('searchInput');
const tableBody = document.getElementById('accountTable');
const allRows = Array.from(tableBody.querySelectorAll('tr'));
let filteredRows = allRows.slice();
const trackFilter = document.getElementById('trackFilter');
const gradeFilter = document.getElementById('gradeFilter');
const pageSize = 10;
let currentPage = 1;

const prevBtn = document.getElementById('prevPage');
const nextBtn = document.getElementById('nextPage');
const pageInfo = document.getElementById('pageInfo');

function renderPage() {
    // Hide all
    allRows.forEach(r => r.style.display = 'none');
    const total = filteredRows.length;
    const totalPages = Math.max(1, Math.ceil(total / pageSize));
    currentPage = Math.min(Math.max(1, currentPage), totalPages);
    const start = (currentPage - 1) * pageSize;
    const end = start + pageSize;
    const pageRows = filteredRows.slice(start, end);
    pageRows.forEach((r, idx) => {
        r.style.display = '';
        const serialCell = r.querySelector('.serial');
        if (serialCell) serialCell.textContent = (start + idx + 1).toString();
    });
    if (pageInfo) pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
    if (prevBtn) prevBtn.disabled = (currentPage <= 1);
    if (nextBtn) nextBtn.disabled = (currentPage >= totalPages);
}

function applyFilter() {
    const q = (searchInput?.value || '').toLowerCase().trim();
    let track = (trackFilter?.value || '').toLowerCase().trim();
    let grade = (gradeFilter?.value || '').toLowerCase().trim();
    // Map UI 'kinder' to data value 'pre-elementary'
    if (track === 'kinder') track = 'pre-elementary';
    // Map UI 'kinder 1'/'kinder 2' to data value 'kinder' if your DB stores just 'Kinder'
    if (grade === 'kinder 1' || grade === 'kinder 2') grade = 'kinder';
    filteredRows = allRows.filter(r => {
        const hay = r.textContent.toLowerCase();
        const rTrack = (r.getAttribute('data-track') || '').toLowerCase();
        const rGrade = (r.getAttribute('data-grade') || '').toLowerCase();
        if (q && !hay.includes(q)) return false;
        if (track && rTrack !== track) return false;
        if (grade && rGrade !== grade) return false;
        return true;
    });
    currentPage = 1;
    renderPage();
}

if (searchInput) searchInput.addEventListener('input', applyFilter);
if (trackFilter) trackFilter.addEventListener('change', applyFilter);
if (gradeFilter) gradeFilter.addEventListener('change', applyFilter);
if (prevBtn) prevBtn.addEventListener('click', () => { currentPage--; renderPage(); });
if (nextBtn) nextBtn.addEventListener('click', () => { currentPage++; renderPage(); });

// Initial render
renderPage();

// Dynamically update grade options based on selected track (like in Add Account)
function updateGradeFilterOptions() {
    if (!gradeFilter || !trackFilter) return;
    const trackVal = trackFilter.value;
    const sets = {
        'Kinder': ['Kinder 1','Kinder 2'],
        'Elementary': ['Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6'],
        'Junior High School': ['Grade 7','Grade 8','Grade 9','Grade 10'],
        'Senior High School Strands': ['Grade 11','Grade 12'],
        'ABM': ['Grade 11','Grade 12'],
        'GAS': ['Grade 11','Grade 12'],
        'HE': ['Grade 11','Grade 12'],
        'HUMSS': ['Grade 11','Grade 12'],
        'ICT': ['Grade 11','Grade 12'],
        'SPORTS': ['Grade 11','Grade 12'],
        'STEM': ['Grade 11','Grade 12'],
        'College Courses': ['1st Year','2nd Year','3rd Year','4th Year'],
        'BPEd (Bachelor of Physical Education)': ['1st Year','2nd Year','3rd Year','4th Year'],
        'BECEd (Bachelor of Early Childhood Education)': ['1st Year','2nd Year','3rd Year','4th Year']
    };
    const current = gradeFilter.value;
    const levels = trackVal && sets[trackVal] ? sets[trackVal] : null;
    // Rebuild options
    gradeFilter.innerHTML = '';
    const optAll = document.createElement('option');
    optAll.value = '';
    optAll.textContent = 'All';
    gradeFilter.appendChild(optAll);
    if (levels) {
        levels.forEach(g => {
            const o = document.createElement('option');
            o.value = g; o.textContent = g;
            if (g === current) o.selected = true;
            gradeFilter.appendChild(o);
        });
    } else {
        // Default full list when no track selected
        ['Kinder 1','Kinder 2','Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6','Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12','1st Year','2nd Year','3rd Year','4th Year']
        .forEach(g => {
            const o = document.createElement('option');
            o.value = g; o.textContent = g;
            if (g === current) o.selected = true;
            gradeFilter.appendChild(o);
        });
    }
}

if (trackFilter) {
    trackFilter.addEventListener('change', function(){
        updateGradeFilterOptions();
        applyFilter();
    });
    // Initialize on load
    updateGradeFilterOptions();
}

// School Year Filter Function
function filterBySchoolYear() {
    const selectedYear = document.getElementById('schoolYearFilter').value;
    const currentUrl = new URL(window.location.href);
    
    if (selectedYear) {
        currentUrl.searchParams.set('school_year', selectedYear);
    } else {
        currentUrl.searchParams.delete('school_year');
    }
    
    window.location.href = currentUrl.toString();
}

// Remove duplicate function definition since it's now at the top

// Add RFID instruction and QR code functionality when modal opens
function openModal() {
    // Your existing modal opening code here
    document.getElementById('addAccountModal').classList.remove('hidden');
    
    // Add instruction and QR code functionality with longer delay and retry
    setTimeout(setupQRCodeFunctionality, 200);
    setTimeout(setupQRCodeFunctionality, 500); // Retry after 500ms
    setTimeout(setupQRCodeFunctionality, 1000); // Retry after 1s
    setTimeout(setupOccupationValidation, 200);
    setTimeout(setupOccupationValidation, 500);
    setTimeout(setupOccupationValidation, 1000);
    // Populate school year dropdowns (last 10 years)
    setTimeout(setupSchoolYearOptions, 200);
    setTimeout(setupSchoolYearOptions, 500);
    setTimeout(setupSchoolYearOptions, 1000);
    // Initialize input validation for all fields
    setTimeout(setupInputValidation, 200);
    setTimeout(setupInputValidation, 500);
    setTimeout(setupInputValidation, 1000);
    // Setup auto-generated Student ID
    setTimeout(setupAutoStudentId, 200);
    setTimeout(setupAutoStudentId, 500);
    setTimeout(setupAutoStudentId, 1000);
    // Initialize HR-like inline validation
    setTimeout(initRegistrarInlineValidation, 200);
    // Setup username and password generation
    setTimeout(setupUsernameAndPasswordGeneration, 200);
    setTimeout(setupUsernameAndPasswordGeneration, 500);
    setTimeout(setupUsernameAndPasswordGeneration, 1000);
}

// Setup comprehensive input validation
function setupInputValidation() {
    console.log('Setting up input validation...');
    
    // Prevent numbers in name fields
    const nameFields = [
        'input[name="first_name"]',
        'input[name="last_name"]', 
        'input[name="middle_name"]',
        'input[name="religion"]',
        'input[name="father_name"]',
        'input[name="mother_name"]',
        'input[name="guardian_name"]'
    ];
    
    nameFields.forEach(selector => {
        const field = document.querySelector(selector);
        if (field) {
            // Remove existing listeners to avoid duplicates
            field.removeEventListener('keypress', preventNumbers);
            field.removeEventListener('input', cleanNumbersFromInput);
            
            // Add new listeners
            field.addEventListener('keypress', preventNumbers);
            field.addEventListener('input', cleanNumbersFromInput);
            field.addEventListener('paste', function(e) {
                setTimeout(() => {
                    const value = this.value;
                    const cleanValue = value.replace(/[0-9]/g, '');
                    if (value !== cleanValue) {
                        this.value = cleanValue;
                    }
                }, 10);
            });
            console.log('Validation applied to:', selector);
        }
    });
    
    // Prevent letters in numeric fields
    const numericFields = [
        'input[name="lrn"]',
        'input[name="id_number"]',
        'input[name="rfid_uid"]',
        'input[name="father_contact"]',
        'input[name="mother_contact"]',
        'input[name="guardian_contact"]'
    ];
    
    numericFields.forEach(selector => {
        const field = document.querySelector(selector);
        if (field) {
            // Remove existing listeners to avoid duplicates
            field.removeEventListener('keypress', preventLetters);
            field.removeEventListener('input', cleanLettersFromInput);
            
            // Add new listeners
            field.addEventListener('keypress', preventLetters);
            field.addEventListener('input', cleanLettersFromInput);
            field.addEventListener('paste', function(e) {
                setTimeout(() => {
                    const value = this.value;
                    const cleanValue = value.replace(/[^0-9]/g, '');
                    if (value !== cleanValue) {
                        this.value = cleanValue;
                    }
                }, 10);
            });
            console.log('Numeric validation applied to:', selector);
        }
    });
}

// Prevent numbers from being typed
function preventNumbers(event) {
    const char = String.fromCharCode(event.which || event.keyCode);
    if (/[0-9]/.test(char)) {
        event.preventDefault();
        return false;
    }
}

// Prevent letters from being typed
function preventLetters(event) {
    const char = String.fromCharCode(event.which || event.keyCode);
    if (/[^0-9]/.test(char) && event.keyCode !== 8 && event.keyCode !== 9 && event.keyCode !== 37 && event.keyCode !== 39 && event.keyCode !== 46) {
        event.preventDefault();
        return false;
    }
}

// Clean numbers from input value
function cleanNumbersFromInput(event) {
    const field = event.target;
    const value = field.value;
    const cleanValue = value.replace(/[0-9]/g, '');
    if (value !== cleanValue) {
        field.value = cleanValue;
    }
}

// Clean letters from input value  
function cleanLettersFromInput(event) {
    const field = event.target;
    const value = field.value;
    const cleanValue = value.replace(/[^0-9]/g, '');
    if (value !== cleanValue) {
        field.value = cleanValue;
    }
}

// Setup auto-generated Student ID and Username
function setupAutoStudentId() {
    const studentIdField = document.querySelector('input[name="id_number"]');
    const lastNameField = document.querySelector('input[name="last_name"]');
    const usernameField = document.querySelector('input[name="username"]');
    
    if (studentIdField) {
        // Fetch and display the next Student ID
        fetch('get_next_student_id.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    studentIdField.value = data.next_id;
                    studentIdField.readOnly = true;
                    studentIdField.style.backgroundColor = '#f3f4f6';
                    studentIdField.style.cursor = 'not-allowed';
                    
                    // Store the last6 digits for username generation
                    window.studentIdLast6 = data.last6 || (data.next_id || '').replace(/\D/g, '').slice(-6).padStart(6, '0');
                    
                    // Setup username generation
                    setupUsernameGeneration();
                } else {
                    studentIdField.placeholder = 'Auto-generated (e.g., 02200000001)';
                    studentIdField.readOnly = true;
                    studentIdField.style.backgroundColor = '#f3f4f6';
                    studentIdField.style.cursor = 'not-allowed';
                }
            })
            .catch(error => {
                console.log('Error fetching next ID, using placeholder');
                studentIdField.placeholder = 'Auto-generated (e.g., 02200000001)';
                studentIdField.readOnly = true;
                studentIdField.style.backgroundColor = '#f3f4f6';
                studentIdField.style.cursor = 'not-allowed';
            });
        
        console.log('Student ID field set to auto-generate mode');
    }
}

// Setup username and password auto-generation
function setupUsernameAndPasswordGeneration() {
    const lastNameField = document.querySelector('input[name="last_name"]');
    const usernameField = document.querySelector('input[name="username"]');
    const parentUsernameField = document.querySelector('input[name="parent_username"]');
    const passwordField = document.querySelector('input[name="password"]');
    const parentPasswordField = document.querySelector('input[name="parent_password"]');
    const dobMonthField = document.querySelector('select[name="dob_month"]');
    const dobDayField = document.querySelector('select[name="dob_day"]');
    const dobYearField = document.querySelector('select[name="dob_year"]');
    
    if (!lastNameField || !usernameField) {
        console.log('Username generation: Required fields not found');
        return;
    }
    
    const generateCredentials = () => {
        const lastName = (lastNameField.value || '').toLowerCase().replace(/[^a-z]/g, '');
        const last6 = window.studentIdLast6 || '000000';
        
        // Always keep username fields readonly
        usernameField.readOnly = true;
        usernameField.style.backgroundColor = '#f3f4f6';
        usernameField.style.cursor = 'not-allowed';
        
        if (parentUsernameField) {
            parentUsernameField.readOnly = true;
            parentUsernameField.style.backgroundColor = '#f3f4f6';
            parentUsernameField.style.cursor = 'not-allowed';
        }
        
        // Generate usernames
        if (lastName && last6.length === 6) {
            // Generate student username
            const username = `${lastName}${last6}muzon@student.cci.edu.ph`;
            usernameField.value = username;
            console.log('Student username generated:', username);
            
            // Generate parent username if field exists
            if (parentUsernameField) {
                const parentUsername = `${lastName}${last6}muzon@parent.cci.edu.ph`;
                parentUsernameField.value = parentUsername;
                console.log('Parent username generated:', parentUsername);
            }
        } else {
            // Clear usernames if last name is empty but keep readonly
            usernameField.value = '';
            
            if (parentUsernameField) {
                parentUsernameField.value = '';
            }
        }
        
        // Generate passwords ONLY when date of birth is complete
        if (lastName && dobMonthField && dobDayField && dobYearField && 
            dobMonthField.value && dobDayField.value && dobYearField.value) {
            
            const months = ['', 'january', 'february', 'march', 'april', 'may', 'june',
                          'july', 'august', 'september', 'october', 'november', 'december'];
            const monthName = months[parseInt(dobMonthField.value)] || '';
            const day = dobDayField.value;
            const year = dobYearField.value;
            
            if (monthName && day && year) {
                const password = `${lastName}${monthName}${day}${year}`;
                console.log('Generated password:', password);
                
                // Set student password
                if (passwordField) {
                    passwordField.value = password;
                    passwordField.readOnly = true;
                    passwordField.style.backgroundColor = '#f3f4f6';
                    passwordField.style.cursor = 'not-allowed';
                    console.log('Student password set:', password);
                }
                
                // Set parent password
                if (parentPasswordField) {
                    parentPasswordField.value = password;
                    parentPasswordField.readOnly = true;
                    parentPasswordField.style.backgroundColor = '#f3f4f6';
                    parentPasswordField.style.cursor = 'not-allowed';
                    console.log('Parent password set:', password);
                }
            }
        } else {
            // Keep passwords readonly but empty if date of birth is not complete
            if (passwordField) {
                passwordField.value = '';
                passwordField.readOnly = true;
                passwordField.style.backgroundColor = '#f3f4f6';
                passwordField.style.cursor = 'not-allowed';
            }
            if (parentPasswordField) {
                parentPasswordField.value = '';
                parentPasswordField.readOnly = true;
                parentPasswordField.style.backgroundColor = '#f3f4f6';
                parentPasswordField.style.cursor = 'not-allowed';
            }
        }
    };
    
    // Generate credentials when last name or date fields change
    lastNameField.addEventListener('input', generateCredentials);
    lastNameField.addEventListener('blur', generateCredentials);
    
    // Also update when date fields change
    if (dobMonthField) dobMonthField.addEventListener('change', generateCredentials);
    if (dobDayField) dobDayField.addEventListener('change', generateCredentials);
    if (dobYearField) dobYearField.addEventListener('change', generateCredentials);
    
    // Initialize username fields as readonly immediately
    usernameField.readOnly = true;
    usernameField.style.backgroundColor = '#f3f4f6';
    usernameField.style.cursor = 'not-allowed';
    
    if (parentUsernameField) {
        parentUsernameField.readOnly = true;
        parentUsernameField.style.backgroundColor = '#f3f4f6';
        parentUsernameField.style.cursor = 'not-allowed';
    }
    
    // Initial generation if fields already have values
    if (lastNameField.value) {
        generateCredentials();
    }
    
    // Add manual trigger for testing
    setTimeout(() => {
        generateCredentials();
    }, 1000);
    
    console.log('Username and password generation setup complete');
}

// Test function for password generation
function testPasswordGeneration() {
    const lastNameField = document.querySelector('input[name="last_name"]');
    const dobMonthField = document.querySelector('select[name="dob_month"]');
    const dobDayField = document.querySelector('select[name="dob_day"]');
    const dobYearField = document.querySelector('select[name="dob_year"]');
    
    console.log('Test - Found fields:', {
        lastName: lastNameField?.value,
        month: dobMonthField?.value,
        day: dobDayField?.value,
        year: dobYearField?.value
    });
    
    // Trigger the generation function
    if (window.setupUsernameAndPasswordGeneration) {
        setupUsernameAndPasswordGeneration();
    }
}

// Close Add Account modal (used by the × button inside the modal markup)
function closeModal(event) {
    // Prevent any default behavior or form submission
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    const modal = document.getElementById('addAccountModal');
    const modalContent = document.getElementById('modalContent');
    
    // Clear any RFID error states
    const rfidInput = document.getElementById('rfidInput');
    if (rfidInput) {
        rfidInput.classList.remove('border-red-500', 'bg-red-50');
        rfidInput.classList.add('border-gray-300');
        rfidInput.value = ''; // Clear the value to reset the field
        
        // Remove error message
        const errorMsg = rfidInput.parentElement?.querySelector('.rfid-error-msg');
        if (errorMsg) {
            errorMsg.remove();
        }
    }
    
    // Clear all error messages
    const allErrorMsgs = modal?.querySelectorAll('.rfid-error-msg');
    if (allErrorMsgs) {
        allErrorMsgs.forEach(msg => msg.remove());
    }
    
    // Reset the form to clear any validation states
    const form = modal?.querySelector('form');
    if (form) {
        // Don't use form.reset() as it might trigger navigation
        // Just clear validation states
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.classList.remove('border-red-500', 'bg-red-50');
            if (input.classList.contains('border')) {
                input.classList.add('border-gray-300');
            }
        });
    }
    
    // Instant close - no animation delay
    if (modalContent) {
        modalContent.classList.remove('scale-100');
        modalContent.classList.add('scale-95');
    }
    
    if (modal) {
        modal.classList.add('hidden');
    }
    document.body.style.overflow = '';
    
    // Prevent any default behavior and stop event propagation completely
    if (event) {
        event.stopImmediatePropagation();
    }
    
    return false;
}

// Inline validation for Add Account modal (HR-style red feedback)
function initRegistrarInlineValidation() {
    const form = document.querySelector('#addAccountModal #unifiedForm form');
    if (!form) return;
    // Disable native browser validation popups/tooltips
    form.setAttribute('novalidate', 'novalidate');
    form.addEventListener('invalid', function(e){ e.preventDefault(); }, true);

    const requiredNames = [
        'lrn','last_name','first_name','dob','birthplace','gender','religion',
        'academic_track','grade_level','semester','school_year','enrollment_status',
        'payment_mode','address',
        'father_first_name','father_last_name','father_occupation','father_contact',
        'mother_first_name','mother_last_name','mother_occupation','mother_contact',
        'guardian_first_name','guardian_last_name','guardian_occupation','guardian_contact','last_school','last_school_year'
    ];

    const findField = (name) => form.querySelector(`[name="${name}"]`);
    const containerFor = (el) => el?.closest('div') || el?.parentElement || form;
    const ensureMsg = (containerEl) => { const c = containerEl || form; let m = c.querySelector(':scope > .error-text'); if (!m) { m = document.createElement('div'); m.className = 'error-text'; c.appendChild(m); } return m; };
    const labelMap = {
        lrn: 'LRN', last_name: 'Last name', first_name: 'First name', middle_name: 'Middle name',
        dob: 'Date of birth', birthplace: 'Birthplace', gender: 'Gender', religion: 'Religion',
        academic_track: 'Academic track / course', grade_level: 'Grade level', semester: 'Semester',
        school_year: 'School year', enrollment_status: 'Enrollment status', payment_mode: 'Mode of payment',
        address: 'Address',
        father_first_name: "Father's first name", father_last_name: "Father's last name", father_middle_name: "Father's middle name", father_occupation: "Father's occupation", father_contact: "Father's contact",
        mother_first_name: "Mother's first name", mother_last_name: "Mother's last name", mother_middle_name: "Mother's middle name", mother_occupation: "Mother's occupation", mother_contact: "Mother's contact",
        guardian_first_name: "Guardian's first name", guardian_last_name: "Guardian's last name", guardian_middle_name: "Guardian's middle name", guardian_occupation: "Guardian's occupation", guardian_contact: "Guardian's contact",
        last_school: 'Last school attended', last_school_year: 'School year',
        rfid_uid: 'RFID number'
    };
    function readableLabel(el, name){
        // Try nearest preceding label
        const cont = containerFor(el);
        const lab = cont ? cont.querySelector(':scope > label') : null;
        if (lab && lab.textContent) return lab.textContent.replace('*','').trim();
        return labelMap[name] || 'This field';
    }
    function setError(el, text, explicitContainer){
        if(!el && !explicitContainer) return;
        const cont = explicitContainer || containerFor(el);
        if (el) el.classList.add('field-error');
        const m = ensureMsg(cont);
        m.textContent = text || 'This field is required.';
    }
    const clearError = (el) => { if(!el) return; el.classList.remove('field-error'); const c = containerFor(el); const m = c.querySelector(':scope > .error-text'); if (m) m.remove(); };

    function validate() {
        let ok = true;
        requiredNames.forEach(n => { const f = findField(n); if (f) clearError(f); });

        requiredNames.forEach(name => {
            if (['enrollment_status','payment_mode'].includes(name)) {
                const group = form.querySelectorAll(`[name="${name}"]`);
                let checked = false; group.forEach(r => { if (r.checked) checked = true; });
                if (!checked && group.length) {
                    const cont = group[0].closest('div') || form; // place message under the radio group container
                    setError(null, `${labelMap[name]} is required`, cont);
                    ok = false;
                }
                return;
            }
            // Special handling for date of birth (separate fields)
            if (name === 'dob') {
                const monthEl = findField('dob_month');
                const dayEl = findField('dob_day');
                const yearEl = findField('dob_year');
                
                if (!monthEl?.value || !dayEl?.value || !yearEl?.value) {
                    if (monthEl && !monthEl.value) { setError(monthEl, 'Birth month is required'); ok = false; }
                    if (dayEl && !dayEl.value) { setError(dayEl, 'Birth day is required'); ok = false; }
                    if (yearEl && !yearEl.value) { setError(yearEl, 'Birth year is required'); ok = false; }
                }
                return;
            }
            const el = findField(name); if (!el) return;
            const val = (el.value || '').trim(); if (!val) { setError(el, `${readableLabel(el, name)} is required`); ok = false; }
        });

        const patterns = [
            { n:'lrn', re:/^[0-9]+$/, msg:'LRN must contain numbers only.' },
            { n:'last_name', re:/^[A-Za-z\s]+$/, msg:'Last name must contain letters only.' },
            { n:'first_name', re:/^[A-Za-z\s]+$/, msg:'First name must contain letters only.' },
            { n:'middle_name', re:/^[A-Za-z\s]*$/, msg:'Middle name must contain letters only.' },
            { n:'father_first_name', re:/^[A-Za-z\s]+$/, msg:"Father's first name must contain letters only." },
            { n:'father_last_name', re:/^[A-Za-z\s]+$/, msg:"Father's last name must contain letters only." },
            { n:'father_middle_name', re:/^[A-Za-z\s]*$/, msg:"Father's middle name must contain letters only." },
            { n:'father_occupation', re:/^[A-Za-z\s]+$/, msg:"Father's occupation must contain letters only." },
            { n:'mother_first_name', re:/^[A-Za-z\s]+$/, msg:"Mother's first name must contain letters only." },
            { n:'mother_last_name', re:/^[A-Za-z\s]+$/, msg:"Mother's last name must contain letters only." },
            { n:'mother_middle_name', re:/^[A-Za-z\s]*$/, msg:"Mother's middle name must contain letters only." },
            { n:'mother_occupation', re:/^[A-Za-z\s]+$/, msg:"Mother's occupation must contain letters only." },
            { n:'guardian_first_name', re:/^[A-Za-z\s]+$/, msg:"Guardian's first name must contain letters only." },
            { n:'guardian_last_name', re:/^[A-Za-z\s]+$/, msg:"Guardian's last name must contain letters only." },
            { n:'guardian_middle_name', re:/^[A-Za-z\s]*$/, msg:"Guardian's middle name must contain letters only." },
            { n:'guardian_occupation', re:/^[A-Za-z\s]+$/, msg:"Guardian's occupation must contain letters only." },
            { n:'birthplace', re:/^[A-Za-z\s,.-]+$/, msg:'Birthplace must contain valid characters only.' },
            { n:'religion', re:/^[A-Za-z\s]+$/, msg:'Religion must contain letters only.' },
            { n:'school_year', re:/^[0-9\-]+$/, msg:'School year must be like 2024-2025.' },
            { n:'address', re:/^.{20,500}$/, msg:'Complete address must be 20-500 characters long.' },
            { n:'rfid_uid', re:/^[0-9]{10}$/, msg:'RFID must be exactly 10 digits.', optional: true }
        ];
        patterns.forEach(p => { const el = findField(p.n); if (el && el.value && !p.re.test(el.value.trim())) { setError(el, p.msg); ok = false; } });

        ['father_contact','mother_contact','guardian_contact'].forEach(n => { const el = findField(n); if (el && el.value && !/^[0-9]{11}$/.test(el.value.trim())) { setError(el, 'Contact must be exactly 11 digits.'); ok = false; } });

        // LRN validation - must be exactly 12 digits
        const lrnEl = findField('lrn');
        if (lrnEl && lrnEl.value && !/^[0-9]{12}$/.test(lrnEl.value.trim())) { setError(lrnEl, 'LRN must be exactly 12 digits.'); ok = false; }

        // Additional address validation for completeness
        const addressEl = findField('address');
        if (addressEl && addressEl.value) {
            const address = addressEl.value.trim();
            if (address.length >= 20) {
                // Check if address contains multiple components (at least 2 commas or multiple words)
                const components = address.split(/[,\s]+/).filter(part => part.length > 0);
                if (components.length < 4) {
                    setError(addressEl, 'Complete address must include multiple components (street, barangay, city, etc.) separated by commas or spaces.');
                    ok = false;
                }
            }
        }

        return ok;
    }

    // Bind once per open
    if (!form.__validatorBound) {
        form.addEventListener('submit', function(e){ 
            if (!validate()) { 
                e.preventDefault(); 
                const firstErr = form.querySelector('.field-error'); 
                if (firstErr) firstErr.scrollIntoView({behavior:'smooth', block:'center'}); 
            }
        });
        form.addEventListener('input', (e) => clearError(e.target));
        form.addEventListener('change', (e) => clearError(e.target));
        form.__validatorBound = true;
    }

    // Don't run validation automatically - only on submit
    // If server rendered error box, show inline highlights too (but only if there are actual server errors)
    const hasServerErrors = !!document.querySelector('#addAccountModal .bg-red-100');
    if (hasServerErrors) {
        // Only show errors for fields that have server-side errors, not all fields
        setTimeout(() => {
            const errorBox = document.querySelector('#addAccountModal .bg-red-100');
            if (errorBox) {
                // Server errors exist, but don't validate all fields - user needs to submit first
                console.log('Server errors detected, but waiting for user to submit');
            }
        }, 0);
    }
}

// Enforce Kinder + Kinder 2 grade levels for Pre-Elementary/Kinder
(function() {
    document.addEventListener('DOMContentLoaded', function(){
        const originalPopulate = window.populateGradeLevels;
        window.populateGradeLevels = function(selectedTrack, selectedGrade = '') {
            if (typeof originalPopulate === 'function') {
                try { originalPopulate(selectedTrack, selectedGrade); } catch (e) {}
            }
            try {
                const academicTrack = document.querySelector('select[name="academic_track"]');
                const gradeLevel = document.getElementById('gradeLevel');
                if (!academicTrack || !gradeLevel) return;
                const groupLabel = academicTrack.options[academicTrack.selectedIndex]?.parentNode?.label || '';
                if (groupLabel === 'Pre-Elementary' || selectedTrack === 'Pre-Elementary' || (academicTrack.value || '').toLowerCase().includes('kinder')) {
                    // Force Kinder options (Kinder 1 and Kinder 2)
                    const opts = ['Kinder 1', 'Kinder 2'];
                    gradeLevel.innerHTML = '<option value="">-- Select Grade Level --</option>';
                    // If an older saved value was simply 'Kinder', map it to 'Kinder 1'
                    const normalizedSelected = (selectedGrade === 'Kinder') ? 'Kinder 1' : selectedGrade;
                    opts.forEach(level => {
                        const opt = document.createElement('option');
                        opt.value = level; opt.textContent = level;
                        if (level === normalizedSelected) opt.selected = true;
                        gradeLevel.appendChild(opt);
                    });
                }
            } catch (e) { console.warn('Kinder options override failed', e); }
        };

        // Also enforce after modal opens
        const origOpen = window.openModal;
        if (typeof origOpen === 'function') {
            window.openModal = function() {
                origOpen.apply(this, arguments);
                setTimeout(() => {
                    const academicTrack = document.querySelector('select[name="academic_track"]');
                    if (academicTrack) {
                        window.populateGradeLevels(academicTrack.value);
                    }
                }, 150);
            };
        }
    });
})();

// Wrapper for the Add Account button to ensure school years are populated
function openAddAccountModal() {
    if (typeof openModal === 'function') {
        openModal();
    }
    
    // Clear any existing error messages from previous submissions
    setTimeout(() => {
        const modal = document.getElementById('addAccountModal');
        if (modal) {
            // Remove server-side error boxes
            const errorBoxes = modal.querySelectorAll('.bg-red-100, .bg-red-50');
            errorBoxes.forEach(box => box.remove());
            
            // Clear inline field errors
            const fieldErrors = modal.querySelectorAll('.field-error');
            fieldErrors.forEach(field => {
                field.classList.remove('field-error');
            });
            
            // Remove error text messages
            const errorTexts = modal.querySelectorAll('.error-text');
            errorTexts.forEach(text => text.remove());
        }
    }, 10);
    
    // Populate years after modal exists
    setTimeout(setupSchoolYearOptions, 50);
    setTimeout(setupSchoolYearOptions, 200);
    // Setup input validation
    setTimeout(setupInputValidation, 50);
    setTimeout(setupInputValidation, 200);
    setTimeout(setupInputValidation, 500);
    // Setup auto-generated Student ID and Username
    setTimeout(setupAutoStudentId, 50);
    setTimeout(setupAutoStudentId, 200);
    setTimeout(setupAutoStudentId, 500);
    // Additional setup for username generation
    setTimeout(() => {
        // Ensure username generation is set up even if Student ID is already loaded
        if (window.studentIdLast6) {
            setupUsernameGeneration();
        }
    }, 600);
    // Add focus-based fallback: if still empty, populate on first focus
    setTimeout(() => {
        ['schoolYearSelect','lastSchoolYearSelect'].forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            const handler = () => { if (el.options.length <= 1) setupSchoolYearOptions(); el.removeEventListener('focus', handler); };
            el.addEventListener('focus', handler, { once: true });
        });
    }, 300);
}

function setupQRCodeFunctionality() {
    const rfidInput = document.querySelector('input[name="rfid_uid"]');
    console.log('Looking for RFID input:', rfidInput); // Debug log
    
    if (rfidInput) {
        console.log('RFID input found, setting up QR functionality'); // Debug log
        
        // Add RFID instruction if not exists
        if (!document.getElementById('rfidInstruction')) {
            const instruction = document.createElement('small');
            instruction.id = 'rfidInstruction';
            instruction.className = 'text-black text-xs mt-1 block';
            instruction.innerHTML = 'Please tap the ID card on the RFID reader';
            rfidInput.parentNode.appendChild(instruction);
        }
        
        // Add QR code container if not exists
        if (!document.getElementById('qrCodeContainer')) {
            const qrContainer = document.createElement('div');
            qrContainer.id = 'qrCodeContainer';
            qrContainer.className = 'mt-3 p-3 bg-gray-50 border border-gray-300 rounded text-center';
            qrContainer.style.display = 'none';
            qrContainer.innerHTML = `
                <p class="text-sm font-medium text-gray-700 mb-2">Student RFID QR Code</p>
                <div class="canvas-container">
                    <canvas id="qrCodeCanvas" width="150" height="150" class="mx-auto bg-white p-2 border border-gray-300"></canvas>
                </div>
                <p class="text-xs text-gray-600 mt-2">Ready for printing on student RFID</p>
                <button type="button" onclick="printQRCode()" class="mt-2 px-3 py-1 bg-gray-600 hover:bg-gray-700 text-white text-xs rounded">
                    Print QR Code
                </button>
            `;
            rfidInput.parentNode.appendChild(qrContainer);
            console.log('QR container added'); // Debug log
        }
        
        // Add event listener for RFID input
        rfidInput.removeEventListener('input', handleRFIDInput); // Remove existing listener
        rfidInput.addEventListener('input', handleRFIDInput);
        
        // Add event listener to clear RFID errors when user types
        rfidInput.addEventListener('input', function() {
            // Clear error styling
            this.classList.remove('border-red-500', 'bg-red-50');
            this.classList.add('border-gray-300');
            
            // Remove error message
            const errorMsg = this.parentElement?.querySelector('.rfid-error-msg');
            if (errorMsg) {
                errorMsg.remove();
            }
            
            // Clear error alert at top
            const errorAlert = document.getElementById('rfidErrorAlert');
            if (errorAlert) {
                errorAlert.classList.add('hidden');
            }
        });
        
        console.log('Event listener added to RFID input'); // Debug log
    } else {
        console.log('RFID input not found yet'); // Debug log
    }
}

// Prevent digits in occupation fields (frontend only, keep it simple)
function setupOccupationValidation() {
    const selectors = [
        'input[name="father_occupation"]',
        'input[name="mother_occupation"]',
        'input[name="guardian_occupation"]'
    ];
    selectors.forEach(sel => {
        const el = document.querySelector(sel);
        if (!el) return;
        // Add a pattern/title for HTML validation too
        el.setAttribute('pattern', '^[^0-9]*$');
        el.setAttribute('title', 'Digits are not allowed');
        // Block typing digits
        el.addEventListener('keypress', (e) => {
            const ch = String.fromCharCode(e.which || e.keyCode);
            if (/\d/.test(ch)) e.preventDefault();
        }, { once: false });
        // Strip digits on paste or programmatic input
        const strip = (e) => { e.target.value = e.target.value.replace(/\d/g, ''); };
        el.addEventListener('input', strip, { once: false });
    });
}

// Populate School Year selects with the last 10 academic years
function setupSchoolYearOptions() {
    try {
        const now = new Date();
        const y = now.getFullYear();

        // Helper to ensure placeholder remains (keep the first option)
        function resetKeepPlaceholder(selectEl) {
            if (!selectEl) return;
            while (selectEl.options.length > 1) selectEl.remove(1);
        }

        // Main School Year (required) - CURRENT YEAR ONLY (auto-updates yearly)
    const schoolYearSelect = document.getElementById('schoolYearSelect');
    console.log('setupSchoolYearOptions: schoolYearSelect found?', !!schoolYearSelect);
    if (schoolYearSelect) {
        const currentDesired = `${y}-${y+1}`;
        // Remove ALL options then add only current academic year
        while (schoolYearSelect.options.length > 0) schoolYearSelect.remove(0);
        const opt = document.createElement('option');
        opt.value = currentDesired; opt.textContent = currentDesired;
        schoolYearSelect.appendChild(opt);
        schoolYearSelect.value = currentDesired;
    }

    // Last School Year (optional)
    const lastSySelect = document.getElementById('lastSchoolYearSelect');
    console.log('setupSchoolYearOptions: lastSchoolYearSelect found?', !!lastSySelect);
    if (lastSySelect) {
        resetKeepPlaceholder(lastSySelect);
        // Newest first: current down to (current-4) => total 5 years
        for (let i = 0; i <= 4; i++) {
            const start = y - i;
            const end = start + 1;
            const label = `${start}-${end}`;
            const opt = document.createElement('option');
            opt.value = label; opt.textContent = label;
            lastSySelect.appendChild(opt);
        }
    }
    } catch (e) {
        console.error('setupSchoolYearOptions error:', e);
    }
}

// Handle RFID input and generate QR code
function handleRFIDInput(event) {
    const rfid = event.target.value.trim();
    const qrContainer = document.getElementById('qrCodeContainer');
    
    console.log('RFID input changed:', rfid, 'Length:', rfid.length); // Debug log
    
    if (rfid.length === 10 && /^\d{10}$/.test(rfid)) {
        console.log('Valid RFID detected, generating QR code'); // Debug log
        // Valid 10-digit RFID, show container first then generate QR code
        if (qrContainer) {
            qrContainer.style.display = 'block';
            console.log('QR container shown'); // Debug log
            
            // Small delay to ensure canvas is rendered before generating QR
            setTimeout(() => {
                generateQRCode(rfid);
            }, 100);
        } else {
            console.log('QR container not found!'); // Debug log
        }
    } else {
        console.log('Invalid RFID, hiding QR container'); // Debug log
        // Invalid or incomplete RFID, hide QR code
        if (qrContainer) qrContainer.style.display = 'none';
    }
}

// Generate QR code
function generateQRCode(rfid) {
    const canvas = document.getElementById('qrCodeCanvas');
    console.log('Generating QR code for:', rfid, 'Canvas found:', !!canvas); // Debug log
    console.log('QRious library available:', typeof QRious); // Debug log
    
    if (!canvas) {
        console.log('Canvas not found!'); // Debug log
        return;
    }
    
    // Check if QRious library is loaded
    if (typeof QRious === 'undefined') {
        console.error('QRious library not loaded!');
        // Fallback: show text instead of QR code
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = '#0B2C62';
        ctx.font = '12px Arial';
        ctx.textAlign = 'center';
        ctx.fillText('QR Library Loading...', 75, 75);
        return;
    }
    
    try {
        // Generate new QR code using QRious
        const qr = new QRious({
            element: canvas,
            value: rfid,
            size: 150,
            foreground: '#000000',
            background: '#FFFFFF'
        });
        console.log('QR Code generated successfully!'); // Debug log
    } catch (e) {
        console.error('QR Code generation exception:', e);
        // Show RFID number as fallback
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = '#000000';
        ctx.font = '14px Arial';
        ctx.textAlign = 'center';
        ctx.fillText('RFID:', 75, 65);
        ctx.fillText(rfid, 75, 85);
    }
}

// Print QR code
function printQRCode() {
    const canvas = document.getElementById('qrCodeCanvas');
    const rfidInput = document.querySelector('input[name="rfid_uid"]');
    
    const rfid = rfidInput.value.trim();
    const studentName = document.querySelector('input[name="first_name"]')?.value + ' ' + 
                       document.querySelector('input[name="last_name"]')?.value;
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Student QR Code - RFID ${rfid}</title>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    text-align: center; 
                    margin: 20px;
                    background: white;
                }
                .header { margin-bottom: 30px; }
                .qr-container {
                    margin: 30px 0;
                    padding: 20px;
                    border: 2px solid #0B2C62;
                    border-radius: 10px;
                    display: inline-block;
                }
                .info { margin-top: 20px; font-size: 14px; color: #666; }
            </style>
        </head>
        <body>
            <div class="qr-container">
                <img src="${canvas.toDataURL()}" alt="QR Code">
            </div>
        </body>
        </html>
    `);
    
    printWindow.document.close();
    setTimeout(() => printWindow.print(), 500);
}

// Handle student delete confirmation (called from inline onclick)
async function handleStudentDeleteConfirm() {
    const studentId = window._deleteStudentId;
    const modal = window._deleteModal;
    const deletionReasonInput = document.getElementById('deletionReasonInput');
    const deletionReason = deletionReasonInput.value.trim();
    
    if (!deletionReason) {
        // Show error styling on the textarea
        deletionReasonInput.classList.add('border-red-500', 'bg-red-50');
        deletionReasonInput.focus();
        
        // Show error message below textarea
        let errorMsg = deletionReasonInput.parentElement.querySelector('.error-message');
        if (!errorMsg) {
            errorMsg = document.createElement('p');
            errorMsg.className = 'error-message text-red-600 text-sm mt-1 font-medium';
            errorMsg.textContent = 'Please provide a reason for deletion';
            deletionReasonInput.parentElement.appendChild(errorMsg);
        }
        
        // Remove error styling when user starts typing
        deletionReasonInput.addEventListener('input', function() {
            this.classList.remove('border-red-500', 'bg-red-50');
            const err = this.parentElement.querySelector('.error-message');
            if (err) err.remove();
        }, { once: true });
        
        return;
    }
    
    // Disable button and show loading
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<svg class="animate-spin h-5 w-5 mx-auto" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
    
    try {
        const formData = new FormData();
        formData.append('action', 'request_student_deletion');
        formData.append('student_id', studentId);
        formData.append('deletion_reason', deletionReason);
        
        const response = await fetch('request_student_deletion.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            modal.remove();
            showToast('✅ Deletion request sent to Owner for approval', 'success');
            
            // Close the student information modal
            closeStudentModal();
            
            // Update the row to show pending status without refresh
            updateRowPendingStatus(studentId, true);
        } else {
            showToast('Error: ' + data.message, 'error');
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = 'Send Request';
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('An error occurred while sending the request', 'error');
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = 'Send Request';
    }
}

// Toast notification function
function showToast(message, type='success'){
    const t = document.getElementById('toast');
    const ti = document.getElementById('toastInner');
    if (!t || !ti) {
        console.error('Toast elements not found');
        return;
    }
    ti.className = 'px-4 py-3 rounded shadow-lg text-white ' + (type==='success'?'bg-green-600':'bg-red-600');
    ti.textContent = message;
    t.classList.remove('hidden');
    clearTimeout(window.__toastTimer);
    window.__toastTimer = setTimeout(()=>{ t.classList.add('hidden'); }, 3000);
}

// Show approval notification
function showApprovalNotification(studentName, studentId, approval) {
    const notif = document.getElementById('approvalNotification');
    const title = document.getElementById('approvalTitle');
    const message = document.getElementById('approvalMessage');
    
    if (!notif || !title || !message) return;
    
    // Update border color for approval (green)
    notif.querySelector('div').className = 'bg-white rounded-lg shadow-2xl p-6 max-w-md border-l-4 border-green-500';
    notif.querySelector('svg').className = 'w-6 h-6 text-green-500';
    
    title.textContent = '✓ Request Approved!';
    message.innerHTML = `
        <div class="space-y-1">
            <p><strong>Your request has been processed</strong></p>
            <p><strong>Request:</strong> Delete Student: ${studentName}</p>
            <p><strong>Student ID:</strong> ${studentId}</p>
            <p><strong>Approved by:</strong> ${approval.reviewedBy || 'School Owner'}</p>
            <p><strong>Time:</strong> ${approval.reviewedAt || 'Just now'}</p>
            ${approval.ownerComments ? `<p><strong>Comments:</strong> ${approval.ownerComments}</p>` : ''}
        </div>
    `;
    
    notif.classList.remove('hidden');
    
    // Auto-hide after 8 seconds
    clearTimeout(window.__approvalTimer);
    window.__approvalTimer = setTimeout(() => {
        notif.classList.add('hidden');
    }, 8000);
}

// Show rejection notification
function showRejectionNotification(studentName, studentId, approval) {
    const notif = document.getElementById('approvalNotification');
    const title = document.getElementById('approvalTitle');
    const message = document.getElementById('approvalMessage');
    
    if (!notif || !title || !message) return;
    
    // Update border color for rejection (red)
    notif.querySelector('div').className = 'bg-white rounded-lg shadow-2xl p-6 max-w-md border-l-4 border-red-500';
    notif.querySelector('svg').className = 'w-6 h-6 text-red-500';
    notif.querySelector('svg').innerHTML = '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>';
    
    title.textContent = '✗ Request Rejected';
    message.innerHTML = `
        <div class="space-y-1">
            <p><strong>Your request has been rejected</strong></p>
            <p><strong>Request:</strong> Delete Student: ${studentName}</p>
            <p><strong>Student ID:</strong> ${studentId}</p>
            <p><strong>Rejected by:</strong> ${approval.reviewedBy || 'School Owner'}</p>
            <p><strong>Time:</strong> ${approval.reviewedAt || 'Just now'}</p>
            ${approval.ownerComments ? `<p><strong>Reason:</strong> ${approval.ownerComments}</p>` : ''}
        </div>
    `;
    
    notif.classList.remove('hidden');
    
    // Auto-hide after 8 seconds
    clearTimeout(window.__approvalTimer);
    window.__approvalTimer = setTimeout(() => {
        notif.classList.add('hidden');
    }, 8000);
}

// Update row pending status dynamically
function updateRowPendingStatus(studentId, isPending) {
    console.log('updateRowPendingStatus called:', studentId, isPending);
    const table = document.getElementById('accountTable');
    if (!table) {
        console.error('Table not found');
        return;
    }
    
    const rows = table.querySelectorAll('tr');
    console.log('Total rows found:', rows.length);
    let found = false;
    
    rows.forEach(row => {
        const idCell = row.querySelector('td:nth-child(2)'); // Student ID column
        if (!idCell) return;
        
        const idText = idCell.textContent.trim();
        console.log('Checking row with ID:', idText);
        
        if (idText.includes(studentId)) {
            found = true;
            console.log('Match found for student:', studentId);
            
            if (isPending) {
                // Add pending status
                row.classList.add('bg-orange-50', 'border-l-4', 'border-orange-500');
                
                // Add badge if not exists
                if (!idCell.querySelector('.bg-orange-100')) {
                    const badge = document.createElement('span');
                    badge.className = 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800 ml-2';
                    badge.innerHTML = `
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                        </svg>
                        Pending Deletion
                    `;
                    idCell.appendChild(badge);
                    console.log('Badge added to student:', studentId);
                }
            } else {
                // Remove pending status
                row.classList.remove('bg-orange-50', 'border-l-4', 'border-orange-500');
                
                // Remove badge
                const badge = idCell.querySelector('.bg-orange-100');
                if (badge) {
                    badge.remove();
                    console.log('Badge removed from student:', studentId);
                }
            }
        }
    });
    
    if (!found) {
        console.warn('No matching row found for student:', studentId);
    }
}

// Remove student row from table
function removeStudentRow(studentId) {
    const table = document.getElementById('accountTable');
    if (!table) return;
    
    const rows = table.querySelectorAll('tr[data-student-id]');
    rows.forEach(row => {
        const rowStudentId = row.getAttribute('data-student-id');
        if (rowStudentId === studentId) {
            // Smooth fade out animation
            row.style.transition = 'opacity 1.3s ease-out, transform 1.3s ease-out';
            row.style.opacity = '0';
            row.style.transform = 'translateX(-20px)';
            
            // Remove after animation completes
            setTimeout(() => {
                row.remove();
                
                // Update total count
                const totalBadge = document.querySelector('.bg-\\[\\#0B2C62\\]');
                if (totalBadge) {
                    const match = totalBadge.textContent.match(/\d+/);
                    if (match) {
                        const currentCount = parseInt(match[0]);
                        totalBadge.textContent = `Total: ${currentCount - 1}`;
                    }
                }
                
                // Re-render pagination
                renderPage();
            }, 1300);
        }
    });
}

// Check for pending deletion requests
async function checkPendingDeletionRequests() {
    console.log('checkPendingDeletionRequests called');
    try {
        const response = await fetch('../AdminF/check_pending_requests.php');
        const data = await response.json();
        console.log('Pending requests response:', data);
        
        if (data.success && data.pending_requests) {
            console.log('Found pending requests:', data.pending_requests.length);
            data.pending_requests.forEach(request => {
                console.log('Processing request:', request);
                // Check if it's a student deletion request (either by request_type or by checking if target_data has student fields)
                const targetData = request.target_data ? JSON.parse(request.target_data) : {};
                const isStudentDeletion = request.request_type === 'student_deletion' || 
                                         (targetData.student_id || targetData.id_number || targetData.student_name);
                
                if (isStudentDeletion) {
                    const studentId = targetData.id_number || targetData.student_id || request.target_id;
                    console.log('Student deletion request for ID:', studentId);
                    if (studentId) {
                        updateRowPendingStatus(studentId, true);
                    }
                }
            });
        } else {
            console.log('No pending requests found or request failed');
        }
    } catch (error) {
        console.error('Error checking pending requests:', error);
    }
}

// Show approval notification
function showApprovalNotification(studentName, studentId, approval) {
    const notif = document.getElementById('approvalNotification');
    const title = document.getElementById('approvalTitle');
    const message = document.getElementById('approvalMessage');
    
    if (!notif || !title || !message) return;
    
    // Update border color for approval (green)
    notif.querySelector('div').className = 'bg-white rounded-lg shadow-2xl p-6 max-w-md border-l-4 border-green-500';
    notif.querySelector('svg').className = 'w-6 h-6 text-green-500';
    notif.querySelector('svg').innerHTML = '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>';
    
    title.textContent = '✓ Request Approved!';
    message.innerHTML = `
        <div class="space-y-1">
            <p><strong>Your request has been processed</strong></p>
            <p><strong>Request:</strong> Delete Student: ${studentName}</p>
            <p><strong>Student ID:</strong> ${studentId}</p>
            <p><strong>Approved by:</strong> ${approval.reviewedBy || 'School Owner'}</p>
            <p><strong>Time:</strong> ${approval.reviewedAt || 'Just now'}</p>
            ${approval.ownerComments ? `<p><strong>Comments:</strong> ${approval.ownerComments}</p>` : ''}
        </div>
    `;
    
    notif.classList.remove('hidden');
    
    // Auto-hide after 8 seconds
    clearTimeout(window.__approvalTimer);
    window.__approvalTimer = setTimeout(() => {
        notif.classList.add('hidden');
    }, 8000);
}

// Show rejection notification
function showRejectionNotification(studentName, studentId, approval) {
    const notif = document.getElementById('approvalNotification');
    const title = document.getElementById('approvalTitle');
    const message = document.getElementById('approvalMessage');
    
    if (!notif || !title || !message) return;
    
    // Update border color for rejection (red)
    notif.querySelector('div').className = 'bg-white rounded-lg shadow-2xl p-6 max-w-md border-l-4 border-red-500';
    notif.querySelector('svg').className = 'w-6 h-6 text-red-500';
    notif.querySelector('svg').innerHTML = '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>';
    
    title.textContent = '✗ Request Rejected';
    message.innerHTML = `
        <div class="space-y-1">
            <p><strong>Your request has been rejected</strong></p>
            <p><strong>Request:</strong> Delete Student: ${studentName}</p>
            <p><strong>Student ID:</strong> ${studentId}</p>
            <p><strong>Rejected by:</strong> ${approval.reviewedBy || 'School Owner'}</p>
            <p><strong>Time:</strong> ${approval.reviewedAt || 'Just now'}</p>
            ${approval.ownerComments ? `<p><strong>Reason:</strong> ${approval.ownerComments}</p>` : ''}
        </div>
    `;
    
    notif.classList.remove('hidden');
    
    // Auto-hide after 8 seconds
    clearTimeout(window.__approvalTimer);
    window.__approvalTimer = setTimeout(() => {
        notif.classList.add('hidden');
    }, 8000);
}

// Check for student deletion approvals/rejections
// Initialize to current server time to avoid processing old approvals
let lastStudentDeletionCheck = null;
let isInitialized = false;

async function checkStudentDeletionApprovals() {
    try {
        // On first call, get current server time and don't process any approvals
        if (!isInitialized) {
            const response = await fetch(`../AdminF/check_approvals.php?last_check=${encodeURIComponent(new Date().toISOString())}`);
            const data = await response.json();
            if (data.success) {
                lastStudentDeletionCheck = data.current_time;
                isInitialized = true;
                console.log('Initialized approval checking. Will check for approvals after:', lastStudentDeletionCheck);
            }
            return;
        }
        
        const response = await fetch(`../AdminF/check_approvals.php?last_check=${encodeURIComponent(lastStudentDeletionCheck)}`);
        const data = await response.json();
        
        console.log('Checking approvals since:', lastStudentDeletionCheck);
        
        if (data.success && data.approvals && data.approvals.length > 0) {
            console.log('Found NEW approvals:', data.approvals.length);
            data.approvals.forEach(approval => {
                console.log('Processing approval:', approval);
                
                // Check if it's a student deletion by type OR by checking title/targetData
                const targetData = approval.targetData ? JSON.parse(approval.targetData) : {};
                const isStudentDeletion = approval.type === 'student_deletion' || 
                                         approval.title?.includes('Delete Student:') ||
                                         (targetData.student_id || targetData.id_number || targetData.student_name);
                
                if (isStudentDeletion) {
                    const studentId = targetData.id_number || targetData.student_id || approval.target_id;
                    const studentName = targetData.student_name || (targetData.first_name + ' ' + targetData.last_name) || 'Student';
                    
                    console.log('Student deletion approval for:', studentId, 'Status:', approval.status);
                    
                    if (approval.status === 'approved') {
                        console.log('Removing row for approved student:', studentId);
                        
                        // Show approval notification
                        showApprovalNotification(studentName, studentId, approval);
                        
                        // Start fading out the row immediately
                        setTimeout(() => {
                            removeStudentRow(studentId);
                        }, 100);
                    } else if (approval.status === 'rejected') {
                        console.log('Removing pending status for rejected student:', studentId);
                        
                        // Show rejection notification
                        showRejectionNotification(studentName, studentId, approval);
                        
                        // Remove pending status from the row
                        updateRowPendingStatus(studentId, false);
                    }
                }
            });
            
            // Update last check time
            lastStudentDeletionCheck = data.current_time;
            console.log('Updated last check time to:', lastStudentDeletionCheck);
        }
    } catch (error) {
        console.error('Error checking student deletion approvals:', error);
    }
}

// Start polling when page loads
document.addEventListener('DOMContentLoaded', function() {
    console.log('Starting student deletion approval polling...');
    
    // Wait a bit for the table to fully render, then check for pending requests
    setTimeout(() => {
        checkPendingDeletionRequests();
    }, 500);
    
    // Check immediately on load
    checkStudentDeletionApprovals();
    
    // Check for student deletion approvals every 1 second for faster response
    setInterval(checkStudentDeletionApprovals, 1000);
    
    // Also check for pending requests every 3 seconds to ensure consistency
    setInterval(checkPendingDeletionRequests, 3000);
});
</script>
</body>
</html>
