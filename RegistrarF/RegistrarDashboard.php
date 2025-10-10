<?php
session_start();
include("../StudentLogin/db_conn.php");

// Set correct timezone for Philippines
date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['registrar_id'])) {
    header("Location: ../StudentLogin/login.html");
    exit;
}

// Prevent browser caching (so back button after logout doesn't show dashboard)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Ensure column exists for read/unread
$conn->query("ALTER TABLE document_requests ADD COLUMN IF NOT EXISTS is_read TINYINT(1) DEFAULT 0");
// Fetch all requests - prioritize oldest pending first, then other statuses, claimed last
$result = $conn->query("SELECT * FROM document_requests ORDER BY 
    CASE 
        WHEN status = 'Pending' THEN 1
        WHEN status = 'Ready to Claim' OR status = 'Ready for Claiming' THEN 2
        WHEN status = 'Claimed' THEN 3
        ELSE 4
    END ASC,
    date_requested ASC");

// Fetch recent requests - prioritize unread first, then oldest, exclude claimed
$recent = $conn->query("SELECT * FROM document_requests WHERE status != 'Claimed' ORDER BY is_read ASC, date_requested ASC LIMIT 10");
// Helper function for PHP fallback time ago (optional)
function timeAgo($time) {
    // Use MySQL timestamp directly to avoid timezone conversion issues
    $diff = time() - strtotime($time);
    
    // Handle both positive and negative differences
    $absDiff = abs($diff);
    
    if ($absDiff < 60) return "Just now";
    elseif ($absDiff < 3600) {
        $minutes = floor($absDiff/60);
        return $minutes . ($minutes === 1 ? " minute ago" : " minutes ago");
    }
    elseif ($absDiff < 86400) {
        $hours = floor($absDiff/3600);
        return $hours . ($hours === 1 ? " hour ago" : " hours ago");
    }
    else {
        $days = floor($absDiff/86400);
        return $days . ($days === 1 ? " day ago" : " days ago");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Registrar Dashboard - Cornerstone College Inc.</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/html5-qrcode" defer></script>
<link rel="icon" type="image/png" href="../images/Logo.png">
<style>
  .school-gradient { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #1e40af 100%); }
  .card-shadow { box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
  .mirror-video video { transform: scaleX(-1); -webkit-transform: scaleX(-1); }
</style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen font-sans">
<!-- RFID Form -->
<form id="rfidForm" method="get" action="ViewStudentInfo.php">
    <input type="hidden" id="rfid_input" name="student_id" autocomplete="off">
</form>

<!-- Header with School Branding -->
<header class="bg-[#0B2C62] text-white shadow-lg">
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
      <button onclick="fetchStudents(document.getElementById('searchInput').value.trim())" class="bg-[#0B2C62] hover:bg-blue-900 text-white px-6 py-3 rounded-xl font-medium transition-colors">Search</button>
      <!-- Scan button beside Search (same as Cashier style) -->
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
      <button type="button" onclick="openManageDocTypes()" class="inline-flex items-center bg-[#0B2C62] hover:bg-blue-900 text-white px-6 py-3 rounded-xl font-medium transition-colors">Document Types</button>
      <p id="searchError" class="text-red-600 text-sm"></p>
    </div>
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
    <!-- Inline error under the camera -->
    <p id="qrInlineError" class="mt-2 text-red-600 text-sm hidden"></p>
    <p id="qrStatus" class="mt-3 text-sm text-gray-600">Point your camera at the QR code. The code should contain the student's RFID only.</p>
    <div class="flex gap-3 pt-4">
      <button onclick="switchQRScannerCamera()" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 py-2 rounded-lg">Switch Camera</button>
      <button onclick="closeQRScanner()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2 rounded-lg">Close</button>
    </div>
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
                      class="w-full bg-[#0B2C62] hover:bg-blue-900 text-white py-2 rounded-lg font-medium transition-colors text-sm mb-2">
                Manage Accounts
              </button>
              <button onclick="window.location.href='ManageGrades.php'" 
                      class="w-full bg-[#0B2C62] hover:bg-blue-900 text-white py-2 rounded-lg font-medium transition-colors text-sm mb-2">
                Manage Grades
              </button>
              <button onclick="window.location.href='ManageSubjects.php'" 
                      class="w-full bg-[#0B2C62] hover:bg-blue-900 text-white py-2 rounded-lg font-medium transition-colors text-sm mb-2">
                Manage Subjects
              </button>
              <button onclick="window.location.href='ManageSchedule.php'" 
                      class="w-full bg-[#0B2C62] hover:bg-blue-900 text-white py-2 rounded-lg font-medium transition-colors text-sm">
                Manage Student Schedule
              </button>
              <button onclick="window.location.href='AttendanceRecords.php'" 
                      class="w-full bg-[#0B2C62] hover:bg-blue-900 text-white py-2 rounded-lg font-medium transition-colors text-sm mt-2">
                Attendance Record
              </button>
              <!-- Manage Employee Schedule moved to HR portal -->

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
                    <div class="group border border-gray-200 rounded-xl p-4 bg-white hover:shadow-md transition flex items-start justify-between" 
                         id="req-<?= $row['id'] ?>"
                         data-timestamp="<?= strtotime($row['date_requested']) ?>"
                         data-date="<?= $row['date_requested'] ?>">
                        <div class="flex flex-col gap-1">
                            <p class="text-[11px] text-gray-400 uppercase tracking-wide time-ago" data-time="<?= strtotime($row['date_requested']) ?>" data-debug="<?= $row['date_requested'] ?>"><?= timeAgo($row['date_requested']) ?></p>
                            <p class="text-base font-semibold text-[#0B2C62] leading-5"><?= htmlspecialchars($row['document_type']) ?></p>
                            <p class="text-xs text-gray-500">By: <?= htmlspecialchars($row['student_name']) ?> (<?= htmlspecialchars($row['student_id']) ?>)</p>
                            <p class="text-xs text-gray-400 line-clamp-1">
                                <?= htmlspecialchars($row['purpose'] ?: 'Document request.') ?>
                            </p>
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            <?php if (!$row['is_read']): ?>
                                <span id="badge-<?= $row['id'] ?>" class="bg-red-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">NEW</span>
                            <?php endif; ?>
                            <a href="ViewStudentInfo.php?student_id=<?= urlencode($row['student_id']) ?>&type=requested"
                               data-id="<?= $row['id'] ?>"
                               class="view-link inline-flex items-center gap-1 text-sm text-blue-600 hover:text-blue-700">
                                View
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="rounded-xl border border-dashed border-gray-300 p-6 text-center text-gray-500 bg-white">
                  <svg class="w-8 h-8 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                  No recent requests.
                </div>
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
                    <thead class="bg-[#0B2C62] text-white">
                        <tr>
                            <th class="px-6 py-4 text-left font-semibold">Student No</th>
                            <th class="px-6 py-4 text-left font-semibold">Document Name</th>
                            <th class="px-6 py-4 text-left font-semibold">Date Requested</th>
                            <th class="px-6 py-4 text-left font-semibold">Date Claimed</th>
                            <th class="px-6 py-4 text-left font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-800 divide-y divide-gray-200">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($r = $result->fetch_assoc()): ?>
                                <tr class="bg-white hover:bg-[#FBB917]/20 transition cursor-pointer" onclick="viewRequest('<?= htmlspecialchars($r['student_id']) ?>', '<?= htmlspecialchars($r['document_type']) ?>', '<?= $r['status'] ?>')">
                                    <td class="px-6 py-4 font-medium"><?= htmlspecialchars($r['student_id']) ?></td>
                                    <td class="px-6 py-4"><?= htmlspecialchars($r['document_type']) ?></td>
                                    <td class="px-6 py-4 text-gray-600"><?= date('M j, Y g:i:s A', strtotime($r['date_requested'])) ?></td>
                                    <td class="px-6 py-4 text-gray-600">
                                        <?= ($r['status'] === 'Claimed' && $r['date_claimed']) ? date('M j, Y g:i:s A', strtotime($r['date_claimed'])) : '---' ?>
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

<!-- Manage Document Types Modal -->
<div id="manageDocTypesModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[80]">
  <div class="bg-white rounded-2xl p-6 w-full max-w-xl mx-4">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-bold text-gray-800">Manage Document Types</h3>
      <button onclick="closeManageDocTypes()" class="text-gray-500 hover:text-gray-700" aria-label="Close">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
      </button>
    </div>
    <!-- Segmented switch: Submit Types | Request Types -->
    <div class="inline-flex mb-3 bg-gray-100 rounded-lg p-1 text-sm" role="tablist">
      <button id="modeSubmitBtn" type="button" class="px-3 py-1 rounded-md transition-colors" onclick="switchDocMode('submit')">Submit Types</button>
      <button id="modeRequestBtn" type="button" class="px-3 py-1 rounded-md transition-colors" onclick="switchDocMode('request')">Request Types</button>
    </div>
    

    <div class="bg-gray-50 rounded-xl p-4 mb-4">
      <div class="grid grid-cols-1 gap-3 items-end">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Document Name</label>
          <input type="text" id="docTypeName" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="e.g., Transcript of Records" />
        </div>
      </div>
      <!-- Inline error box -->
      <div id="docTypeError" class="hidden mt-2 bg-red-50 text-red-700 border border-red-200 rounded-lg px-3 py-2 text-sm"></div>
      <div class="flex gap-3 mt-3">
        <button id="addDocTypeBtn" onclick="createDocType()" class="bg-[#0B2C62] hover:bg-blue-900 text-white px-4 py-2 rounded-lg">Add Document Type</button>
        <!-- Cancel button appears only in edit mode -->
      </div>
    </div>

    <div>
      <h4 class="font-semibold text-gray-800 mb-2">Existing Document Types</h4>
      <div id="docTypesList" class="space-y-2 max-h-80 overflow-y-auto">
        <!-- items -->
      </div>
    </div>
  </div>
  
</div>

<!-- Confirm Delete Modal -->
<div id="confirmDeleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[90]">
  <div class="bg-white rounded-2xl p-6 w-full max-w-md mx-4">
    <div class="flex flex-col items-center text-center">
      <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center mb-3">
        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.29 3.86l-8.02 13.86A2 2 0 003.99 20h16.02a2 2 0 001.72-3.28L13.71 3.86a2 2 0 00-3.42 0z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01" />
        </svg>
      </div>
      <h3 class="text-lg font-bold text-gray-800 mb-1">Delete Document Type</h3>
      <p id="confirmDeleteText" class="text-sm text-gray-600 mb-4">Are you sure you want to delete this type?</p>
      <div class="flex gap-3 w-full mt-2">
        <button onclick="closeConfirmDelete()" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg">Cancel</button>
        <button id="confirmDeleteBtn" onclick="confirmDelete()" class="flex-1 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg">Delete</button>
      </div>
    </div>
  </div>
  </div>

<!-- Toast Notification -->
<div id="toastContainer" class="fixed top-4 right-4 z-[100]"></div>

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
    
    // Reset the view more functionality when reopening
    if (!recentContent.classList.contains("hidden")) {
        shownRecent = 3;
        renderRecent();
    }
});

// ===== Live Time Ago Function =====
function updateTimeAgo() {
    // Find all time elements in recent content
    const timeElements = document.querySelectorAll('#recentContent .time-ago[data-time]');
    
    timeElements.forEach(element => {
        const timestamp = parseInt(element.getAttribute('data-time'));
        
        // Create a date object from the timestamp and get current time
        const requestDate = new Date(timestamp * 1000);
        const now = new Date();
        
        // Calculate difference in milliseconds, then convert to seconds
        const diffMs = now.getTime() - requestDate.getTime();
        const diffSeconds = Math.floor(diffMs / 1000);
        
        let timeText = '';
        
        // Handle both positive and negative differences (past and future dates)
        const absDiff = Math.abs(diffSeconds);
        
        if (absDiff < 60) {
            timeText = 'Just now';
        } else if (absDiff < 3600) {
            const minutes = Math.floor(absDiff / 60);
            timeText = minutes === 1 ? '1 minute ago' : `${minutes} minutes ago`;
        } else if (absDiff < 86400) {
            const hours = Math.floor(absDiff / 3600);
            timeText = hours === 1 ? '1 hour ago' : `${hours} hours ago`;
        } else {
            const days = Math.floor(absDiff / 86400);
            timeText = days === 1 ? '1 day ago' : `${days} days ago`;
        }
        
        element.textContent = timeText;
    });
}

// Wait for DOM to be ready, then start updating
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM ready, starting time updates');
    updateTimeAgo();
    setInterval(updateTimeAgo, 5000);
});

// Also run immediately in case DOM is already loaded
if (document.readyState === 'loading') {
    // DOM is still loading
} else {
    // DOM is already loaded
    console.log('DOM already loaded, starting time updates immediately');
    updateTimeAgo();
    setInterval(updateTimeAgo, 5000);
}

// ===== Render Recent Requests with View More =====
let recentRequests = Array.from(document.querySelectorAll('#recentContent > div'));
let shownRecent = 3;

function renderRecent() {
    recentRequests.forEach((el, idx) => {
        el.style.display = idx < shownRecent ? '' : 'none';
    });

    if (shownRecent < recentRequests.length) {
        if (!document.getElementById('viewMoreRecent')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'mt-2 flex justify-center';
            const btn = document.createElement('button');
            btn.id = 'viewMoreRecent';
            btn.className = 'inline-flex items-center gap-2 text-sm px-4 py-2 rounded-full border border-[#0B2C62] text-[#0B2C62] hover:bg-[#0B2C62] hover:text-white transition';
            btn.innerHTML = 'View More <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>';
            btn.addEventListener('click', () => {
                shownRecent += 3;
                renderRecent();
            });
            wrapper.appendChild(btn);
            document.getElementById('recentContent').appendChild(wrapper);
        }
    } else {
        const btn = document.getElementById('viewMoreRecent');
        if (btn) btn.parentElement.remove();
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
});

// Add viewRequest function for clickable table rows
function viewRequest(studentId, documentType, status) {
    window.location.href = `ViewStudentInfo.php?student_id=${encodeURIComponent(studentId)}&type=requested`;
}

// ===== QR SCANNER (html5-qrcode) =====
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

// Inline error helper for the scanner modal
function setQRInlineError(message) {
  const el = document.getElementById('qrInlineError');
  if (!el) return;
  if (message) { el.textContent = message; el.classList.remove('hidden'); }
  else { el.textContent = ''; el.classList.add('hidden'); }
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
    if (fm.includes('user') || fm.includes('front') || fm.includes('selfie')) { readerEl && readerEl.classList.add('mirror-video'); }
    else { readerEl && readerEl.classList.remove('mirror-video'); }
  } catch (_) {}
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
      try { await qrScanner.stop(); } catch(_){ }
      try { await qrScanner.clear(); } catch(_){ }
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
        // Validate first so we can show error INSIDE the modal instead of closing it
        try {
          const res = await fetch(`SearchStudent.php?query=${encodeURIComponent(text)}`);
          const data = await res.json();
          const students = (data && data.students) ? data.students : [];

          // RFID-only: require exact rfid_uid match to scanned text
          const match = students.find(s => (s.rfid_uid || '').toString() === text);

          if (match && match.id_number) {
            setQRInlineError('');
            // Submit with id_number for Registrar document view
            document.getElementById('rfid_input').value = match.id_number;
            document.getElementById('rfidForm').submit();
            closeQRScanner();
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
    try { await qrScanner.stop(); } catch(_){ }
    try { await qrScanner.clear(); } catch(_){ }
  }
}

// ===== Manage Document Types Logic =====
function openManageDocTypes(){
  const m = document.getElementById('manageDocTypesModal');
  if(m){ 
    m.classList.remove('hidden'); 
    // default to submit tab when opening
    currentDocMode = currentDocMode || 'submit';
    updateModeButtons();
    loadDocTypes(); 
  }
}
function closeManageDocTypes(){
  const m = document.getElementById('manageDocTypesModal');
  if(m){ m.classList.add('hidden'); }
}
// Filtering tabs state
let currentDocMode = 'submit';
function switchDocMode(mode){ currentDocMode = mode; updateModeButtons(); loadDocTypes(); }
function updateModeButtons(){
  const a = document.getElementById('modeSubmitBtn');
  const b = document.getElementById('modeRequestBtn');
  if(!a||!b) return;
  if(currentDocMode==='submit'){
    a.className = 'px-3 py-1 rounded-md bg-[#0B2C62] text-white shadow transition-colors';
    b.className = 'px-3 py-1 rounded-md text-[#0B2C62] bg-white border border-[#0B2C62]/30 hover:bg-gray-100 transition-colors';
  } else {
    b.className = 'px-3 py-1 rounded-md bg-[#0B2C62] text-white shadow transition-colors';
    a.className = 'px-3 py-1 rounded-md text-[#0B2C62] bg-white border border-[#0B2C62]/30 hover:bg-gray-100 transition-colors';
  }
}
function resetDocTypeForm(){
  document.getElementById('docTypeName').value='';
  const saveBtn = document.getElementById('saveDocTypeBtn');
  if (saveBtn) saveBtn.remove();
  const cancelBtn = document.getElementById('cancelDocTypeBtn');
  if (cancelBtn) cancelBtn.remove();
  const addBtn = document.getElementById('addDocTypeBtn');
  if (addBtn){ addBtn.disabled = false; addBtn.classList.remove('opacity-50','pointer-events-none'); }
  hideDocTypeError();
}
async function loadDocTypes(){
  try{
    const res = await fetch('manage_document_types.php?action=list');
    const data = await res.json();
    const list = document.getElementById('docTypesList');
    list.innerHTML = '';
    if(data.success && Array.isArray(data.items)){
      const filtered = data.items.filter(it => currentDocMode==='submit' ? (it.is_submittable==1) : (it.is_requestable==1));
      if(filtered.length===0){ list.innerHTML = '<div class="text-gray-500 text-sm">No document types in this tab yet.</div>'; return; }
      filtered.forEach(item=>{
        const row = document.createElement('div');
        row.className = 'flex items-center justify-between bg-white border rounded-lg p-3';
        row.innerHTML = `
          <div>
            <div class=\"font-medium text-gray-800\">${escapeHtml(item.name)}</div>
          </div>
          <div class=\"flex gap-2\">
            <button class=\"text-blue-600\" onclick=\"editDocType(${item.id}, '${escapeAttr(item.name)}')\">Edit</button>
            <button class=\"text-red-600\" onclick=\"openConfirmDelete(${item.id}, '${escapeAttr(item.name)}')\">Delete</button>
          </div>`;
        list.appendChild(row);
      });
    } else {
      list.innerHTML = '<div class="text-gray-500 text-sm">No document types yet.</div>';
    }
  }catch(e){ console.error(e); }
}
function showDocTypeError(msg){
  const el = document.getElementById('docTypeError');
  if(!el) return; el.textContent = msg||'Something went wrong.'; el.classList.remove('hidden');
}
function hideDocTypeError(){
  const el = document.getElementById('docTypeError');
  if(!el) return; el.textContent=''; el.classList.add('hidden');
}
function escapeHtml(s){ return (s||'').replace(/[&<>"]/g, c=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;"}[c])); }
function escapeAttr(s){ return (s||'').replace(/['"\\]/g, m=>({"'":"&#39;","\"":"&quot;","\\":"\\\\"}[m])); }
async function createDocType(){
  const name = document.getElementById('docTypeName').value.trim();
  if(!name){ showDocTypeError('Please enter a document name.'); return; }
  const fd = new FormData(); fd.append('action','create'); fd.append('name', name); fd.append('mode', currentDocMode);
  const res = await fetch('manage_document_types.php', { method:'POST', body: fd});
  const data = await res.json();
  if(data.success){ resetDocTypeForm(); loadDocTypes(); showToast('Document type added successfully'); }
  else showDocTypeError(data.message||'Failed to add document type.');
}
function editDocType(id, name){
  document.getElementById('docTypeName').value = name;
  let saveBtn = document.getElementById('saveDocTypeBtn');
  const addBtn = document.querySelector('#manageDocTypesModal button[onclick="createDocType()"]');
  if(!saveBtn){
    saveBtn = document.createElement('button');
    saveBtn.id = 'saveDocTypeBtn';
    saveBtn.className = 'bg-[#0B2C62] hover:bg-blue-900 text-white px-4 py-2 rounded-lg ml-3';
    saveBtn.textContent = 'Save Changes';
    addBtn.after(saveBtn);
    // Create cancel button only in edit mode
    let cancelBtn = document.createElement('button');
    cancelBtn.id = 'cancelDocTypeBtn';
    cancelBtn.className = 'bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded-lg ml-3';
    cancelBtn.textContent = 'Cancel';
    saveBtn.after(cancelBtn);
    cancelBtn.onclick = ()=>{ resetDocTypeForm(); };
  }
  // Disable Add while editing
  const addMain = document.getElementById('addDocTypeBtn');
  if(addMain){ addMain.disabled = true; addMain.classList.add('opacity-50','pointer-events-none'); }
  saveBtn.onclick = async ()=>{
    const newName = document.getElementById('docTypeName').value.trim();
    if(!newName){ showDocTypeError('Please enter a document name.'); return; }
    const fd = new FormData(); fd.append('action','update'); fd.append('id', id); fd.append('name', newName);
    const res = await fetch('manage_document_types.php', { method:'POST', body: fd});
    const data = await res.json();
    if(data.success){ resetDocTypeForm(); loadDocTypes(); showToast('Document type updated successfully'); }
    else showDocTypeError(data.message||'Failed to update document type.');
  };
}
let pendingDeleteId = null;
function openConfirmDelete(id, name){
  pendingDeleteId = id;
  const txt = document.getElementById('confirmDeleteText');
  if (txt) txt.textContent = `Are you sure you want to delete "${name}"? This action cannot be undone.`;
  const m = document.getElementById('confirmDeleteModal'); if(m) m.classList.remove('hidden');
}
function closeConfirmDelete(){ const m = document.getElementById('confirmDeleteModal'); if(m) m.classList.add('hidden'); pendingDeleteId = null; }
async function confirmDelete(){
  if(!pendingDeleteId) { closeConfirmDelete(); return; }
  const fd = new FormData(); fd.append('action','delete'); fd.append('id', pendingDeleteId);
  const res = await fetch('manage_document_types.php', { method:'POST', body: fd});
  const data = await res.json();
  if(data.success){ closeConfirmDelete(); loadDocTypes(); showToast('Document type deleted successfully'); }
  else { closeConfirmDelete(); showDocTypeError(data.message||'Failed to delete document type.'); }
}
function showToast(message){
  const cont = document.getElementById('toastContainer');
  if(!cont) return;
  const toast = document.createElement('div');
  toast.className = 'mb-2 bg-green-600 text-white px-4 py-2 rounded-lg shadow transition-opacity';
  toast.textContent = message || 'Action completed successfully';
  cont.appendChild(toast);
  setTimeout(()=>{ toast.style.opacity='0'; }, 2000);
  setTimeout(()=>{ toast.remove(); }, 2600);
}

// ===== PREVENT BACK BUTTON AFTER LOGOUT =====
window.addEventListener("pageshow", function(event) {
  if (event.persisted || (performance.navigation.type === 2)) window.location.reload();
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