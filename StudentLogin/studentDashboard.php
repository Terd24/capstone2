<?php
session_start();

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
  <header class="bg-blue-600 text-white shadow-lg">
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
          <div onclick="location.href='requested-document.php'" class="bg-white rounded-2xl card-shadow p-6 cursor-pointer hover:shadow-xl transition-all duration-200">
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
</body>
</html>
