<?php
session_start();
include("../StudentLogin/db_conn.php");

// Ensure server timezone aligns with local time for correct day-of-week calculations
date_default_timezone_set('Asia/Manila');

// Require registrar login
if (!isset($_SESSION['registrar_id'])) {
    header("Location: ../StudentLogin/login.php");
    exit;
}

// Handle create/edit/delete/assign for schedules (reuse class_schedules/day_schedules like students)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Unknown action'];

    if ($_POST['action'] === 'create_schedule') {
        $section_name = $_POST['section_name'] ?? '';
        $schedule_type = $_POST['schedule_type'] ?? 'same';

        if ($schedule_type === 'same') {
            $start_time = $_POST['start_time'];
            $end_time   = $_POST['end_time'];
            $days       = isset($_POST['days']) ? implode(', ', $_POST['days']) : '';

            $stmt = $conn->prepare("INSERT INTO employee_work_schedules (schedule_name, start_time, end_time, days, created_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $section_name, $start_time, $end_time, $days, $_SESSION['registrar_id']);
            if ($stmt->execute()) {
                $response = ['success' => true, 'message' => 'Schedule created successfully!'];
            } else {
                $response['message'] = 'Error creating schedule: ' . $conn->error;
            }
        } else {
            // Different times per day
            $day_schedules = $_POST['day_schedules'] ?? [];
            $stmt = $conn->prepare("INSERT INTO employee_work_schedules (schedule_name, start_time, end_time, days, created_by) VALUES (?, '00:00:00', '23:59:59', 'Variable', ?)");
            $stmt->bind_param("si", $section_name, $_SESSION['registrar_id']);
            if ($stmt->execute()) {
                $schedule_id = $conn->insert_id;
                $enabled_days = [];
                foreach ($day_schedules as $day => $sched) {
                    if (!empty($sched['enabled']) && !empty($sched['start_time']) && !empty($sched['end_time'])) {
                        $d = $conn->prepare("INSERT INTO employee_work_day_schedules (schedule_id, day_name, start_time, end_time) VALUES (?, ?, ?, ?)");
                        $d->bind_param("isss", $schedule_id, $day, $sched['start_time'], $sched['end_time']);
                        $d->execute();
                        $enabled_days[] = $day;
                    }
                }
                $days_string = implode(', ', $enabled_days);
                $u = $conn->prepare("UPDATE employee_work_schedules SET days = ? WHERE id = ?");
                $u->bind_param("si", $days_string, $schedule_id);
                $u->execute();
                $response = ['success' => true, 'message' => 'Day-specific schedule created successfully!'];
            } else {
                $response['message'] = 'Error creating variable schedule: ' . $conn->error;
            }
        }
        echo json_encode($response); exit;
    }

    if ($_POST['action'] === 'edit_schedule') {
        $schedule_id  = (int)($_POST['schedule_id'] ?? 0);
        $section_name = $_POST['section_name'] ?? '';
        $schedule_type = $_POST['schedule_type'] ?? 'same';

        if ($schedule_type === 'same') {
            $start_time = $_POST['start_time'];
            $end_time   = $_POST['end_time'];
            $days       = isset($_POST['days']) ? implode(', ', $_POST['days']) : '';

            // Clear any day-specific rows
            $clear = $conn->prepare("DELETE FROM employee_work_day_schedules WHERE schedule_id = ?");
            $clear->bind_param("i", $schedule_id);
            $clear->execute();

            $stmt = $conn->prepare("UPDATE employee_work_schedules SET schedule_name = ?, start_time = ?, end_time = ?, days = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $section_name, $start_time, $end_time, $days, $schedule_id);
            $stmt->execute();
        } else {
            $day_schedules = $_POST['day_schedules'] ?? [];
            $clear = $conn->prepare("DELETE FROM employee_work_day_schedules WHERE schedule_id = ?");
            $clear->bind_param("i", $schedule_id);
            $clear->execute();

            $stmt = $conn->prepare("UPDATE employee_work_schedules SET schedule_name = ?, start_time = '00:00:00', end_time = '23:59:59', days = 'Variable' WHERE id = ?");
            $stmt->bind_param("si", $section_name, $schedule_id);
            $stmt->execute();

            $enabled_days = [];
            foreach ($day_schedules as $day => $sched) {
                if (!empty($sched['enabled']) && !empty($sched['start_time']) && !empty($sched['end_time'])) {
                    $d = $conn->prepare("INSERT INTO employee_work_day_schedules (schedule_id, day_name, start_time, end_time) VALUES (?, ?, ?, ?)");
                    $d->bind_param("isss", $schedule_id, $day, $sched['start_time'], $sched['end_time']);
                    $d->execute();
                    $enabled_days[] = $day;
                }
            }
            $days_string = implode(', ', $enabled_days);
            $u = $conn->prepare("UPDATE employee_work_schedules SET days = ? WHERE id = ?");
            $u->bind_param("si", $days_string, $schedule_id);
            $u->execute();
        }

        echo json_encode(['success' => true, 'message' => 'Schedule updated successfully!']);
        exit;
    }

    if ($_POST['action'] === 'delete_schedule') {
        $schedule_id = (int)($_POST['schedule_id'] ?? 0);

        // Remove employee assignments for this schedule
        $clear_emp = $conn->prepare("DELETE FROM employee_schedules WHERE schedule_id = ?");
        $clear_emp->bind_param("i", $schedule_id);
        $clear_emp->execute();

        // Delete schedule
        $stmt = $conn->prepare("DELETE FROM employee_work_schedules WHERE id = ?");
        $stmt->bind_param("i", $schedule_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Schedule deleted successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting schedule: ' . $conn->error]);
        }
        exit;
    }

    if ($_POST['action'] === 'assign_schedule') {
        $schedule_id = (int)($_POST['schedule_id'] ?? 0);
        $employee_ids = $_POST['employee_ids'] ?? [];

        if (!$schedule_id || empty($employee_ids)) {
            echo json_encode(['success' => false, 'message' => 'Missing schedule or employees']);
            exit;
        }

        $success = 0; $failed = 0;
        foreach ($employee_ids as $emp_id) {
            // Remove existing assignment for employee
            $rm = $conn->prepare("DELETE FROM employee_schedules WHERE employee_id = ?");
            $rm->bind_param("s", $emp_id);
            $rm->execute();

            // Insert new
            $ins = $conn->prepare("INSERT INTO employee_schedules (employee_id, schedule_id, assigned_by) VALUES (?, ?, ?)");
            $ins->bind_param("sii", $emp_id, $schedule_id, $_SESSION['registrar_id']);
            if ($ins->execute()) { $success++; } else { $failed++; }
        }

        $msg = "Assigned schedule to $success employee(s)" . ($failed ? " ($failed failed)" : "");
        echo json_encode(['success' => true, 'message' => $msg]);
        exit;
    }

    echo json_encode($response); exit;
}

// Fetch schedules with employee counts
$schedules_query = "SELECT cs.*, COUNT(es.id) as employee_count
                    FROM employee_work_schedules cs
                    LEFT JOIN employee_schedules es ON cs.id = es.schedule_id
                    GROUP BY cs.id
                    ORDER BY cs.schedule_name";
$schedules_result = $conn->query($schedules_query);
// Separate result for the Assign modal select (fresh pointer)
$schedules_for_select = $conn->query("SELECT id, schedule_name, start_time, end_time FROM employee_work_schedules ORDER BY schedule_name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Manage Employee Schedule - Cornerstone College Inc.</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">

<header class="bg-[#0B2C62] text-white shadow-lg">
    <div class="container mx-auto px-6 py-4 flex justify-between items-center">
        <div class="flex items-center gap-3">
            <a href="RegistrarDashboard.php" class="bg-white bg-opacity-20 hover:bg-opacity-30 p-2 rounded-lg transition" title="Back">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h1 class="text-xl font-bold">Employee Schedule Management</h1>
        </div>
        <div class="flex items-center gap-3">
            <img src="../images/LogoCCI.png" alt="Cornerstone College Inc." class="h-12 w-12 rounded-full bg-white p-1" />
            <div class="text-right">
                <div class="text-sm text-blue-200">Registrar Module</div>
                <div class="font-semibold">Cornerstone College Inc.</div>
            </div>
        </div>
    </div>
</header>

<div class="container mx-auto px-6 py-8">
    <div id="successMessage" class="hidden mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
        <span id="successText"></span>
    </div>

    <div class="mb-6 flex gap-4">
        <button onclick="showCreateScheduleModal()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Create Schedule
        </button>
        <button onclick="showAssignScheduleModal()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197"/></svg>
            Assign Schedule to Employees
        </button>
    </div>

    <div class="bg-white rounded-xl shadow p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
          <h2 class="text-xl font-bold text-gray-800">Schedules</h2>
          <div class="w-full md:w-80">
            <div class="relative">
              <div id="scheduleSearchIcon" class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
              </div>
              <input id="scheduleSearch" type="text" placeholder="Search schedules by name or day..." class="w-full pl-10 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-transparent" />
              <button id="scheduleSearchClear" type="button" class="hidden absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600" aria-label="Clear search">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              </button>
            </div>
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="schedulesGrid">
            <?php if ($schedules_result && $schedules_result->num_rows > 0): ?>
                <?php while ($schedule = $schedules_result->fetch_assoc()): ?>
                    <div class="bg-gray-50 rounded-lg p-4 border hover:shadow-md transition-shadow schedule-card">
                        <div class="flex justify-between items-start mb-3">
                            <h3 class="text-lg font-bold text-gray-800 schedule-name"><?= htmlspecialchars($schedule['schedule_name']) ?></h3>
                            <div class="flex gap-2">
                                <button onclick="openScheduleDetails(<?= (int)$schedule['id'] ?>)" class="text-blue-500 hover:text-blue-700 text-sm">View</button>
                                <button onclick="editSchedule(<?= (int)$schedule['id'] ?>)" class="text-blue-500 hover:text-blue-700 text-sm">Edit</button>
                                <button onclick="deleteSchedule(<?= (int)$schedule['id'] ?>)" class="text-red-500 hover:text-red-700 text-sm">Delete</button>
                            </div>
                        </div>

                        <?php
                          // Compute display time:
                          // 1) If there is a day-specific schedule for TODAY, show it, e.g. "7:00 AM - 10:00 PM (Monday)"
                          // 2) Otherwise, do NOT look ahead. If schedule is variable or full-day default,
                          //    show placeholder "--:-- - --:--"; else show the general time range.

                          $todayName = date('l');
                          $display_time = date('g:i A', strtotime($schedule['start_time'])) . ' - ' . date('g:i A', strtotime($schedule['end_time']));
                          $sid = (int)$schedule['id'];

                          // Try to get today's schedule first
                          $ds_today = $conn->prepare("SELECT start_time, end_time FROM employee_work_day_schedules WHERE schedule_id = ? AND LOWER(day_name) = LOWER(?)");
                          if ($ds_today) {
                              $ds_today->bind_param("is", $sid, $todayName);
                              if ($ds_today->execute()) {
                                  $res_today = $ds_today->get_result();
                                  if ($res_today && $res_today->num_rows > 0) {
                                      $row = $res_today->fetch_assoc();
                                      $display_time = date('g:i A', strtotime($row['start_time'])) . ' - ' . date('g:i A', strtotime($row['end_time'])) . ' (' . $todayName . ')';
                                  } else {
                                      // No schedule for today.
                                      $isFullDay = ($schedule['start_time'] === '00:00:00' && $schedule['end_time'] === '23:59:59');
                                      $daysStr = isset($schedule['days']) ? trim((string)$schedule['days']) : '';
                                      $isVariable = strcasecmp($daysStr, 'Variable') === 0;
                                      if ($isVariable || $isFullDay) {
                                          // Variable or default full-day: no specific time for today
                                          $display_time = '--:-- - --:--';
                                      } else {
                                          // Same time for selected days. Only show time if today is one of those days; otherwise placeholder
                                          $daysArr = array_filter(array_map('trim', explode(',', $daysStr)));
                                          $match = false;
                                          foreach ($daysArr as $dname) {
                                              if (strcasecmp($dname, $todayName) === 0) { $match = true; break; }
                                          }
                                          if ($match) {
                                              $display_time = date('g:i A', strtotime($schedule['start_time'])) . ' - ' . date('g:i A', strtotime($schedule['end_time'])) . ' (' . $todayName . ')';
                                          } else {
                                              $display_time = '--:-- - --:--';
                                          }
                                      }
                                  }
                              }
                          }
                        ?>
                        <div class="space-y-2 text-sm">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="font-medium"><?= htmlspecialchars($display_time) ?></span>
                            </div>
                            <div class="flex items-center gap-2 schedule-days">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <span><?= htmlspecialchars($schedule['days']) ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                <span><?= (int)$schedule['employee_count'] ?> employee(s) assigned</span>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-t">
                            <a href="view_schedule_employees.php?schedule_id=<?= (int)$schedule['id'] ?>&schedule_name=<?= urlencode($schedule['schedule_name']) ?>"
                               class="block text-center bg-[#0B2C62] hover:bg-blue-900 text-white py-2 rounded-lg text-sm font-medium">
                                View Employees
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-span-full text-center py-8 text-gray-500">No employee schedules created yet</div>
            <?php endif; ?>
        </div>
        <div id="scheduleNoResults" class="hidden col-span-full text-center py-8 text-gray-500">No schedules found</div>
    </div>

    </div>

    <!-- Create/Edit Schedule Modal (Student-style) -->
    <div id="createScheduleModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-2xl p-6 w-full max-w-md mx-4">
        <div class="flex justify-between items-center mb-4">
          <h3 id="modalTitle" class="text-lg font-bold text-gray-800">Create New Schedule</h3>
          <button onclick="hideCreateScheduleModal()" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
          </button>
        </div>
        <form id="createScheduleForm" class="space-y-4">
          <input type="hidden" id="scheduleId" name="schedule_id">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Schedule Name</label>
            <input type="text" id="sectionName" name="section_name" placeholder="--:-- --" required 
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Start Time</label>
              <input type="time" id="startTime" name="start_time" required 
                     class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">End Time</label>
              <input type="time" id="endTime" name="end_time" required 
                     class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Schedule Type</label>
            <div class="mb-4">
              <label class="flex items-center mb-2">
                <input type="radio" name="schedule_type" value="same" checked class="mr-2" onchange="toggleScheduleType()">
                Same time for all days
              </label>
              <label class="flex items-center">
                <input type="radio" name="schedule_type" value="different" class="mr-2" onchange="toggleScheduleType()">
                Different times for each day
              </label>
            </div>

            <!-- Same Time Schedule -->
            <div id="sameTimeSchedule">
              <label class="block text-sm font-medium text-gray-700 mb-2">Days</label>
              <div class="grid grid-cols-2 gap-2 mb-4">
                <label class="flex items-center"><input type="checkbox" name="days[]" value="Monday" class="mr-2">Monday</label>
                <label class="flex items-center"><input type="checkbox" name="days[]" value="Tuesday" class="mr-2">Tuesday</label>
                <label class="flex items-center"><input type="checkbox" name="days[]" value="Wednesday" class="mr-2">Wednesday</label>
                <label class="flex items-center"><input type="checkbox" name="days[]" value="Thursday" class="mr-2">Thursday</label>
                <label class="flex items-center"><input type="checkbox" name="days[]" value="Friday" class="mr-2">Friday</label>
                <label class="flex items-center"><input type="checkbox" name="days[]" value="Saturday" class="mr-2">Saturday</label>
                <label class="flex items-center"><input type="checkbox" name="days[]" value="Sunday" class="mr-2">Sunday</label>
              </div>
            </div>

            <!-- Different Time Schedule -->
            <div id="differentTimeSchedule" class="hidden">
              <div class="space-y-4">
                <?php foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $d): ?>
                  <div class="grid grid-cols-3 gap-2 items-center">
                    <label class="flex items-center"><input type="checkbox" name="day_schedules[<?= $d ?>][enabled]" value="1" class="mr-2"><?= $d ?></label>
                    <input type="time" name="day_schedules[<?= $d ?>][start_time]" class="border border-gray-300 rounded px-3 py-2">
                    <input type="time" name="day_schedules[<?= $d ?>][end_time]" class="border border-gray-300 rounded px-3 py-2">
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
          <div class="flex gap-3 pt-4">
            <button type="button" onclick="hideCreateScheduleModal()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2 rounded-lg">Cancel</button>
            <button type="submit" id="submitBtn" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg">Create Schedule</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Assign Schedule Modal (Student-style) -->
    <div id="assignScheduleModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-2xl p-6 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-bold text-gray-800">Assign Schedule to Employees</h3>
          <button onclick="hideAssignScheduleModal()" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
          </button>
        </div>

        <!-- Schedule Selection -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Select Schedule</label>
          <select id="scheduleSelect" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
            <option value="">Choose a schedule...</option>
            <?php if($schedules_for_select){ while($s = $schedules_for_select->fetch_assoc()): ?>
              <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['schedule_name']) ?> (<?= date('g:i A', strtotime($s['start_time'])) ?> - <?= date('g:i A', strtotime($s['end_time'])) ?>)</option>
            <?php endwhile; } ?>
          </select>
        </div>

        <!-- Search Employees -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Search Employees</label>
          <input type="text" id="employeeSearch" placeholder="Search by name or ID..." 
                 class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
        </div>

        <!-- Employees List -->
        <div id="employeesList" class="mb-4 max-h-60 overflow-y-auto border rounded-lg">
          <p class="text-gray-500 text-center py-4">Loading employees...</p>
        </div>

        <!-- Selected Count -->
        <div class="mb-4 text-sm text-gray-700">Selected: <span id="selectedCount">0</span> employee(s)</div>

        <div class="flex gap-3 pt-2">
          <button type="button" onclick="hideAssignScheduleModal()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2 rounded-lg">Cancel</button>
          <button type="button" onclick="assignScheduleToEmployees()" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg">Assign Schedule</button>
        </div>
      </div>
    </div>

    <!-- View Schedule Details Modal -->
    <div id="scheduleDetailsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-2xl p-6 w-full max-w-md mx-4">
        <div class="flex justify-between items-center mb-4">
          <h3 id="schedTitle" class="text-lg font-bold text-gray-800">Schedule Details</h3>
          <button onclick="closeScheduleDetails()" class="text-gray-500 hover:text-gray-700" aria-label="Close">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
          </button>
        </div>
        <div id="schedBody" class="space-y-3 text-sm text-gray-800">
          <p>Loading schedule...</p>
        </div>
        <div class="pt-4">
          <button onclick="closeScheduleDetails()" class="w-full bg-gray-600 hover:bg-gray-700 text-white py-2 rounded-lg">Close</button>
        </div>
      </div>
    </div>

    <script>
// State
let selectedEmployees = [];
let currentScheduleId = null;

// Create/Edit Schedule modal controls
function showCreateScheduleModal(){
  currentScheduleId = null;
  document.getElementById('modalTitle').textContent='Create New Schedule';
  document.getElementById('submitBtn').textContent='Create Schedule';
  document.getElementById('createScheduleForm').reset();
  document.getElementById('differentTimeSchedule').classList.add('hidden');
  document.getElementById('sameTimeSchedule').classList.remove('hidden');
  document.getElementById('createScheduleModal').classList.remove('hidden');
}
function hideCreateScheduleModal(){ document.getElementById('createScheduleModal').classList.add('hidden'); }
function toggleScheduleType(){
  const type = document.querySelector('input[name="schedule_type"]:checked').value;
  document.getElementById('sameTimeSchedule').classList.toggle('hidden', type!=='same');
  document.getElementById('differentTimeSchedule').classList.toggle('hidden', type!=='different');
  document.getElementById('startTime').required = (type==='same');
  document.getElementById('endTime').required = (type==='same');
}

// Submit create/edit schedule
document.getElementById('createScheduleForm').addEventListener('submit', function(e){
  e.preventDefault();
  const fd = new FormData(this);
  if(currentScheduleId){ fd.append('action','edit_schedule'); }
  else { fd.append('action','create_schedule'); }
  fetch('ManageEmployeeSchedule.php', { method:'POST', body: fd })
    .then(r=>r.json())
    .then(d=>{ if(d.success){ hideCreateScheduleModal(); showSuccessMessage(d.message); location.reload(); } else { alert('Error: '+d.message); } })
    .catch(()=>alert('Failed to save schedule.'));
});

// Assign modal controls
function showAssignScheduleModal(){ selectedEmployees=[]; updateSelectedCount(); loadAllEmployees(); document.getElementById('assignScheduleModal').classList.remove('hidden'); }
function hideAssignScheduleModal(){ document.getElementById('assignScheduleModal').classList.add('hidden'); }

// Load/Display Employees
function loadAllEmployees(){
  fetch('SearchEmployee.php?all=1')
    .then(async r=>{
      const text = await r.text();
      try{ const d = JSON.parse(text); return { ok:true, data:d }; }
      catch(e){ return { ok:false, text }; }
    })
    .then(res=>{
      const c = document.getElementById('employeesList');
      if(!res.ok){ c.innerHTML = `<pre class="text-red-500 text-xs whitespace-pre-wrap p-4">${res.text || 'Failed to load employees'}</pre>`; return; }
      const d = res.data;
      if(d.error){ c.innerHTML = `<p class="text-red-500 text-center py-4">${d.error}${d.details?': '+d.details:''}</p>`; return; }
      displayEmployees(d.employees||[]);
    })
    .catch(err=>{ document.getElementById('employeesList').innerHTML = `<p class="text-red-500 text-center py-4">Failed to load employees</p>`; console.error('SearchEmployee fetch error', err); });
}
function displayEmployees(list){
  const c = document.getElementById('employeesList');
  if(list.length===0){ c.innerHTML='<p class="text-gray-500 text-center py-4">No employees found</p>'; return; }
  let html='';
  list.forEach(emp=>{
    const isSelected = selectedEmployees.includes(emp.id_number);
    const hasSchedule = !!emp.current_section;
    const scheduleInfo = hasSchedule ? `<span class=\"text-orange-600 font-medium\">Current: ${emp.current_section}</span>` : '<span class=\"text-green-600\">Available</span>';
    const fullName = emp.full_name || (emp.first_name + ' ' + emp.last_name);
    html += `
      <div class="flex items-center p-3 border-b hover:bg-gray-50">
        <input type="checkbox" id="emp_${emp.id_number}" ${isSelected?'checked':''} onchange="toggleEmployee('${emp.id_number}', ${hasSchedule?'true':'false'}, '${fullName}', '${emp.current_section||''}')" class="mr-3" />
        <label for="emp_${emp.id_number}" class="flex-1 cursor-pointer">
          <div class="font-medium">${fullName}</div>
          <div class="text-sm text-gray-600">ID: ${emp.id_number}</div>
          <div class="text-sm">${scheduleInfo}</div>
        </label>
      </div>`;
  });
  c.innerHTML = html;
}
function toggleEmployee(id, hasSchedule, name, currentSection){
  const i = selectedEmployees.indexOf(id);
  if(i>-1){ selectedEmployees.splice(i,1); }
  else {
    if(hasSchedule==='true'){
      const msg = `${name} is already assigned to \"${currentSection}\". Type the employee's name to confirm reassignment: \"${name}\"`;
      const input = prompt(msg);
      if(input!==name){ document.getElementById('emp_'+id).checked=false; return; }
    }
    selectedEmployees.push(id);
  }
  updateSelectedCount();
}
function updateSelectedCount(){ document.getElementById('selectedCount').textContent = selectedEmployees.length; }

// Assign
function assignScheduleToEmployees(){
  const scheduleId = document.getElementById('scheduleSelect').value;
  if(!scheduleId){ alert('Please select a schedule'); return; }
  if(selectedEmployees.length===0){ alert('Please select at least one employee'); return; }
  const fd = new FormData();
  fd.append('action','assign_schedule');
  fd.append('schedule_id', scheduleId);
  selectedEmployees.forEach(id=> fd.append('employee_ids[]', id));
  fetch('ManageEmployeeSchedule.php',{ method:'POST', body: fd })
    .then(r=>r.json()).then(d=>{ if(d.success){ showSuccessMessage(d.message); hideAssignScheduleModal(); location.reload(); } else { alert('Error: '+d.message); } })
    .catch(()=>alert('Failed to assign schedule. Please try again.'));
}

// View Employees follows the student pattern: navigate to a dedicated page
function viewScheduleEmployees(id, scheduleName){
  window.location.href = `view_schedule_employees.php?schedule_id=${id}&schedule_name=${encodeURIComponent(scheduleName)}`;
}

// Employee search
 document.getElementById('employeeSearch').addEventListener('input', function(){
  const q = this.value.trim();
  if(q.length === 0){ loadAllEmployees(); return; }
  if(q.length < 2) return;
  fetch(`SearchEmployee.php?query=${encodeURIComponent(q)}`)
    .then(async r=>{ const text = await r.text(); try{ return JSON.parse(text); } catch(e){ return { error:'Invalid JSON', details:text }; } })
    .then(d=>{ const c=document.getElementById('employeesList'); if(d.error){ c.innerHTML = `<pre class=\"text-red-500 text-xs whitespace-pre-wrap p-4\">${d.details||d.error}</pre>`; return; } displayEmployees(d.employees||[]); })
    .catch(err=>{ document.getElementById('employeesList').innerHTML = `<p class=\"text-red-500 text-center py-4\">Failed to load employees</p>`; console.error('SearchEmployee fetch error', err); });
});

function showSuccessMessage(message){
  const s=document.getElementById('successMessage'); const t=document.getElementById('successText'); t.textContent=message; s.classList.remove('hidden'); setTimeout(()=>s.classList.add('hidden'),3000);
}

function editSchedule(id){
  fetch(`get_employee_schedule.php?id=${id}`)
    .then(r=>r.json()).then(data=>{
      if(data.success){
        const schedule=data.schedule; 
        document.getElementById('scheduleId').value=schedule.id;
        document.getElementById('sectionName').value=schedule.schedule_name;
        if(schedule.has_day_schedules){
          document.querySelector('input[name="schedule_type"][value="different"]').checked = true; toggleScheduleType();
          const ds = schedule.day_schedules; Object.keys(ds).forEach(day=>{
            const d=ds[day];
            const cb=document.querySelector(`input[name=\"day_schedules[${day}][enabled]\"]`);
            const st=document.querySelector(`input[name=\"day_schedules[${day}][start_time]\"]`);
            const et=document.querySelector(`input[name=\"day_schedules[${day}][end_time]\"]`);
            if(cb) cb.checked = true; if(st) st.value = d.start_time; if(et) et.value = d.end_time;
          });
        } else {
          document.querySelector('input[name="schedule_type"][value="same"]').checked = true; toggleScheduleType();
          document.getElementById('startTime').value = schedule.start_time;
          document.getElementById('endTime').value = schedule.end_time;
          document.querySelectorAll('input[name="days[]"]').forEach(cb=>cb.checked=false);
          if(schedule.days && schedule.days !== 'Variable'){
            schedule.days.split(',').forEach(day=>{
              const cb=document.querySelector(`input[name=\"days[]\"][value=\"${day.trim()}\"]`); if(cb) cb.checked=true;
            });
          }
        }
        document.getElementById('modalTitle').textContent='Edit Schedule';
        document.getElementById('submitBtn').textContent='Update Schedule';
        currentScheduleId = id;
        document.getElementById('createScheduleModal').classList.remove('hidden');
      } else { alert('Error loading schedule data'); }
    }).catch(()=>alert('Failed to load schedule data'));
}

function deleteSchedule(id){
  if(!confirm('Delete this schedule? This will remove all employee assignments.')) return;
  const fd=new FormData(); fd.append('action','delete_schedule'); fd.append('schedule_id', id);
  fetch('ManageEmployeeSchedule.php',{ method:'POST', body: fd })
    .then(r=>r.json()).then(d=>{ if(d.success){ showSuccessMessage(d.message); location.reload(); } else { alert('Error: '+d.message); } });
}

// View Schedule Details
function openScheduleDetails(id){
  const modal = document.getElementById('scheduleDetailsModal');
  const body = document.getElementById('schedBody');
  const title = document.getElementById('schedTitle');
  body.innerHTML = '<p>Loading schedule...</p>';
  title.textContent = 'Schedule Details';
  modal.classList.remove('hidden');
  fetch(`get_employee_schedule.php?id=${id}`)
    .then(res=>res.json())
    .then(d=>{
      if(!d.success){ body.innerHTML = `<p class=\"text-red-600\">Failed to load schedule.</p>`; return; }
      const s = d.schedule;
      title.textContent = s.schedule_name + ' — Details';
      const items = [];
      if(s.has_day_schedules && s.day_schedules && Object.keys(s.day_schedules).length){
        const order = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
        items.push(`<div><div class=\"font-semibold mb-1\">Per-day Times</div>`);
        items.push('<ul class=\"list-disc ml-5 space-y-1\">');
        order.forEach(day=>{
          const dsd = s.day_schedules[day];
          if(dsd){
            items.push(`<li><span class=\"font-medium\">${day}:</span> ${formatTime(dsd.start_time)} - ${formatTime(dsd.end_time)}</li>`);
          }
        });
        items.push('</ul></div>');
      } else {
        items.push(`<div><span class=\"font-semibold\">Time:</span> ${formatTime(s.start_time)} - ${formatTime(s.end_time)}</div>`);
        items.push(`<div><span class=\"font-semibold\">Days:</span> ${s.days || '—'}</div>`);
      }
      body.innerHTML = items.join('');
    })
    .catch(()=>{ body.innerHTML = '<p class="text-red-600">Failed to load schedule.</p>'; });
}
function closeScheduleDetails(){ document.getElementById('scheduleDetailsModal').classList.add('hidden'); }
function formatTime(t){ try{ const d = new Date(`1970-01-01T${t}`); return d.toLocaleTimeString([], {hour:'numeric', minute:'2-digit'}); } catch(e){ return t; } }

// Schedule search (client-side filter)
const scheduleSearchEl = document.getElementById('scheduleSearch');
if (scheduleSearchEl) {
  const icon = document.getElementById('scheduleSearchIcon');
  const clearBtn = document.getElementById('scheduleSearchClear');
  const applyFilter = () => {
    const q = scheduleSearchEl.value.trim().toLowerCase();
    if (icon) icon.classList.toggle('hidden', q.length > 0);
    if (clearBtn) clearBtn.classList.toggle('hidden', q.length === 0);
    // Adjust left padding based on icon visibility
    if (q.length > 0) {
      scheduleSearchEl.classList.remove('pl-10');
      scheduleSearchEl.classList.add('pl-3');
    } else {
      scheduleSearchEl.classList.add('pl-10');
      scheduleSearchEl.classList.remove('pl-3');
    }
    const cards = document.querySelectorAll('.schedule-card');
    let visibleCount = 0;
    cards.forEach(card => {
      const nameEl = card.querySelector('.schedule-name');
      const daysEl = card.querySelector('.schedule-days');
      const name = nameEl ? nameEl.textContent.toLowerCase() : '';
      const days = daysEl ? daysEl.textContent.toLowerCase() : '';
      const match = q.length === 0 || name.includes(q) || days.includes(q);
      card.style.display = match ? '' : 'none';
      if (match) visibleCount++;
    });
    const noRes = document.getElementById('scheduleNoResults');
    if (noRes) noRes.classList.toggle('hidden', visibleCount > 0);
  };
  scheduleSearchEl.addEventListener('input', applyFilter);
  scheduleSearchEl.addEventListener('focus', applyFilter);
  scheduleSearchEl.addEventListener('blur', applyFilter);
  if (clearBtn) {
    clearBtn.addEventListener('click', () => {
      scheduleSearchEl.value = '';
      scheduleSearchEl.focus();
      applyFilter();
    });
  }
}
</script>

</body>
</html>
