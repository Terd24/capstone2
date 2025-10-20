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
    header("Location: Dashboard.php");
    exit;
}

// Get filters from session or use defaults
$filters = $_SESSION['teacher_attendance_filters'] ?? [];
$start_date = $filters['start_date'] ?? '';
$end_date = $filters['end_date'] ?? '';

// Clear session filters if explicitly requested
if (isset($_GET['clear'])) {
    unset($_SESSION['teacher_attendance_filters']);
    header("Location: Dashboard.php");
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
  <title>Teacher Dashboard - CCI</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="manifest" href="/onecci/manifest.webmanifest">
  <style>
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .fade-in { animation: fadeIn 0.5s ease-out; }
    .stat-card { transition: all 0.3s ease; }
    .stat-card:hover { transform: translateY(-5px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); }
  </style>
  <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => {
        navigator.serviceWorker.register('/onecci/sw.js').catch(console.error);
      });
    }
  </script>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50">
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

  <!-- Main Container -->
  <div class="container mx-auto px-4 sm:px-6 py-6 max-w-7xl">
    
    <!-- Welcome Banner -->
    <div class="bg-gradient-to-r from-[#0B2C62] to-[#1E3A8A] rounded-2xl shadow-xl p-6 sm:p-8 mb-6 fade-in">
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm">
            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
          </div>
          <div>
            <h2 class="text-2xl sm:text-3xl font-bold text-white">Hello, <?php echo htmlspecialchars($employee_name ?: 'Teacher'); ?>!</h2>
            <p class="text-blue-200 text-sm mt-1">Employee ID: <?php echo htmlspecialchars($employee_id_number); ?></p>
          </div>
        </div>
        <div class="bg-white/10 backdrop-blur-sm rounded-xl px-6 py-3 border border-white/20">
          <p class="text-blue-200 text-xs uppercase tracking-wide">Today</p>
          <p class="text-white font-semibold text-lg"><?php echo date('F j, Y', strtotime($today)); ?></p>
          <p class="text-blue-200 text-sm"><?php echo date('l', strtotime($today)); ?></p>
        </div>
      </div>
    </div>

    <?php
    // Calculate attendance statistics
    $stats_sql = "SELECT 
                    COUNT(*) as total_days,
                    SUM(CASE WHEN time_in IS NOT NULL THEN 1 ELSE 0 END) as present_days,
                    SUM(CASE WHEN time_in IS NULL THEN 1 ELSE 0 END) as absent_days
                  FROM teacher_attendance 
                  WHERE $employee_id_column = ? 
                  AND MONTH(date) = MONTH(CURDATE()) 
                  AND YEAR(date) = YEAR(CURDATE())";
    $stats_stmt = $conn->prepare($stats_sql);
    $stats_stmt->bind_param('s', $employee_id_number);
    $stats_stmt->execute();
    $stats = $stats_stmt->get_result()->fetch_assoc();
    $attendance_rate = $stats['total_days'] > 0 ? round(($stats['present_days'] / $stats['total_days']) * 100, 1) : 0;
    ?>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-6">
      <!-- Attendance Rate -->
      <div class="stat-card bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-500 text-sm font-medium uppercase tracking-wide">Attendance Rate</p>
            <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo $attendance_rate; ?>%</p>
            <p class="text-xs text-gray-500 mt-1">This month</p>
          </div>
          <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
        </div>
      </div>

      <!-- Present Days -->
      <div class="stat-card bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-500 text-sm font-medium uppercase tracking-wide">Present Days</p>
            <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo $stats['present_days']; ?></p>
            <p class="text-xs text-gray-500 mt-1">This month</p>
          </div>
          <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
          </div>
        </div>
      </div>

      <!-- Absent Days -->
      <div class="stat-card bg-white rounded-xl shadow-lg p-6 border-l-4 border-red-500">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-500 text-sm font-medium uppercase tracking-wide">Absent Days</p>
            <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo $stats['absent_days']; ?></p>
            <p class="text-xs text-gray-500 mt-1">This month</p>
          </div>
          <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </div>
        </div>
      </div>

      <!-- Total Records -->
      <div class="stat-card bg-white rounded-xl shadow-lg p-6 border-l-4 border-purple-500">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-gray-500 text-sm font-medium uppercase tracking-wide">Total Records</p>
            <p class="text-3xl font-bold text-gray-800 mt-2"><?php echo $stats['total_days']; ?></p>
            <p class="text-xs text-gray-500 mt-1">This month</p>
          </div>
          <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
      <div class="flex items-center gap-3 mb-6">
        <div class="w-10 h-10 bg-[#0B2C62] rounded-lg flex items-center justify-center">
          <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
          </svg>
        </div>
        <h3 class="text-xl font-bold text-gray-800">Quick Actions</h3>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php if (($_SESSION['role'] ?? '') === 'teacher'): ?>
          <a href="ManageGrades.php" class="group flex items-center gap-4 bg-gradient-to-r from-[#1E3A8A] to-[#0B2C62] hover:from-[#0B2C62] hover:to-[#1E3A8A] text-white px-6 py-4 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-300">
            <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
              </svg>
            </div>
            <div>
              <p class="font-bold text-lg">Manage Grades</p>
              <p class="text-sm text-blue-200">View and update student grades</p>
            </div>
          </a>
        <?php endif; ?>
      </div>
    </div>

    <?php
    // Fetch teacher's schedule with day-specific schedules
    $schedule_query = "SELECT ws.*, ws.schedule_name as section_name
                       FROM employees e
                       LEFT JOIN employee_schedules es ON e.id_number = es.employee_id
                       LEFT JOIN employee_work_schedules ws ON es.schedule_id = ws.id
                       WHERE e.id_number = ? LIMIT 1";
    $schedule_stmt = $conn->prepare($schedule_query);
    $schedule_stmt->bind_param('s', $employee_id_number);
    $schedule_stmt->execute();
    $schedule_result = $schedule_stmt->get_result();
    $teacher_schedule = $schedule_result->fetch_assoc();
    
    // Fetch day-specific schedules if they exist
    $day_schedules = [];
    if ($teacher_schedule && isset($teacher_schedule['id'])) {
        $day_query = "SELECT * FROM employee_work_day_schedules WHERE schedule_id = ? ORDER BY FIELD(day_name, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')";
        $day_stmt = $conn->prepare($day_query);
        $day_stmt->bind_param('i', $teacher_schedule['id']);
        $day_stmt->execute();
        $day_result = $day_stmt->get_result();
        while ($day_row = $day_result->fetch_assoc()) {
            $day_schedules[$day_row['day_name']] = $day_row;
        }
    }
    ?>

    <!-- My Schedule Section -->
    <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
      <div class="flex items-center gap-3 mb-6">
        <div class="w-10 h-10 bg-[#0B2C62] rounded-lg flex items-center justify-center">
          <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
          </svg>
        </div>
        <div>
          <h3 class="text-xl font-bold text-gray-800">My Schedule</h3>
          <p class="text-sm text-gray-500">Your weekly teaching schedule</p>
        </div>
      </div>

      <?php if ($teacher_schedule): ?>
        <?php if (!empty($day_schedules)): ?>
          <!-- Variable Schedule (Different times per day) -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php 
            $days_order = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            foreach ($days_order as $day): 
              if (isset($day_schedules[$day])):
                $day_info = $day_schedules[$day];
            ?>
              <div class="bg-white border-2 border-gray-200 rounded-xl p-4 hover:border-[#0B2C62] transition-all">
                <div class="flex items-center gap-2 mb-3">
                  <div class="w-8 h-8 bg-[#0B2C62] rounded-lg flex items-center justify-center">
                    <span class="text-white font-bold text-sm"><?php echo substr($day, 0, 1); ?></span>
                  </div>
                  <h5 class="font-bold text-gray-800"><?php echo $day; ?></h5>
                </div>
                <div class="space-y-2">
                  <div class="flex items-center gap-2 text-sm">
                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-gray-600">Start:</span>
                    <span class="font-semibold text-gray-800"><?php echo date('g:i A', strtotime($day_info['start_time'])); ?></span>
                  </div>
                  <div class="flex items-center gap-2 text-sm">
                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-gray-600">End:</span>
                    <span class="font-semibold text-gray-800"><?php echo date('g:i A', strtotime($day_info['end_time'])); ?></span>
                  </div>
                </div>
              </div>
            <?php 
              endif;
            endforeach; 
            ?>
          </div>
        <?php else: ?>
          <!-- Fixed Schedule (Same time for all days) -->
          <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl p-6 border border-green-200">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center">
                  <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                </div>
                <div>
                  <p class="text-sm text-gray-600 font-medium">Start Time</p>
                  <p class="text-xl font-bold text-gray-800"><?php echo date('g:i A', strtotime($teacher_schedule['start_time'])); ?></p>
                </div>
              </div>
              <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-red-600 rounded-lg flex items-center justify-center">
                  <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                </div>
                <div>
                  <p class="text-sm text-gray-600 font-medium">End Time</p>
                  <p class="text-xl font-bold text-gray-800"><?php echo date('g:i A', strtotime($teacher_schedule['end_time'])); ?></p>
                </div>
              </div>
              <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center">
                  <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                  </svg>
                </div>
                <div>
                  <p class="text-sm text-gray-600 font-medium">Working Days</p>
                  <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($teacher_schedule['days']); ?></p>
                </div>
              </div>
            </div>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 rounded-lg p-6">
          <div class="flex items-center gap-3">
            <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div>
              <p class="text-lg font-semibold text-yellow-800">No Schedule Assigned</p>
              <p class="text-sm text-yellow-700 mt-1">Please contact HR to have your teaching schedule assigned.</p>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- Attendance Records Section -->
    <div id="attendance" class="bg-white rounded-2xl shadow-lg p-6">
      <div class="flex items-center gap-3 mb-6">
        <div class="w-10 h-10 bg-[#0B2C62] rounded-lg flex items-center justify-center">
          <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
          </svg>
        </div>
        <div>
          <h3 class="text-xl font-bold text-gray-800">Attendance Records</h3>
          <p class="text-sm text-gray-500">View and filter your attendance history</p>
        </div>
      </div>

      <!-- Filter Form -->
      <form method="post" id="filterForm" class="bg-gray-50 rounded-xl p-6 mb-6 border border-gray-200">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
          <div class="md:col-span-4">
            <label class="text-sm font-semibold text-gray-700 mb-2 block flex items-center gap-2">
              <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
              Start Date
            </label>
            <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($start_date); ?>" min="2025-01-01" max="<?= $today ?>" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62] transition-all">
          </div>
          <div class="md:col-span-4">
            <label class="text-sm font-semibold text-gray-700 mb-2 block flex items-center gap-2">
              <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
              End Date
            </label>
            <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($end_date); ?>" min="2025-01-01" max="<?= $today ?>" class="w-full border-2 border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-[#0B2C62] focus:border-[#0B2C62] transition-all">
          </div>
          <div class="md:col-span-4 flex gap-2">
            <button type="submit" class="flex-1 bg-[#0B2C62] hover:bg-blue-900 text-white px-6 py-2.5 rounded-lg font-semibold text-sm whitespace-nowrap transition-all shadow-md hover:shadow-lg flex items-center justify-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              Generate
            </button>
            <a href="Dashboard.php?clear=1" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white px-6 py-2.5 rounded-lg font-semibold text-sm whitespace-nowrap transition-all shadow-md hover:shadow-lg flex items-center justify-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
              Clear
            </a>
          </div>
        </div>
      </form>

      <!-- Records Info Banner -->
      <div class="bg-blue-50 border-l-4 border-[#0B2C62] rounded-lg p-4 mb-6">
        <div class="flex items-center gap-3">
          <svg class="w-5 h-5 text-[#0B2C62]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <p class="text-gray-700 font-medium">
            <?php 
            if ($start_date !== '' && $end_date !== '') {
                echo "Showing attendance records from <span class='font-bold text-[#0B2C62]'>" . date('F j, Y', strtotime($start_date)) . "</span> to <span class='font-bold text-[#0B2C62]'>" . date('F j, Y', strtotime($end_date)) . "</span>";
            } else {
                echo "Showing attendance records for <span class='font-bold text-[#0B2C62]'>" . date('F j, Y', strtotime($today)) . "</span>";
            }
            ?>
            <span class="ml-2 text-sm text-gray-600">(<?php echo $total_records; ?> record<?php echo $total_records != 1 ? 's' : ''; ?>)</span>
          </p>
        </div>
      </div>

      <!-- Attendance Table -->
      <div class="overflow-x-auto rounded-xl border border-gray-200">
        <table class="min-w-full text-sm">
          <thead class="bg-gradient-to-r from-[#0B2C62] to-[#1E3A8A] text-white">
            <tr>
              <th class="px-4 py-4 text-left font-semibold">Date</th>
              <th class="px-4 py-4 text-left font-semibold">Day</th>
              <th class="px-4 py-4 text-left font-semibold">Shift In</th>
              <th class="px-4 py-4 text-left font-semibold">Shift Out</th>
              <th class="px-4 py-4 text-left font-semibold">Time In</th>
              <th class="px-4 py-4 text-left font-semibold">Time Out</th>
              <th class="px-4 py-4 text-left font-semibold">Required Hrs</th>
              <th class="px-4 py-4 text-left font-semibold">Tardiness</th>
              <th class="px-4 py-4 text-left font-semibold">Undertime</th>
              <th class="px-4 py-4 text-left font-semibold">OT</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white">
            <?php if ($records && $records->num_rows > 0): ?>
              <?php $row_num = 0; while ($r = $records->fetch_assoc()): $row_num++; ?>
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
                  
                  $row_class = $row_num % 2 == 0 ? 'bg-gray-50' : 'bg-white';
                ?>
                <tr class="<?php echo $row_class; ?> hover:bg-blue-50 transition-colors">
                  <td class="px-4 py-4 text-gray-800 font-medium"><?php echo date('M j, Y', strtotime($r['date'])); ?></td>
                  <td class="px-4 py-4">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                      <?php echo htmlspecialchars($r['day'] ?: $dayName ?: '-'); ?>
                    </span>
                  </td>
                  <td class="px-4 py-4 text-gray-700"><?php echo $curShiftIn ? date('g:i A', strtotime($curShiftIn)) : '<span class="text-gray-400">--</span>'; ?></td>
                  <td class="px-4 py-4 text-gray-700"><?php echo $curShiftOut ? date('g:i A', strtotime($curShiftOut)) : '<span class="text-gray-400">--</span>'; ?></td>
                  <td class="px-4 py-4">
                    <?php if ($timeIn): ?>
                      <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-green-100 text-green-800">
                        <?php echo date('g:i A', strtotime($timeIn)); ?>
                      </span>
                    <?php else: ?>
                      <span class="text-gray-400">--</span>
                    <?php endif; ?>
                  </td>
                  <td class="px-4 py-4">
                    <?php if ($timeOut): ?>
                      <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-purple-100 text-purple-800">
                        <?php echo date('g:i A', strtotime($timeOut)); ?>
                      </span>
                    <?php else: ?>
                      <span class="text-gray-400">--</span>
                    <?php endif; ?>
                  </td>
                  <td class="px-4 py-4 text-gray-700 font-medium"><?php echo number_format($reqHours, 2); ?> hrs</td>
                  <td class="px-4 py-4">
                    <?php if ($tardinessMin > 0): ?>
                      <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-red-100 text-red-800">
                        <?php echo $tardinessMin; ?> min
                      </span>
                    <?php else: ?>
                      <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-green-100 text-green-800">
                        On Time
                      </span>
                    <?php endif; ?>
                  </td>
                  <td class="px-4 py-4">
                    <?php if ($undertimeMin > 0): ?>
                      <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-orange-100 text-orange-800">
                        <?php echo $undertimeMin; ?> min
                      </span>
                    <?php else: ?>
                      <span class="text-gray-400">--</span>
                    <?php endif; ?>
                  </td>
                  <td class="px-4 py-4">
                    <?php if ($otMin > 0): ?>
                      <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-blue-100 text-blue-800">
                        <?php echo $otMin; ?> min
                      </span>
                    <?php else: ?>
                      <span class="text-gray-400">--</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="10" class="px-6 py-12 text-center">
                  <div class="flex flex-col items-center justify-center">
                    <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-gray-500 text-lg font-medium">No attendance records found</p>
                    <p class="text-gray-400 text-sm mt-1">Try adjusting your date filters</p>
                  </div>
                </td>
              </tr>
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
