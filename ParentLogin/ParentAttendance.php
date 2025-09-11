<?php
session_start();
include('../StudentLogin/db_conn.php');

if (!isset($_SESSION['parent_id']) || !isset($_SESSION['child_id'])) {
    header("Location: ../StudentLogin/login.php");
    exit();
}

// Prevent browser from caching this page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

$id_number = $_SESSION['child_id'];

// Get student's schedule information including day-specific schedules
date_default_timezone_set('Asia/Manila');
$today = date("Y-m-d");
$current_day = date("l");
$current_time = date("H:i:s");

$schedule_info_query = $conn->prepare("
    SELECT sa.class_schedule, cs.section_name, cs.start_time, cs.end_time, cs.days, cs.id as schedule_id
    FROM student_account sa
    LEFT JOIN student_schedules ss ON sa.id_number = ss.student_id
    LEFT JOIN class_schedules cs ON ss.schedule_id = cs.id
    WHERE sa.id_number = ?
");
$schedule_info_query->bind_param("s", $id_number);
$schedule_info_query->execute();
$schedule_info = $schedule_info_query->get_result()->fetch_assoc();

// Get day-specific schedule for today if exists
$today_specific_schedule = null;
if ($schedule_info && $schedule_info['schedule_id']) {
    $day_schedule_query = $conn->prepare("SELECT start_time, end_time FROM day_schedules WHERE schedule_id = ? AND day_name = ?");
    $day_schedule_query->bind_param("is", $schedule_info['schedule_id'], $current_day);
    $day_schedule_query->execute();
    $today_specific_schedule = $day_schedule_query->get_result()->fetch_assoc();
}

// Check if student has class today
$has_class_today = false;
$today_schedule = null;
$effective_start_time = null;
$effective_end_time = null;

if ($schedule_info && $schedule_info['days']) {
    $class_days = explode(',', $schedule_info['days']);
    $has_class_today = in_array($current_day, array_map('trim', $class_days));
    
    if ($has_class_today) {
        // Use day-specific schedule if available, otherwise use default schedule
        if ($today_specific_schedule) {
            $effective_start_time = $today_specific_schedule['start_time'];
            $effective_end_time = $today_specific_schedule['end_time'];
        } else {
            $effective_start_time = $schedule_info['start_time'];
            $effective_end_time = $schedule_info['end_time'];
        }
        
        $today_schedule = $schedule_info['section_name'] . " (" . 
                         date('g:i A', strtotime($effective_start_time)) . " - " . 
                         date('g:i A', strtotime($effective_end_time)) . ")";
    }
}

// Check today's attendance record
$today_attendance_query = $conn->prepare("SELECT * FROM attendance_record WHERE id_number = ? AND date = ?");
$today_attendance_query->bind_param("ss", $id_number, $today);
$today_attendance_query->execute();
$today_attendance = $today_attendance_query->get_result()->fetch_assoc();

$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;

$sql = "SELECT ar.*, cs.start_time, cs.end_time, cs.days, cs.id as schedule_id
        FROM attendance_record ar
        LEFT JOIN student_schedules ss ON ar.id_number = ss.student_id
        LEFT JOIN class_schedules cs ON ss.schedule_id = cs.id
        WHERE ar.id_number = ?";
$params = [$id_number];

if ($startDate && $endDate) {
    $sql .= " AND ar.date BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
}

$sql .= " ORDER BY ar.date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param(str_repeat("s", count($params)), ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Attendance - Cornerstone College Inc.</title>
  <link rel="icon" type="image/png" href="../images/LogoCCI.png">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            'cci-blue': '#1e3a8a',
            'cci-light-blue': '#3b82f6',
            'cci-accent': '#1e40af'
          }
        }
      }
    }
  </script>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">

  <!-- Header with School Branding -->
  <header class="bg-[#0B2C62] text-white shadow-lg">
    <div class="container mx-auto px-6 py-4">
      <div class="flex justify-between items-center">
        <div class="flex items-center space-x-4">
          <button onclick="window.location.replace('ParentDashboard.php')" 
                  class="bg-white bg-opacity-20 hover:bg-opacity-30 p-3 rounded-xl transition-all duration-200 text-white">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
          </button>
          <div class="text-left">
            <p class="text-sm text-blue-200">Parent Portal</p>
            <p class="font-semibold">Attendance Records</p>
          </div>
        </div>
        
        <div class="flex items-center space-x-4">
          <img src="../images/LogoCCI.png" alt="Cornerstone College Inc." class="h-12 w-12 rounded-full bg-white p-1">
          <div class="text-right">
            <h1 class="text-xl font-bold">Cornerstone College Inc.</h1>
            <p class="text-blue-200 text-sm">Parent Portal</p>
          </div>
        </div>
      </div>
    </div>
  </header>

  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Today's Schedule Card -->
    <?php if ($has_class_today): ?>
    <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
      <div class="flex items-center mb-6">
        <div class="w-1 h-8 bg-[#0B2C62] rounded-full mr-4"></div>
        <h2 class="text-xl font-bold text-gray-800">Today's Schedule</h2>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-blue-50 rounded-lg p-4 border border-blue-100">
          <p class="text-sm text-gray-600 mb-1">Date</p>
          <p class="text-lg font-bold text-gray-800"><?= date('F j, Y') ?></p>
        </div>
        <div class="bg-blue-50 rounded-lg p-4 border border-blue-100">
          <p class="text-sm text-gray-600 mb-1">Day</p>
          <p class="text-lg font-bold text-gray-800"><?= $current_day ?></p>
        </div>
        <div class="bg-blue-50 rounded-lg p-4 border border-blue-100">
          <p class="text-sm text-gray-600 mb-1">Class Schedule</p>
          <p class="text-lg font-bold text-gray-800">
            <?= date('g:i A', strtotime($effective_start_time)) ?> - <?= date('g:i A', strtotime($effective_end_time)) ?>
          </p>
        </div>
        <div class="bg-blue-50 rounded-lg p-4 border border-blue-100">
          <p class="text-sm text-gray-600 mb-1">Section</p>
          <p class="text-lg font-bold text-gray-800"><?= htmlspecialchars($schedule_info['section_name'] ?? 'N/A') ?></p>
        </div>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
        <div class="bg-blue-50 rounded-lg p-4 border border-blue-100">
          <p class="text-sm text-gray-600 mb-1">Time In</p>
          <p class="text-xl font-bold text-gray-800">
            <?= $today_attendance && $today_attendance['time_in'] ? date('g:i A', strtotime($today_attendance['time_in'])) : '--' ?>
          </p>
        </div>
        <div class="bg-blue-50 rounded-lg p-4 border border-blue-100">
          <p class="text-sm text-gray-600 mb-1">Time Out</p>
          <p class="text-xl font-bold text-gray-800">
            <?= $today_attendance && $today_attendance['time_out'] ? date('g:i A', strtotime($today_attendance['time_out'])) : '--' ?>
          </p>
        </div>
        <div class="bg-blue-50 rounded-lg p-4 border border-blue-100">
          <p class="text-sm text-gray-600 mb-1">Status</p>
          <p class="text-xl font-bold <?php 
            if ($today_attendance) {
                $status = $today_attendance['status'];
                if ($status === 'Present') {
                    echo 'text-green-600';
                } elseif ($status === 'Time In Only' || $status === 'Time In On') {
                    echo 'text-blue-600';
                } elseif ($status === 'Absent') {
                    echo 'text-red-600';
                } else {
                    echo 'text-gray-600';
                }
            } else {
                echo 'text-gray-500';
            }
            ?>">
            <?php 
            if ($today_attendance) {
                $status = $today_attendance['status'];
                echo htmlspecialchars($status);
            } else {
                echo '--';
            }
            ?>
          </p>
        </div>
      </div>
    </div>
    <?php else: ?>
    <div class="bg-white rounded-2xl shadow-lg p-8 mb-8 text-center">
      <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
        </svg>
      </div>
      <h2 class="text-xl font-bold text-gray-600 mb-2">No Class Today</h2>
      <p class="text-gray-500"><?= date('F j, Y') ?> - <?= $current_day ?></p>
    </div>
    <?php endif; ?>
    
    <!-- Attendance Records -->
    <div class="bg-white rounded-2xl shadow-lg">
      <div class="p-6 border-b border-gray-100">
        <div class="flex items-center mb-6">
          <div class="w-1 h-8 bg-[#0B2C62] rounded-full mr-4"></div>
          <h2 class="text-xl font-bold text-gray-800">Attendance Records</h2>
        </div>
        
        <form method="get" class="flex flex-col md:flex-row gap-4 items-end">
          <div class="flex-1">
            <label for="start-date" class="text-sm font-medium text-gray-700 block mb-2">Start Date</label>
            <input type="date" id="start-date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" 
                   class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-[#0B2C62] focus:border-transparent transition-all duration-200">
          </div>
          <div class="flex-1">
            <label for="end-date" class="text-sm font-medium text-gray-700 block mb-2">End Date</label>
            <input type="date" id="end-date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" 
                   class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-[#0B2C62] focus:border-transparent transition-all duration-200">
          </div>
          <div>
            <button type="submit" class="bg-[#0B2C62] text-white px-6 py-3 rounded-lg hover:bg-blue-900 transition-all duration-200 font-medium">
              Generate Report
            </button>
          </div>
        </form>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-[#0B2C62] text-white">
            <tr>
              <th class="px-6 py-4 text-left font-semibold">Date</th>
              <th class="px-6 py-4 text-left font-semibold">Day</th>
              <th class="px-6 py-4 text-left font-semibold">Class Schedule</th>
              <th class="px-6 py-4 text-left font-semibold">Time In</th>
              <th class="px-6 py-4 text-left font-semibold">Time Out</th>
              <th class="px-6 py-4 text-left font-semibold">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <?php if ($result->num_rows > 0): ?>
              <?php while($row = $result->fetch_assoc()): ?>
                <tr class="bg-blue-50 hover:bg-blue-100 transition-colors duration-150">
                  <td class="px-6 py-4 font-medium text-gray-900"><?= htmlspecialchars(date('F j, Y', strtotime($row['date']))) ?></td>
                  <td class="px-6 py-4 text-gray-700"><?= htmlspecialchars($row['day']) ?></td>
                  <td class="px-6 py-4 text-gray-700">
                    <?php 
                    // Check if student has class on this day
                    $record_day = $row['day'];
                    $has_class_on_day = false;
                    $display_start_time = $row['start_time'];
                    $display_end_time = $row['end_time'];
                    
                    if ($row['days']) {
                        $class_days = explode(',', $row['days']);
                        $has_class_on_day = in_array($record_day, array_map('trim', $class_days));
                    }
                    
                    // Check for day-specific schedule times
                    if ($has_class_on_day && $row['schedule_id']) {
                        $day_schedule_query = $conn->prepare("SELECT start_time, end_time FROM day_schedules WHERE schedule_id = ? AND day_name = ?");
                        $day_schedule_query->bind_param("is", $row['schedule_id'], $record_day);
                        $day_schedule_query->execute();
                        $day_schedule_result = $day_schedule_query->get_result();
                        
                        if ($day_schedule_result->num_rows > 0) {
                            $day_schedule = $day_schedule_result->fetch_assoc();
                            $display_start_time = $day_schedule['start_time'];
                            $display_end_time = $day_schedule['end_time'];
                        }
                    }
                    
                    if ($has_class_on_day && $display_start_time && $display_end_time) {
                        echo date('g:i A', strtotime($display_start_time)) . ' - ' . date('g:i A', strtotime($display_end_time));
                    } else {
                        echo 'No Class Schedule';
                    }
                    ?>
                  </td>
                  <td class="px-6 py-4 text-gray-700">
                    <?= $row['time_in'] ? date("h:i A", strtotime($row['time_in'])) : '--' ?>
                  </td>
                  <td class="px-6 py-4 text-gray-700">
                    <?= $row['time_out'] ? date("h:i A", strtotime($row['time_out'])) : '--' ?>
                  </td>
                  <td class="px-6 py-4 text-gray-700">
                    <?php 
                    // Show status with color coding
                    if (!$has_class_on_day) {
                        echo '<span class="text-gray-600">No Schedule</span>';
                    } else {
                        // Always use the database status, don't calculate it
                        $status = $row['status'];
                        if ($status === 'Present') {
                            echo '<span class="text-green-600 font-medium">Present</span>';
                        } elseif ($status === 'Time In Only' || $status === 'Time In On') {
                            echo '<span class="text-blue-600 font-medium">' . htmlspecialchars($status) . '</span>';
                        } elseif ($status === 'Absent') {
                            echo '<span class="text-red-600 font-medium">Absent</span>';
                        } else {
                            // Fallback for any other status
                            echo '<span class="text-gray-600 font-medium">' . htmlspecialchars($status) . '</span>';
                        }
                    }
                    ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="px-6 py-12 text-center">
                  <div class="flex flex-col items-center">
                    <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <p class="text-gray-500 font-medium">No attendance records found</p>
                    <p class="text-gray-400 text-sm mt-1">Try adjusting your date range</p>
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
