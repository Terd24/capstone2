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
  <title>Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white font-sans">

  <div class="p-4 flex justify-between items-center border-b relative">

    <div class="relative">
      <button id="menuBtn" class="text-2xl font-bold hover:text-blue-600 transition">
        &#9776;
      </button>

      <div id="menuDropdown" 
           class="hidden absolute left-0 mt-2 w-40 bg-white border rounded-lg shadow-lg z-50">
        <a href="logout.php" 
           class="block px-4 py-2 text-black-500 hover:bg-gray-100">
          Logout
        </a>
      </div>
    </div>
    <div class="w-6 h-6 bg-gray-300 rounded-full flex items-center justify-center">🔔</div>
  </div>

  <!-- Main Content -->
  <div class="p-6 flex flex-col md:flex-row gap-6">
    
    <!-- Student Info -->
    <div class="bg-gray-100 w-full md:w-1/2 p-6 rounded-lg shadow-md">
      <div class="flex items-center mb-4">
        <div class="w-12 h-12 rounded-full bg-gray-300 flex items-center justify-center text-xl">👤</div>
        <div class="ml-4">
          <p class="font-semibold text-lg"><?= $_SESSION['full_name'] ?></p>
          <p class="text-sm text-gray-600">ID: <?= $_SESSION['id_number'] ?></p>
        </div>
      </div>
      <p class="text-sm text-gray-700 mb-2">Program: <?= $_SESSION['program'] ?></p>
      <p class="text-sm text-gray-700">Year & Section: <?= $_SESSION['year_section'] ?></p>
    </div>

    <!-- Buttons -->
    <div class="w-full md:w-1/2 flex flex-col gap-4">
      <button onclick="location.href='registrar.php'" class="bg-black text-white py-3 rounded-lg hover:bg-gray-800">Registrar</button>
      <button onclick="location.href='balances.php'" class="bg-black text-white py-3 rounded-lg hover:bg-gray-800">Balances</button>
      <button onclick="location.href='grades.php'" class="bg-black text-white py-3 rounded-lg hover:bg-gray-800">Grades</button>
      <button onclick="location.href='attendance/attendance.php'" class="bg-black text-white py-3 rounded-lg hover:bg-gray-800">Attendance</button>
      <button onclick="location.href='GuidanceRecord.php'" class="bg-black text-white py-3 rounded-lg hover:bg-gray-800">Guidance Record</button>
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
