<?php
session_start();
if (!isset($_SESSION['id_number'])) {
    header("Location: login.html");
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

  <!-- Header with menu and notifications -->
  <div class="p-4 flex justify-between items-center border-b relative">
    <!-- Menu button -->
    <div id="menuBtn" class="text-2xl font-bold cursor-pointer">&#9776;</div>
    <div class="w-6 h-6 bg-gray-300 rounded-full flex items-center justify-center">🔔</div>

    <!-- Dropdown menu -->
    <div id="menuDropdown" class="hidden absolute top-12 left-4 bg-white border border-gray-300 rounded shadow-md">
        <button onclick="location.href='Login.html'" class="block px-4 py-2 text-left w-full hover:bg-gray-100">
            Logout
        </button>
    </div>
  </div>

  <!-- Main content -->
  <div class="p-6 flex flex-col md:flex-row gap-6">
    
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

    <div class="w-full md:w-1/2 flex flex-col gap-4">
      <button onclick="location.href='registrar.php'" class="bg-black text-white py-3 rounded-lg hover:bg-gray-800">Registrar</button>
      <button onclick="location.href='balances.php'" class="bg-black text-white py-3 rounded-lg hover:bg-gray-800">Balances</button>
      <button onclick="location.href='grades.php'" class="bg-black text-white py-3 rounded-lg hover:bg-gray-800">Grades</button>
      <button onclick="location.href='attendance/attendance.php'" class="bg-black text-white py-3 rounded-lg hover:bg-gray-800">Attendance</button>
      <button onclick="location.href='GuidanceRecord.php'" class="bg-black text-white py-3 rounded-lg hover:bg-gray-800">Guidance Record</button>
    </div>
  </div>

<script>
// Toggle dropdown when clicking menu icon
document.getElementById('menuBtn').addEventListener('click', function () {
    document.getElementById('menuDropdown').classList.toggle('hidden');
});

// Close dropdown if clicking outside
document.addEventListener('click', function (e) {
    const menuBtn = document.getElementById('menuBtn');
    const menuDropdown = document.getElementById('menuDropdown');
    if (!menuBtn.contains(e.target) && !menuDropdown.contains(e.target)) {
        menuDropdown.classList.add('hidden');
    }
});
</script>

</body>
</html>
