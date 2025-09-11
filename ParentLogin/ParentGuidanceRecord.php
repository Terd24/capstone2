<?php
session_start();
include '../StudentLogin/db_conn.php';

if (!isset($_SESSION['parent_id']) || !isset($_SESSION['child_id'])) {
  header("Location: ParentLogin.html");
  exit();
}

$id_number = $_SESSION['child_id'];
$full_name = $_SESSION['child_name'] ?? 'Student';

// Get student info for program and year section
$student_stmt = $conn->prepare("SELECT academic_track, grade_level FROM student_account WHERE id_number = ?");
$student_stmt->bind_param("s", $id_number);
$student_stmt->execute();
$student_result = $student_stmt->get_result();
$student_info = $student_result->fetch_assoc();

$program = $student_info['academic_track'] ?? 'N/A';
$year_section = $student_info['grade_level'] ?? 'N/A';

// Get guidance record
$sql = "SELECT * FROM guidance_records WHERE id_number = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id_number);
$stmt->execute();
$result = $stmt->get_result();

$guidance_data = [];
while ($row = $result->fetch_assoc()) {
  $guidance_data[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Guidance Records - Cornerstone College Inc.</title>
  <link rel="icon" type="image/png" href="../images/LogoCCI.png">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .school-gradient { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #1e40af 100%); }
    .card-shadow { box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
  </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen font-sans">

  <!-- Header with School Branding -->
  <header class="bg-[#0B2C62] text-white shadow-lg">
    <div class="container mx-auto px-6 py-4">
      <div class="flex justify-between items-center">
        <div class="flex items-center space-x-4">
          <button onclick="window.location.replace('ParentDashboard.php')" class="bg-white bg-opacity-20 hover:bg-opacity-30 p-2 rounded-lg transition mr-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
          </button>
          <div class="text-left">
            <p class="text-sm text-blue-200">Guidance Records</p>
            <p class="font-semibold"><?= htmlspecialchars($full_name) ?></p>
          </div>
        </div>
        
        <div class="flex items-center space-x-4">
          <img src="../images/LogoCCI.png" alt="Cornerstone College Inc." class="h-12 w-12 rounded-full bg-white p-1">
          <div class="text-right">
            <h1 class="text-xl font-bold">Cornerstone College Inc.</h1>
            <p class="text-blue-200 text-sm">Student Portal</p>
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
            <h3 class="text-lg font-bold text-gray-800"><?= htmlspecialchars($full_name) ?></h3>
            <p class="text-gray-600 text-sm">ID: <?= htmlspecialchars($id_number) ?></p>
          </div>
          
          <div class="mt-6 pt-6 border-t">
            <div class="space-y-3 text-sm">
              <div>
                <span class="text-gray-500 font-medium">Program:</span>
                <p class="text-gray-800"><?= htmlspecialchars($program) ?></p>
              </div>
              <div>
                <span class="text-gray-500 font-medium">Year & Section:</span>
                <p class="text-gray-800"><?= htmlspecialchars($year_section) ?></p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Guidance Records -->
      <div class="lg:col-span-3">
        <div class="bg-white rounded-2xl card-shadow p-6">
          <div class="flex items-center mb-6">
            <svg class="w-6 h-6 text-orange-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="text-xl font-bold text-gray-800">Guidance Records</h3>
          </div>

          <div class="bg-gray-50 rounded-lg p-4 min-h-[200px]">
            <?php if (count($guidance_data) === 0): ?>
              <div class="text-center py-8">
                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-gray-500 text-sm italic">No guidance records found</p>
              </div>
            <?php else: ?>
              <div class="space-y-3">
                <?php foreach ($guidance_data as $record): ?>
                  <div class="bg-white rounded-lg p-4 border border-gray-200 shadow-sm">
                    <p class="text-sm text-gray-700 font-medium"><?= htmlspecialchars($record['remarks']) ?></p>
                    <p class="text-xs text-gray-500 mt-2"><?= htmlspecialchars(date("F j, Y", strtotime($record['record_date']))) ?></p>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

</body>
</html>