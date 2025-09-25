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

// Get recent notifications (limited for dropdown initially)
$recent_stmt = $conn->prepare("SELECT id, message, date_sent, is_read FROM notifications WHERE student_id = ? ORDER BY date_sent DESC LIMIT 5");
$recent_stmt->bind_param("s", $student_id);
$recent_stmt->execute();
$recent_notifications = $recent_stmt->get_result();
$recent_stmt->close();

// Get all notifications for AJAX loading
$all_stmt = $conn->prepare("SELECT id, message, date_sent, is_read FROM notifications WHERE student_id = ? ORDER BY date_sent DESC");
$all_stmt->bind_param("s", $student_id);
$all_stmt->execute();
$all_notifications = $all_stmt->get_result();
$all_notifications_array = [];
while ($row = $all_notifications->fetch_assoc()) {
    $all_notifications_array[] = $row;
}
$all_stmt->close();
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
        </div>
        
        <div class="flex items-center space-x-4">
          <img src="../images/LogoCCI.png" alt="Cornerstone College Inc." class="h-12 w-12 rounded-full bg-white p-1">
          <div class="text-right">
            <h1 class="text-xl font-bold">Cornerstone College Inc.</h1>
            <p class="text-blue-200 text-sm">Student Portal</p>
          </div>
          
          <!-- Notifications Bell -->
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
                <?php if ($recent_notifications->num_rows > 0): ?>
                  <?php 
                  $recent_notifications->data_seek(0); // Reset pointer
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
              <?php if ($recent_notifications->num_rows > 0): ?>
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
            <div class="w-20 h-20 mx-auto bg-gray-400 rounded-full flex items-center justify-center mb-4">
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
    const button = document.getElementById('notificationBtn');
    
    if (dropdown.classList.contains('hidden')) {
      // Get button position relative to viewport
      const rect = button.getBoundingClientRect();
      const viewportWidth = window.innerWidth;
      const dropdownWidth = 320;
      
      // Position dropdown using fixed positioning
      dropdown.style.top = (rect.bottom + 8) + 'px';
      
      // Calculate horizontal position - align dropdown right edge with button right edge
      let rightPos = viewportWidth - rect.right;
      
      // Ensure dropdown doesn't go off-screen on the left
      if (rect.right - dropdownWidth < 10) {
        rightPos = 10;
      }
      
      // Use right positioning for better alignment
      dropdown.style.right = rightPos + 'px';
      dropdown.style.left = 'auto';
      
      // Adjust width for very small screens
      if (viewportWidth < 350) {
        dropdown.style.width = (viewportWidth - 20) + 'px';
        dropdown.style.right = '10px';
      } else {
        dropdown.style.width = dropdownWidth + 'px';
      }
    }
    
    dropdown.classList.toggle('hidden');
    console.log('Dropdown positioned at right:', dropdown.style.right, 'top:', dropdown.style.top);
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
  
  // Store all notifications data
  const allNotifications = <?= json_encode($all_notifications_array) ?>;
  let currentlyShowing = 5;
  
  function loadMoreNotifications() {
    const notificationsList = document.getElementById('notificationsList');
    const viewMoreBtn = document.getElementById('viewMoreBtn');
    
    // Show more notifications (next 5)
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
    
    // Hide "View More" button if all notifications are shown
    if (currentlyShowing >= allNotifications.length) {
      viewMoreBtn.style.display = 'none';
    }
    
    // Update dropdown height if needed
    const dropdown = document.getElementById('notificationsDropdown');
    dropdown.style.maxHeight = '90vh';
  }
  
  // Add click event listener to notification button
  document.addEventListener('DOMContentLoaded', function() {
    const notificationBtn = document.getElementById('notificationBtn');
    const dropdown = document.getElementById('notificationsDropdown');
    const notificationsList = document.getElementById('notificationsList');
    
    console.log('DOM loaded, notification button:', notificationBtn);
    console.log('Dropdown element:', dropdown);
    
    if (notificationBtn && dropdown) {
      notificationBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('Notification button clicked');
        toggleNotifications();
      });
      
      // Close notifications dropdown when clicking outside
      document.addEventListener('click', function(event) {
        if (!notificationBtn.contains(event.target) && !dropdown.contains(event.target)) {
          dropdown.classList.add('hidden');
        }
      });

      // Helpers to decrement badges safely
      function decrementBadge(el) {
        if (!el) return;
        const txt = (el.textContent || '').trim();
        let num = 0;
        if (txt === '' ) return;
        if (txt.includes('+')) {
          // treat 9+ as at least 9
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

      // Delegated click handler for notifications
      function routeFromMessage(message) {
        if (!message) { window.location.href = 'requested_document.php'; return; }
        const msg = message.toLowerCase();
        // Specific document-related routing first
        if (msg.includes('successfully submitted')) return window.location.href = 'documents.php';
        if (msg.includes('document request') || msg.includes("status has been updated") || msg.includes('ready to claim') || msg.includes('pending')) return window.location.href = 'requested_document.php';
        // Other modules
        if (msg.includes('grade')) return window.location.href = 'Grades.php';
        if (msg.includes('balance') || msg.includes('payment')) return window.location.href = 'Balances.php';
        if (msg.includes('attendance')) return window.location.href = 'attendance/Attendance.php';
        if (msg.includes('guidance')) return window.location.href = 'GuidanceRecord.php';
        if (msg.includes('document')) return window.location.href = 'requested_document.php';
        // default
        window.location.href = 'requested_document.php';
      }

      notificationsList.addEventListener('click', function(e) {
        const item = e.target.closest('.notification-item');
        if (item) {
          const message = item.getAttribute('data-message') || '';
          const wasRead = item.getAttribute('data-read') === '1';
          const id = parseInt(item.getAttribute('data-id') || '0');

          // Optimistic UI update if it was unread
          if (!wasRead) {
            // Remove blue highlight and dot, dim text
            item.classList.remove('bg-blue-25');
            item.classList.add('opacity-75');
            const dot = item.querySelector('.w-2.h-2');
            if (dot) { dot.classList.remove('bg-blue-500'); dot.classList.add('bg-gray-300'); }
            const title = item.querySelector('p.text-sm');
            if (title) { title.classList.remove('font-semibold'); }
            item.setAttribute('data-read', '1');
            // Decrement badges
            decrementBadge(document.getElementById('notifHeaderCount'));
            decrementBadge(document.getElementById('notifBellCount'));
          }

          // Mark as read in backend (fire and forget)
          if (id > 0 && !wasRead) {
            fetch('mark_notification_read.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ id })
            }).catch(()=>{});
          }

          // Navigate
          routeFromMessage(message);
        }
      });
    } else {
      console.error('Notification elements not found');
    }
  });
</script>
</body>
</html>
