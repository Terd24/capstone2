<?php
session_start();
include '../StudentLogin/db_conn.php';
// Ensure we have today's date early for queries that run before the later timezone block
date_default_timezone_set('Asia/Manila');
$today = date('Y-m-d');

// Handle AJAX requests
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $ajax_request = true;
} else {
    $ajax_request = false;
}

// Latest employee for today (if someone just tapped)
$latest_employee = null;
if (isset($_SESSION['show_latest_employee']) && $_SESSION['show_latest_employee']) {
    $latest_employee_query = $conn->prepare("\n        SELECT ea.*, e.first_name, e.last_name,\n               GREATEST(\n                   COALESCE(UNIX_TIMESTAMP(CONCAT(ea.date, ' ', ea.time_in)), 0),\n                   COALESCE(UNIX_TIMESTAMP(CONCAT(ea.date, ' ', ea.time_out)), 0)\n               ) as last_activity\n        FROM employee_attendance ea\n        JOIN employees e ON ea.employee_id = e.id_number\n        WHERE ea.date = ? \n        ORDER BY last_activity DESC, ea.id DESC\n        LIMIT 1\n    ");
    $latest_employee_query->bind_param("s", $today);
    $latest_employee_query->execute();
    $latest_employee_result = $latest_employee_query->get_result();
    $latest_employee = $latest_employee_result->num_rows > 0 ? $latest_employee_result->fetch_assoc() : null;
}

// Handle clearing the student/employee display flags
if (isset($_GET['clear_student'])) {
    unset($_SESSION['show_latest_student']);
    unset($_SESSION['show_latest_employee']);
    unset($_SESSION['view_role']);
    unset($_SESSION['last_employee_id']);
    unset($_SESSION['success']);
    unset($_SESSION['error']);
    exit();
}

// Check if user is logged in and has appropriate role (registrar, admin, etc.)
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['registrar', 'admin', 'attendance'])) {
    header("Location: ../login.php");
    exit();
}
// Handle RFID scanning
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rfid'])) {
    $rfid = strtoupper(trim($_POST['rfid']));
    
    // First, try EMPLOYEE by RFID (any role). We use the employee_attendance table for employees.
    $employee_query = $conn->prepare("
        SELECT e.id_number, e.first_name, e.last_name, TRIM(UPPER(e.rfid_uid)) AS db_rfid
        FROM employees e
        WHERE TRIM(UPPER(e.rfid_uid)) = ?
    ");
    $employee_query->bind_param("s", $rfid);
    $employee_query->execute();
    $employee_result = $employee_query->get_result();
    if ($employee_result && $employee_result->num_rows > 0) {
        $employee = $employee_result->fetch_assoc();
        $emp_id = $employee['id_number'];
        $employee_name = trim($employee['first_name'] . ' ' . $employee['last_name']);

        date_default_timezone_set('Asia/Manila');
        $today = date("Y-m-d");
        $time = date("H:i:s");
        $day = date("l");

        // Time in/out for employee_attendance
        $t_check = $conn->prepare("SELECT * FROM employee_attendance WHERE employee_id = ? AND date = ? LIMIT 1");
        $t_check->bind_param("ss", $emp_id, $today);
        $t_check->execute();
        $t_res = $t_check->get_result();
        if ($t_res && $t_res->num_rows === 1) {
            $row = $t_res->fetch_assoc();
            if (empty($row['time_in'])) {
                // No time in yet -> record time in
                $upd = $conn->prepare("UPDATE employee_attendance SET time_in = ? WHERE id = ?");
                $upd->bind_param("si", $time, $row['id']);
                $upd->execute();
                $_SESSION['success'] = "Time In recorded for $employee_name at " . date('g:i A', strtotime($time));
            } else {
                // Has time in -> always update time out to latest tap (mirror student behavior)
                $upd = $conn->prepare("UPDATE employee_attendance SET time_out = ? WHERE id = ?");
                $upd->bind_param("si", $time, $row['id']);
                $upd->execute();
                $_SESSION['success'] = "Time Out recorded for $employee_name at " . date('g:i A', strtotime($time));
            }
        } else {
            $ins = $conn->prepare("INSERT INTO employee_attendance (employee_id, date, day, time_in) VALUES (?,?,?,?)");
            $ins->bind_param("ssss", $emp_id, $today, $day, $time);
            $ins->execute();
            $_SESSION['success'] = "Time In recorded for $employee_name at " . date('g:i A', strtotime($time));
        }

        $_SESSION['show_latest_employee'] = true;
        $_SESSION['view_role'] = 'employee';
        $_SESSION['last_employee_id'] = $emp_id;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    // Otherwise, find STUDENT by RFID
    $student_query = $conn->prepare("
        SELECT id_number, first_name, last_name, middle_name, TRIM(UPPER(rfid_uid)) AS db_rfid
        FROM student_account
        WHERE TRIM(UPPER(rfid_uid)) = ?
    ");
    $student_query->bind_param("s", $rfid);
    $student_query->execute();
    $student_result = $student_query->get_result();
    
    if ($student_result->num_rows === 0) {
        $_SESSION['error'] = "RFID card not registered to any student or employee.";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    $student = $student_result->fetch_assoc();
    $id_number = $student['id_number'];
    $student_name = trim($student['first_name'] . ' ' . $student['middle_name'] . ' ' . $student['last_name']);
    
    date_default_timezone_set('Asia/Manila');
    $today = date("Y-m-d");
    $time = date("H:i:s");
    $day = date("l");
    $status = "Present";
    
    // Get student's assigned schedule
    $schedule_query = $conn->prepare("SELECT class_schedule FROM student_account WHERE id_number = ?");
    $schedule_query->bind_param("s", $id_number);
    $schedule_query->execute();
    $schedule_result = $schedule_query->get_result();
    $student_schedule = null;
    
    if ($schedule_result->num_rows > 0) {
        $schedule_row = $schedule_result->fetch_assoc();
        $student_schedule = $schedule_row['class_schedule'];
    }
    
    // Check if student already has attendance record for today
    $check = $conn->prepare("SELECT * FROM attendance_record WHERE id_number = ? AND date = ?");
    $check->bind_param("ss", $id_number, $today);
    $check->execute();
    $res = $check->get_result();
    
    if ($res->num_rows === 0) {
        // Time In - Record time regardless of existing status
        $insert = $conn->prepare("INSERT INTO attendance_record (id_number, date, day, time_in, status, schedule) VALUES (?, ?, ?, ?, ?, ?)");
        $status = 'Time In Only';
        $insert->bind_param("ssssss", $id_number, $today, $day, $time, $status, $student_schedule);
        $insert->execute();
        $_SESSION['success'] = "Time In recorded for " . $student_name . " at " . date('g:i A', strtotime($time));
        $_SESSION['show_latest_student'] = true; // Flag to show student info
        $_SESSION['view_role'] = 'student';
    } else {
        // Student already has a record - check if we need time_in or time_out
        $record = $res->fetch_assoc();
        $existing_status = $record['status'];
        
        if (!$record['time_in']) {
            // No time_in yet - record this tap as time_in
            // Absent status is IMMUTABLE - never change it
            if ($existing_status === 'Absent') {
                // For absent students, only update time_in and schedule, DO NOT touch status
                $update = $conn->prepare("UPDATE attendance_record SET time_in = ?, schedule = ? WHERE id = ?");
                $update->bind_param("ssi", $time, $student_schedule, $record['id']);
            } else {
                // For normal students, update to 'Time In Only'
                $update = $conn->prepare("UPDATE attendance_record SET time_in = ?, status = ?, schedule = ? WHERE id = ?");
                $status = 'Time In Only';
                $update->bind_param("sssi", $time, $status, $student_schedule, $record['id']);
            }
            
            $update->execute();
            $_SESSION['success'] = "Time In recorded for " . $student_name . " at " . date('g:i A', strtotime($time));
            $_SESSION['show_latest_student'] = true;
            $_SESSION['view_role'] = 'student';
        } else {
            // Already has time_in - record this tap as time_out
            // Absent status is IMMUTABLE - never change it
            if ($existing_status === 'Absent') {
                // Only update time_out, DO NOT touch status
                $update = $conn->prepare("UPDATE attendance_record SET time_out = ?, schedule = ? WHERE id = ?");
                $update->bind_param("ssi", $time, $student_schedule, $record['id']);
            } else {
                // Normal students can change to Present
                $update = $conn->prepare("UPDATE attendance_record SET time_out = ?, status = ?, schedule = ? WHERE id = ?");
                $status = 'Present';
                $update->bind_param("sssi", $time, $status, $student_schedule, $record['id']);
            }
            
            $update->execute();
            $_SESSION['success'] = "Time Out recorded for " . $student_name . " at " . date('g:i A', strtotime($time));
            $_SESSION['show_latest_student'] = true;
            $_SESSION['view_role'] = 'student';
        }
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Get today's attendance records
date_default_timezone_set('Asia/Manila');
$today = date("Y-m-d");
$current_time = date("H:i:s");
$current_day = date("l");

// Auto-mark absent students who have class today but haven't tapped in
$auto_absent_query = $conn->prepare("
    SELECT sa.id_number, sa.first_name, sa.last_name, sa.middle_name, sa.class_schedule,
           cs.start_time, cs.end_time, cs.days, cs.section_name, cs.id as schedule_id
    FROM student_account sa
    LEFT JOIN student_schedules ss ON sa.id_number = ss.student_id
    LEFT JOIN class_schedules cs ON ss.schedule_id = cs.id
    LEFT JOIN attendance_record ar ON sa.id_number = ar.id_number AND ar.date = ?
    WHERE ar.id IS NULL
");
$auto_absent_query->bind_param("s", $today);
$auto_absent_query->execute();
$students_without_attendance = $auto_absent_query->get_result();

while ($student = $students_without_attendance->fetch_assoc()) {
    $has_class_today = false;
    $effective_start_time = null;
    $effective_end_time = null;
    
    if ($student['days']) {
        $class_days = explode(',', $student['days']);
        $has_class_today = in_array($current_day, array_map('trim', $class_days));
    }
    
    if ($has_class_today) {
        $effective_start_time = $student['start_time'];
        $effective_end_time = $student['end_time'];
        
        // Check for day-specific schedule override
        if ($student['schedule_id']) {
            $day_schedule_query = $conn->prepare("SELECT start_time, end_time FROM day_schedules WHERE schedule_id = ? AND day_name = ?");
            $day_schedule_query->bind_param("is", $student['schedule_id'], $current_day);
            $day_schedule_query->execute();
            $day_schedule_result = $day_schedule_query->get_result();
            
            if ($day_schedule_result->num_rows > 0) {
                $day_schedule = $day_schedule_result->fetch_assoc();
                $effective_start_time = $day_schedule['start_time'];
                $effective_end_time = $day_schedule['end_time'];
            }
        }
        
        // Mark as absent if class time has ended
        if ($effective_end_time) {
            $end_time = strtotime($effective_end_time);
            $current_timestamp = strtotime($current_time);
            
            if ($current_timestamp > $end_time) {
                $today_schedule = ($student['section_name'] ?? 'No Section') . " (" . 
                                 date('g:i A', strtotime($effective_start_time)) . " - " . 
                                 date('g:i A', strtotime($effective_end_time)) . ")";
                
                $absent_insert = $conn->prepare("INSERT INTO attendance_record (id_number, date, day, status, schedule) VALUES (?, ?, ?, 'Absent', ?)");
                $absent_insert->bind_param("ssss", $student['id_number'], $today, $current_day, $today_schedule);
                $absent_insert->execute();
            }
        }
    }
}

// Only get latest student if someone just tapped (show_latest_student flag is set)
$latest_student = null;
if (isset($_SESSION['show_latest_student']) && $_SESSION['show_latest_student']) {
    $latest_student_query = $conn->prepare("
        SELECT ar.*, sa.first_name, sa.last_name, sa.middle_name,
               GREATEST(
                   COALESCE(UNIX_TIMESTAMP(CONCAT(ar.date, ' ', ar.time_in)), 0),
                   COALESCE(UNIX_TIMESTAMP(CONCAT(ar.date, ' ', ar.time_out)), 0)
               ) as last_activity
        FROM attendance_record ar 
        JOIN student_account sa ON ar.id_number = sa.id_number 
        WHERE ar.date = ? 
        ORDER BY last_activity DESC, ar.id DESC
        LIMIT 1
    ");
    $latest_student_query->bind_param("s", $today);
    $latest_student_query->execute();
    $latest_student_result = $latest_student_query->get_result();
    $latest_student = $latest_student_result->num_rows > 0 ? $latest_student_result->fetch_assoc() : null;
}

// Handle search and date filtering
$search_name = $_GET['search_name'] ?? '';
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;

// Determine view role (defaults to student if none)
$view_role = $_SESSION['view_role'] ?? 'student';

// Build query for attendance records depending on role (exclude absent records)
if ($view_role === 'employee') {
    $sql = "SELECT ta.*, e.first_name, e.last_name 
            FROM employee_attendance ta
            JOIN employees e ON ta.employee_id = e.id_number
            WHERE (ta.time_in IS NOT NULL OR ta.time_out IS NOT NULL)";
} else {
    $sql = "SELECT ar.*, sa.first_name, sa.last_name, sa.middle_name 
            FROM attendance_record ar 
            JOIN student_account sa ON ar.id_number = sa.id_number 
            WHERE (ar.time_in IS NOT NULL OR ar.time_out IS NOT NULL)";
}
$params = [];
$param_types = "";

// Add date filtering
if ($start_date && $end_date) {
    $sql .= ($view_role === 'employee') ? " AND ta.date BETWEEN ? AND ?" : " AND ar.date BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
    $param_types .= "ss";
} else {
    // Default to today if no date range specified
    $sql .= ($view_role === 'employee') ? " AND ta.date = ?" : " AND ar.date = ?";
    $params[] = $today;
    $param_types .= "s";
}

// Add name search filtering
if ($search_name) {
    if ($view_role === 'employee') {
        $sql .= " AND CONCAT(e.first_name, ' ', e.last_name) LIKE ?";
    } else {
        $sql .= " AND (CONCAT(sa.first_name, ' ', sa.middle_name, ' ', sa.last_name) LIKE ? 
                  OR CONCAT(sa.first_name, ' ', sa.last_name) LIKE ?)";
    }
    $search_param = "%" . $search_name . "%";
    $params[] = $search_param;
    if ($view_role !== 'employee') {
        $params[] = $search_param;
    }
    $param_types .= ($view_role === 'employee') ? "s" : "ss";
}

$sql .= ($view_role === 'employee') ? " ORDER BY ta.date DESC, ta.id DESC" : " ORDER BY ar.date DESC, ar.id DESC";

$attendance_query = $conn->prepare($sql);
if (!empty($params)) {
    $attendance_query->bind_param($param_types, ...$params);
}
$attendance_query->execute();
$attendance_records = $attendance_query->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Dashboard - Cornerstone College Inc.</title>
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
                    <img src="../images/LogoCCI.png" alt="Cornerstone College Inc." class="h-12 w-12 rounded-full bg-white p-1">
                    <div class="text-left">
                        <h1 class="text-xl font-bold">Cornerstone College Inc.</h1>
                        <p class="text-blue-200 text-sm">Attendance Portal</p>
                    </div>

    <script>
      // Preserve scroll position across reloads so the page doesn't jump to top after tap
      try {
        window.history.scrollRestoration = 'manual';
        const key = 'attendance_scroll_y';
        const savedY = sessionStorage.getItem(key);
        if (savedY !== null) {
          window.scrollTo(0, parseInt(savedY, 10) || 0);
          sessionStorage.removeItem(key);
        }
        window.addEventListener('beforeunload', function(){
          try { sessionStorage.setItem(key, String(window.scrollY || window.pageYOffset || 0)); } catch(e) {}
        });
      } catch(e) {}
    </script>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="text-sm text-blue-200">Attendance System</p>
                        <p class="font-semibold">RFID Attendance Dashboard</p>
                    </div>
                    <div class="relative z-50">
                        <button id="burgerBtn" class="bg-white bg-opacity-20 hover:bg-opacity-30 p-2 rounded-lg transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>
                        <div id="burgerMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg z-50 text-gray-800">
                            <a href="logout.php" class="block px-4 py-3 hover:bg-gray-100 rounded-lg">
                                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                                Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Current Time Display -->
        <div class="bg-gradient-to-r from-orange-400 to-yellow-500 rounded-2xl shadow-xl p-8 mb-6 text-center text-white">
            <div class="text-5xl font-bold mb-2" id="currentTime">
                <?= date('h:i:s A') ?>
            </div>
            <div class="text-lg opacity-90">
                <?= date('F j, Y') ?>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php 
        $has_success = isset($_SESSION['success']);
        $has_error = isset($_SESSION['error']);
        ?>
        <?php if ($has_success): ?>
            <div id="successBanner" class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 text-green-800 px-6 py-4 rounded-xl mb-6 shadow-sm">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="font-medium"><?= htmlspecialchars($_SESSION['success']) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($has_error): ?>
            <div class="bg-gradient-to-r from-red-50 to-rose-50 border border-red-200 text-red-800 px-6 py-4 rounded-xl mb-6 shadow-sm">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-red-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="font-medium"><?= htmlspecialchars($_SESSION['error']) ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Today's Schedule -->
        <div id="todayScheduleBlock" class="bg-white rounded-2xl shadow-lg mb-3">
            <div class="p-2 border-b border-gray-100">
                <div class="flex items-center mb-1">
                    <div class="w-1 h-8 bg-[#0B2C62] rounded-full mr-4"></div>
                    <h2 class="text-xl font-bold text-gray-800">Today's Schedule</h2>
                </div>
            </div>
            
            <?php if (($view_role ?? 'student') === 'employee' && isset($latest_employee) && $latest_employee): ?>
            <?php
              // Build employee display similar to HR logic
              $empId = $latest_employee['employee_id'];
              $dayName = date('l', strtotime($today));
              $shiftIn = null; $shiftOut = null; $hasDay = false;
              $ws = $conn->prepare("SELECT ws.start_time, ws.end_time, ws.days, ws.id AS schedule_id
                                     FROM employees e
                                     LEFT JOIN employee_schedules es ON e.id_number = es.employee_id
                                     LEFT JOIN employee_work_schedules ws ON es.schedule_id = ws.id
                                     WHERE e.id_number = ? LIMIT 1");
              if ($ws) {
                $ws->bind_param('s', $empId);
                $ws->execute();
                $resWS = $ws->get_result();
                if ($resWS && $resWS->num_rows > 0) {
                  $sd = $resWS->fetch_assoc();
                  $shiftIn = $sd['start_time'];
                  $shiftOut = $sd['end_time'];
                  if (!empty($sd['days'])) {
                    $daysArr = array_map('trim', explode(',', $sd['days']));
                    $hasDay = in_array($dayName, $daysArr, true) || strcasecmp(trim((string)$sd['days']), 'Variable') === 0;
                  }
                  if (!empty($sd['schedule_id'])) {
                    $ds = $conn->prepare("SELECT start_time, end_time FROM employee_work_day_schedules WHERE schedule_id = ? AND day_name = ? LIMIT 1");
                    if ($ds) { $ds->bind_param('is', $sd['schedule_id'], $dayName); $ds->execute(); $ovr=$ds->get_result(); if ($ovr && $ovr->num_rows>0){ $o=$ovr->fetch_assoc(); $shiftIn=$o['start_time']; $shiftOut=$o['end_time']; $hasDay=true; } }
                  }
                }
              }
              $empTimeIn = $latest_employee['time_in'];
              $empTimeOut = $latest_employee['time_out'];
              $empStatus = (!empty($empTimeIn) && !empty($empTimeOut)) ? 'Present' : ((!empty($empTimeIn)) ? 'Time In Only' : '—');
              $empStatusColor = ($empStatus==='Present') ? 'text-green-600' : (($empStatus==='Time In Only')?'text-blue-600':'text-gray-600');
            ?>
            <div class="p-2">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-2 mb-2">
                    <div class="bg-blue-50 rounded-lg p-2.5 border border-blue-100">
                        <p class="text-sm text-gray-600 mb-1">Date</p>
                        <p class="font-semibold text-gray-900"><?= date('F j, Y') ?></p>
                    </div>
                    <div class="bg-blue-50 rounded-lg p-2.5 border border-blue-100">
                        <p class="text-sm text-gray-600 mb-1">Day</p>
                        <p class="font-semibold text-gray-900"><?= date('l') ?></p>
                    </div>
                    <div class="bg-blue-50 rounded-lg p-2.5 border border-blue-100">
                        <p class="text-sm text-gray-600 mb-1">Work Schedule</p>
                        <p class="font-semibold text-gray-900">
                          <?php if ($hasDay && $shiftIn && $shiftOut): ?>
                            <?= date('g:i A', strtotime($shiftIn)) . ' - ' . date('g:i A', strtotime($shiftOut)) ?>
                          <?php elseif ($hasDay && $shiftIn): ?>
                            <?= date('g:i A', strtotime($shiftIn)) ?>
                          <?php else: ?>
                            No Work Schedule
                          <?php endif; ?>
                        </p>
                    </div>
                    <div class="bg-blue-50 rounded-lg p-2.5 border border-blue-100 md:col-span-2">
                        <p class="text-sm text-gray-600 mb-1">Employee</p>
                        <p class="font-semibold text-gray-900">
                          <?= htmlspecialchars(trim(($latest_employee['first_name'] ?? '') . ' ' . ($latest_employee['last_name'] ?? ''))) ?>
                        </p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                    <div class="bg-blue-50 rounded-lg p-2.5 border border-blue-100">
                        <p class="text-sm text-gray-600 mb-1">Time In</p>
                        <p class="text-xl font-bold text-gray-800">
                            <?= $empTimeIn ? date('g:i A', strtotime($empTimeIn)) : '--' ?>
                        </p>
                    </div>
                    <div class="bg-blue-50 rounded-lg p-2.5 border border-blue-100">
                        <p class="text-sm text-gray-600 mb-1">Time Out</p>
                        <p class="text-xl font-bold text-gray-800">
                            <?= $empTimeOut ? date('g:i A', strtotime($empTimeOut)) : '--' ?>
                        </p>
                    </div>
                    <div class="bg-blue-50 rounded-lg p-2.5 border border-blue-100">
                        <p class="text-sm text-gray-600 mb-1">Status</p>
                        <p class="text-xl font-bold <?= $empStatusColor ?>"><?= htmlspecialchars($empStatus) ?></p>
                    </div>
                </div>
            </div>
            <?php elseif ($latest_student): ?>
            <div class="p-2">
                <div class="grid grid-cols-1 md:grid-cols-5 gap-2 mb-2">
                    <div class="bg-blue-50 rounded-lg p-2.5 border border-blue-100">
                        <p class="text-sm text-gray-600 mb-1">Date</p>
                        <p class="font-semibold text-gray-900"><?= date('F j, Y') ?></p>
                    </div>
                    <div class="bg-blue-50 rounded-lg p-2.5 border border-blue-100">
                        <p class="text-sm text-gray-600 mb-1">Day</p>
                        <p class="font-semibold text-gray-900"><?= date('l') ?></p>
                    </div>
                    <div class="bg-blue-50 rounded-lg p-2.5 border border-blue-100">
                        <p class="text-sm text-gray-600 mb-1">Section</p>
                        <p class="font-semibold text-gray-900">
                            <?php 
                            $schedule = $latest_student['schedule'] ?? 'No Section';
                            // Extract only section name, remove (Variable) part
                            if (preg_match('/^([^(]+)/', $schedule, $matches)) {
                                echo htmlspecialchars(trim($matches[1]));
                            } else {
                                echo htmlspecialchars($schedule);
                            }
                            ?>
                        </p>
                    </div>
                    <div class="bg-blue-50 rounded-lg p-2.5 border border-blue-100">
                        <p class="text-sm text-gray-600 mb-1">Class Schedule</p>
                        <p class="font-semibold text-gray-900">
                            <?php 
                            // Get today's specific schedule
                            $current_day = date('l');
                            $schedule_query = $conn->prepare("
                                SELECT cs.start_time, cs.end_time, cs.days, cs.id as schedule_id
                                FROM student_account sa
                                LEFT JOIN student_schedules ss ON sa.id_number = ss.student_id
                                LEFT JOIN class_schedules cs ON ss.schedule_id = cs.id
                                WHERE sa.id_number = ?
                            ");
                            $schedule_query->bind_param("s", $latest_student['id_number']);
                            $schedule_query->execute();
                            $schedule_result = $schedule_query->get_result();
                            $schedule_data = $schedule_result->fetch_assoc();
                            
                            $display_start_time = null;
                            $display_end_time = null;
                            $has_class_today = false;
                            
                            if ($schedule_data) {
                                // Check if student has class today
                                if ($schedule_data['days']) {
                                    $class_days = explode(',', $schedule_data['days']);
                                    $has_class_today = in_array($current_day, array_map('trim', $class_days));
                                }
                                
                                if ($has_class_today) {
                                    $display_start_time = $schedule_data['start_time'];
                                    $display_end_time = $schedule_data['end_time'];
                                    
                                    // Check for today-specific schedule override
                                    if ($schedule_data['schedule_id']) {
                                        $day_schedule_query = $conn->prepare("SELECT start_time, end_time FROM day_schedules WHERE schedule_id = ? AND day_name = ?");
                                        $day_schedule_query->bind_param("is", $schedule_data['schedule_id'], $current_day);
                                        $day_schedule_query->execute();
                                        $day_schedule_result = $day_schedule_query->get_result();
                                        
                                        if ($day_schedule_result->num_rows > 0) {
                                            $day_schedule = $day_schedule_result->fetch_assoc();
                                            $display_start_time = $day_schedule['start_time'];
                                            $display_end_time = $day_schedule['end_time'];
                                        }
                                    }
                                }
                            }
                            
                            if ($has_class_today && $display_start_time && $display_end_time) {
                                echo date('g:i A', strtotime($display_start_time)) . ' - ' . date('g:i A', strtotime($display_end_time));
                            } else {
                                echo 'No Class Today';
                            }
                            ?>
                        </p>
                    </div>
                    <div class="bg-blue-50 rounded-lg p-2.5 border border-blue-100">
                        <p class="text-sm text-gray-600 mb-1">Student</p>
                        <p class="font-semibold text-gray-900">
                            <?= htmlspecialchars(trim($latest_student['first_name'] . ' ' . $latest_student['middle_name'] . ' ' . $latest_student['last_name'])) ?>
                        </p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                    <div class="bg-blue-50 rounded-lg p-2.5 border border-blue-100">
                        <p class="text-sm text-gray-600 mb-1">Time In</p>
                        <p class="text-xl font-bold text-gray-800">
                            <?= $latest_student['time_in'] ? date('g:i A', strtotime($latest_student['time_in'])) : '--' ?>
                        </p>
                    </div>
                    <div class="bg-blue-50 rounded-lg p-2.5 border border-blue-100">
                        <p class="text-sm text-gray-600 mb-1">Time Out</p>
                        <p class="text-xl font-bold text-gray-800">
                            <?= $latest_student['time_out'] ? date('g:i A', strtotime($latest_student['time_out'])) : '--' ?>
                        </p>
                    </div>
                    <div class="bg-blue-50 rounded-lg p-2.5 border border-blue-100">
                        <p class="text-sm text-gray-600 mb-1">Status</p>
                        <p class="text-xl font-bold <?php 
                        // Always use the database status, don't calculate it
                        $status = $latest_student['status'];
                        if ($status === 'Present') {
                            $status_color = 'text-green-600';
                        } elseif ($status === 'Time In Only') {
                            $status_color = 'text-blue-600';
                        } elseif ($status === 'Absent') {
                            $status_color = 'text-red-600';
                        } else {
                            $status_color = 'text-gray-600';
                        }
                        echo $status_color . '">' . htmlspecialchars($status);
                        ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="p-8 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-600 mb-2">No Students Tapped Today</h3>
                <p class="text-gray-500">Student information will appear here when they tap their RFID cards</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Hidden RFID Form -->
        <form method="POST" id="rfidForm" class="hidden">
            <input type="text" name="rfid" id="rfidInput">
        </form>

        <script>
          // Auto-clear latest student and reload so the schedule shows the empty state
          (function(){
            const banner = document.getElementById('successBanner');
            const sched = document.getElementById('todayScheduleBlock');
            if (banner && sched) {
              setTimeout(async function(){
                try { await fetch('?clear_student=1', { credentials: 'same-origin' }); } catch(e) {}
                // Reload; scroll position is preserved by the scrollRestoration/sessionStorage logic
                location.reload();
              }, 3000); // 3 seconds; adjust if you want
            }
          })();
        </script>

    <!-- Attendance Records (hidden; feature moved to Registrar portal) -->
    <div class="bg-white rounded-2xl shadow-lg hidden">
        <div class="p-6 border-b border-gray-100">
            <div class="flex items-center mb-6">
                <div class="w-1 h-8 bg-[#0B2C62] rounded-full mr-4"></div>
                <h2 class="text-xl font-bold text-gray-800">Attendance Records</h2>
            </div>
            
            <!-- Search and Filter Form -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end mb-6">
                <div>
                    <label for="search-name" class="text-sm font-medium text-gray-700 block mb-2"><?php echo ($view_role === 'employee') ? 'Search Employee' : 'Search Student'; ?></label>
                    <input type="text" id="search-name" name="search_name" value="<?= htmlspecialchars($search_name) ?>" 
                           placeholder="Enter student name..."
                           class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-[#0B2C62] focus:border-transparent transition-all duration-200">
                </div>
                <div>
                    <label for="start-date" class="text-sm font-medium text-gray-700 block mb-2">Start Date</label>
                    <input type="date" id="start-date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" 
                           class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-[#0B2C62] focus:border-transparent transition-all duration-200">
                </div>
                <div>
                    <label for="end-date" class="text-sm font-medium text-gray-700 block mb-2">End Date</label>
                    <input type="date" id="end-date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" 
                           class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-[#0B2C62] focus:border-transparent transition-all duration-200">
                </div>
                <div class="flex gap-2">
                    <button type="button" id="generateReport" class="bg-[#0B2C62] text-white px-6 py-3 rounded-lg hover:bg-blue-900 transition-all duration-200 font-medium flex-1">
                        Generate Report
                    </button>
                    <button type="button" id="clearFilters" class="bg-gray-500 text-white px-4 py-3 rounded-lg hover:bg-gray-600 transition-all duration-200 font-medium">
                        Clear
                    </button>
                </div>
            </div>
            
            <p id="filterStatus" class="text-gray-600">
                <?php if ($start_date && $end_date): ?>
                    Attendance records from <?= date('F j, Y', strtotime($start_date)) ?> to <?= date('F j, Y', strtotime($end_date)) ?>
                <?php elseif ($search_name): ?>
                    Search results for "<?= htmlspecialchars($search_name) ?>" on <?= date('F j, Y') ?>
                <?php else: ?>
                    Real-time attendance tracking for <?= date('F j, Y') ?>
                <?php endif; ?>
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-[#0B2C62] text-white">
                    <?php if ($view_role === 'teacher'): ?>
                    <tr>
                        <th class="px-6 py-4 text-left font-semibold">Name</th>
                        <th class="px-6 py-4 text-left font-semibold">Date</th>
                        <th class="px-6 py-4 text-left font-semibold">Day</th>
                        <th class="px-6 py-4 text-left font-semibold">Shift Type</th>
                        <th class="px-6 py-4 text-left font-semibold">Shift In</th>
                        <th class="px-6 py-4 text-left font-semibold">Shift Out</th>
                        <th class="px-6 py-4 text-left font-semibold">Time In</th>
                        <th class="px-6 py-4 text-left font-semibold">Time Out</th>
                        <th class="px-6 py-4 text-left font-semibold">Required Hours</th>
                        <th class="px-6 py-4 text-left font-semibold">Tardiness</th>
                        <th class="px-6 py-4 text-left font-semibold">Undertime</th>
                        <th class="px-6 py-4 text-left font-semibold">OT</th>
                        <th class="px-6 py-4 text-left font-semibold">OB</th>
                    </tr>
                    <?php else: ?>
                    <tr>
                        <th class="px-6 py-4 text-left font-semibold">Name</th>
                        <th class="px-6 py-4 text-left font-semibold">Date</th>
                        <th class="px-6 py-4 text-left font-semibold">Day</th>
                        <th class="px-6 py-4 text-left font-semibold">Section</th>
                        <th class="px-6 py-4 text-left font-semibold">Class Schedule</th>
                        <th class="px-6 py-4 text-left font-semibold">Time In</th>
                        <th class="px-6 py-4 text-left font-semibold">Time Out</th>
                        <th class="px-6 py-4 text-left font-semibold">Status</th>
                    </tr>
                    <?php endif; ?>
                </thead>
                <tbody id="attendanceTableBody" class="divide-y divide-gray-100">
                    <?php if ($attendance_records->num_rows > 0): ?>
                        <?php while($record = $attendance_records->fetch_assoc()): ?>
                            <?php if ($view_role === 'teacher'): ?>
                            <tr class="hover:bg-blue-50 transition-colors duration-150">
                                <td class="px-6 py-4 font-medium text-gray-900">
                                    <?= htmlspecialchars(trim(($record['first_name'] ?? '') . ' ' . ($record['last_name'] ?? ''))) ?>
                                </td>
                                <td class="px-6 py-4 text-gray-700"><?= htmlspecialchars(date('F j, Y', strtotime($record['date']))) ?></td>
                                <td class="px-6 py-4 text-gray-700"><?= htmlspecialchars($record['day']) ?></td>
                                <td class="px-6 py-4 text-gray-700"><?= htmlspecialchars($record['shift_type'] ?? 'Regular') ?></td>
                                <td class="px-6 py-4 text-gray-700"><?= $record['shift_in'] ? date('h:i A', strtotime($record['shift_in'])) : '--' ?></td>
                                <td class="px-6 py-4 text-gray-700"><?= $record['shift_out'] ? date('h:i A', strtotime($record['shift_out'])) : '--' ?></td>
                                <td class="px-6 py-4 text-gray-700"><?= $record['time_in'] ? date('h:i A', strtotime($record['time_in'])) : '--' ?></td>
                                <td class="px-6 py-4 text-gray-700"><?= $record['time_out'] ? date('h:i A', strtotime($record['time_out'])) : '--' ?></td>
                                <td class="px-6 py-4 text-gray-700"><?= isset($record['required_hours']) ? htmlspecialchars($record['required_hours']) : '8.00' ?></td>
                                <td class="px-6 py-4 text-gray-700"><?= (int)($record['tardiness_minutes'] ?? 0) ?> mins</td>
                                <td class="px-6 py-4 text-gray-700"><?= (int)($record['undertime_minutes'] ?? 0) ?> mins</td>
                                <td class="px-6 py-4 text-gray-700"><?= (int)($record['ot_minutes'] ?? 0) ?> mins</td>
                                <td class="px-6 py-4 text-gray-700"><?= (int)($record['ob_minutes'] ?? 0) ?> mins</td>
                            </tr>
                            <?php else: ?>
                            <tr class="hover:bg-blue-50 transition-colors duration-150">
                                <td class="px-6 py-4 font-medium text-gray-900">
                                    <?= htmlspecialchars(trim($record['first_name'] . ' ' . ($record['middle_name'] ?? '') . ' ' . $record['last_name'])) ?>
                                </td>
                                <td class="px-6 py-4 text-gray-700">
                                    <?= htmlspecialchars(date('F j, Y', strtotime($record['date']))) ?>
                                </td>
                                <td class="px-6 py-4 text-gray-700">
                                    <?= htmlspecialchars($record['day']) ?>
                                </td>
                                <td class="px-6 py-4 text-gray-700 whitespace-nowrap">
                                    <?php 
                                    $schedule = $record['schedule'] ?? 'No Section';
                                    if (preg_match('/^([^(]+)/', $schedule, $matches)) {
                                        echo htmlspecialchars(trim($matches[1]));
                                    } else {
                                        echo htmlspecialchars($schedule);
                                    }
                                    ?>
                                </td>
                                <td class="px-6 py-4 text-gray-700 whitespace-nowrap">
                                    <?php 
                                    $record_day = $record['day'];
                                    $schedule_query = $conn->prepare("\n                                            SELECT cs.start_time, cs.end_time, cs.days, cs.id as schedule_id\n                                            FROM student_account sa\n                                            LEFT JOIN student_schedules ss ON sa.id_number = ss.student_id\n                                            LEFT JOIN class_schedules cs ON ss.schedule_id = cs.id\n                                            WHERE sa.id_number = ?\n                                        ");
                                    $schedule_query->bind_param("s", $record['id_number']);
                                    $schedule_query->execute();
                                    $schedule_result = $schedule_query->get_result();
                                    $schedule_data = $schedule_result->fetch_assoc();
                                    $display_start_time = null;
                                    $display_end_time = null;
                                    $has_class_on_day = false;
                                    if ($schedule_data) {
                                        if ($schedule_data['days']) {
                                            $class_days = explode(',', $schedule_data['days']);
                                            $has_class_on_day = in_array($record_day, array_map('trim', $class_days));
                                        }
                                        if ($has_class_on_day) {
                                            $display_start_time = $schedule_data['start_time'];
                                            $display_end_time = $schedule_data['end_time'];
                                            if ($schedule_data['schedule_id']) {
                                                $day_schedule_query = $conn->prepare("SELECT start_time, end_time FROM day_schedules WHERE schedule_id = ? AND day_name = ?");
                                                $day_schedule_query->bind_param("is", $schedule_data['schedule_id'], $record_day);
                                                $day_schedule_query->execute();
                                                $day_schedule_result = $day_schedule_query->get_result();
                                                
                                                if ($day_schedule_result->num_rows > 0) {
                                                    $day_schedule = $day_schedule_result->fetch_assoc();
                                                    $display_start_time = $day_schedule['start_time'];
                                                    $display_end_time = $day_schedule['end_time'];
                                                }
                                            }
                                        }
                                    }
                                    echo ($has_class_on_day && $display_start_time && $display_end_time)
                                        ? date('g:i A', strtotime($display_start_time)) . ' - ' . date('g:i A', strtotime($display_end_time))
                                        : 'No Class Schedule';
                                    ?>
                                </td>
                                <td class="px-6 py-4 text-gray-700">
                                    <?= $record['time_in'] ? date("h:i A", strtotime($record['time_in'])) : '--' ?>
                                </td>
                                <td class="px-6 py-4 text-gray-700">
                                    <?= $record['time_out'] ? date("h:i A", strtotime($record['time_out'])) : '--' ?>
                                </td>
                                <td class="px-6 py-4 text-gray-700">
                                    <?php 
                                    $status = $record['status'];
                                    if ($status === 'Present') {
                                        echo '<span class="text-green-600 font-medium">Present</span>';
                                    } elseif ($status === 'Time In Only') {
                                        echo '<span class="text-blue-600 font-medium">Time In Only</span>';
                                    } elseif ($status === 'Absent') {
                                        echo '<span class="text-red-600 font-medium">Absent</span>';
                                    } else {
                                        echo '<span class="text-gray-600 font-medium">' . htmlspecialchars($status) . '</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= ($view_role === 'employee') ? 13 : 8 ?>" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <p class="text-gray-500 font-medium">No attendance records for today</p>
                                    <p class="text-gray-400 text-sm mt-1"><?php echo ($view_role==='employee') ? 'Employees will appear here when they tap their RFID cards' : 'Students will appear here when they tap their RFID cards'; ?></p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Update current time every second
function updateTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('en-US', {
        hour12: true,
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
    document.getElementById('currentTime').textContent = timeString;
}

setInterval(updateTime, 1000);

// RFID Scanner functionality
let rfidBuffer = '';
let rfidTimer;

// Global listener for RFID scanner input
window.addEventListener('keydown', function(e) {
    // Only process if no input fields are focused
    const active = document.activeElement;
    if (active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA')) {
        return;
    }

    if (/^[a-zA-Z0-9]$/.test(e.key)) {
        rfidBuffer += e.key;
    }

    if (e.key === 'Enter') {
        if (rfidBuffer.length > 0) {
            submitRFID(rfidBuffer.trim());
            rfidBuffer = '';
        }
    }

    if (rfidTimer) clearTimeout(rfidTimer);
    rfidTimer = setTimeout(() => rfidBuffer = '', 400);
});

function submitRFID(rfid) {
    const form = document.getElementById('rfidForm');
    const input = document.getElementById('rfidInput');
    input.value = rfid;
    form.submit();
}

// Store the last known student to track changes
let lastStudentData = <?= $latest_student ? json_encode($latest_student) : 'null' ?>;
let clearScheduleTimer = null;

// Function to clear the schedule display
function clearScheduleDisplay() {
    const scheduleSection = document.querySelector('.bg-white.rounded-2xl.shadow-lg.mb-8');
    if (scheduleSection) {
        const emptyStateHTML = `
            <div class="p-6 border-b border-gray-100">
                <div class="flex items-center mb-4">
                    <div class="w-1 h-8 bg-[#0B2C62] rounded-full mr-4"></div>
                    <h2 class="text-xl font-bold text-gray-800">Today's Schedule</h2>
                </div>
            </div>
            <div class="p-8 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-gray-600 mb-2">No Students Tapped Today</h3>
                <p class="text-gray-500">Student information will appear here when they tap their RFID cards</p>
            </div>
        `;
        scheduleSection.innerHTML = emptyStateHTML;
        lastStudentData = null;
    }
}

// Set timer for both schedule display and notifications (10 seconds)
<?php if ($latest_student || $has_success || $has_error): ?>
setTimeout(function() {
    // Hide success/error notifications and clear schedule simultaneously
    <?php if ($has_success || $has_error): ?>
    const successMsg = document.querySelector('.bg-gradient-to-r.from-green-50');
    const errorMsg = document.querySelector('.bg-gradient-to-r.from-red-50');
    
    if (successMsg) {
        successMsg.style.transition = 'opacity 0.5s ease-out';
        successMsg.style.opacity = '0';
        setTimeout(() => successMsg.remove(), 500);
    }
    
    if (errorMsg) {
        errorMsg.style.transition = 'opacity 0.5s ease-out';
        errorMsg.style.opacity = '0';
        setTimeout(() => errorMsg.remove(), 500);
    }
    <?php endif; ?>
    
    // Clear schedule display at the same time as messages
    <?php if ($latest_student): ?>
    clearScheduleDisplay();
    <?php endif; ?>
    
    // Clear all session flags after elements are hidden
    fetch('<?= $_SERVER['PHP_SELF'] ?>?clear_student=1', {method: 'GET'});
}, 10000);
<?php 
// Now unset the session variables after JavaScript has been generated
if ($has_success) unset($_SESSION['success']);
if ($has_error) unset($_SESSION['error']);
?>
<?php endif; ?>

// Search and filter functionality
let searchTimeout;

// Real-time search as user types
document.getElementById('search-name').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(function() {
        updateAttendanceTable();
    }, 300); // Wait 300ms after user stops typing
});

// Date change handlers
document.getElementById('start-date').addEventListener('change', updateAttendanceTable);
document.getElementById('end-date').addEventListener('change', updateAttendanceTable);

// Generate report button
document.getElementById('generateReport').addEventListener('click', updateAttendanceTable);

// Clear filters button
document.getElementById('clearFilters').addEventListener('click', function() {
    document.getElementById('search-name').value = '';
    document.getElementById('start-date').value = '';
    document.getElementById('end-date').value = '';
    updateAttendanceTable();
});

// Function to update attendance table via AJAX
function updateAttendanceTable() {
    const searchName = document.getElementById('search-name').value;
    const startDate = document.getElementById('start-date').value;
    const endDate = document.getElementById('end-date').value;
    
    // Build query parameters
    const params = new URLSearchParams();
    if (searchName) params.append('search_name', searchName);
    if (startDate) params.append('start_date', startDate);
    if (endDate) params.append('end_date', endDate);
    params.append('ajax', '1'); // Flag to indicate AJAX request
    
    // Show loading state
    const tableBody = document.getElementById('attendanceTableBody');
    tableBody.innerHTML = '<tr><td colspan="8" class="px-6 py-12 text-center"><div class="flex items-center justify-center"><svg class="animate-spin h-5 w-5 text-gray-400 mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Loading...</div></td></tr>';
    
    fetch(window.location.pathname + '?' + params.toString())
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            // Update table body
            const newTableBody = doc.getElementById('attendanceTableBody');
            if (newTableBody) {
                tableBody.innerHTML = newTableBody.innerHTML;
            }
            
            // Update filter status
            const newFilterStatus = doc.getElementById('filterStatus');
            const currentFilterStatus = document.getElementById('filterStatus');
            if (newFilterStatus && currentFilterStatus) {
                currentFilterStatus.innerHTML = newFilterStatus.innerHTML;
            }
        })
        .catch(error => {
            console.log('Error updating attendance:', error);
            tableBody.innerHTML = '<tr><td colspan="8" class="px-6 py-12 text-center text-red-600">Error loading data. Please try again.</td></tr>';
        });
}

// Check for new attendance data every 5 seconds without full page reload
setInterval(function() {
    // Skip auto-refresh if we have active notifications, student display, or active filters
    if (document.querySelector('.bg-gradient-to-r.from-green-50') || 
        document.querySelector('.bg-gradient-to-r.from-red-50') ||
        lastStudentData ||
        document.getElementById('search-name').value ||
        document.getElementById('start-date').value ||
        document.getElementById('end-date').value) {
        return;
    }
    
    updateAttendanceTable();
}, 5000);

// Full page refresh every 60 seconds (reduced frequency)
setInterval(function() {
    location.reload();
}, 60000);

// Burger menu functionality
document.getElementById('burgerBtn').addEventListener('click', function() {
    const menu = document.getElementById('burgerMenu');
    menu.classList.toggle('hidden');
});

// Close menu when clicking outside
document.addEventListener('click', function(event) {
    const burgerBtn = document.getElementById('burgerBtn');
    const burgerMenu = document.getElementById('burgerMenu');
    
    if (!burgerBtn.contains(event.target) && !burgerMenu.contains(event.target)) {
        burgerMenu.classList.add('hidden');
    }
});
</script>

</body>
</html>