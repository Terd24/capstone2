<?php
session_start();
include("../StudentLogin/db_conn.php");

// Allow only teachers on this dashboard
$allowed_roles = ['teacher'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles, true)) {
    header("Location: ../StudentLogin/login.php");
    exit;
}

// Basic session vars
$employee_id_number = $_SESSION['id_number'] ?? '';
$employee_name = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));

if ($employee_id_number === '') {
    header("Location: ../StudentLogin/login.php");
    exit;
}

date_default_timezone_set('Asia/Manila');
$today = date('Y-m-d');

// Cache prevention for secure back-button behavior
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Check which column exists in teacher_attendance table
$employee_id_column = 'teacher_id'; // default for teacher_attendance table
$check_columns = $conn->query("SHOW COLUMNS FROM teacher_attendance LIKE '%id'");
if ($check_columns && $check_columns->num_rows > 0) {
    while ($col = $check_columns->fetch_assoc()) {
        if ($col['Field'] === 'employee_id') {
            $employee_id_column = 'employee_id';
            break;
        } elseif ($col['Field'] === 'teacher_id') {
            $employee_id_column = 'teacher_id';
            break;
        }
    }
}

// Handle POST submission - store in session and redirect to clean URL
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['teacher_attendance_filters'] = [
        'start_date' => trim($_POST['start_date'] ?? ''),
        'end_date' => trim($_POST['end_date'] ?? '')
    ];
    header("Location: AttendanceRecords.php");
    exit;
}

// Get filters from session or use defaults
$filters = $_SESSION['teacher_attendance_filters'] ?? [];
$start_date = $filters['start_date'] ?? '';
$end_date = $filters['end_date'] ?? '';

// Clear session filters if explicitly requested
if (isset($_GET['clear'])) {
    unset($_SESSION['teacher_attendance_filters']);
    header("Location: AttendanceRecords.php");
    exit;
}

// Validate date restrictions
if ($start_date !== '') {
    // Start date must be from 2025-01-01 onwards and not in the future
    if ($start_date < '2025-01-01') {
        $start_date = '2025-01-01';
    }
    if ($start_date > $today) {
        $start_date = $today;
    }
}

if ($end_date !== '') {
    // End date cannot be in the future
    if ($end_date > $today) {
        $end_date = $today;
    }
    // End date should not be before start date if both are provided
    if ($start_date !== '' && $end_date < $start_date) {
        $end_date = $start_date;
    }
}

// Build attendance query for this employee only (teacher_attendance table)
$sql = "SELECT * FROM teacher_attendance WHERE $employee_id_column = ?";
$params = [$employee_id_number];
$types  = 's';

if ($start_date !== '' && $end_date !== '') {
    $sql .= " AND date BETWEEN ? AND ?";
    $params[] = $start_date; $types .= 's';
    $params[] = $end_date;   $types .= 's';
} else {
    // Default to today's records
    $sql .= " AND date = ?";
    $params[] = $today; $types .= 's';
}

$sql .= " ORDER BY date DESC, id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$records = $stmt->get_result();
$total_records = $records->num_rows; // Store count before iterating

// Fetch schedule info for calculations
$shiftType = '—';
$shiftIn = null; $shiftOut = null; $hasDay = false;
$dayName = '';

// Pull assigned schedule (if any)
$ws = $conn->prepare("SELECT ws.start_time, ws.end_time, ws.days, ws.id AS schedule_id
                      FROM employees e
                      LEFT JOIN employee_schedules es ON e.id_number = es.employee_id
                      LEFT JOIN employee_work_schedules ws ON es.schedule_id = ws.id
                      WHERE e.id_number = ? LIMIT 1");
if ($ws) {
    $ws->bind_param('s', $employee_id_number);
    $ws->execute();
    $resWS = $ws->get_result();
    $scheduleData = $resWS && $resWS->num_rows > 0 ? $resWS->fetch_assoc() : null;
    $ws->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Teacher Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="manifest" href="/onecci/manifest.webmanifest">
  <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => {
        navigator.serviceWorker.register('/onecci/sw.js').catch(console.error);
      });
    }
  </script>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
  <header class="bg-[#0B2C62] text-white shadow-lg">
    <div class="container mx-auto px-6 py-4">
      <div class="flex justify-between items-center">
        <div class="flex items-center space-x-4">
          <div class="text-left">
            <p class="text-sm text-blue-200">Welcome,</p>
            <p class="font-semibold"><?php echo htmlspecialchars($employee_name ?: 'Teacher'); ?></p>
          </div>
        </div>
        <div class="flex items-center space-x-4 relative">
          <img src="../images/LogoCCI.png" alt="Cornerstone College Inc." class="h-12 w-12 rounded-full bg-white p-1">
          <div class="text-right">
            <h1 class="text-xl font-bold">Cornerstone College Inc.</h1>
            <p class="text-blue-200 text-sm">Teacher Portal</p>
          </div>
          <button id="empMenuBtn" class="ml-2 bg-white bg-opacity-20 hover:bg-opacity-30 p-2 rounded-lg transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>
          <div id="empDropdown" class="hidden absolute right-0 top-12 w-48 bg-white rounded-lg shadow-lg z-50 text-gray-800">
            <a href="../StudentLogin/logout.php" class="block px-4 py-3 hover:bg-gray-100 rounded-lg">
              <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
              Logout
            </a>
          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- Summary Card -->
  <div class="container mx-auto px-6 py-6">
    <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-xl font-semibold text-gray-800">Hello, <?php echo htmlspecialchars($employee_name ?: 'Employee'); ?></h2>
          <p class="text-gray-600 text-sm">ID: <?php echo htmlspecialchars($employee_id_number); ?></p>
        </div>
        <div class="text-right">
          <p class="text-gray-500 text-sm">Today</p>
          <p class="text-gray-900 font-semibold"><?php echo date('F j, Y - l', strtotime($today)); ?></p>
        </div>
      </div>
    </div>

    <!-- Quick Actions (Dashboard) -->
    <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
      <h3 class="text-lg font-bold text-gray-800 mb-4">Quick Actions</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <?php if (($_SESSION['role'] ?? '') === 'teacher'): ?>
          <a href="ManageGrades.php" class="block text-center bg-[#1E3A8A] hover:bg-[#0B2C62] text-white px-6 py-4 rounded-xl font-semibold shadow">
            Manage Grades
          </a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Filters & Records -->
    <div id="attendance" class="bg-white rounded-2xl shadow-lg p-6">
      <h3 class="text-lg font-bold text-gray-800 mb-4">Attendance Records</h3>
      <form method="post" id="filterForm" class="grid grid-cols-1 md:grid-cols-8 gap-4 items-end mb-4">
        <div class="md:col-span-2">
          <label class="text-sm font-medium text-gray-700 mb-2 block">Start Date</label>
          <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($start_date); ?>" min="2025-01-01" max="<?= $today ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-[#0B2C62] focus:border-transparent">
        </div>
        <div class="md:col-span-2">
          <label class="text-sm font-medium text-gray-700 mb-2 block">End Date</label>
          <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($end_date); ?>" min="2025-01-01" max="<?= $today ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-[#0B2C62] focus:border-transparent">
        </div>
        <div class="md:col-span-4 flex gap-2">
          <button type="submit" class="bg-[#0B2C62] hover:bg-blue-900 text-white px-6 py-2 rounded-lg font-medium text-sm whitespace-nowrap">Generate Report</button>
          <a href="AttendanceRecords.php?clear=1" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg font-medium text-sm whitespace-nowrap">Clear</a>
        </div>
      </form>
      <div class="pt-4 mt-4 border-t border-gray-200">
        <p class="text-gray-600 mb-6">
          <?php 
          if ($start_date !== '' && $end_date !== '') {
              echo "Attendance records from " . date('F j, Y', strtotime($start_date)) . " to " . date('F j, Y', strtotime($end_date));
          } else {
              echo "Attendance records for " . date('F j, Y', strtotime($today));
          }
          ?>
        </p>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-[#0B2C62] text-white">
            <tr>
              <th class="px-6 py-4 text-left font-semibold">Date</th>
              <th class="px-6 py-4 text-left font-semibold">Day</th>
              <th class="px-6 py-4 text-left font-semibold">Shift In</th>
              <th class="px-6 py-4 text-left font-semibold">Shift Out</th>
              <th class="px-6 py-4 text-left font-semibold">Time In</th>
              <th class="px-6 py-4 text-left font-semibold">Time Out</th>
              <th class="px-6 py-4 text-left font-semibold">Required Hours</th>
              <th class="px-6 py-4 text-left font-semibold">Tardiness</th>
              <th class="px-6 py-4 text-left font-semibold">Undertime</th>
              <th class="px-6 py-4 text-left font-semibold">OT</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <?php if ($records && $records->num_rows > 0): ?>
              <?php while ($r = $records->fetch_assoc()): ?>
                <?php
                  $dayName = date('l', strtotime($r['date']));
                  $curShiftIn = null; $curShiftOut = null; $hasDay = false; $reqHours = 0.0;
                  $tardinessMin = 0; $undertimeMin = 0; $otMin = 0;

                  if (!empty($scheduleData)) {
                    $curShiftIn = $scheduleData['start_time'] ?? null;
                    $curShiftOut = $scheduleData['end_time'] ?? null;
                    if (!empty($scheduleData['days'])) {
                      $daysArr = array_map('trim', explode(',', $scheduleData['days']));
                      $hasDay = in_array($dayName, $daysArr, true) || strcasecmp(trim((string)$scheduleData['days']), 'Variable') === 0;
                    }
                    // Day-specific override
                    if (!empty($scheduleData['schedule_id'])) {
                      $ds = $conn->prepare("SELECT start_time, end_time FROM employee_work_day_schedules WHERE schedule_id = ? AND day_name = ? LIMIT 1");
                      if ($ds) {
                        $ds->bind_param('is', $scheduleData['schedule_id'], $dayName);
                        $ds->execute();
                        $ovr = $ds->get_result();
                        if ($ovr && $ovr->num_rows > 0) { $o=$ovr->fetch_assoc(); $curShiftIn=$o['start_time']; $curShiftOut=$o['end_time']; $hasDay=true; }
                        $ds->close();
                      }
                    }
                  }

                  $timeIn = $r['time_in'];
                  $timeOut = $r['time_out'];
                  if ($hasDay && $curShiftIn && $curShiftOut) {
                    $reqHours = max(0, (strtotime($curShiftOut) - strtotime($curShiftIn)) / 3600.0);
                    if (!empty($timeIn)) {
                      $tardinessMin = max(0, intval((strtotime($timeIn) - strtotime($curShiftIn)) / 60));
                    }
                    if (!empty($timeOut)) {
                      $undertimeMin = max(0, intval((strtotime($curShiftOut) - strtotime($timeOut)) / 60));
                      $otMin = max(0, intval((strtotime($timeOut) - strtotime($curShiftOut)) / 60));
                    }
                  }
                ?>
                <tr class="hover:bg-blue-50">
                  <td class="px-6 py-3 text-gray-800"><?php echo date('F j, Y', strtotime($r['date'])); ?></td>
                  <td class="px-6 py-3 text-gray-800"><?php echo htmlspecialchars($r['day'] ?: $dayName ?: '-'); ?></td>
                  <td class="px-6 py-3 text-gray-800"><?php echo $curShiftIn ? date('g:i A', strtotime($curShiftIn)) : '--'; ?></td>
                  <td class="px-6 py-3 text-gray-800"><?php echo $curShiftOut ? date('g:i A', strtotime($curShiftOut)) : '--'; ?></td>
                  <td class="px-6 py-3 "><?php echo $timeIn ? date('g:i A', strtotime($timeIn)) : '--'; ?></td>
                  <td class="px-6 py-3 "><?php echo $timeOut ? date('g:i A', strtotime($timeOut)) : '--'; ?></td>
                  <td class="px-6 py-3 "><?php echo number_format($reqHours, 2); ?></td>
                  <td class="px-6 py-3 "><?php echo $tardinessMin; ?> mins</td>
                  <td class="px-6 py-3 "><?php echo $undertimeMin; ?> mins</td>
                  <td class="px-6 py-3 "><?php echo $otMin; ?> mins</td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="10" class="px-6 py-8 text-center text-gray-500">No attendance records found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</body>
</html>
<script>
  // Toggle logout dropdown like other portals
  const empMenuBtn = document.getElementById('empMenuBtn');
  const empDropdown = document.getElementById('empDropdown');
  if (empMenuBtn && empDropdown) {
    empMenuBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      empDropdown.classList.toggle('hidden');
    });
    document.addEventListener('click', (e) => {
      if (!empDropdown.contains(e.target) && !empMenuBtn.contains(e.target)) {
        empDropdown.classList.add('hidden');
      }
    });
  }

  // Date validation and dynamic min/max updates
  const startDate = document.getElementById('start_date');
  const endDate = document.getElementById('end_date');
  const today = '<?= $today ?>';
  
  // Update end date minimum when start date changes
  startDate.addEventListener('change', function() {
      if (this.value) {
          endDate.min = this.value;
          // If end date is before start date, clear it
          if (endDate.value && endDate.value < this.value) {
              endDate.value = '';
          }
      } else {
          endDate.min = '2025-01-01';
      }
  });
  
  // Update start date maximum when end date changes
  endDate.addEventListener('change', function() {
      if (this.value) {
          startDate.max = this.value;
          // If start date is after end date, clear it
          if (startDate.value && startDate.value > this.value) {
              startDate.value = '';
          }
      } else {
          startDate.max = today;
      }
  });
  
  // Initialize min/max based on current values
  if (startDate.value) {
      endDate.min = startDate.value;
  }
  if (endDate.value) {
      startDate.max = endDate.value;
  }
</script>
</html>
