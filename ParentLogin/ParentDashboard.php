<?php
session_start();
if (!isset($_SESSION['parent_id'])) {
    header("Location: ../StudentLogin/login.php");
    exit();
}

$parent_id = $_SESSION['parent_id'];
$child_id = $_SESSION['child_id'];

$conn = new mysqli("localhost", "root", "", "onecci_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$child_query = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) as full_name, academic_track, grade_level FROM student_account WHERE id_number = ?");
$child_query->bind_param("s", $child_id);
$child_query->execute();
$child_result = $child_query->get_result();

if ($child_result->num_rows === 1) {
    $child = $child_result->fetch_assoc();
    $child_name = $child['full_name'];
    $child_program = $child['academic_track'];
    $child_year_section = $child['grade_level'];
} else {
    $child_name = "Not Found";
    $child_program = "N/A";
    $child_year_section = "N/A";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Parent Dashboard - Cornerstone College Inc.</title>
  <link rel="icon" href="../images/LogoCCI.png" type="image/png">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .school-gradient { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #1e40af 100%); }
    .card-shadow { box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
  </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen font-sans">

  <!-- RFID Form -->
  <form id="rfidForm" method="get" action="ParentBalances.php">
    <input type="hidden" id="rfid_input" name="student_id" autocomplete="off">
  </form>

  <!-- Header with School Branding -->
  <header class="school-gradient text-white shadow-lg">
    <div class="container mx-auto px-6 py-4">
      <div class="flex justify-between items-center">
        <div class="flex items-center space-x-4">
          <img src="../images/LogoCCI.png" alt="Cornerstone College Inc." class="h-12 w-12 rounded-full bg-white p-1">
          <div>
            <h1 class="text-xl font-bold">Cornerstone College Inc.</h1>
            <p class="text-blue-200 text-sm">Parent Portal</p>
          </div>
        </div>
        
        <div class="flex items-center space-x-4">
          <div class="text-right">
            <p class="text-sm text-blue-200">Welcome,</p>
            <p class="font-semibold"><?= htmlspecialchars($_SESSION['parent_name'] ?? 'Parent') ?></p>
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
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      
      <!-- Child Profile Card -->
      <div class="bg-white rounded-2xl card-shadow p-8">
        <div class="text-center mb-6">
          <div class="w-24 h-24 mx-auto bg-gray-400 rounded-full flex items-center justify-center mb-4">
            <svg class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
            </svg>
          </div>
          <h2 class="text-2xl font-bold text-gray-800 mb-2"><?= htmlspecialchars($child_name) ?></h2>
          <p class="text-blue-600 font-medium">Student ID: <?= htmlspecialchars($child_id) ?></p>
        </div>
        
        <div class="bg-blue-50 rounded-lg p-4 space-y-3">
          <h3 class="font-semibold text-gray-700 mb-3">Academic Information</h3>
          <div class="flex justify-between">
            <span class="text-gray-600">Program:</span>
            <span class="font-medium text-gray-800"><?= htmlspecialchars($child_program) ?></span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600">Year & Section:</span>
            <span class="font-medium text-gray-800"><?= htmlspecialchars($child_year_section) ?></span>
          </div>
        </div>
      </div>

      <!-- Quick Access Menu -->
      <div class="space-y-4">
        <h3 class="text-xl font-bold text-gray-800 mb-6">Quick Access</h3>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <button onclick="location.href='ParentBalances.php'" 
                  class="bg-white rounded-xl card-shadow p-6 hover:shadow-lg transition-all group">
            <div class="flex items-center space-x-4">
              <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center group-hover:bg-green-200 transition-colors">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                </svg>
              </div>
              <div class="text-left">
                <h4 class="font-semibold text-gray-800">Balances</h4>
                <p class="text-sm text-gray-600">View account balances</p>
              </div>
            </div>
          </button>

          <button onclick="location.href='ParentGrades.php'" 
                  class="bg-white rounded-xl card-shadow p-6 hover:shadow-lg transition-all group">
            <div class="flex items-center space-x-4">
              <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center group-hover:bg-blue-200 transition-colors">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </div>
              <div class="text-left">
                <h4 class="font-semibold text-gray-800">Grades</h4>
                <p class="text-sm text-gray-600">Check academic performance</p>
              </div>
            </div>
          </button>

          <button onclick="location.href='ParentAttendance.php'" 
                  class="bg-white rounded-xl card-shadow p-6 hover:shadow-lg transition-all group">
            <div class="flex items-center space-x-4">
              <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center group-hover:bg-purple-200 transition-colors">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
              </div>
              <div class="text-left">
                <h4 class="font-semibold text-gray-800">Attendance</h4>
                <p class="text-sm text-gray-600">View attendance records</p>
              </div>
            </div>
          </button>

          <button onclick="location.href='ParentGuidanceRecord.php'" 
                  class="bg-white rounded-xl card-shadow p-6 hover:shadow-lg transition-all group">
            <div class="flex items-center space-x-4">
              <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center group-hover:bg-orange-200 transition-colors">
                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
              </div>
              <div class="text-left">
                <h4 class="font-semibold text-gray-800">Guidance Record</h4>
                <p class="text-sm text-gray-600">View guidance counseling</p>
              </div>
            </div>
          </button>
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
  </script>

</body>
</html>
