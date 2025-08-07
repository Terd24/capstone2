<?php
session_start();
if (!isset($_SESSION['parent_id'])) {
    header("Location: ParentLogin.html"); // go back to login if not logged in
    exit();
}

$parent_id = $_SESSION['parent_id'];
$child_id = $_SESSION['child_id'];

$conn = new mysqli("localhost", "root", "", "onecci_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$child_query = $conn->prepare("SELECT full_name, program, year_section FROM users WHERE id_number = ?");
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
          <p class="font-semibold text-lg"><?php echo htmlspecialchars($child_name); ?></p>
          <p class="text-sm text-gray-600">ID: <?php echo htmlspecialchars($child_id); ?></p>
        </div>
      </div>
      <p class="text-sm text-gray-700 mb-2">Program: <?php echo htmlspecialchars($child_program); ?></p>
      <p class="text-sm text-gray-700">Year & Section: <?php echo htmlspecialchars($child_year_section); ?></p>
    </div>

    <div class="w-full md:w-1/2 flex flex-col gap-4">
      <button onclick="location.href='ParentBalances.html'" class="bg-black text-white py-3 rounded-lg hover:bg-gray-800">Balances</button>
      <button onclick="location.href='ParentGrades.html'" class="bg-black text-white py-3 rounded-lg hover:bg-gray-800">Grades</button>
      <button onclick="location.href='ParentAttendance.html'" class="bg-black text-white py-3 rounded-lg hover:bg-gray-800">Attendance</button>
      <button onclick="location.href='ParentGuidanceRecord.html'" class="bg-black text-white py-3 rounded-lg hover:bg-gray-800">Guidance Record</button>
    </div>
  </div>
</body>
</html>
