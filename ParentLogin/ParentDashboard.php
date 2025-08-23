<?php
session_start();
if (!isset($_SESSION['parent_id'])) {
    header("Location: ParentLogin.html");
    exit();
}

$parent_id = $_SESSION['parent_id'];
$child_id = $_SESSION['child_id'];

$conn = new mysqli("localhost", "root", "", "onecci_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$child_query = $conn->prepare("SELECT full_name, program, year_section FROM student_account WHERE id_number = ?");
$child_query->bind_param("s", $child_id);
$child_query->execute();
$child_result = $child_query->get_result();

if ($child_result->num_rows === 1) {
    $child = $child_result->fetch_assoc();
    $child_name = $child['full_name'];
    $child_program = $child['program'];
    $child_year_section = $child['year_section'];
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
  <title>Parent Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-white font-sans">

  <!-- Header with Burger Menu -->
  <div class="p-4 flex justify-between items-center border-b relative">
    <div class="relative">
      <button id="menuBtn" class="text-2xl font-bold hover:text-blue-600 transition">
        &#9776;
      </button>
      <!-- Dropdown Menu -->
      <div id="menuDropdown" class="hidden absolute left-0 mt-2 w-40 bg-white border rounded-lg shadow-lg z-50">
        <a href="logout.php" class="block px-4 py-2 text-black hover:bg-gray-100">Logout</a>
      </div>
    </div>
    <div class="w-6 h-6 bg-gray-300 rounded-full flex items-center justify-center">🔔</div>
  </div>

  <!-- Main Content -->
  <div class="p-6 flex flex-col md:flex-row gap-6">
    
    <!-- Child Info -->
    <div class="bg-gray-100 w-full md:w-1/2 p-6 rounded-lg shadow-md">
      <div class="flex items-center mb-4">
        <div class="w-12 h-12 rounded-full bg-gray-300 flex items-center justify-center text-xl">👤</div>
        <div class="ml-4">
          <p class="font-semibold text-lg"><?= htmlspecialchars($child_name) ?></p>
          <p class="text-sm text-gray-600">ID: <?= htmlspecialchars($child_id) ?></p>
        </div>
      </div>
      <p class="text-sm text-gray-700 mb-2">Program: <?= htmlspecialchars($child_program) ?></p>
      <p class="text-sm text-gray-700">Year & Section: <?= htmlspecialchars($child_year_section) ?></p>
    </div>

    <!-- Buttons -->
    <div class="w-full md:w-1/2 flex flex-col gap-4">
      <button onclick="location.href='ParentBalances.php'" class="bg-black text-white py-3 rounded-lg hover:bg-gray-800">Balances</button>
      <button onclick="location.href='ParentGrades.php'" class="bg-black text-white py-3 rounded-lg hover:bg-gray-800">Grades</button>
      <button onclick="location.href='ParentAttendance.php'" class="bg-black text-white py-3 rounded-lg hover:bg-gray-800">Attendance</button>
      <button onclick="location.href='ParentGuidanceRecord.php'" class="bg-black text-white py-3 rounded-lg hover:bg-gray-800">Guidance Record</button>
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

</body>
</html>
