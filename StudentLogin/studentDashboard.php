<?php
session_start();
include("db_conn.php");

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit;
}

// Prevent browser from caching this page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['id_number'])) {
    header("Location: login.php");
    exit();
}

// Get unread notifications count
$student_id = $_SESSION['id_number'];
$notif_stmt = $conn->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE student_id = ? AND is_read = 0");
$notif_stmt->bind_param("s", $student_id);
$notif_stmt->execute();
$notif_result = $notif_stmt->get_result();
$unread_count = $notif_result->fetch_assoc()['unread_count'];
$notif_stmt->close();

// Get recent notifications
$recent_stmt = $conn->prepare("SELECT message, date_sent FROM notifications WHERE student_id = ? ORDER BY date_sent DESC LIMIT 3");
$recent_stmt->bind_param("s", $student_id);
$recent_stmt->execute();
$recent_notifications = $recent_stmt->get_result();
$recent_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Student Portal - Cornerstone College Inc.</title>
  <link rel="icon" type="image/png" href="../images/LogoCCI.png">
  <script src="https://cdn.tailwindcss.com"></script>
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
            <p class="text-sm text-blue-200">Welcome,</p>
            <p class="font-semibold"><?= htmlspecialchars($_SESSION['student_name'] ?? 'Student') ?></p>
          </div>
          
          <!-- Notifications Bell -->
          <div class="relative">
            <button onclick="toggleNotifications()" class="bg-white bg-opacity-20 hover:bg-opacity-30 p-2 rounded-lg transition relative">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
              </svg>
              <?php if ($unread_count > 0): ?>
                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                  <?= $unread_count > 9 ? '9+' : $unread_count ?>
                </span>
              <?php endif; ?>
            </button>
            
            <!-- Notifications Dropdown -->
            <div id="notificationsDropdown" class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50 hidden">
              <div class="p-4 border-b border-gray-200">
                <h3 class="font-semibold text-gray-900">Notifications</h3>
                <p class="text-sm text-gray-500"><?= $unread_count ?> unread</p>
              </div>
              <div class="max-h-64 overflow-y-auto">
                <?php if ($recent_notifications->num_rows > 0): ?>
                  <?php while ($notification = $recent_notifications->fetch_assoc()): ?>
                    <div class="p-4 border-b border-gray-100 hover:bg-gray-50">
                      <p class="text-sm text-gray-800"><?= htmlspecialchars($notification['message']) ?></p>
                      <p class="text-xs text-gray-500 mt-1"><?= date('M d, Y g:i A', strtotime($notification['date_sent'])) ?></p>
                    </div>
                  <?php endwhile; ?>
                  <div class="p-4 text-center">
                    <button onclick="markAllAsRead()" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                      Mark all as read
                    </button>
                  </div>
                <?php else: ?>
                  <div class="p-4 text-center text-gray-500">
                    <p class="text-sm">No notifications</p>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
        
        <div class="flex items-center space-x-4">
          <img src="../images/LogoCCI.png" alt="Cornerstone College Inc." class="h-12 w-12 rounded-full bg-white p-1">
          <div class="text-right">
            <h1 class="text-xl font-bold">Cornerstone College Inc.</h1>
            <p class="text-blue-200 text-sm">Student Portal</p>
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

  <!-- Main Content -->
  <div class="container mx-auto px-6 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
      
      <!-- Student Profile -->
      <div class="lg:col-span-1">
        <div class="bg-white rounded-2xl card-shadow p-6">
          <div class="text-center">
            <div class="w-20 h-20 mx-auto bg-gray-600 rounded-full flex items-center justify-center mb-4">
              <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
              </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-800"><?= $_SESSION['student_name'] ?? 'Student' ?></h3>
            <p class="text-gray-600 text-sm">ID: <?= $_SESSION['id_number'] ?></p>
          </div>
          
          <div class="mt-6 pt-6 border-t">
            <div class="space-y-3 text-sm">
              <div>
                <span class="text-gray-500 font-medium">Program:</span>
                <p class="text-gray-800"><?= $_SESSION['program'] ?? 'N/A' ?></p>
              </div>
              <div>
                <span class="text-gray-500 font-medium">Year & Section:</span>
                <p class="text-gray-800"><?= $_SESSION['year_section'] ?? 'N/A' ?></p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Access -->
      <div class="lg:col-span-3">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Student Dashboard</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <!-- Balance Card -->
          <div onclick="location.href='Balances.php'" class="bg-white rounded-2xl card-shadow p-6 cursor-pointer hover:shadow-xl transition-all duration-200">
            <div class="flex items-center space-x-4">
              <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                </svg>
              </div>
              <div>
                <h4 class="text-lg font-semibold text-gray-800">Balance</h4>
                <p class="text-sm text-gray-600">View account balance</p>
              </div>
            </div>
          </div>

          <!-- Grades Card -->
          <div onclick="location.href='Grades.php'" class="bg-white rounded-2xl card-shadow p-6 cursor-pointer hover:shadow-xl transition-all duration-200">
            <div class="flex items-center space-x-4">
              <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
              </div>
              <div>
                <h4 class="text-lg font-semibold text-gray-800">Grades</h4>
                <p class="text-sm text-gray-600">View academic grades</p>
              </div>
            </div>
          </div>


          <!-- Attendance Card -->
          <div onclick="location.href='attendance/Attendance.php'" class="bg-white rounded-2xl card-shadow p-6 cursor-pointer hover:shadow-xl transition-all duration-200">
            <div class="flex items-center space-x-4">
              <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                </svg>
              </div>
              <div>
                <h4 class="text-lg font-semibold text-gray-800">Attendance</h4>
                <p class="text-sm text-gray-600">View attendance record</p>
              </div>
            </div>
          </div>

          <!-- Guidance Card -->
          <div onclick="location.href='GuidanceRecord.php'" class="bg-white rounded-2xl card-shadow p-6 cursor-pointer hover:shadow-xl transition-all duration-200">
            <div class="flex items-center space-x-4">
              <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
              </div>
              <div>
                <h4 class="text-lg font-semibold text-gray-800">Guidance</h4>
                <p class="text-sm text-gray-600">View guidance records</p>
              </div>
            </div>
          </div>

          <!-- Documents Card -->
          <div onclick="location.href='documents.php'" class="bg-white rounded-2xl card-shadow p-6 cursor-pointer hover:shadow-xl transition-all duration-200">
            <div class="flex items-center space-x-4">
              <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
              </div>
              <div>
                <h4 class="text-lg font-semibold text-gray-800">Documents</h4>
                <p class="text-sm text-gray-600">View available documents</p>
              </div>
            </div>
          </div>

          <!-- Request Document Card -->
          <div onclick="location.href='request-document.php'" class="bg-white rounded-2xl card-shadow p-6 cursor-pointer hover:shadow-xl transition-all duration-200">
            <div class="flex items-center space-x-4">
              <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
              </div>
              <div>
                <h4 class="text-lg font-semibold text-gray-800">Request Document</h4>
                <p class="text-sm text-gray-600">Submit new document request</p>
              </div>
            </div>
          </div>

          <!-- Requested Documents Card -->
          <div onclick="location.href='requested_document.php'" class="bg-white rounded-2xl card-shadow p-6 cursor-pointer hover:shadow-xl transition-all duration-200">
            <div class="flex items-center space-x-4">
              <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                </svg>
              </div>
              <div>
                <h4 class="text-lg font-semibold text-gray-800">Requested Documents</h4>
                <p class="text-sm text-gray-600">Track document requests</p>
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

  </script>
<script>
  // Prevent Back button from showing cached dashboard
  window.addEventListener("pageshow", function (event) {
    if (event.persisted) {
      window.location.reload();
    }
  });
</script>
<script>
  // Logout function
  function logout() {
    if (confirm('Are you sure you want to logout?')) {
      window.location.href = 'logout.php';
    }
  }
  
  // Notifications functions
  function toggleNotifications() {
    const dropdown = document.getElementById('notificationsDropdown');
    dropdown.classList.toggle('hidden');
  }
  
  function markAllAsRead() {
    fetch('mark_notifications_read.php', {
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
  
  // Close notifications dropdown when clicking outside
  document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('notificationsDropdown');
    const button = event.target.closest('button');
    
    if (!button || button.onclick !== toggleNotifications) {
      dropdown.classList.add('hidden');
    }
  });
</script>
</body>
</html>
