<?php
session_start();
include("../StudentLogin/db_conn.php");

// Require Department Head login
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'department_head') {
    header("Location: ../StudentLogin/login.php");
    exit;
}

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Get department head name
$dept_head_name = $_SESSION['dept_head_name'] ?? $_SESSION['username'] ?? 'Department Head';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Department Head Dashboard - CCI</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="../js/logout-confirm.js"></script>
</head>
<body class="bg-gradient-to-br from-[#f3f6fb] to-[#e6ecf7] font-sans min-h-screen text-gray-900">

<header class="bg-[#0B2C62] text-white shadow-lg">
  <div class="container mx-auto px-6 py-4">
    <div class="flex justify-between items-center">
      <div class="flex items-center space-x-4">
        <div class="text-left">
          <p class="text-sm text-blue-200">Welcome,</p>
          <p class="font-semibold"><?= htmlspecialchars($dept_head_name) ?></p>
        </div>
      </div>
      
      <div class="flex items-center space-x-4">
        <img src="../images/LogoCCI.png" alt="Cornerstone College Inc." class="h-12 w-12 rounded-full bg-white p-1">
        <div class="text-right">
          <h1 class="text-xl font-bold">Cornerstone College Inc.</h1>
          <p class="text-blue-200 text-sm">Department Head Portal</p>
        </div>
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

<div class="max-w-7xl mx-auto mt-8 p-6">
    <!-- Header Section -->
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-[#0B2C62]">Department Head Dashboard</h2>
        <p class="text-gray-600 mt-2">Manage teacher schedules and attendance</p>
    </div>

    <!-- Module Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Manage Teacher Schedule Card -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-[#0B2C62]/10 hover:shadow-xl transition flex flex-col">
            <div class="flex items-center mb-4">
                <div class="bg-[#0B2C62] p-3 rounded-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 ml-4">Manage Teacher Schedule</h3>
            </div>
            <p class="text-gray-600 mb-4 flex-grow">Create and manage teacher work schedules, assign subjects and sections</p>
            <button onclick="window.location.href='ManageTeacherSchedule.php'" class="w-full px-4 py-3 bg-[#0B2C62] text-white rounded-lg shadow hover:bg-blue-900 transition font-medium mt-auto">
                Open Schedule Management
            </button>
        </div>

        <!-- Teacher Attendance Card -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-[#0B2C62]/10 hover:shadow-xl transition flex flex-col">
            <div class="flex items-center mb-4">
                <div class="bg-[#0B2C62] p-3 rounded-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 ml-4">Teacher Attendance</h3>
            </div>
            <p class="text-gray-600 mb-4 flex-grow">View and monitor teacher attendance records and reports</p>
            <button onclick="window.location.href='TeacherAttendance.php'" class="w-full px-4 py-3 bg-[#0B2C62] text-white rounded-lg shadow hover:bg-blue-900 transition font-medium mt-auto">
                View Attendance Records
            </button>
        </div>

        <!-- Manage Subjects Card -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-[#0B2C62]/10 hover:shadow-xl transition flex flex-col">
            <div class="flex items-center mb-4">
                <div class="bg-[#0B2C62] p-3 rounded-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-gray-800 ml-4">Manage Student Subjects</h3>
            </div>
            <p class="text-gray-600 mb-4 flex-grow">Create, edit, and manage student academic subjects for different grade levels, strands, and terms</p>
            <button onclick="window.location.href='ManageSubjects.php'" class="w-full px-4 py-3 bg-[#0B2C62] text-white rounded-lg shadow hover:bg-blue-900 transition font-medium mt-auto">
                Manage Student Subjects
            </button>
        </div>
    </div>
</div>

<script>
// Menu dropdown toggle
const menuBtn = document.getElementById('menuBtn');
const dropdownMenu = document.getElementById('dropdownMenu');

if (menuBtn && dropdownMenu) {
    menuBtn.addEventListener('click', () => {
        dropdownMenu.classList.toggle('hidden');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!menuBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
            dropdownMenu.classList.add('hidden');
        }
    });
}
</script>

</body>
</html>
