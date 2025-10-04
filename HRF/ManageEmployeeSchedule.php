<?php
session_start();
include("../StudentLogin/db_conn.php");

// Require HR login
if (!((isset($_SESSION['role']) && $_SESSION['role'] === 'hr') || isset($_SESSION['hr_name']))) {
    header("Location: ../StudentLogin/login.php");
    exit;
}
// Actor id for audit fields
$actorId = $_SESSION['id_number'] ?? 0;

// Handle form submission for creating/editing schedules
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    if ($_POST['action'] == 'create_schedule') {
        // Teacher context
        $teacher_id = $_POST['teacher_id'] ?? '';
        $section_name = $_POST['section_name'] ?? '';
        $schedule_type = $_POST['schedule_type'];
        if (empty($teacher_id) || empty($section_name)) {
            $response = ['success' => false, 'message' => 'Please select a teacher.'];
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }
        
        if ($schedule_type == 'same') {
            // Same time for all days
            $start_time = $_POST['start_time'];
            $end_time = $_POST['end_time'];
            $days = isset($_POST['days']) ? implode(', ', (array)$_POST['days']) : '';

            // Validation: require at least one day
            if (trim($days) === '') {
                $response = ['success' => false, 'message' => 'Please select at least one day.'];
                header('Content-Type: application/json');
                echo json_encode($response);
                exit;
            }
            
            // Employees: store in employee_work_schedules using schedule_name
            $stmt = $conn->prepare("INSERT INTO employee_work_schedules (schedule_name, start_time, end_time, days, created_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $section_name, $start_time, $end_time, $days, $actorId);
            
            if ($stmt->execute()) {
                // Auto-assign teacher to this schedule
                $new_schedule_id = $conn->insert_id;
                $rem = $conn->prepare("DELETE FROM employee_schedules WHERE employee_id = ?");
                $rem->bind_param("s", $teacher_id);
                $rem->execute();
                $ins = $conn->prepare("INSERT INTO employee_schedules (employee_id, schedule_id, assigned_by) VALUES (?, ?, ?)");
                $ins->bind_param("sii", $teacher_id, $new_schedule_id, $actorId);
                $ins->execute();
                $response['success'] = true;
                $response['message'] = "Schedule created and teacher assigned successfully!";
            } else {
                $response['message'] = "Error creating schedule.";
            }
        } else {
            // Different times for each day
            $day_schedules = isset($_POST['day_schedules']) ? $_POST['day_schedules'] : [];
            $enabled_days = [];

            // Validation: ensure at least one enabled day with valid times
            $has_valid_day = false;
            foreach ($day_schedules as $day => $schedule) {
                if (!empty($schedule['enabled']) && !empty($schedule['start_time']) && !empty($schedule['end_time'])) {
                    $has_valid_day = true; break;
                }
            }
            if (!$has_valid_day) {
                $response = ['success' => false, 'message' => 'Please enable at least one day and provide start and end times.'];
                header('Content-Type: application/json');
                echo json_encode($response);
                exit;
            }
            
            // First, create the main schedule record with placeholder times
            $stmt = $conn->prepare("INSERT INTO employee_work_schedules (schedule_name, start_time, end_time, days, created_by) VALUES (?, '00:00:00', '23:59:59', 'Variable', ?)");
            $stmt->bind_param("si", $section_name, $actorId);
            
            if ($stmt->execute()) {
                $schedule_id = $conn->insert_id;
                
                // Insert day-specific schedules (employee)
                foreach ($day_schedules as $day => $schedule) {
                    if (isset($schedule['enabled']) && $schedule['start_time'] && $schedule['end_time']) {
                        $day_stmt = $conn->prepare("INSERT INTO employee_work_day_schedules (schedule_id, day_name, start_time, end_time) VALUES (?, ?, ?, ?)");
                        $day_stmt->bind_param("isss", $schedule_id, $day, $schedule['start_time'], $schedule['end_time']);
                        $day_stmt->execute();
                        $enabled_days[] = $day;
                    }
                }
                
                // Update the main schedule with actual days
                $days_string = implode(', ', $enabled_days);
                $update_stmt = $conn->prepare("UPDATE employee_work_schedules SET days = ? WHERE id = ?");
                $update_stmt->bind_param("si", $days_string, $schedule_id);
                $update_stmt->execute();
                
                // Auto-assign teacher to this schedule
                $rem = $conn->prepare("DELETE FROM employee_schedules WHERE employee_id = ?");
                $rem->bind_param("s", $teacher_id);
                $rem->execute();
                $ins = $conn->prepare("INSERT INTO employee_schedules (employee_id, schedule_id, assigned_by) VALUES (?, ?, ?)");
                $ins->bind_param("sii", $teacher_id, $schedule_id, $actorId);
                $ins->execute();

                $response['success'] = true;
                $response['message'] = "Day-specific schedule created and teacher assigned successfully!";
            } else {
                $response['message'] = "Error creating schedule.";
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    if ($_POST['action'] == 'edit_schedule') {
        $schedule_id = $_POST['schedule_id'];
        $section_name = $_POST['section_name'];
        $schedule_type = $_POST['schedule_type'];
        
        if ($schedule_type == 'same') {
            // Same time for all days
            $start_time = $_POST['start_time'];
            $end_time = $_POST['end_time'];
            $days = implode(',', $_POST['days']);
            
            // Clear any existing day-specific schedules
            $clear_day_schedules = $conn->prepare("DELETE FROM employee_work_day_schedules WHERE schedule_id = ?");
            $clear_day_schedules->bind_param("i", $schedule_id);
            $clear_day_schedules->execute();
            
            // Update main schedule
            $stmt = $conn->prepare("UPDATE employee_work_schedules SET schedule_name = ?, start_time = ?, end_time = ?, days = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $section_name, $start_time, $end_time, $days, $schedule_id);
            $stmt->execute();
            
            $schedule_text = $section_name . " (" . 
                            date('g:i A', strtotime($start_time)) . " - " . 
                            date('g:i A', strtotime($end_time)) . ")";
        } else {
            // Different times for each day
            $day_schedules = $_POST['day_schedules'];
            $enabled_days = [];
            
            // Clear existing day-specific schedules
            $clear_day_schedules = $conn->prepare("DELETE FROM employee_work_day_schedules WHERE schedule_id = ?");
            $clear_day_schedules->bind_param("i", $schedule_id);
            $clear_day_schedules->execute();
            
            // Update main schedule with placeholder times
            $stmt = $conn->prepare("UPDATE employee_work_schedules SET schedule_name = ?, start_time = '00:00:00', end_time = '23:59:59', days = 'Variable' WHERE id = ?");
            $stmt->bind_param("si", $section_name, $schedule_id);
            $stmt->execute();
            
            // Insert new day-specific schedules
            foreach ($day_schedules as $day => $schedule) {
                if (isset($schedule['enabled']) && $schedule['start_time'] && $schedule['end_time']) {
                    $day_stmt = $conn->prepare("INSERT INTO employee_work_day_schedules (schedule_id, day_name, start_time, end_time) VALUES (?, ?, ?, ?)");
                    $day_stmt->bind_param("isss", $schedule_id, $day, $schedule['start_time'], $schedule['end_time']);
                    $day_stmt->execute();
                    $enabled_days[] = $day;
                }
            }
            
            // Update the main schedule with actual days
            $days_string = implode(', ', $enabled_days);
            $update_days = $conn->prepare("UPDATE employee_work_schedules SET days = ? WHERE id = ?");
            $update_days->bind_param("si", $days_string, $schedule_id);
            $update_days->execute();
            
            $schedule_text = $section_name . " (Variable Times)";
        }
        
        // No employee-side mirrors required here
        
        $response = ['success' => true, 'message' => 'Schedule updated successfully!'];
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    if ($_POST['action'] == 'delete_schedule') {
        $schedule_id = $_POST['schedule_id'];
        
        // Delete schedule (employee_schedules will be deleted by CASCADE)
        $stmt = $conn->prepare("DELETE FROM employee_work_schedules WHERE id = ?");
        $stmt->bind_param("i", $schedule_id);
        
        if ($stmt->execute()) {
            $response = ['success' => true, 'message' => 'Schedule deleted successfully!'];
        } else {
            $response = ['success' => false, 'message' => 'Error deleting schedule: ' . $conn->error];
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    if ($_POST['action'] == 'assign_schedule') {
        $schedule_id = $_POST['schedule_id'];
        // Accept either employee_ids[] or student_ids[] from frontend
        $employee_ids = $_POST['employee_ids'] ?? $_POST['student_ids'] ?? [];
        
        // Get schedule details (employee)
        $schedule_stmt = $conn->prepare("SELECT schedule_name, start_time, end_time FROM employee_work_schedules WHERE id = ?");
        $schedule_stmt->bind_param("i", $schedule_id);
        $schedule_stmt->execute();
        $schedule_result = $schedule_stmt->get_result();
        $schedule_info = $schedule_result->fetch_assoc();
        
        // Check if this schedule has day-specific times
        $day_schedules_query = $conn->prepare("SELECT day_name, start_time, end_time FROM employee_work_day_schedules WHERE schedule_id = ? ORDER BY FIELD(day_name, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')");
        $day_schedules_query->bind_param("i", $schedule_id);
        $day_schedules_query->execute();
        $day_schedules_result = $day_schedules_query->get_result();
        
        if ($day_schedules_result->num_rows > 0) {
            // Has day-specific schedules - create descriptive text
            $schedule_text = $schedule_info['schedule_name'] . " (Variable Times)";
        } else {
            // Uses same time for all days
            $schedule_text = $schedule_info['schedule_name'] . " (" . 
                            date('g:i A', strtotime($schedule_info['start_time'])) . " - " . 
                            date('g:i A', strtotime($schedule_info['end_time'])) . ")";
        }
        
        $success_count = 0; $error_count = 0;
        foreach ($employee_ids as $emp_id) {
            // Remove any existing schedule assignments for this employee
            $remove_existing = $conn->prepare("DELETE FROM employee_schedules WHERE employee_id = ?");
            $remove_existing->bind_param("s", $emp_id);
            $remove_existing->execute();
            
            // Insert new assignment
            $insert_stmt = $conn->prepare("INSERT INTO employee_schedules (employee_id, schedule_id, assigned_by) VALUES (?, ?, ?)");
            $insert_stmt->bind_param("sii", $emp_id, $schedule_id, $actorId);
            if ($insert_stmt->execute()) { $success_count++; } else { $error_count++; }
        }
        
        $message = "Assigned $success_count Employee(s) Successfully";
        if ($error_count > 0) {
            $message .= " ($error_count failed)";
        }
        
        $response = ['success' => true, 'message' => $message];
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// Fetch all schedules
$schedules_query = "SELECT cs.*, COUNT(es.id) as employee_count 
                   FROM employee_work_schedules cs 
                   LEFT JOIN employee_schedules es ON cs.id = es.schedule_id 
                   GROUP BY cs.id 
                   ORDER BY cs.schedule_name";
$schedules_result = $conn->query($schedules_query);

// Fetch available teachers for dropdown (active employees with teacher accounts)
// Uses unified employee_accounts with role = 'teacher'
$teachers_sql = "SELECT e.id_number, CONCAT(e.first_name, ' ', e.last_name) AS full_name
                 FROM employees e
                 INNER JOIN employee_accounts a ON a.employee_id = e.id_number AND a.role = 'teacher'
                 WHERE e.deleted_at IS NULL OR e.deleted_at IS NULL
                 ORDER BY full_name";
$teachers_result = $conn->query($teachers_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Schedule Management - Cornerstone College Inc.</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .school-gradient { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #1e40af 100%); }
        .card-shadow { box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        /* Match student dropdown UI */
        #scheduleSelect { font-size: 0.875rem; }
        #scheduleSelect option { padding-top: 2px; padding-bottom: 2px; }
        .sched-item { padding: 8px 12px; cursor: pointer; }
        .sched-item:hover { background-color: #f3f4f6; }
        #scheduleTrigger { border-color: #0B2C62 !important; box-shadow: 0 0 0 2px rgba(11, 44, 98, 0.12); background-color: #ffffff; }
        #scheduleTrigger:focus { box-shadow: 0 0 0 3px rgba(11, 44, 98, 0.25); outline: none; }
        #scheduleMenu { border-color: #0B2C62 !important; box-shadow: 0 10px 20px rgba(0,0,0,0.15); background-color: #ffffff; }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">

<!-- Header -->
<header class="bg-[#0B2C62] text-white shadow-lg">
    <div class="container mx-auto px-6 py-4">
        <div class="flex justify-between items-center">
            <!-- Left: back button + page title -->
            <div class="flex items-center space-x-3">
                <button onclick="handleBackNavigation()" class="bg-white bg-opacity-20 hover:bg-opacity-30 p-2 rounded-lg transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>
                <span class="text-lg md:text-xl font-bold">Schedule Management</span>
            </div>
            <!-- Right: school logo + name -->
            <div class="flex items-center space-x-3">
                <img src="../images/LogoCCI.png" alt="Cornerstone College Inc." class="h-10 w-10 md:h-12 md:w-12 rounded-full bg-white p-1">
                <div class="text-right leading-tight">
                    <div class="text-sm md:text-base font-bold">Cornerstone College Inc.</div>
                    <div class="text-[11px] md:text-sm text-blue-200">Schedule Management System</div>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Main Content -->
<div class="container mx-auto px-6 py-8">
    <!-- Success Message -->
    <div id="successMessage" class="hidden mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">
        <span id="successText"></span>
    </div>
    <div id="errorMessage" class="hidden mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
        <span id="errorText"></span>
    </div>

    <!-- Action Buttons -->
    <div class="mb-6 flex gap-4">
        <button onclick="showCreateScheduleModal()" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Create Schedule
        </button>
        <!-- Removed Assign Schedule to Employees button -->
    </div>

    <!-- Schedules List -->
    <div class="bg-white rounded-xl card-shadow p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
            <h2 class="text-xl font-bold text-gray-800">Schedules</h2>
            <div class="w-full md:w-80">
                <div class="relative">
                    <div id="classSearchIcon" class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input id="classScheduleSearch" type="text" placeholder="Search schedules by name or day..." class="w-full pl-10 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0B2C62] focus:border-transparent" />
                    <button id="classSearchClear" type="button" class="hidden absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600" aria-label="Clear search">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="classSchedulesGrid">
            <?php if ($schedules_result && $schedules_result->num_rows > 0): ?>
                <?php while ($schedule = $schedules_result->fetch_assoc()): ?>
                    <div class="bg-gray-50 rounded-lg p-4 border hover:shadow-md transition-shadow class-schedule-card">
                        <div class="flex justify-between items-start mb-3">
                            <h3 class="text-lg font-bold text-gray-800 class-schedule-name"><?= htmlspecialchars($schedule['schedule_name']) ?></h3>
                            <div class="flex gap-2">
                                <button onclick="openClassScheduleDetails(<?= (int)$schedule['id'] ?>)" class="text-blue-500 hover:text-blue-700 text-sm">View</button>
                                <button onclick="editSchedule(<?= (int)$schedule['id'] ?>)" class="text-blue-500 hover:text-blue-700 text-sm">Edit</button>
                                <button onclick="deleteSchedule(<?= (int)$schedule['id'] ?>)" class="text-red-500 hover:text-red-700 text-sm">Delete</button>
                            </div>
                        </div>

                        <?php
                          // Real-time display time for employee schedules
                          date_default_timezone_set('Asia/Manila');
                          $todayName = date('l');
                          $display_time = date('g:i A', strtotime($schedule['start_time'])) . ' - ' . date('g:i A', strtotime($schedule['end_time']));
                          $sid = (int)$schedule['id'];
                          $ds_today = $conn->prepare("SELECT start_time, end_time FROM employee_work_day_schedules WHERE schedule_id = ? AND LOWER(day_name) = LOWER(?)");
                          if ($ds_today) {
                              $ds_today->bind_param("is", $sid, $todayName);
                              if ($ds_today->execute()) {
                                  $res_today = $ds_today->get_result();
                                  if ($res_today && $res_today->num_rows > 0) {
                                      $row = $res_today->fetch_assoc();
                                      $display_time = date('g:i A', strtotime($row['start_time'])) . ' - ' . date('g:i A', strtotime($row['end_time'])) . ' (' . $todayName . ')';
                                  } else {
                                      $isFullDay = ($schedule['start_time'] === '00:00:00' && $schedule['end_time'] === '23:59:59');
                                      $daysStr = isset($schedule['days']) ? trim((string)$schedule['days']) : '';
                                      $isVariable = strcasecmp($daysStr, 'Variable') === 0;
                                      if ($isVariable || $isFullDay) {
                                          $display_time = '--:-- - --:--';
                                      } else {
                                          $daysArr = array_filter(array_map('trim', explode(',', $daysStr)));
                                          $match = false;
                                          foreach ($daysArr as $dname) { if (strcasecmp($dname, $todayName) === 0) { $match = true; break; } }
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
                            <div class="flex items-center gap-2 class-schedule-days">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <span><?= htmlspecialchars($schedule['days']) ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <span>Teacher: <?= htmlspecialchars($schedule['schedule_name']) ?></span>
                            </div>
                        </div>
                        
                        <!-- Removed View Employees button as each schedule is for a single teacher -->
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-span-full text-center py-8">
                    <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <p class="text-gray-500 text-lg">No schedules created yet</p>
                    <p class="text-gray-400 text-sm">Click "Create Schedule" to get started</p>
                </div>
            <?php endif; ?>
        </div>
        <!-- No results message for search -->
        <div id="classNoResults" class="hidden col-span-full text-center py-8 text-gray-500">No schedules found</div>
    </div>
</div>

<!-- Reassign Confirmation Modal -->
<div id="reassignConfirmModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[60]">
  <div class="bg-white rounded-2xl p-6 w-full max-w-md mx-4">
    <div class="flex justify-between items-center mb-3">
      <h3 class="text-lg font-bold text-gray-800">Confirm Reassignment</h3>
      <button onclick="hideReassignConfirm()" class="text-gray-500 hover:text-gray-700" aria-label="Close">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
      </button>
    </div>
    <div id="reassignConfirmBody" class="text-sm text-gray-700 space-y-2">
      <!-- dynamic content -->
    </div>
    <div class="flex gap-3 pt-4">
      <button onclick="hideReassignConfirm()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2 rounded-lg">Cancel</button>
      <button onclick="confirmProceedReassign()" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg">Confirm</button>
    </div>
  </div>
  
</div>

<!-- View Schedule Details Modal (Employees) -->
<div id="classScheduleDetailsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 id="classSchedTitle" class="text-lg font-bold text-gray-800">Schedule Details</h3>
            <button onclick="closeClassScheduleDetails()" class="text-gray-500 hover:text-gray-700" aria-label="Close">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
        <div id="classSchedBody" class="space-y-3 text-sm text-gray-800">
            <p>Loading schedule...</p>
        </div>
        <div class="pt-4">
            <button onclick="closeClassScheduleDetails()" class="w-full bg-gray-600 hover:bg-gray-700 text-white py-2 rounded-lg">Close</button>
        </div>
    </div>
    
    </div>

<!-- Create/Edit Schedule Modal -->
<div id="createScheduleModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 id="modalTitle" class="text-lg font-bold text-gray-800">Create New Schedule</h3>
            <button onclick="hideCreateScheduleModal()" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form id="createScheduleForm" class="space-y-4">
            <input type="hidden" id="scheduleId" name="schedule_id">
            <!-- Hidden field to keep schedule_name (teacher full name) for backend compatibility -->
            <input type="hidden" id="sectionNameHidden" name="section_name">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Teacher Name</label>
                <select id="sectionName" name="teacher_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    <option value="">-- Select Teacher --</option>
                    <?php if ($teachers_result && $teachers_result->num_rows > 0): ?>
                        <?php while ($t = $teachers_result->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($t['id_number']) ?>" data-name="<?= htmlspecialchars($t['full_name']) ?>">
                                <?= htmlspecialchars($t['full_name']) ?>
                            </option>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <option value="" disabled>No teachers found</option>
                    <?php endif; ?>
                </select>
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
                    <div id="sameDaysGroup" class="grid grid-cols-2 gap-2 mb-2">
                        <label class="flex items-center">
                            <input type="checkbox" name="days[]" value="Monday" class="mr-2">
                            Monday
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="days[]" value="Tuesday" class="mr-2">
                            Tuesday
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="days[]" value="Wednesday" class="mr-2">
                            Wednesday
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="days[]" value="Thursday" class="mr-2">
                            Thursday
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="days[]" value="Friday" class="mr-2">
                            Friday
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="days[]" value="Saturday" class="mr-2">
                            Saturday
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" name="days[]" value="Sunday" class="mr-2">
                            Sunday
                        </label>
                    </div>
                    <p id="sameDaysError" class="hidden text-sm text-red-600 mb-4">Please select at least one day.</p>
                </div>

                <!-- Different Time Schedule -->
                <div id="differentTimeSchedule" class="hidden">
                    <div class="space-y-4">
                        <div class="grid grid-cols-3 gap-2 items-center">
                            <label class="flex items-center">
                                <input type="checkbox" name="day_schedules[Monday][enabled]" value="1" class="mr-2">
                                Monday
                            </label>
                            <input type="time" name="day_schedules[Monday][start_time]" class="border border-gray-300 rounded px-3 py-2">
                            <input type="time" name="day_schedules[Monday][end_time]" class="border border-gray-300 rounded px-3 py-2">
                        </div>
                        <div class="grid grid-cols-3 gap-2 items-center">
                            <label class="flex items-center">
                                <input type="checkbox" name="day_schedules[Tuesday][enabled]" value="1" class="mr-2">
                                Tuesday
                            </label>
                            <input type="time" name="day_schedules[Tuesday][start_time]" class="border border-gray-300 rounded px-3 py-2">
                            <input type="time" name="day_schedules[Tuesday][end_time]" class="border border-gray-300 rounded px-3 py-2">
                        </div>
                        <div class="grid grid-cols-3 gap-2 items-center">
                            <label class="flex items-center">
                                <input type="checkbox" name="day_schedules[Wednesday][enabled]" value="1" class="mr-2">
                                Wednesday
                            </label>
                            <input type="time" name="day_schedules[Wednesday][start_time]" class="border border-gray-300 rounded px-3 py-2">
                            <input type="time" name="day_schedules[Wednesday][end_time]" class="border border-gray-300 rounded px-3 py-2">
                        </div>
                        <div class="grid grid-cols-3 gap-2 items-center">
                            <label class="flex items-center">
                                <input type="checkbox" name="day_schedules[Thursday][enabled]" value="1" class="mr-2">
                                Thursday
                            </label>
                            <input type="time" name="day_schedules[Thursday][start_time]" class="border border-gray-300 rounded px-3 py-2">
                            <input type="time" name="day_schedules[Thursday][end_time]" class="border border-gray-300 rounded px-3 py-2">
                        </div>
                        <div class="grid grid-cols-3 gap-2 items-center">
                            <label class="flex items-center">
                                <input type="checkbox" name="day_schedules[Friday][enabled]" value="1" class="mr-2">
                                Friday
                            </label>
                            <input type="time" name="day_schedules[Friday][start_time]" class="border border-gray-300 rounded px-3 py-2">
                            <input type="time" name="day_schedules[Friday][end_time]" class="border border-gray-300 rounded px-3 py-2">
                        </div>
                        <div class="grid grid-cols-3 gap-2 items-center">
                            <label class="flex items-center">
                                <input type="checkbox" name="day_schedules[Saturday][enabled]" value="1" class="mr-2">
                                Saturday
                            </label>
                            <input type="time" name="day_schedules[Saturday][start_time]" class="border border-gray-300 rounded px-3 py-2">
                            <input type="time" name="day_schedules[Saturday][end_time]" class="border border-gray-300 rounded px-3 py-2">
                        </div>
                        <div class="grid grid-cols-3 gap-2 items-center">
                            <label class="flex items-center">
                                <input type="checkbox" name="day_schedules[Sunday][enabled]" value="1" class="mr-2">
                                Sunday
                            </label>
                            <input type="time" name="day_schedules[Sunday][start_time]" class="border border-gray-300 rounded px-3 py-2">
                            <input type="time" name="day_schedules[Sunday][end_time]" class="border border-gray-300 rounded px-3 py-2">
                        </div>
                    </div>
                    <p id="diffDaysError" class="hidden text-sm text-red-600">Please enable at least one day and provide start and end times.</p>
                </div>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="hideCreateScheduleModal()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2 rounded-lg">Cancel</button>
                <button type="submit" id="submitBtn" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg">Create Schedule</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-6 w-full max-w-sm mx-4">
        <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Delete Schedule</h3>
            <p class="text-sm text-gray-500 mb-6">Are you sure you want to delete this schedule? This will remove all employee assignments and cannot be undone.</p>
            <div class="flex gap-3">
                <button onclick="hideDeleteModal()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg font-medium">
                    Cancel
                </button>
                <button onclick="confirmDelete()" class="flex-1 bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg font-medium">
                    Delete
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Assign Schedule Modal -->
<div id="assignScheduleModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-6 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-800">Assign Schedule to Employees</h3>
            <button onclick="hideAssignScheduleModal()" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <!-- Schedule Selection -->
        <div class="mb-2">
          <label class="block text-sm font-medium text-gray-700 mb-2">Select Schedule</label>
          <input type="text" id="scheduleSearch" placeholder="Search by Schedule Name" 
                 class="w-full mb-2 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500" />
          <div class="relative">
            <!-- Trigger that looks like a select -->
            <button type="button" id="scheduleTrigger" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-left bg-white focus:ring-2 focus:ring-green-500">
              <span id="scheduleTriggerText">Choose a schedule...</span>
              <span class="float-right">▾</span>
            </button>
            <!-- Scrollable menu (about 10 items tall) -->
            <div id="scheduleMenu" class="hidden absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow max-h-64 overflow-y-auto">
              <div id="scheduleMenuList"></div>
            </div>
          </div>
          <!-- Hidden native select for value/state -->
          <select id="scheduleSelect" class="hidden">
            <option value="">Choose a schedule...</option>
            <?php 
            $schedules_result->data_seek(0); // Reset result pointer
            while ($s = $schedules_result->fetch_assoc()): ?>
              <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['schedule_name']) ?> (<?= date('g:i A', strtotime($s['start_time'])) ?> - <?= date('g:i A', strtotime($s['end_time'])) ?>)</option>
            <?php endwhile; ?>
          </select>
          <p id="assignInlineError" class="hidden text-sm text-red-600 mt-1"></p>
        </div>

        <!-- Search Students -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Search Employees</label>
            <input type="text" id="studentSearch" placeholder="Search employee by name or ID..." 
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500">
        </div>

        <!-- Employees List -->
        <div id="studentsList" class="border rounded-lg h-72 overflow-y-auto">
            <p class="text-gray-500 text-center py-4">Loading employees...</p>
        </div>
        <!-- View More (replaced by infinite scroll) -->
        <div id="studentsMoreContainer" class="mb-4 hidden text-center"></div>

        <!-- Selected Students Count -->
        <div class="mb-4">
            <p class="text-sm text-gray-600">Selected: <span id="selectedCount">0</span> employee(s)</p>
        </div>

        <div class="flex gap-3">
            <button onclick="hideAssignScheduleModal()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2 rounded-lg">Cancel</button>
            <button onclick="assignScheduleToStudents()" class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg">Assign Schedule</button>
        </div>
    </div>
</div>

<script>
let selectedStudents = [];
// Cache minimal info for students we've displayed so we can show them in confirmation even after search filters
const studentsIndex = {}; // id -> { name, hasCurrent }
// Pagination state for students
let studentsLimit = 15;
let studentsOffset = 0;
let studentsQuery = '';
let studentsHasMore = false;
let studentsLoading = false;
// Preserve previous page overflow so we can restore after Assign modal closes
let __prevBodyOverflow = '';
let __prevHtmlOverflow = '';
// Remember last clicked teacher name so Create Schedule can auto-fill
let lastTeacherClickedName = '';

// Show/Hide Modals
function showCreateScheduleModal() {
    // Reset to defaults every time the modal opens
    const modal = document.getElementById('createScheduleModal');
    const form = document.getElementById('createScheduleForm');
    if (form) form.reset();
    // Title/button
    const titleEl = document.getElementById('modalTitle'); if (titleEl) titleEl.textContent = 'Create New Schedule';
    const submitEl = document.getElementById('submitBtn'); if (submitEl) submitEl.textContent = 'Create Schedule';
    // Hidden id and basic fields
    const idEl = document.getElementById('scheduleId'); if (idEl) idEl.value = '';
    const nameEl = document.getElementById('sectionName'); if (nameEl) {
        // Default clear
        nameEl.value = '';
        const hiddenName = document.getElementById('sectionNameHidden'); if (hiddenName) hiddenName.value = '';
        // If a teacher was recently clicked, preselect them
        if (lastTeacherClickedName) {
            // Find option by data-name (full name)
            const opt = Array.from(nameEl.options).find(o => (o.dataset && o.dataset.name) === lastTeacherClickedName);
            if (opt) { nameEl.value = opt.value; if (hiddenName) hiddenName.value = opt.dataset.name || opt.textContent; }
        }
    }
    const stEl = document.getElementById('startTime'); if (stEl) stEl.value = '';
    const etEl = document.getElementById('endTime'); if (etEl) etEl.value = '';
    // Force schedule type to 'same'
    const sameRadio = document.querySelector('input[name="schedule_type"][value="same"]');
    const diffRadio = document.querySelector('input[name="schedule_type"][value="different"]');
    if (sameRadio) sameRadio.checked = true; if (diffRadio) diffRadio.checked = false;
    // Show same days group, hide different-day grid
    const sameDiv = document.getElementById('sameTimeSchedule'); if (sameDiv) sameDiv.classList.remove('hidden');
    const diffDiv = document.getElementById('differentTimeSchedule'); if (diffDiv) diffDiv.classList.add('hidden');
    // Uncheck all same-days checkboxes
    document.querySelectorAll('input[name="days[]"]').forEach(cb => { cb.checked = false; });
    // Clear all per-day checkboxes and time inputs
    document.querySelectorAll('#differentTimeSchedule input[type="checkbox"]').forEach(cb => { cb.checked = false; });
    document.querySelectorAll('#differentTimeSchedule input[type="time"]').forEach(inp => { inp.value = ''; });
    // Hide any inline errors
    const sameErr = document.getElementById('sameDaysError'); if (sameErr) sameErr.classList.add('hidden');
    const diffErr = document.getElementById('diffDaysError'); if (diffErr) diffErr.classList.add('hidden');
    // Finally open
    modal.classList.remove('hidden');
}

function hideCreateScheduleModal() {
    document.getElementById('createScheduleModal').classList.add('hidden');
    document.getElementById('createScheduleForm').reset();
    // Clear inline validation styles/messages
    const sameErr = document.getElementById('sameDaysError'); if (sameErr) sameErr.classList.add('hidden');
    const sameGrp = document.getElementById('sameDaysGroup'); if (sameGrp) sameGrp.classList.remove('ring-1','ring-red-400','rounded');
    const diffErr = document.getElementById('diffDaysError'); if (diffErr) diffErr.classList.add('hidden');
    
    // Reset modal to create mode
    document.getElementById('modalTitle').textContent = 'Create New Schedule';
    document.getElementById('submitBtn').textContent = 'Create Schedule';
    currentScheduleId = null;
    
    // Clear all form fields
    document.getElementById('scheduleId').value = '';
    const sectionEl = document.getElementById('sectionName');
    if (sectionEl) sectionEl.value = '';
    document.getElementById('startTime').value = '';
    document.getElementById('endTime').value = '';
    
    // Uncheck all day checkboxes
    document.querySelectorAll('input[name="days[]"]').forEach(checkbox => {
        checkbox.checked = false;
    });
}

function showAssignScheduleModal() {
    document.getElementById('assignScheduleModal').classList.remove('hidden');
    // Lock background scroll
    try {
        __prevBodyOverflow = document.body.style.overflow;
        __prevHtmlOverflow = document.documentElement.style.overflow;
        document.body.style.overflow = 'hidden';
        document.documentElement.style.overflow = 'hidden';
    } catch(e){}
    selectedStudents = [];
    updateSelectedCount();
    // Reset search box and state
    const searchEl = document.getElementById('studentSearch'); if (searchEl) searchEl.value = '';
    // Reset schedule select to default (empty)
    const sel = document.getElementById('scheduleSelect');
    if (sel) { sel.value = ''; }
    // Build the custom schedule dropdown and bind search
    const schSearchEl = document.getElementById('scheduleSearch');
    if (schSearchEl){
        schSearchEl.value = '';
        schSearchEl.removeEventListener('input', onScheduleSearchInput);
        schSearchEl.addEventListener('input', onScheduleSearchInput);
    }
    renderScheduleMenuFromSelect();
    studentsQuery = '';
    studentsOffset = 0;
    const listC = document.getElementById('studentsList'); if (listC) listC.innerHTML = '<p class="text-gray-500 text-center py-4">Loading employees...</p>';
    loadStudentsPage(false);
    // Attach infinite scroll listener
    const listEl = document.getElementById('studentsList');
    listEl.addEventListener('scroll', (e)=>{ onStudentsScroll(e); hideScheduleMenu(); });
    // Close menu on clicks inside list area
    listEl.addEventListener('click', hideScheduleMenu);
    if (sel) sel.addEventListener('change', onEmployeeScheduleChangeReload);
    // Open/close menu + outside click + Esc
    const trig = document.getElementById('scheduleTrigger');
    if (trig){
        trig.removeEventListener('click', toggleScheduleMenu);
        trig.addEventListener('click', toggleScheduleMenu);
    }
    document.addEventListener('click', closeScheduleMenuOnOutside);
    document.addEventListener('keydown', onScheduleMenuKeyDown);
    // Also close when focusing the schedule search box
    const schSearch = document.getElementById('scheduleSearch');
    if (schSearch){ schSearch.addEventListener('focus', hideScheduleMenu); }
}

function hideAssignScheduleModal() {
    document.getElementById('assignScheduleModal').classList.add('hidden');
    // Restore background scroll
    try {
        document.body.style.overflow = __prevBodyOverflow || '';
        document.documentElement.style.overflow = __prevHtmlOverflow || '';
    } catch(e){}
    selectedStudents = [];
    document.getElementById('studentsList').innerHTML = '<p class="text-gray-500 text-center py-4">Loading employees...</p>';
    const moreC = document.getElementById('studentsMoreContainer'); if(moreC) moreC.classList.add('hidden');
    // Detach scroll listener
    const listEl = document.getElementById('studentsList');
    listEl.removeEventListener('scroll', onStudentsScroll);
    listEl.removeEventListener('scroll', hideScheduleMenu);
    listEl.removeEventListener('click', hideScheduleMenu);
    const schSearchEl = document.getElementById('scheduleSearch');
    if (schSearchEl){ schSearchEl.removeEventListener('input', onScheduleSearchInput); schSearchEl.removeEventListener('focus', hideScheduleMenu); }
    document.removeEventListener('click', closeScheduleMenuOnOutside);
    document.removeEventListener('keydown', onScheduleMenuKeyDown);
}

let currentScheduleId = null;
// State for pending assignment confirmation
let pendingAssignment = null; // { scheduleId, movingIds }

// ---- Custom dropdown helpers (copied from ManageSchedule) ----
function renderScheduleMenuFromSelect(){
    const sel = document.getElementById('scheduleSelect');
    const list = document.getElementById('scheduleMenuList');
    const label = document.getElementById('scheduleTriggerText');
    if (!sel || !list || !label) return;
    // build items from options, skipping first placeholder
    let html = '';
    for (let i = 1; i < sel.options.length; i++) {
        const opt = sel.options[i];
        const active = sel.value === opt.value ? ' active' : '';
        html += `<div class="sched-item${active}" data-value="${opt.value.replace(/\"/g,'&quot;')}">${opt.textContent}</div>`;
    }
    list.innerHTML = html || '<div class="p-3 text-sm text-gray-500">No schedules found</div>';
    // update trigger text
    const cur = sel.value ? (sel.options[sel.selectedIndex]?.textContent || 'Choose a schedule...') : 'Choose a schedule...';
    label.textContent = cur;
    // bind click handlers
    list.querySelectorAll('.sched-item').forEach(el => {
        el.addEventListener('click', () => {
            const val = el.getAttribute('data-value');
            setScheduleSelection(val);
            hideScheduleMenu();
        });
    });
}

function setScheduleSelection(value){
    const sel = document.getElementById('scheduleSelect');
    const label = document.getElementById('scheduleTriggerText');
    if (!sel) return;
    sel.value = value || '';
    const text = sel.value ? (sel.options[sel.selectedIndex]?.textContent || 'Choose a schedule...') : 'Choose a schedule...';
    if (label) label.textContent = text;
    sel.dispatchEvent(new Event('change'));
}

function toggleScheduleMenu(){
    const menu = document.getElementById('scheduleMenu'); if (!menu) return;
    menu.classList.toggle('hidden');
}
function hideScheduleMenu(){ const menu = document.getElementById('scheduleMenu'); if (menu) menu.classList.add('hidden'); }
function closeScheduleMenuOnOutside(e){
    const menu = document.getElementById('scheduleMenu');
    const trig = document.getElementById('scheduleTrigger');
    if (!menu || !trig) return;
    if (menu.classList.contains('hidden')) return;
    const within = menu.contains(e.target) || trig.contains(e.target);
    if (!within) hideScheduleMenu();
}
function onScheduleMenuKeyDown(e){ if (e.key === 'Escape') hideScheduleMenu(); }
function onScheduleSearchInput(e){ filterScheduleOptions(e.target.value); }
// Cache and filter options in the hidden select, then re-render the custom menu
let originalScheduleOptionsEmp = null; // Array<{value,text}>
function cacheOriginalScheduleOptionsEmp(){
    if (originalScheduleOptionsEmp) return;
    const select = document.getElementById('scheduleSelect'); if (!select) return;
    originalScheduleOptionsEmp = Array.from(select.options).map(o=>({ value: o.value, text: o.textContent }));
}
function filterScheduleOptions(q){
    cacheOriginalScheduleOptionsEmp();
    const select = document.getElementById('scheduleSelect'); if (!select) return;
    const query = (q||'').toLowerCase();
    // Keep the first option (placeholder)
    const placeholder = originalScheduleOptionsEmp[0];
    const rest = originalScheduleOptionsEmp.slice(1);
    const filtered = rest.filter(opt => opt.text.toLowerCase().includes(query));
    // Rebuild options
    select.innerHTML = '';
    const def = document.createElement('option'); def.value = placeholder.value; def.textContent = placeholder.text; select.appendChild(def);
    filtered.forEach(opt => {
        const o = document.createElement('option'); o.value = opt.value; o.textContent = opt.text; select.appendChild(o);
    });
    // Re-render custom menu so it reflects the filtered list
    renderScheduleMenuFromSelect();
}

// Reload employees when selected schedule changes (like students)
function onEmployeeScheduleChangeReload(){
    studentsOffset = 0; studentsQuery = '';
    loadEmployeesAfterSchedule();
}
// Wrapper to call existing loader name
function loadEmployeesAfterSchedule(){ loadStudentsPage(false); }

// Create/Edit Schedule Form Submission
document.getElementById('createScheduleForm').addEventListener('submit', function(e) {
    e.preventDefault();
    // Client-side validation for day selection
    const type = document.querySelector('input[name="schedule_type"]:checked')?.value || 'same';
    let valid = true;
    const formErrEl = document.getElementById('formError');
    if (formErrEl) { formErrEl.classList.add('hidden'); formErrEl.textContent=''; }
    if (type === 'same') {
        const checks = Array.from(document.querySelectorAll('#sameDaysGroup input[type="checkbox"]:checked'));
        const st = document.getElementById('startTime')?.value;
        const et = document.getElementById('endTime')?.value;
        const err = document.getElementById('sameDaysError');
        if (checks.length === 0 || !st || !et) {
            valid = false;
            if (err) err.textContent = 'Please select at least one day and provide start and end times.';
            if (err) err.classList.remove('hidden');
        } else {
            if (err) err.classList.add('hidden');
        }
    } else {
        // different: at least one enabled day, and ALL enabled days must have both times
        const enabled = Array.from(document.querySelectorAll('input[name^="day_schedules"][name$="[enabled]"]:checked'));
        let hasAny = enabled.length > 0;
        let allComplete = true;
        enabled.forEach(cb => {
            const day = cb.name.match(/day_schedules\[(.+?)\]/)?.[1];
            if (!day) return;
            const st = document.querySelector(`input[name="day_schedules[${day}][start_time]"]`)?.value;
            const et = document.querySelector(`input[name="day_schedules[${day}][end_time]"]`)?.value;
            if (!st || !et) { allComplete = false; }
        });
        const err = document.getElementById('diffDaysError');
        if (!hasAny || !allComplete) {
            valid = false;
            if (err) err.textContent = 'Please enable at least one day and provide start and end times for all enabled days.';
            if (err) err.classList.remove('hidden');
        } else { if (err) err.classList.add('hidden'); }
    }
    if (!valid) { if (formErrEl){ formErrEl.textContent = 'Please fix the highlighted fields.'; formErrEl.classList.remove('hidden'); } return; }

    // Keep hidden section_name in sync with teacher select before submit
    try {
        const sel = document.getElementById('sectionName');
        const hidden = document.getElementById('sectionNameHidden');
        if (sel && hidden) {
            const opt = sel.options[sel.selectedIndex];
            hidden.value = (opt && (opt.dataset?.name || opt.textContent || ''));
        }
    } catch(_) {}

    const formData = new FormData(this);
    const action = currentScheduleId ? 'edit_schedule' : 'create_schedule';
    formData.append('action', action);
    
    if (currentScheduleId) {
        formData.append('schedule_id', currentScheduleId);
    }
    
    fetch('ManageEmployeeSchedule.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Persist notice across reload so user sees it
            sessionStorage.setItem('schedule_notice', JSON.stringify({ type: 'success', message: data.message }));
            hideCreateScheduleModal();
            location.reload();
        } else {
            if (formErrEl){ formErrEl.textContent = data.message || 'Action failed.'; formErrEl.classList.remove('hidden'); }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (formErrEl){ formErrEl.textContent = 'Failed to save schedule. Please try again.'; formErrEl.classList.remove('hidden'); }
    });
});


// Load students page (supports search and pagination)
function loadStudentsPage(append){
    if (studentsLoading) return;
    studentsLoading = true;
    const params = new URLSearchParams();
    if (studentsQuery === '') { params.set('all','1'); }
    else { params.set('query', studentsQuery); }
    params.set('limit', studentsLimit);
    params.set('offset', studentsOffset);
    params.set('role', 'teacher'); // Only show teachers
    fetch('SearchEmployee.php?' + params.toString())
        .then(r=>r.json())
        .then(d=>{
            const container = document.getElementById('studentsList');
            if (d.error){ container.innerHTML = `<p class="text-red-500 text-center py-4">${d.error}</p>`; return; }
            const list = d.employees || d.students || [];
            if (!append){ container.innerHTML = ''; }
            displayStudents(list, append === true);
            studentsHasMore = !!d.has_more;
            const moreC = document.getElementById('studentsMoreContainer');
            if (moreC){ moreC.classList.toggle('hidden', !studentsHasMore); }
        })
        .catch(err=>{
            console.error('Error:', err);
            document.getElementById('studentsList').innerHTML = '<p class="text-red-500 text-center py-4">Failed to load employees</p>';
            const moreC = document.getElementById('studentsMoreContainer'); if(moreC) moreC.classList.add('hidden');
        })
        .finally(()=>{ studentsLoading = false; });
}

// Display students in the list
function displayStudents(students, append=false) {
    const container = document.getElementById('studentsList');
    
    if (students.length === 0) {
        if (!append) container.innerHTML = '<p class="text-gray-500 text-center py-4">No employees found</p>';
        return;
    }
    
    let html = '';
    students.forEach(emp => {
        const id = emp.id_number;
        const fullName = emp.full_name || (emp.first_name + ' ' + emp.last_name);
        const currentName = emp.current_schedule || emp.schedule_text || emp.current_section || emp.class_schedule;
        const hasSchedule = !!currentName;
        const isSelected = selectedStudents.includes(id);
        const scheduleInfo = hasSchedule ? `<span class="text-orange-600 font-medium">Current: ${currentName}</span>` : '<span class="text-green-600">Available</span>';
        // cache
        studentsIndex[id] = { name: fullName, hasCurrent: !!hasSchedule };
        // Escape quotes for safe inline attribute usage
        const safeName = String(fullName||'').replace(/\\/g,'\\\\').replace(/'/g,"\\'");
        const safeCurrent = String(currentName||'').replace(/\\/g,'\\\\').replace(/'/g,"\\'");
        html += `
            <div class="flex items-center p-3 border-b hover:bg-gray-50 cursor-pointer select-none" onclick="toggleStudent('${id}', ${hasSchedule ? 'true' : 'false'}, '${safeName}', '${safeCurrent}')">
                <input type="checkbox" id="student_${id}" ${isSelected ? 'checked' : ''} class="mr-3 pointer-events-none">
                <div class="flex-1">
                    <div class="font-medium">${fullName}</div>
                    <div class="text-sm text-gray-600">ID: ${id} • ${emp.position || emp.department || 'N/A'}</div>
                    <div class="text-sm">${scheduleInfo}</div>
                </div>
            </div>
        `;
    });
    
    if (append) container.insertAdjacentHTML('beforeend', html);
    else container.innerHTML = html;
}

// Toggle student selection with confirmation for reassignment
function toggleStudent(studentId, hasSchedule, studentName, currentSection) {
    const index = selectedStudents.indexOf(studentId);
    
    if (index > -1) {
        // Deselecting student
        selectedStudents.splice(index, 1);
    } else {
        selectedStudents.push(studentId);
    }
    // Record the last clicked teacher name for Create Schedule prefill
    if (studentName) { lastTeacherClickedName = studentName; }
    updateSelectedCount();
    const cb = document.getElementById(`student_${studentId}`); if (cb) cb.checked = selectedStudents.includes(studentId);
}

// Update selected count
function updateSelectedCount() {
    document.getElementById('selectedCount').textContent = selectedStudents.length;
}

// Assign schedule to selected students
function assignScheduleToStudents() {
    const scheduleId = document.getElementById('scheduleSelect').value;
    
    const assignErr = document.getElementById('assignInlineError');
    if (assignErr) { assignErr.classList.add('hidden'); assignErr.textContent=''; }
    if (!scheduleId) { if(assignErr){ assignErr.textContent='Please select a schedule'; assignErr.classList.remove('hidden'); } return; }
    if (selectedStudents.length === 0) { if(assignErr){ assignErr.textContent='Please select at least one employee'; assignErr.classList.remove('hidden'); } return; }
    
    // Build confirmation from selectedStudents (not just visible rows), using cached info
    try {
        const items = selectedStudents.map(id => {
            const meta = studentsIndex[id] || { name: id, hasCurrent: false };
            return { id, name: meta.name, hasCurrent: !!meta.hasCurrent };
        });
        const movingCount = items.filter(i=>i.hasCurrent).length;
        pendingAssignment = { scheduleId };
        showReassignConfirmItems(items, items.length, movingCount);
        return; // wait for user confirmation
    } catch (e) { /* ignore */ }
    
    proceedAssign(scheduleId);
}

function showReassignConfirmItems(items, totalCount, movingCount){
    const body = document.getElementById('reassignConfirmBody');
    const modal = document.getElementById('reassignConfirmModal');
    if(!body || !modal){ return; }
    const titleLine = `You are about to move <strong>${totalCount}</strong> employee(s) to the selected schedule.`;
    const allNames = items.map(i=>i.name);
    const currentNames = items.filter(i=>i.hasCurrent).map(i=>i.name);
    const makeList = (names, cap=10) => {
        const rows = names.slice(0, cap).map(n=>`<li>${n}</li>`).join('');
        const extra = names.length > cap ? `<div class=\"text-xs text-gray-500 mt-1\">and ${names.length-cap} more…</div>` : '';
        return `<ul class=\"list-disc ml-5 space-y-0.5\">${rows}</ul>${extra}`;
    };
    const note = movingCount > 0 ? `<p class=\"mt-2 text-gray-600\">Moving will replace their existing schedule.</p>` : '';
    body.innerHTML = `
      <p>${titleLine}</p>
      <div class=\"mt-2 space-y-3\">
        <div>
          <div class=\"font-medium mb-1\">Selected employees (${allNames.length}):</div>
          ${makeList(allNames)}
        </div>
        <div>
          <div class=\"font-medium mb-1\">Have current schedule (${currentNames.length}):</div>
          ${currentNames.length ? makeList(currentNames) : '<div class=\"text-gray-500 text-sm\">None</div>'}
        </div>
      </div>
      ${note}
    `;
    modal.classList.remove('hidden');
}

function hideReassignConfirm(){ const m=document.getElementById('reassignConfirmModal'); if(m) m.classList.add('hidden'); pendingAssignment=null; }

function confirmProceedReassign(){
    const data = pendingAssignment; hideReassignConfirm();
    if(!data) return;
    proceedAssign(data.scheduleId);
}

function proceedAssign(scheduleId){
    const formData = new FormData();
    formData.append('action', 'assign_schedule');
    formData.append('schedule_id', scheduleId);
    selectedStudents.forEach(studentId => formData.append('employee_ids[]', studentId));
    fetch('ManageEmployeeSchedule.php', { method: 'POST', body: formData })
      .then(r=>r.json())
      .then(d=>{
        if(d.success){ sessionStorage.setItem('schedule_notice', JSON.stringify({ type: 'success', message: d.message })); hideAssignScheduleModal(); location.reload(); }
        else { showErrorBanner(d.message || 'Assign failed.'); }
      })
      .catch(()=>{ showErrorBanner('Failed to assign schedule. Please try again.'); });
}

// Student search functionality
document.getElementById('studentSearch').addEventListener('input', function() {
    const query = this.value.trim();
    // When cleared, show all employees again
    if (query.length === 0) {
        studentsQuery = '';
        studentsOffset = 0;
        loadStudentsPage(false);
        return;
    }
    // Search even with a single character
    studentsQuery = query;
    studentsOffset = 0;
    loadStudentsPage(false);
});

// Infinite scroll handler
function onStudentsScroll(){
    const el = document.getElementById('studentsList');
    const nearBottom = el.scrollTop + el.clientHeight >= el.scrollHeight - 20;
    if (nearBottom && studentsHasMore && !studentsLoading){
        studentsOffset += studentsLimit;
        loadStudentsPage(true);
    }
}

// ---------- Toast/Banner Notifications ----------
function showBanner(message, type='success', timeout=3000){
  try{
    const box = document.createElement('div');
    box.className = 'fixed top-4 right-4 z-[70] px-4 py-2 rounded-lg shadow text-white ' + (type==='success' ? 'bg-green-600' : 'bg-red-600');
    box.textContent = message;
    document.body.appendChild(box);
    setTimeout(()=>{ box.style.transition='opacity .3s'; box.style.opacity='0'; setTimeout(()=>box.remove(), 300); }, timeout);
  }catch(e){}
}
function showSuccessBanner(msg){ showBanner(msg, 'success'); }
function showErrorBanner(msg){ showBanner(msg, 'error'); }

document.addEventListener('DOMContentLoaded', ()=>{
  try{
    const raw = sessionStorage.getItem('schedule_notice');
    if(raw){ const data = JSON.parse(raw); showBanner(data.message || 'Action completed', data.type || 'success'); sessionStorage.removeItem('schedule_notice'); }
  }catch(e){}
});

// Toggle schedule type function
function toggleScheduleType() {
    const scheduleType = document.querySelector('input[name="schedule_type"]:checked').value;
    const sameTimeDiv = document.getElementById('sameTimeSchedule');
    const differentTimeDiv = document.getElementById('differentTimeSchedule');
    const startTimeInput = document.getElementById('startTime');
    const endTimeInput = document.getElementById('endTime');
    const timeFieldsDiv = startTimeInput.closest('.grid'); // Get the parent div containing both time fields
    
    if (scheduleType === 'same') {
        sameTimeDiv.classList.remove('hidden');
        differentTimeDiv.classList.add('hidden');
        timeFieldsDiv.classList.remove('hidden'); // Show time fields
        startTimeInput.required = true;
        endTimeInput.required = true;
    } else {
        sameTimeDiv.classList.add('hidden');
        differentTimeDiv.classList.remove('hidden');
        timeFieldsDiv.classList.add('hidden'); // Hide time fields
        startTimeInput.required = false;
        endTimeInput.required = false;
    }
}

// Show success message (banner fallback) and toast utility
function showSuccessMessage(message) {
    const successDiv = document.getElementById('successMessage');
    const successText = document.getElementById('successText');
    if (successDiv && successText) {
        successText.textContent = message;
        successDiv.classList.remove('hidden');
        setTimeout(() => successDiv.classList.add('hidden'), 3000);
    }
    showToast('success', message);
}

function showToast(type, message) {
    // Create container once
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.position = 'fixed';
        container.style.top = '16px';
        container.style.right = '16px';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }
    const toast = document.createElement('div');
    toast.className = 'mb-2 px-4 py-3 rounded shadow text-white text-sm';
    toast.style.minWidth = '240px';
    toast.style.backgroundColor = type === 'success' ? '#16a34a' : '#dc2626';
    toast.textContent = message;
    container.appendChild(toast);
    setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity 300ms'; }, 2700);
    setTimeout(() => { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 3100);
}

// After reload, show persisted notice
document.addEventListener('DOMContentLoaded', () => {
    try {
        const raw = sessionStorage.getItem('schedule_notice');
        if (raw) {
            const n = JSON.parse(raw);
            if (n && n.message) { showToast(n.type || 'success', n.message); }
            sessionStorage.removeItem('schedule_notice');
        }
    } catch(e) { /* ignore */ }
});

// Back navigation
function handleBackNavigation() {
    window.location.href = 'Dashboard.php';
}

// Edit schedule function
function editSchedule(id) {
    // Fetch schedule data
    fetch(`get_employee_schedule.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const schedule = data.schedule;
                
                // Populate form with existing data
                document.getElementById('scheduleId').value = schedule.id;
                // sectionName is a select of teacher_id; match by data-name (full name stored as schedule_name)
                const sectionSel = document.getElementById('sectionName');
                const hiddenName = document.getElementById('sectionNameHidden');
                if (sectionSel) {
                    const match = Array.from(sectionSel.options).find(o => (o.dataset && o.dataset.name) === schedule.schedule_name);
                    if (match) {
                        sectionSel.value = match.value;
                        if (hiddenName) hiddenName.value = match.dataset.name || match.textContent;
                    } else {
                        // If no matching teacher present, clear selection but keep hidden name for display
                        sectionSel.value = '';
                        if (hiddenName) hiddenName.value = schedule.schedule_name;
                    }
                }
                
                // Set schedule type based on whether it has day-specific schedules
                if (schedule.has_day_schedules) {
                    document.querySelector('input[name="schedule_type"][value="different"]').checked = true;
                    toggleScheduleType();
                    
                    // Populate day-specific schedules
                    const daySchedules = schedule.day_schedules;
                    Object.keys(daySchedules).forEach(day => {
                        const dayData = daySchedules[day];
                        const dayCheckbox = document.querySelector(`input[name="day_schedules[${day}][enabled]"]`);
                        const startTimeInput = document.querySelector(`input[name="day_schedules[${day}][start_time]"]`);
                        const endTimeInput = document.querySelector(`input[name="day_schedules[${day}][end_time]"]`);
                        
                        if (dayCheckbox) dayCheckbox.checked = true;
                        if (startTimeInput) startTimeInput.value = dayData.start_time;
                        if (endTimeInput) endTimeInput.value = dayData.end_time;
                    });
                } else {
                    document.querySelector('input[name="schedule_type"][value="same"]').checked = true;
                    toggleScheduleType();
                    
                    // Populate uniform schedule data
                    document.getElementById('startTime').value = schedule.start_time;
                    document.getElementById('endTime').value = schedule.end_time;
                    
                    // Clear all checkboxes first
                    document.querySelectorAll('input[name="days[]"]').forEach(checkbox => {
                        checkbox.checked = false;
                    });
                    
                    // Check the appropriate days
                    if (schedule.days && schedule.days !== 'Variable') {
                        const days = schedule.days.split(',');
                        days.forEach(day => {
                            const checkbox = document.querySelector(`input[name="days[]"][value="${day.trim()}"]`);
                            if (checkbox) checkbox.checked = true;
                        });
                    }
                }
                
                // Update modal title and button
                document.getElementById('modalTitle').textContent = 'Edit Schedule';
                document.getElementById('submitBtn').textContent = 'Update Schedule';
                
                currentScheduleId = id;
                document.getElementById('createScheduleModal').classList.remove('hidden');
            } else {
                alert('Error loading schedule data');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to load schedule data');
        });
}

let scheduleToDelete = null;

// Delete schedule function
function deleteSchedule(id) {
    scheduleToDelete = id;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function hideDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    scheduleToDelete = null;
}

function confirmDelete() {
    if (scheduleToDelete) {
        const formData = new FormData();
        formData.append('action', 'delete_schedule');
        formData.append('schedule_id', scheduleToDelete);
        
        fetch('ManageEmployeeSchedule.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                sessionStorage.setItem('schedule_notice', JSON.stringify({ type: 'success', message: data.message }));
                hideDeleteModal();
                location.reload();
            } else {
                showErrorMessage(data.message || 'Delete failed.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorMessage('Failed to delete schedule. Please try again.');
        });
    }
}

function viewScheduleStudents(id, sectionName) {
    window.location.href = `view_schedule_students.php?schedule_id=${id}&section_name=${encodeURIComponent(sectionName)}`;
}

// View Schedule Details (Students)
function openClassScheduleDetails(id){
    const modal = document.getElementById('classScheduleDetailsModal');
    const body = document.getElementById('classSchedBody');
    const title = document.getElementById('classSchedTitle');
    if(!modal || !body || !title){ console.error('Class schedule modal elements missing'); return; }
    body.innerHTML = '<p>Loading schedule...</p>';
    title.textContent = 'Schedule Details';
    modal.classList.remove('hidden');
    fetch(`get_employee_schedule.php?id=${id}`)
      .then(res=>res.json())
      .then(d=>{
        if(!d.success){ body.innerHTML = '<p class="text-red-600">Failed to load schedule.</p>'; return; }
        const s = d.schedule; const items = [];
        title.textContent = ((s.schedule_name||'') + ' — Details').trim();
        if(s.has_day_schedules && s.day_schedules && Object.keys(s.day_schedules).length){
          const order=['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
          items.push('<div><div class="font-semibold mb-1">Per-day Times</div><ul class="list-disc ml-5 space-y-1">');
          order.forEach(day=>{ const dsd=s.day_schedules[day]; if(dsd){ items.push(`<li><span class=\"font-medium\">${day}:</span> ${formatTime(dsd.start_time)} - ${formatTime(dsd.end_time)}</li>`); }});
          items.push('</ul></div>');
        } else {
          items.push(`<div><span class=\"font-semibold\">Time:</span> ${formatTime(s.start_time)} - ${formatTime(s.end_time)}</div>`);
          items.push(`<div><span class=\"font-semibold\">Days:</span> ${s.days||'—'}</div>`);
        }
        body.innerHTML = items.join('');
      })
      .catch(()=>{ body.innerHTML = '<p class="text-red-600">Failed to load schedule.</p>'; });
}
function closeClassScheduleDetails(){ const m=document.getElementById('classScheduleDetailsModal'); if(m) m.classList.add('hidden'); }
function formatTime(t){ try{ const d = new Date(`1970-01-01T${t}`); return d.toLocaleTimeString([], {hour:'numeric', minute:'2-digit'}); } catch(e){ return t; } }

// Search filter for student schedules
(function(){
  const classSearchEl = document.getElementById('classScheduleSearch');
  if(!classSearchEl) return;
  const icon = document.getElementById('classSearchIcon');
  const clearBtn = document.getElementById('classSearchClear');
  const noRes = document.getElementById('classNoResults');
  const applyFilter = () => {
    const q = classSearchEl.value.trim().toLowerCase();
    if(icon) icon.classList.toggle('hidden', q.length>0);
    if(clearBtn) clearBtn.classList.toggle('hidden', q.length===0);
    if(q.length>0){ classSearchEl.classList.remove('pl-10'); classSearchEl.classList.add('pl-3'); }
    else { classSearchEl.classList.add('pl-10'); classSearchEl.classList.remove('pl-3'); }
    const cards = document.querySelectorAll('.class-schedule-card');
    let visible=0;
    cards.forEach(card=>{
      const name = (card.querySelector('.class-schedule-name')?.textContent || '').toLowerCase();
      const days = (card.querySelector('.class-schedule-days')?.textContent || '').toLowerCase();
      const match = q.length===0 || name.includes(q) || days.includes(q);
      card.style.display = match ? '' : 'none';
      if(match) visible++;
    });
    if(noRes) noRes.classList.toggle('hidden', visible>0);
  };
  classSearchEl.addEventListener('input', applyFilter);
  classSearchEl.addEventListener('focus', applyFilter);
  classSearchEl.addEventListener('blur', applyFilter);
  if(clearBtn){ clearBtn.addEventListener('click', ()=>{ classSearchEl.value=''; classSearchEl.focus(); applyFilter(); }); }
})();
</script>

</body>
</html>
