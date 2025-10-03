<?php
session_start();
include("../StudentLogin/db_conn.php");

// Allow logged-in teachers only
$allowed_roles = ['teacher'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles, true)) {
    header("Location: ../StudentLogin/login.php");
    exit;
}

// Prevent caching (so back button after logout doesn't show dashboard)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

// Basic session vars
$teacher_id_number = $_SESSION['id_number'] ?? '';
$teacher_name = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));

if ($teacher_id_number === '') {
    header("Location: ../StudentLogin/login.php");
    exit;
}

date_default_timezone_set('Asia/Manila');
$today = date('Y-m-d');

// Check which column exists in teacher_attendance table
$teacher_id_column = 'teacher_id'; // default
$check_columns = $conn->query("SHOW COLUMNS FROM teacher_attendance LIKE '%id'");
if ($check_columns && $check_columns->num_rows > 0) {
    while ($col = $check_columns->fetch_assoc()) {
        if ($col['Field'] === 'employee_id') {
            $teacher_id_column = 'employee_id';
            break;
        } elseif ($col['Field'] === 'teacher_id') {
            $teacher_id_column = 'teacher_id';
            break;
        }
    }
}

// Get today's attendance record
$attendance_today = null;
$att_query = $conn->prepare("SELECT * FROM teacher_attendance WHERE $teacher_id_column = ? AND date = ? LIMIT 1");
if ($att_query) {
    $att_query->bind_param('ss', $teacher_id_number, $today);
    $att_query->execute();
    $att_result = $att_query->get_result();
    if ($att_result && $att_result->num_rows > 0) {
        $attendance_today = $att_result->fetch_assoc();
    }
    $att_query->close();
}

// Get this week's attendance summary
$week_start = date('Y-m-d', strtotime('monday this week'));
$week_end = date('Y-m-d', strtotime('sunday this week'));

// Get this week's attendance summary
$week_query = $conn->prepare("SELECT COUNT(*) as days_present FROM teacher_attendance WHERE $teacher_id_column = ? AND date BETWEEN ? AND ? AND time_in IS NOT NULL");
$days_present = 0;
if ($week_query) {
    $week_query->bind_param('sss', $teacher_id_number, $week_start, $week_end);
    $week_query->execute();
    $week_result = $week_query->get_result();
    if ($week_result && $week_result->num_rows > 0) {
        $week_data = $week_result->fetch_assoc();
        $days_present = $week_data['days_present'];
    }
    $week_query->close();
}

// Get schedule info
$schedule_info = null;
$schedule_query = $conn->prepare("SELECT ws.start_time, ws.end_time, ws.days 
                                 FROM employees e
                                 LEFT JOIN employee_schedules es ON e.id_number = es.employee_id
                                 LEFT JOIN employee_work_schedules ws ON es.schedule_id = ws.id
                                 WHERE e.id_number = ? LIMIT 1");
if ($schedule_query) {
    $schedule_query->bind_param('s', $teacher_id_number);
    $schedule_query->execute();
    $schedule_result = $schedule_query->get_result();
    if ($schedule_result && $schedule_result->num_rows > 0) {
        $schedule_info = $schedule_result->fetch_assoc();
    }
    $schedule_query->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Teacher Portal - Dashboard</title>
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
        <div class="flex items-center space-x-3">
          <img src="../images/LogoCCI.png" class="h-10 w-10 rounded-full bg-white p-1" alt="Logo">
          <div>
            <h1 class="text-xl font-bold">Teacher Portal</h1>
            <p class="text-blue-200 text-sm">Cornerstone College Inc.</p>
          </div>
        </div>
        <div class="flex items-center space-x-3 relative">
          <div class="text-right leading-tight">
            <div class="text-sm font-bold"><?php echo htmlspecialchars($teacher_name); ?></div>
            <div class="text-[11px] text-blue-200">ID: <?php echo htmlspecialchars($teacher_id_number); ?></div>
          </div>
          <button id="teacherMenuBtn" class="ml-2 bg-white bg-opacity-20 hover:bg-opacity-30 p-2 rounded-lg transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>
          <div id="teacherDropdown" class="hidden absolute right-0 top-12 w-48 bg-white rounded-lg shadow-lg z-50 text-gray-800">
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

  <div class="container mx-auto px-6 py-6">
    <!-- Today's Schedule Card -->
    <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
      <div class="border-l-4 border-blue-600 pl-4 mb-4">
        <h2 class="text-xl font-bold text-gray-800">Today's Schedule</h2>
      </div>
      
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
        <div>
          <label class="block text-sm font-medium text-gray-600 mb-1">Date</label>
          <div class="text-gray-800 font-semibold"><?php echo date('F j, Y'); ?></div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-600 mb-1">Day</label>
          <div class="text-gray-800 font-semibold"><?php echo date('l'); ?></div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-600 mb-1">Work Schedule</label>
          <div class="text-gray-800 font-semibold">
            <?php if ($schedule_info && $schedule_info['start_time'] && $schedule_info['end_time']): ?>
              <?php echo date('g:i A', strtotime($schedule_info['start_time'])); ?> - <?php echo date('g:i A', strtotime($schedule_info['end_time'])); ?>
            <?php else: ?>
              No Schedule
            <?php endif; ?>
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-600 mb-1">Department</label>
          <div class="text-gray-800 font-semibold">
            <?php 
            // Get department from employees table
            $dept_query = $conn->prepare("SELECT department FROM employees WHERE id_number = ? LIMIT 1");
            $department = 'Not Set';
            if ($dept_query) {
                $dept_query->bind_param('s', $teacher_id_number);
                $dept_query->execute();
                $dept_result = $dept_query->get_result();
                if ($dept_result && $dept_result->num_rows > 0) {
                    $dept_data = $dept_result->fetch_assoc();
                    $department = $dept_data['department'] ?: 'Not Set';
                }
                $dept_query->close();
            }
            echo htmlspecialchars($department);
            ?>
          </div>
        </div>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-4 border-t border-gray-200">
        <div>
          <label class="block text-sm font-medium text-gray-600 mb-1">Time In</label>
          <div class="text-gray-800 font-semibold">
            <?php if ($attendance_today && !empty($attendance_today['time_in'])): ?>
              <?php echo date('g:i A', strtotime($attendance_today['time_in'])); ?>
            <?php else: ?>
              --
            <?php endif; ?>
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-600 mb-1">Time Out</label>
          <div class="text-gray-800 font-semibold">
            <?php if ($attendance_today && !empty($attendance_today['time_out'])): ?>
              <?php echo date('g:i A', strtotime($attendance_today['time_out'])); ?>
            <?php else: ?>
              --
            <?php endif; ?>
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-600 mb-1">Status</label>
          <div class="font-semibold">
            <?php if ($attendance_today): ?>
              <?php if (!empty($attendance_today['time_in']) && !empty($attendance_today['time_out'])): ?>
                <span class="text-green-600">Present</span>
              <?php elseif (!empty($attendance_today['time_in'])): ?>
                <span class="text-blue-600">In Progress</span>
              <?php else: ?>
                <span class="text-red-600">No Record</span>
              <?php endif; ?>
            <?php else: ?>
              <span class="text-gray-500">--</span>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
      <!-- This Week Attendance -->
      <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-lg font-semibold text-gray-800">This Week</h3>
            <p class="text-3xl font-bold text-blue-600"><?php echo $days_present; ?>/5</p>
            <p class="text-gray-600 text-sm">Days Present</p>
          </div>
          <div class="bg-blue-100 p-3 rounded-full">
            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Schedule Info -->
      <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between">
          <div>
            <h3 class="text-lg font-semibold text-gray-800">Schedule</h3>
            <?php if ($schedule_info): ?>
              <p class="text-lg font-bold text-green-600">
                <?php echo date('g:i A', strtotime($schedule_info['start_time'])); ?> - 
                <?php echo date('g:i A', strtotime($schedule_info['end_time'])); ?>
              </p>
              <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($schedule_info['days']); ?></p>
            <?php else: ?>
              <p class="text-gray-500">No schedule assigned</p>
            <?php endif; ?>
          </div>
          <div class="bg-green-100 p-3 rounded-full">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h3>
        <div class="space-y-2">
          <a href="AttendanceRecords.php" class="block w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-center transition">
            View Attendance Records
          </a>
        </div>
      </div>
    </div>

    <!-- Attendance Records -->
    <div class="bg-white rounded-2xl shadow-lg p-6">
      <div class="border-l-4 border-blue-600 pl-4 mb-4">
        <h2 class="text-xl font-bold text-gray-800">Attendance Records</h2>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
        <div>
          <label class="block text-sm font-medium text-gray-600 mb-2">Start Date</label>
          <input type="date" id="start_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-600 mb-2">End Date</label>
          <input type="date" id="end_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        </div>
      </div>
      
      <div class="mb-4">
        <button onclick="generateReport()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition">
          Generate Report
        </button>
      </div>
      
      <div class="overflow-x-auto">
        <?php
        // Get recent attendance records (default to last 10 days)
        $recent_query = $conn->prepare("SELECT * FROM teacher_attendance WHERE $teacher_id_column = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 10 DAY) ORDER BY date DESC");
        $recent_records = [];
        if ($recent_query) {
            $recent_query->bind_param('s', $teacher_id_number);
            $recent_query->execute();
            $recent_result = $recent_query->get_result();
            while ($row = $recent_result->fetch_assoc()) {
                $recent_records[] = $row;
            }
            $recent_query->close();
        }
        ?>
        <table class="min-w-full text-sm">
          <thead class="bg-[#0B2C62] text-white">
            <tr>
              <th class="px-6 py-3 text-left font-semibold">Date</th>
              <th class="px-6 py-3 text-left font-semibold">Day</th>
              <th class="px-6 py-3 text-left font-semibold">Work Schedule</th>
              <th class="px-6 py-3 text-left font-semibold">Time In</th>
              <th class="px-6 py-3 text-left font-semibold">Time Out</th>
              <th class="px-6 py-3 text-left font-semibold">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <?php if (!empty($recent_records)): ?>
              <?php foreach ($recent_records as $record): ?>
                <?php
                $dayName = date('l', strtotime($record['date']));
                $workSchedule = 'No Schedule';
                if ($schedule_info && $schedule_info['start_time'] && $schedule_info['end_time']) {
                    $workSchedule = date('g:i A', strtotime($schedule_info['start_time'])) . ' - ' . date('g:i A', strtotime($schedule_info['end_time']));
                }
                ?>
                <tr class="hover:bg-gray-50">
                  <td class="px-6 py-3 text-gray-800"><?php echo date('F j, Y', strtotime($record['date'])); ?></td>
                  <td class="px-6 py-3 text-gray-800"><?php echo $dayName; ?></td>
                  <td class="px-6 py-3 text-gray-800"><?php echo $workSchedule; ?></td>
                  <td class="px-6 py-3">
                    <?php if (!empty($record['time_in'])): ?>
                      <?php echo date('g:i A', strtotime($record['time_in'])); ?>
                    <?php else: ?>
                      --
                    <?php endif; ?>
                  </td>
                  <td class="px-6 py-3">
                    <?php if (!empty($record['time_out'])): ?>
                      <?php echo date('g:i A', strtotime($record['time_out'])); ?>
                    <?php else: ?>
                      --
                    <?php endif; ?>
                  </td>
                  <td class="px-6 py-3">
                    <?php if (!empty($record['time_in']) && !empty($record['time_out'])): ?>
                      <span class="text-green-600 font-medium">Present</span>
                    <?php elseif (!empty($record['time_in'])): ?>
                      <span class="text-blue-600 font-medium">In Progress</span>
                    <?php else: ?>
                      <span class="text-red-600 font-medium">No Schedule</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="px-6 py-8 text-center text-gray-500">No attendance records found.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="mt-4 text-center">
        <a href="AttendanceRecords.php" class="text-blue-600 hover:text-blue-800 font-medium">View All Records →</a>
      </div>
    </div>
  </div>

  <script>
    // Toggle dropdown menu
    const teacherMenuBtn = document.getElementById('teacherMenuBtn');
    const teacherDropdown = document.getElementById('teacherDropdown');
    
    if (teacherMenuBtn && teacherDropdown) {
      teacherMenuBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        teacherDropdown.classList.toggle('hidden');
      });
      
      document.addEventListener('click', (e) => {
        if (!teacherDropdown.contains(e.target) && !teacherMenuBtn.contains(e.target)) {
          teacherDropdown.classList.add('hidden');
        }
      });
    }
    
    // Generate Report function
    function generateReport() {
      const startDate = document.getElementById('start_date').value;
      const endDate = document.getElementById('end_date').value;
      
      if (startDate && endDate) {
        // Redirect to AttendanceRecords.php with date parameters
        window.location.href = `AttendanceRecords.php?start_date=${startDate}&end_date=${endDate}`;
      } else {
        // If no dates selected, just go to AttendanceRecords.php
        window.location.href = 'AttendanceRecords.php';
      }
    }
    
    // ===== PREVENT BACK BUTTON AFTER LOGOUT =====
    window.addEventListener("pageshow", function(event) {
      if (event.persisted || (performance.navigation.type === 2)) window.location.reload();
    });
  </script>
</body>
</html>
