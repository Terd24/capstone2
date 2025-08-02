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

  <div class="p-4 flex justify-between items-center border-b">
    <div class="text-2xl font-bold">&#9776;</div>
    <div class="w-6 h-6 bg-gray-300 rounded-full flex items-center justify-center">🔔</div>
  </div>

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
      <button onclick="location.href='registrar.html'" class="bg-black text-white py-3 rounded-lg hover:bg-gray-800">Registrar</button>
      <button onclick="location.href='balances.html'" class="bg-black text-white py-3 rounded-lg hover:bg-gray-800">Balances</button>
      <button onclick="location.href='grades.html'" class="bg-black text-white py-3 rounded-lg hover:bg-gray-800">Grades</button>
      <button onclick="location.href='attendance.html'" class="bg-black text-white py-3 rounded-lg hover:bg-gray-800">Attendance</button>
      <button onclick="location.href='GuidanceRecord.html'" class="bg-black text-white py-3 rounded-lg hover:bg-gray-800">Guidance Record</button>
    </div>
  </div>

</body>
</html>
