<?php
session_start();
include 'db_conn.php';

if (!isset($_SESSION['id_number'])) {
  header("Location: index.php");
  exit();
}

$id_number = $_SESSION['id_number'];
$full_name = $_SESSION['full_name'];
$program = $_SESSION['program'];
$year_section = $_SESSION['year_section'];

// Pagination setup
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$offset = ($page - 1) * $records_per_page;

// Get total record count
$count_sql = "SELECT COUNT(*) as total FROM guidance_records WHERE id_number = ?";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param("s", $id_number);
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get paginated guidance records
$sql = "SELECT * FROM guidance_records WHERE id_number = ? ORDER BY record_date DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sii", $id_number, $records_per_page, $offset);
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
    <div class="container mx-auto px-4 sm:px-6 py-3 sm:py-4">
      <div class="flex justify-between items-center">
        <div class="flex items-center space-x-3 sm:space-x-4">
          <button onclick="window.location.replace('studentDashboard.php')" class="bg-white bg-opacity-20 hover:bg-opacity-30 p-2 rounded-lg transition">
            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
          </button>
          <div class="text-left">
            <p class="text-xs sm:text-sm text-blue-200">Guidance Records</p>
            <p class="font-semibold text-sm sm:text-base text-white truncate max-w-[120px] sm:max-w-none">
              <?= htmlspecialchars($full_name) ?>
            </p>
          </div>
        </div>

        <div class="flex items-center space-x-3 sm:space-x-4">
          <div class="hidden sm:block text-right">
            <h1 class="text-lg sm:text-xl font-bold text-white leading-tight">Cornerstone College Inc.</h1>
            <p class="text-blue-100 text-xs sm:text-sm">Student Portal</p>
          </div>

          <!-- Mobile compact title -->
          <div class="sm:hidden text-center">

          </div>

          <img src="../images/LogoCCI.png" alt="Cornerstone College Inc." class="h-10 w-10 sm:h-12 sm:w-12 rounded-full bg-white p-1">
        </div>
      </div>
    </div>
  </header>

  <!-- Main Content -->
  <div class="container mx-auto px-4 sm:px-6 py-6 sm:py-8">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 sm:gap-8">

      <!-- Student Profile -->
      <div class="lg:col-span-1 order-2 lg:order-1">
        <div class="bg-white rounded-2xl card-shadow p-4 sm:p-6">
          <div class="text-center">
            <div class="w-16 h-16 sm:w-20 sm:h-20 mx-auto bg-gray-400 rounded-full flex items-center justify-center mb-3 sm:mb-4">
              <svg class="w-8 h-8 sm:w-10 sm:h-10 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
              </svg>
            </div>
            <h3 class="text-base sm:text-lg font-bold text-gray-800 leading-tight">
              <?= htmlspecialchars($full_name) ?>
            </h3>
            <p class="text-gray-600 text-xs sm:text-sm mt-1">ID: <?= htmlspecialchars($id_number) ?></p>
          </div>

          <div class="mt-4 sm:mt-6 pt-4 sm:pt-6 border-t">
            <div class="space-y-3 text-sm">
              <div>
                <span class="text-gray-500 font-medium text-xs sm:text-sm">Program:</span>
                <p class="text-gray-800 text-sm sm:text-base mt-1">
                  <?= htmlspecialchars($program) ?>
                </p>
              </div>
              <div>
                <span class="text-gray-500 font-medium text-xs sm:text-sm">Year & Section:</span>
                <p class="text-gray-800 text-sm sm:text-base mt-1">
                  <?= htmlspecialchars($year_section) ?>
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Guidance Records -->
      <div class="lg:col-span-3 order-1 lg:order-2">
        <div class="bg-white rounded-2xl card-shadow p-4 sm:p-6">
          <div class="flex items-center mb-4 sm:mb-6">
            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-orange-600 mr-2 sm:mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="text-lg sm:text-xl font-bold text-gray-800">Guidance Records</h3>
          </div>

          <div class="bg-gray-50 rounded-lg p-3 sm:p-4 min-h-[150px] sm:min-h-[200px]">
            <?php if (count($guidance_data) === 0): ?>
              <div class="text-center py-6 sm:py-8">
                <svg class="w-12 h-12 sm:w-16 sm:h-16 mx-auto text-gray-300 mb-3 sm:mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-gray-500 text-xs sm:text-sm italic">No guidance records found</p>
              </div>
            <?php else: ?>
              <div class="space-y-3">
                <?php foreach ($guidance_data as $record): ?>
                  <div class="bg-white rounded-lg p-3 sm:p-4 border border-gray-200 shadow-sm">
                    <p class="text-sm text-gray-700 font-medium leading-relaxed">
                      <?= htmlspecialchars($record['remarks']) ?>
                    </p>
                    <p class="text-xs text-gray-500 mt-2">
                      <?= htmlspecialchars(date("M j, Y", strtotime($record['record_date']))) ?>
                    </p>
                  </div>
                <?php endforeach; ?>
              </div>
              
              <!-- Pagination -->
              <?php if ($total_pages > 1): ?>
                <div class="mt-4 pt-4 border-t border-gray-200">
                  <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="text-xs sm:text-sm text-gray-600">
                      Showing <?= min($offset + 1, $total_records) ?> to <?= min($offset + $records_per_page, $total_records) ?> of <?= $total_records ?> records
                    </div>
                    <div class="flex gap-2">
                      <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>" class="px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 text-xs sm:text-sm font-medium text-gray-700">
                          Previous
                        </a>
                      <?php endif; ?>
                      
                      <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <a href="?page=<?= $i ?>" class="px-3 py-2 <?= $i === $page ? 'bg-orange-600 text-white' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50' ?> rounded-lg text-xs sm:text-sm font-medium">
                          <?= $i ?>
                        </a>
                      <?php endfor; ?>
                      
                      <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?>" class="px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 text-xs sm:text-sm font-medium text-gray-700">
                          Next
                        </a>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

</body>
</html>
