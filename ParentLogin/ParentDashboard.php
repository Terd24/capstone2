<?php
session_start();
include('../StudentLogin/db_conn.php');

if (!isset($_SESSION['parent_id'])) {
    header("Location: ParentLogin.php");
    exit();
}

// Check if parent must change password (first-time login)
$parent_id = $_SESSION['parent_id'];
$check_pwd = $conn->prepare("SELECT must_change_password FROM parent_account WHERE parent_id = ?");
$check_pwd->bind_param("s", $parent_id);
$check_pwd->execute();
$pwd_result = $check_pwd->get_result();
if ($pwd_result && $pwd_result->num_rows === 1) {
    $pwd_row = $pwd_result->fetch_assoc();
    if ($pwd_row['must_change_password'] == 1) {
        header("Location: change_password.php");
        exit();
    }
}
$check_pwd->close();

// Prevent browser from caching this page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

$child_id = $_SESSION['child_id'];

$child_query = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) as full_name, academic_track, grade_level, first_name, last_name, guardian_name FROM student_account WHERE id_number = ?");
$child_query->bind_param("s", $child_id);
$child_query->execute();
$child_result = $child_query->get_result();

if ($child_result->num_rows === 1) {
    $child = $child_result->fetch_assoc();
    $child_name = $child['full_name'];
    $child_program = $child['academic_track'];
    $child_year_section = $child['grade_level'];
    $child_first_name = $child['first_name'];
    $child_last_name = $child['last_name'];
    $guardian_name = $child['guardian_name'] ?? 'Guardian';
} else {
    $child_name = "Not Found";
    $child_program = "N/A";
    $child_year_section = "N/A";
    $child_first_name = "";
    $child_last_name = "";
    $guardian_name = "Guardian";
}

// Check if child is in Kinder (Pre-Elementary)
$is_kinder = (stripos($child_program, 'kinder') !== false || 
              stripos($child_program, 'pre-elementary') !== false ||
              stripos($child_year_section, 'kinder') !== false);

// Get unread notifications count for Kinder parents
$unread_count = 0;
$recent_notifications = null;
$all_notifications_array = [];

if ($is_kinder) {
    $notif_stmt = $conn->prepare("SELECT COUNT(*) as unread_count FROM parent_notifications WHERE parent_id = ? AND child_id = ? AND is_read = 0");
    $notif_stmt->bind_param("ss", $parent_id, $child_id);
    $notif_stmt->execute();
    $notif_result = $notif_stmt->get_result();
    $unread_count = $notif_result->fetch_assoc()['unread_count'];
    $notif_stmt->close();

    // Get recent notifications (limited for dropdown initially)
    $recent_stmt = $conn->prepare("SELECT id, message, date_sent, is_read FROM parent_notifications WHERE parent_id = ? AND child_id = ? ORDER BY date_sent DESC LIMIT 5");
    $recent_stmt->bind_param("ss", $parent_id, $child_id);
    $recent_stmt->execute();
    $recent_notifications = $recent_stmt->get_result();
    $recent_stmt->close();

    // Get all notifications for AJAX loading
    $all_stmt = $conn->prepare("SELECT id, message, date_sent, is_read FROM parent_notifications WHERE parent_id = ? AND child_id = ? ORDER BY date_sent DESC");
    $all_stmt->bind_param("ss", $parent_id, $child_id);
    $all_stmt->execute();
    $all_notifications = $all_stmt->get_result();
    while ($row = $all_notifications->fetch_assoc()) {
        $all_notifications_array[] = $row;
    }
    $all_stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Parent Portal - Cornerstone College Inc.</title>
  <link rel="icon" type="image/png" href="../images/LogoCCI.png">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            'cci-blue': '#1e3a8a',
            'cci-light-blue': '#3b82f6',
            'cci-accent': '#1e40af'
          }
        }
      }
    }
  </script>
  <style>
    .card-shadow { box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
  </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen font-sans">

  <!-- Header with School Branding -->
  <header class="bg-[#0B2C62] text-white shadow-lg">
    <div class="container mx-auto px-6 py-4">
      <div class="flex justify-between items-center">
        <div class="flex items-center space-x-4">
          <div class="text-left">
            <p class="text-sm text-blue-200">Welcome back,</p>
            <p class="font-semibold"><?= htmlspecialchars($guardian_name) ?></p>
            <p class="text-xs text-blue-300 mt-0.5">Parent of <?= htmlspecialchars($child_first_name) ?></p>
          </div>
        </div>
        
        <div class="flex items-center space-x-4">
          <img src="../images/LogoCCI.png" alt="Cornerstone College Inc." class="h-12 w-12 rounded-full bg-white p-1">
          <div class="text-right">
            <h1 class="text-xl font-bold">Cornerstone College Inc.</h1>
            <p class="text-blue-200 text-sm">Parent Portal</p>
          </div>
          
          <?php if ($is_kinder): ?>
          <!-- Notifications Bell (Only for Kinder Parents) -->
          <div class="relative">
            <button id="notificationBtn" class="bg-white bg-opacity-20 hover:bg-opacity-30 p-2 rounded-lg transition relative cursor-pointer">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
              </svg>
              <?php if ($unread_count > 0): ?>
                <span id="notifBellCount" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                  <?= $unread_count > 9 ? '9+' : $unread_count ?>
                </span>
              <?php endif; ?>
            </button>
            
            <!-- Notifications Dropdown -->
            <div id="notificationsDropdown" class="fixed bg-white rounded-xl shadow-2xl border border-gray-200 z-[9999] hidden w-80" style="max-width: 90vw;">
              <!-- Header -->
              <div class="p-4 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
                <div class="flex items-center justify-between">
                  <h3 class="font-bold text-gray-900 flex items-center">
                    <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    Notifications
                  </h3>
                  <?php if ($unread_count > 0): ?>
                    <span id="notifHeaderCount" class="bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full">
                      <?= $unread_count ?>
                    </span>
                  <?php endif; ?>
                </div>
              </div>
              
              <!-- Notifications List -->
              <div id="notificationsList" class="max-h-80 overflow-y-auto">
                <?php if ($recent_notifications && $recent_notifications->num_rows > 0): ?>
                  <?php 
                  $recent_notifications->data_seek(0);
                  $count = 0;
                  while ($notification = $recent_notifications->fetch_assoc()): 
                    $count++;
                  ?>
                    <div class="notification-item p-4 border-b border-gray-100 hover:bg-blue-50 transition-colors cursor-pointer <?= $notification['is_read'] ? 'opacity-75' : 'bg-blue-25' ?>" data-initial="<?= $count <= 5 ? 'true' : 'false' ?>" data-message="<?= htmlspecialchars($notification['message'], ENT_QUOTES) ?>" data-id="<?= (int)$notification['id'] ?>" data-read="<?= $notification['is_read'] ? '1' : '0' ?>">
                      <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                          <?php if (!$notification['is_read']): ?>
                            <div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                          <?php else: ?>
                            <div class="w-2 h-2 bg-gray-300 rounded-full mt-2"></div>
                          <?php endif; ?>
                        </div>
                        <div class="flex-1 min-w-0">
                          <p class="text-sm text-gray-800 <?= !$notification['is_read'] ? 'font-semibold' : '' ?>">
                            <?= htmlspecialchars($notification['message']) ?>
                          </p>
                          <p class="text-xs text-gray-500 mt-1">
                            <?= date('M d, Y g:i A', strtotime($notification['date_sent'])) ?>
                          </p>
                        </div>
                      </div>
                    </div>
                  <?php endwhile; ?>
                <?php else: ?>
                  <div class="p-8 text-center text-gray-500">
                    <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    <p class="text-sm font-medium">No notifications</p>
                    <p class="text-xs mt-1">You're all caught up!</p>
                  </div>
                <?php endif; ?>
              </div>
              
              <!-- Footer Actions -->
              <?php if ($recent_notifications && $recent_notifications->num_rows > 0): ?>
                <div class="p-3 border-t border-gray-200 bg-gray-50 rounded-b-xl">
                  <div class="flex justify-between items-center">
                    <button onclick="markAllAsRead()" class="text-sm text-blue-600 hover:text-blue-800 font-medium transition-colors">
                      Mark all as read
                    </button>
                    <button id="viewMoreBtn" onclick="loadMoreNotifications()" class="text-sm bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-lg font-medium transition-colors">
                      View More
                    </button>
                  </div>
                </div>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>
          
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

  <!-- Main Content -->
  <div class="container mx-auto px-6 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
      
      <!-- Child Profile -->
      <div class="lg:col-span-1">
        <div class="bg-white rounded-2xl card-shadow p-6">
          <div class="mb-4 pb-4 border-b">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Your Child</h3>
          </div>
          <div class="text-center">
            <div class="w-20 h-20 mx-auto bg-gradient-to-br from-blue-400 to-indigo-500 rounded-full flex items-center justify-center mb-4 shadow-lg">
              <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
              </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-800"><?= htmlspecialchars($child_name) ?></h3>
            <p class="text-gray-500 text-xs mt-1">Student ID: <?= htmlspecialchars($child_id) ?></p>
          </div>
          
          <div class="mt-6 pt-6 border-t">
            <div class="space-y-4 text-sm">
              <div class="bg-blue-50 rounded-lg p-3">
                <span class="text-gray-600 font-medium block mb-1">Program</span>
                <p class="text-gray-800 font-semibold"><?= htmlspecialchars($child_program) ?></p>
              </div>
              <div class="bg-indigo-50 rounded-lg p-3">
                <span class="text-gray-600 font-medium block mb-1">Year & Section</span>
                <p class="text-gray-800 font-semibold"><?= htmlspecialchars($child_year_section) ?></p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Access -->
      <div class="lg:col-span-3">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Parent Dashboard</h2>
        <p class="text-gray-600 mb-6">Monitor your child's academic progress and school information</p>

        <!-- Academic Information Section -->
        <div class="mb-6">
          <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider mb-3">Academic Information</h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Grades Card -->
            <div onclick="location.href='ParentGrades.php'" class="bg-white rounded-2xl card-shadow p-6 cursor-pointer hover:shadow-xl transition-all duration-200">
              <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                  <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                </div>
                <div>
                  <h4 class="text-lg font-semibold text-gray-800">Grades</h4>
                  <p class="text-sm text-gray-600">View your child's grades</p>
                </div>
              </div>
            </div>

            <?php if ($is_kinder): ?>
            <!-- Documents Card (Only for Kinder Students) -->
            <div onclick="location.href='ParentDocuments.php'" class="bg-white rounded-2xl card-shadow p-6 cursor-pointer hover:shadow-xl transition-all duration-200">
              <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                  <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
                <div>
                  <h4 class="text-lg font-semibold text-gray-800">Documents</h4>
                  <p class="text-sm text-gray-600">View child's documents</p>
                </div>
              </div>
            </div>

            <!-- Request Document Card (Only for Kinder Students) -->
            <div onclick="location.href='ParentDocumentRequest.php'" class="bg-white rounded-2xl card-shadow p-6 cursor-pointer hover:shadow-xl transition-all duration-200">
              <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                  <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                </div>
                <div>
                  <h4 class="text-lg font-semibold text-gray-800">Request Document</h4>
                  <p class="text-sm text-gray-600">Request child's documents</p>
                </div>
              </div>
            </div>

            <!-- Attendance Card -->
            <div onclick="location.href='ParentAttendance.php'" class="bg-white rounded-2xl card-shadow p-6 cursor-pointer hover:shadow-xl transition-all duration-200">
              <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                  <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                </div>
                <div>
                  <h4 class="text-lg font-semibold text-gray-800">Attendance</h4>
                  <p class="text-sm text-gray-600">Monitor attendance record</p>
                </div>
              </div>
            </div>

            <!-- Requested Documents Card (Only for Kinder Students) -->
            <div onclick="location.href='ParentRequestedDocuments.php'" class="bg-white rounded-2xl card-shadow p-6 cursor-pointer hover:shadow-xl transition-all duration-200">
              <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                  <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                </div>
                <div>
                  <h4 class="text-lg font-semibold text-gray-800">Requested Documents</h4>
                  <p class="text-sm text-gray-600">Track your requests</p>
                </div>
              </div>
            </div>
            <?php else: ?>
            <!-- Attendance Card (Non-Kinder) -->
            <div onclick="location.href='ParentAttendance.php'" class="bg-white rounded-2xl card-shadow p-6 cursor-pointer hover:shadow-xl transition-all duration-200">
              <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                  <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                </div>
                <div>
                  <h4 class="text-lg font-semibold text-gray-800">Attendance</h4>
                  <p class="text-sm text-gray-600">Monitor attendance record</p>
                </div>
              </div>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Financial Information Section -->
        <div class="mb-6">
          <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider mb-3">Financial Information</h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Balance Card -->
            <div onclick="location.href='ParentBalances.php'" class="bg-white rounded-2xl card-shadow p-6 cursor-pointer hover:shadow-xl transition-all duration-200">
              <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                  <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path></svg>
                </div>
                <div>
                  <h4 class="text-lg font-semibold text-gray-800">Balance</h4>
                  <p class="text-sm text-gray-600">Check tuition & fees</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Student Welfare Section -->
        <div class="mb-6">
          <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wider mb-3">Student Welfare</h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Guidance Card -->
            <div onclick="location.href='ParentGuidanceRecord.php'" class="bg-white rounded-2xl card-shadow p-6 cursor-pointer hover:shadow-xl transition-all duration-200">
              <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                  <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                </div>
                <div>
                  <h4 class="text-lg font-semibold text-gray-800">Guidance</h4>
                  <p class="text-sm text-gray-600">View counseling records</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    const menuBtn = document.getElementById('menuBtn');
    const menuDropdown = document.getElementById('menuDropdown');

    menuBtn.addEventListener('click', () => {
      menuDropdown.classList.toggle('hidden');
    });

    window.addEventListener('click', (e) => {
      if (!menuBtn.contains(e.target) && !menuDropdown.contains(e.target)) {
        menuDropdown.classList.add('hidden');
      }
    });

    // Prevent Back button from showing cached dashboard
    window.addEventListener("pageshow", function (event) {
      if (event.persisted) {
        window.location.reload();
      }
    });

    <?php if ($is_kinder): ?>
    // Notification functions for Kinder parents
    const allNotifications = <?= json_encode($all_notifications_array) ?>;
    let currentlyShowing = 5;

    function toggleNotifications() {
      const dropdown = document.getElementById('notificationsDropdown');
      const button = document.getElementById('notificationBtn');
      
      if (dropdown.classList.contains('hidden')) {
        const rect = button.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        const dropdownWidth = 320;
        
        dropdown.style.top = (rect.bottom + 8) + 'px';
        let rightPos = viewportWidth - rect.right;
        
        if (rect.right - dropdownWidth < 10) {
          rightPos = 10;
        }
        
        dropdown.style.right = rightPos + 'px';
        dropdown.style.left = 'auto';
        
        if (viewportWidth < 350) {
          dropdown.style.width = (viewportWidth - 20) + 'px';
          dropdown.style.right = '10px';
        } else {
          dropdown.style.width = dropdownWidth + 'px';
        }
      }
      
      dropdown.classList.toggle('hidden');
    }

    function markAllAsRead() {
      fetch('parent_mark_notifications_read.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        }
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          location.reload();
        }
      })
      .catch(error => console.error('Error:', error));
    }

    function loadMoreNotifications() {
      const notificationsList = document.getElementById('notificationsList');
      const viewMoreBtn = document.getElementById('viewMoreBtn');
      
      const nextBatch = allNotifications.slice(currentlyShowing, currentlyShowing + 5);
      
      nextBatch.forEach(notification => {
        const notificationHTML = `
          <div class="notification-item p-4 border-b border-gray-100 hover:bg-blue-50 transition-colors cursor-pointer ${notification.is_read == '0' ? 'bg-blue-25' : 'opacity-75'}" data-message="${notification.message.replace(/\"/g, '&quot;')}" data-id="${Number(notification.id)}" data-read="${notification.is_read}">
            <div class="flex items-start space-x-3">
              <div class="flex-shrink-0">
                ${notification.is_read == '0' ? 
                  '<div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>' : 
                  '<div class="w-2 h-2 bg-gray-300 rounded-full mt-2"></div>'
                }
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm text-gray-800 ${notification.is_read == '0' ? 'font-semibold' : ''}">
                  ${notification.message}
                </p>
                <p class="text-xs text-gray-500 mt-1">
                  ${new Date(notification.date_sent).toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric',
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                  })}
                </p>
              </div>
            </div>
          </div>
        `;
        notificationsList.insertAdjacentHTML('beforeend', notificationHTML);
      });
      
      currentlyShowing += 5;
      
      if (currentlyShowing >= allNotifications.length) {
        viewMoreBtn.style.display = 'none';
      }
    }

    document.addEventListener('DOMContentLoaded', function() {
      const notificationBtn = document.getElementById('notificationBtn');
      const dropdown = document.getElementById('notificationsDropdown');
      const notificationsList = document.getElementById('notificationsList');
      
      if (notificationBtn && dropdown) {
        notificationBtn.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          toggleNotifications();
        });
        
        document.addEventListener('click', function(event) {
          if (!notificationBtn.contains(event.target) && !dropdown.contains(event.target)) {
            dropdown.classList.add('hidden');
          }
        });

        function decrementBadge(el) {
          if (!el) return;
          const txt = (el.textContent || '').trim();
          let num = 0;
          if (txt === '') return;
          if (txt.includes('+')) {
            num = parseInt(txt) || 9;
          } else {
            num = parseInt(txt) || 0;
          }
          if (num <= 1) {
            el.remove();
          } else {
            el.textContent = String(num - 1);
          }
        }

        function routeFromMessage(message) {
          if (!message) { window.location.href = 'ParentRequestedDocuments.php'; return; }
          const msg = message.toLowerCase();
          if (msg.includes('successfully submitted')) return window.location.href = 'ParentDocuments.php';
          if (msg.includes('document request') || msg.includes("status has been updated") || msg.includes('ready to claim') || msg.includes('pending')) return window.location.href = 'ParentRequestedDocuments.php';
          if (msg.includes('grade')) return window.location.href = 'ParentGrades.php';
          if (msg.includes('balance') || msg.includes('payment')) return window.location.href = 'ParentBalances.php';
          if (msg.includes('attendance')) return window.location.href = 'ParentAttendance.php';
          if (msg.includes('guidance')) return window.location.href = 'ParentGuidanceRecord.php';
          window.location.href = 'ParentRequestedDocuments.php';
        }

        if (notificationsList) {
          notificationsList.addEventListener('click', function(e) {
            const item = e.target.closest('.notification-item');
            if (!item) return;
            
            const notifId = parseInt(item.dataset.id);
            const isRead = item.dataset.read === '1';
            const message = item.dataset.message;
            
            if (!isRead) {
              fetch('parent_mark_notification_read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ notification_id: notifId })
              })
              .then(response => response.json())
              .then(data => {
                if (data.success) {
                  decrementBadge(document.getElementById('notifBellCount'));
                  decrementBadge(document.getElementById('notifHeaderCount'));
                  item.dataset.read = '1';
                  item.classList.remove('bg-blue-25');
                  item.classList.add('opacity-75');
                  const dot = item.querySelector('.bg-blue-500');
                  if (dot) {
                    dot.classList.remove('bg-blue-500');
                    dot.classList.add('bg-gray-300');
                  }
                  const text = item.querySelector('.font-semibold');
                  if (text) text.classList.remove('font-semibold');
                }
                routeFromMessage(message);
              })
              .catch(error => {
                console.error('Error:', error);
                routeFromMessage(message);
              });
            } else {
              routeFromMessage(message);
            }
          });
        }
      }
    });
    <?php endif; ?>
  </script>

</body>
</html>
