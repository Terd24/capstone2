<?php
session_start();
include("../StudentLogin/db_conn.php");

// Require registrar login
if (!isset($_SESSION['registrar_id'])) {
    header("Location: ../StudentLogin/login.php");
    exit;
}

// Create sections table if it doesn't exist
$conn->query("CREATE TABLE IF NOT EXISTS sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_name VARCHAR(100) UNIQUE NOT NULL,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    INDEX idx_section_name (section_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Handle form submission for creating/editing schedules and sections
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    // Section management actions
    if ($_POST['action'] == 'create_section') {
        $section_name = trim($_POST['section_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($section_name)) {
            echo json_encode(['success' => false, 'message' => 'Section name is required']);
            exit;
        }
        
        $stmt = $conn->prepare("INSERT INTO sections (section_name, description, created_by) VALUES (?, ?, ?)");
        $stmt->bind_param('ssi', $section_name, $description, $_SESSION['registrar_id']);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Section created successfully']);
        } else {
            if ($conn->errno === 1062) {
                echo json_encode(['success' => false, 'message' => 'Section name already exists']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error creating section']);
            }
        }
        exit;
    }
    
    if ($_POST['action'] == 'update_section') {
        $id = intval($_POST['id'] ?? 0);
        $section_name = trim($_POST['section_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($section_name)) {
            echo json_encode(['success' => false, 'message' => 'Section name is required']);
            exit;
        }
        
        $stmt = $conn->prepare("UPDATE sections SET section_name = ?, description = ? WHERE id = ?");
        $stmt->bind_param('ssi', $section_name, $description, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Section updated successfully']);
        } else {
            if ($conn->errno === 1062) {
                echo json_encode(['success' => false, 'message' => 'Section name already exists']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error updating section']);
            }
        }
        exit;
    }
    
    if ($_POST['action'] == 'delete_section') {
        $id = intval($_POST['id'] ?? 0);
        
        // Check if section is used in class_schedules
        $check = $conn->prepare("SELECT COUNT(*) as count FROM class_schedules WHERE section_name = (SELECT section_name FROM sections WHERE id = ?)");
        $check->bind_param('i', $id);
        $check->execute();
        $result = $check->get_result()->fetch_assoc();
        
        if ($result['count'] > 0) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete section that is used in schedules']);
            exit;
        }
        
        $stmt = $conn->prepare("DELETE FROM sections WHERE id = ?");
        $stmt->bind_param('i', $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Section deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error deleting section']);
        }
        exit;
    }
    
    if ($_POST['action'] == 'get_sections') {
        $sections_query = "SELECT s.*, 
                          (SELECT COUNT(*) FROM class_schedules cs WHERE cs.section_name = s.section_name) as usage_count
                          FROM sections s 
                          ORDER BY s.section_name ASC";
        $sections_result = $conn->query($sections_query);
        $sections = [];
        while ($row = $sections_result->fetch_assoc()) {
            $sections[] = $row;
        }
        echo json_encode(['success' => true, 'sections' => $sections]);
        exit;
    }
    
    if ($_POST['action'] == 'create_schedule') {
        $section_name = $_POST['section_name'];
        $schedule_type = $_POST['schedule_type'];
        
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
            
            $stmt = $conn->prepare("INSERT INTO class_schedules (section_name, start_time, end_time, days, created_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $section_name, $start_time, $end_time, $days, $_SESSION['registrar_id']);
            
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = "Schedule created successfully!";
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
            $stmt = $conn->prepare("INSERT INTO class_schedules (section_name, start_time, end_time, days, created_by) VALUES (?, '00:00:00', '23:59:59', 'Variable', ?)");
            $stmt->bind_param("si", $section_name, $_SESSION['registrar_id']);
            
            if ($stmt->execute()) {
                $schedule_id = $conn->insert_id;
                
                // Insert day-specific schedules
                foreach ($day_schedules as $day => $schedule) {
                    if (isset($schedule['enabled']) && $schedule['start_time'] && $schedule['end_time']) {
                        $day_stmt = $conn->prepare("INSERT INTO day_schedules (schedule_id, day_name, start_time, end_time) VALUES (?, ?, ?, ?)");
                        $day_stmt->bind_param("isss", $schedule_id, $day, $schedule['start_time'], $schedule['end_time']);
                        $day_stmt->execute();
                        $enabled_days[] = $day;
                    }
                }
                
                // Update the main schedule with actual days
                $days_string = implode(', ', $enabled_days);
                $update_stmt = $conn->prepare("UPDATE class_schedules SET days = ? WHERE id = ?");
                $update_stmt->bind_param("si", $days_string, $schedule_id);
                $update_stmt->execute();
                
                $response['success'] = true;
                $response['message'] = "Day-specific schedule created successfully!";
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
            $clear_day_schedules = $conn->prepare("DELETE FROM day_schedules WHERE schedule_id = ?");
            $clear_day_schedules->bind_param("i", $schedule_id);
            $clear_day_schedules->execute();
            
            // Update main schedule
            $stmt = $conn->prepare("UPDATE class_schedules SET section_name = ?, start_time = ?, end_time = ?, days = ? WHERE id = ?");
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
            $clear_day_schedules = $conn->prepare("DELETE FROM day_schedules WHERE schedule_id = ?");
            $clear_day_schedules->bind_param("i", $schedule_id);
            $clear_day_schedules->execute();
            
            // Update main schedule with placeholder times
            $stmt = $conn->prepare("UPDATE class_schedules SET section_name = ?, start_time = '00:00:00', end_time = '23:59:59', days = 'Variable' WHERE id = ?");
            $stmt->bind_param("si", $section_name, $schedule_id);
            $stmt->execute();
            
            // Insert new day-specific schedules
            foreach ($day_schedules as $day => $schedule) {
                if (isset($schedule['enabled']) && $schedule['start_time'] && $schedule['end_time']) {
                    $day_stmt = $conn->prepare("INSERT INTO day_schedules (schedule_id, day_name, start_time, end_time) VALUES (?, ?, ?, ?)");
                    $day_stmt->bind_param("isss", $schedule_id, $day, $schedule['start_time'], $schedule['end_time']);
                    $day_stmt->execute();
                    $enabled_days[] = $day;
                }
            }
            
            // Update the main schedule with actual days
            $days_string = implode(', ', $enabled_days);
            $update_days = $conn->prepare("UPDATE class_schedules SET days = ? WHERE id = ?");
            $update_days->bind_param("si", $days_string, $schedule_id);
            $update_days->execute();
            
            $schedule_text = $section_name . " (Variable Times)";
        }
        
        // Update student_account records
        $update_students = $conn->prepare("
            UPDATE student_account sa 
            JOIN student_schedules ss ON sa.id_number = ss.student_id 
            SET sa.class_schedule = ? 
            WHERE ss.schedule_id = ?
        ");
        $update_students->bind_param("si", $schedule_text, $schedule_id);
        $update_students->execute();
        
        // Update attendance_record records
        $update_attendance = $conn->prepare("
            UPDATE attendance_record ar 
            JOIN student_schedules ss ON ar.id_number = ss.student_id 
            SET ar.schedule = ? 
            WHERE ss.schedule_id = ?
        ");
        $update_attendance->bind_param("si", $schedule_text, $schedule_id);
        $update_attendance->execute();
        
        $response = ['success' => true, 'message' => 'Schedule updated successfully!'];
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    if ($_POST['action'] == 'delete_schedule') {
        $schedule_id = $_POST['schedule_id'];
        
        // First, clear schedule from student_account and attendance_record
        $clear_students = $conn->prepare("
            UPDATE student_account sa 
            JOIN student_schedules ss ON sa.id_number = ss.student_id 
            SET sa.class_schedule = NULL 
            WHERE ss.schedule_id = ?
        ");
        $clear_students->bind_param("i", $schedule_id);
        $clear_students->execute();
        
        $clear_attendance = $conn->prepare("
            UPDATE attendance_record ar 
            JOIN student_schedules ss ON ar.id_number = ss.student_id 
            SET ar.schedule = NULL 
            WHERE ss.schedule_id = ?
        ");
        $clear_attendance->bind_param("i", $schedule_id);
        $clear_attendance->execute();
        
        // Delete schedule (student_schedules will be deleted by CASCADE)
        $stmt = $conn->prepare("DELETE FROM class_schedules WHERE id = ?");
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
        $student_ids = $_POST['student_ids']; // Array of student IDs
        
        // Clear schedule from student_account table
        $clear_student = $conn->prepare("UPDATE student_account SET class_schedule = NULL WHERE id_number IN (SELECT student_id FROM student_schedules WHERE schedule_id = ?)");
        $clear_student->bind_param("i", $schedule_id);
        $clear_student->execute();
        
        // Get schedule details
        $schedule_stmt = $conn->prepare("SELECT section_name, start_time, end_time FROM class_schedules WHERE id = ?");
        $schedule_stmt->bind_param("i", $schedule_id);
        $schedule_stmt->execute();
        $schedule_result = $schedule_stmt->get_result();
        $schedule_info = $schedule_result->fetch_assoc();
        
        // Check if this schedule has day-specific times
        $day_schedules_query = $conn->prepare("SELECT day_name, start_time, end_time FROM day_schedules WHERE schedule_id = ? ORDER BY FIELD(day_name, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')");
        $day_schedules_query->bind_param("i", $schedule_id);
        $day_schedules_query->execute();
        $day_schedules_result = $day_schedules_query->get_result();
        
        if ($day_schedules_result->num_rows > 0) {
            // Has day-specific schedules - create descriptive text
            $schedule_text = $schedule_info['section_name'] . " (Variable Times)";
        } else {
            // Uses same time for all days
            $schedule_text = $schedule_info['section_name'] . " (" . 
                            date('g:i A', strtotime($schedule_info['start_time'])) . " - " . 
                            date('g:i A', strtotime($schedule_info['end_time'])) . ")";
        }
        
        $success_count = 0;
        $error_count = 0;
        
        foreach ($student_ids as $student_id) {
            // Remove any existing schedule assignments for this student
            $remove_existing = $conn->prepare("DELETE FROM student_schedules WHERE student_id = ?");
            $remove_existing->bind_param("s", $student_id);
            $remove_existing->execute();
            
            // Insert new assignment
            $insert_stmt = $conn->prepare("INSERT INTO student_schedules (student_id, schedule_id, assigned_by) VALUES (?, ?, ?)");
            $insert_stmt->bind_param("sii", $student_id, $schedule_id, $_SESSION['registrar_id']);
            
            if ($insert_stmt->execute()) {
                // Update student_account table with schedule text
                $update_student = $conn->prepare("UPDATE student_account SET class_schedule = ? WHERE id_number = ?");
                $update_student->bind_param("ss", $schedule_text, $student_id);
                $update_student->execute();
                
                // Update any existing attendance records with schedule info
                $update_attendance = $conn->prepare("UPDATE attendance_record SET schedule = ? WHERE id_number = ?");
                $update_attendance->bind_param("ss", $schedule_text, $student_id);
                    $update_attendance->execute();
                    
                    $success_count++;
                } else {
                    $error_count++;
                }
            }
        }
        
        $message = "Assigned $success_count Student(s) Successfully";
        if ($error_count > 0) {
            $message .= " ($error_count failed)";
        }
        
        $response = ['success' => true, 'message' => $message];
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }


// Fetch all schedules
$schedules_query = "SELECT cs.*, COUNT(ss.id) as student_count 
                   FROM class_schedules cs 
                   LEFT JOIN student_schedules ss ON cs.id = ss.schedule_id 
                   GROUP BY cs.id 
                   ORDER BY cs.section_name";
$schedules_result = $conn->query($schedules_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Schedule - Cornerstone College Inc.</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .school-gradient { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #1e40af 100%); }
        .card-shadow { box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        /* Make the schedule dropdown visually smaller without changing native dropdown behavior */
        #scheduleSelect { font-size: 0.875rem; /* 14px */ }
        #scheduleSelect option { padding-top: 2px; padding-bottom: 2px; }
        /* Custom schedule menu items */
        .sched-item { padding: 8px 12px; cursor: pointer; }
        .sched-item:hover { background-color: #f3f4f6; }
        .sched-item.active { background-color: #e5e7eb; }
        /* Around 10 items tall */
        .schedule-menu { max-height: 20rem; }
        /* Emphasize custom dropdown visual boundaries */
        #scheduleTrigger {
            border-color: #0B2C62 !important; /* school dark blue */
            box-shadow: 0 0 0 2px rgba(11, 44, 98, 0.12);
            background-color: #ffffff;
        }
        #scheduleTrigger:focus {
            box-shadow: 0 0 0 3px rgba(11, 44, 98, 0.25);
            outline: none;
        }
        #scheduleMenu {
            border-color: #0B2C62 !important;
            box-shadow: 0 10px 20px rgba(0,0,0,0.15);
            background-color: #ffffff;
        }
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
        <button onclick="showManageSectionsModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
            </svg>
            Manage Sections
        </button>
    </div>

    <!-- Schedules List -->
    <div class="bg-white rounded-xl card-shadow p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
            <h2 class="text-xl font-bold text-gray-800">Class Schedules</h2>
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
                            <h3 class="text-lg font-bold text-gray-800 class-schedule-name"><?= htmlspecialchars($schedule['section_name']) ?></h3>
                            <div class="flex gap-2">
                                <button onclick="openClassScheduleDetails(<?= $schedule['id'] ?>)" class="text-blue-500 hover:text-blue-700 text-sm">View</button>
                                <button onclick="editSchedule(<?= $schedule['id'] ?>)" class="text-blue-500 hover:text-blue-700 text-sm">Edit</button>
                                <button onclick="deleteSchedule(<?= $schedule['id'] ?>)" class="text-red-500 hover:text-red-700 text-sm">Delete</button>
                            </div>
                        </div>

                        <?php
                          // Real-time display time for student schedules
                          date_default_timezone_set('Asia/Manila');
                          $todayName = date('l');
                          $display_time = date('g:i A', strtotime($schedule['start_time'])) . ' - ' . date('g:i A', strtotime($schedule['end_time']));
                          $sid = (int)$schedule['id'];
                          $ds_today = $conn->prepare("SELECT start_time, end_time FROM day_schedules WHERE schedule_id = ? AND LOWER(day_name) = LOWER(?)");
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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                <span><?= $schedule['student_count'] ?> student(s) assigned</span>
                            </div>
                        </div>
                        
                        <div class="mt-4 pt-3 border-t">
                            <button onclick="viewScheduleStudents(<?= $schedule['id'] ?>, '<?= htmlspecialchars($schedule['section_name']) ?>')" 
                                    class="w-full bg-[#0B2C62] hover:bg-blue-900 text-white py-2 rounded-lg text-sm font-medium">
                                View Students
                            </button>
                        </div>
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
    
    <!-- Pagination Controls -->
    <div id="paginationBar" class="mt-6 flex items-center justify-center gap-2 text-sm">
        <button id="prevPage" class="px-4 py-2 border border-[#0B2C62] rounded-lg text-[#0B2C62] hover:bg-[#0B2C62] hover:text-white disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Previous
        </button>
        <span id="pageInfo" class="px-4 py-2 text-gray-600 font-medium">Page 1 of 1</span>
        <button id="nextPage" class="px-4 py-2 border border-[#0B2C62] rounded-lg text-[#0B2C62] hover:bg-[#0B2C62] hover:text-white disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
            Next
            <svg class="w-4 h-4 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        </button>
    </div>
</div>



<!-- View Schedule Details Modal (Students) -->
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
            <div>
                <div class="flex justify-between items-center mb-1">
                    <label class="block text-sm font-medium text-gray-700">Section Name</label>
                    <button type="button" onclick="showManageSectionsModal()" class="text-xs text-blue-600 hover:text-blue-700 font-medium">+ Manage Sections</button>
                </div>
                <select id="sectionName" name="section_name" required 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500">
                    <option value="">-- Select Section --</option>
                </select>
                <p class="text-xs text-gray-500 mt-1">Select a section or click "Manage Sections" to add new ones</p>
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
            <p class="text-sm text-gray-500 mb-6">Are you sure you want to delete this schedule? This will remove all student assignments and cannot be undone.</p>
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

<!-- Manage Sections Modal -->
<div id="manageSectionsModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col">
        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-blue-600 to-blue-700 rounded-t-xl">
            <h3 class="text-lg font-bold text-white">Manage Sections</h3>
            <button onclick="hideManageSectionsModal()" class="text-white hover:text-gray-200 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <div class="overflow-y-auto flex-1 px-6 py-4">
            <div class="mb-4">
                <button onclick="showAddSectionForm()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add New Section
                </button>
            </div>
            
            <div id="addSectionForm" class="hidden mb-4 p-4 bg-gray-50 rounded-lg border-2 border-green-200">
                <h4 class="font-semibold text-gray-800 mb-3">Add New Section</h4>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Section Name <span class="text-red-500">*</span></label>
                        <input type="text" id="newSectionName" placeholder="e.g., BSIT 603, ABM - 12A" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="flex gap-2">
                        <button onclick="saveNewSection()" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium">Save</button>
                        <button onclick="hideAddSectionForm()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium">Cancel</button>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <input type="text" id="sectionSearchInput" placeholder="Search sections..." onkeyup="filterSectionsList()" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div id="sectionsListContainer" class="space-y-2 max-h-96 overflow-y-auto">
                <p class="text-gray-500 text-center py-4">Loading sections...</p>
            </div>
        </div>
        
    </div>
</div>

<!-- Edit Section Modal -->
<div id="editSectionModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[60]">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-4 rounded-t-xl">
            <h3 class="text-lg font-bold">Edit Section</h3>
        </div>
        <div class="p-6 space-y-4">
            <input type="hidden" id="editSectionId">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1.5">Section Name <span class="text-red-500">*</span></label>
                <input type="text" id="editSectionName" class="w-full px-4 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex gap-3 pt-4">
                <button onclick="hideEditSectionModal()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2.5 rounded-lg font-medium">Cancel</button>
                <button onclick="saveEditSection()" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2.5 rounded-lg font-medium">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Section Confirmation Modal -->
<div id="deleteSectionModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[60]">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-sm mx-4 p-6">
        <div class="text-center">
            <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-800 mb-2">Delete Section</h3>
            <p class="text-sm text-gray-600 mb-6">Are you sure you want to delete this section? This action cannot be undone.</p>
            <input type="hidden" id="deleteSectionId">
            <div class="flex gap-3">
                <button onclick="hideDeleteSectionModal()" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white py-2.5 rounded-lg font-medium">Cancel</button>
                <button onclick="confirmDeleteSection()" class="flex-1 bg-red-600 hover:bg-red-700 text-white py-2.5 rounded-lg font-medium">Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
// Show/Hide Modals
function showCreateScheduleModal() {
    // Reset to defaults every time the modal opens
    const modal = document.getElementById('createScheduleModal');
    const form = document.getElementById('createScheduleForm');
    if (form) form.reset();
    // Title/button
    const titleEl = document.getElementById('modalTitle'); if (titleEl) titleEl.textContent = 'Create New Schedule';
    const submitEl = document.getElementById('submitBtn'); if (submitEl) submitEl.textContent = 'Create Schedule';
    // Hidden id and fields
    const idEl = document.getElementById('scheduleId'); if (idEl) idEl.value = '';
    const nameEl = document.getElementById('sectionName'); if (nameEl) nameEl.value = '';
    const stEl = document.getElementById('startTime'); if (stEl) stEl.value = '';
    const etEl = document.getElementById('endTime'); if (etEl) etEl.value = '';
    // Force schedule type to 'same'
    const sameRadio = document.querySelector('input[name="schedule_type"][value="same"]');
    const diffRadio = document.querySelector('input[name="schedule_type"][value="different"]');
    if (sameRadio) sameRadio.checked = true; if (diffRadio) diffRadio.checked = false;
    // Show same group, hide different group
    const sameDiv = document.getElementById('sameTimeSchedule'); if (sameDiv) sameDiv.classList.remove('hidden');
    const diffDiv = document.getElementById('differentTimeSchedule'); if (diffDiv) diffDiv.classList.add('hidden');
    // Uncheck all same-days
    document.querySelectorAll('input[name="days[]"]').forEach(cb => { cb.checked = false; });
    // Clear per-day inputs
    document.querySelectorAll('#differentTimeSchedule input[type="checkbox"]').forEach(cb => { cb.checked = false; });
    document.querySelectorAll('#differentTimeSchedule input[type="time"]').forEach(inp => { inp.value = ''; });
    // Hide inline errors
    const sameErr = document.getElementById('sameDaysError'); if (sameErr) sameErr.classList.add('hidden');
    const diffErr = document.getElementById('diffDaysError'); if (diffErr) diffErr.classList.add('hidden');
    // Load sections into dropdown
    loadSectionsDropdown();
    // Show modal
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
    document.getElementById('sectionName').value = '';
    document.getElementById('startTime').value = '';
    document.getElementById('endTime').value = '';
    
    // Uncheck all day checkboxes
    document.querySelectorAll('input[name="days[]"]').forEach(checkbox => {
        checkbox.checked = false;
    });
}



let currentScheduleId = null;

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

    const formData = new FormData(this);
    const action = currentScheduleId ? 'edit_schedule' : 'create_schedule';
    formData.append('action', action);
    
    if (currentScheduleId) {
        formData.append('schedule_id', currentScheduleId);
    }
    
    fetch('ManageSchedule.php', {
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













// Cache original schedule options and implement filtering for the dropdown
let originalScheduleOptions = null; // Array<{value,text}>
function cacheOriginalScheduleOptions(){
    if (originalScheduleOptions) return;
    const select = document.getElementById('scheduleSelect'); if (!select) return;
    originalScheduleOptions = Array.from(select.options).map(o=>({ value: o.value, text: o.textContent }));
}
function filterScheduleOptions(q){
    cacheOriginalScheduleOptions();
    const select = document.getElementById('scheduleSelect'); if (!select) return;
    const query = (q||'').toLowerCase();
    // Keep the first option (placeholder)
    const placeholder = originalScheduleOptions[0];
    const rest = originalScheduleOptions.slice(1);
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
function onScheduleSearchInput(e){
    filterScheduleOptions(e.target.value);
}

// ---- Custom dropdown helpers ----
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
        html += `<div class="sched-item${active}" data-value="${opt.value.replace(/"/g,'&quot;')}">${opt.textContent}</div>`;
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
    // propagate change
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
    window.location.href = 'RegistrarDashboard.php';
}

// Edit schedule function
function editSchedule(id) {
    // Fetch schedule data
    fetch(`get_schedule.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const schedule = data.schedule;
                
                // Populate form with existing data
                document.getElementById('scheduleId').value = schedule.id;
                document.getElementById('sectionName').value = schedule.section_name;
                
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
        
        fetch('ManageSchedule.php', {
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
    // Use secure POST method to set session data instead of URL parameters
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'set_schedule_session.php';
    form.style.display = 'none';
    
    const scheduleIdInput = document.createElement('input');
    scheduleIdInput.type = 'hidden';
    scheduleIdInput.name = 'schedule_id';
    scheduleIdInput.value = id;
    
    const sectionNameInput = document.createElement('input');
    sectionNameInput.type = 'hidden';
    sectionNameInput.name = 'section_name';
    sectionNameInput.value = sectionName;
    
    form.appendChild(scheduleIdInput);
    form.appendChild(sectionNameInput);
    document.body.appendChild(form);
    form.submit();
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
    fetch(`get_schedule.php?id=${id}`)
      .then(res=>res.json())
      .then(d=>{
        if(!d.success){ body.innerHTML = '<p class="text-red-600">Failed to load schedule.</p>'; return; }
        const s = d.schedule; const items = [];
        title.textContent = ((s.section_name||'') + ' — Details').trim();
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

// Schedule Pagination Logic (6 per page)
(function(){
  const scheduleGrid = document.getElementById('classSchedulesGrid');
  const prevBtn = document.getElementById('prevPage');
  const nextBtn = document.getElementById('nextPage');
  const pageInfo = document.getElementById('pageInfo');
  const paginationBar = document.getElementById('paginationBar');
  
  if (!scheduleGrid || !prevBtn || !nextBtn || !pageInfo) return;
  
  const pageSize = 6; // 6 schedules per page (2 rows of 3 in lg grid)
  let currentPage = 1;
  let allScheduleCards = [];
  let filteredScheduleCards = [];
  
  // Initialize pagination
  function initPagination() {
    allScheduleCards = Array.from(scheduleGrid.querySelectorAll('.class-schedule-card'));
    filteredScheduleCards = [...allScheduleCards];
    
    // Show pagination only if there are schedules
    if (allScheduleCards.length > 0) {
      paginationBar.classList.remove('hidden');
      renderPage();
    } else {
      paginationBar.classList.add('hidden');
    }
  }
  
  // Render current page
  function renderPage() {
    const totalPages = Math.max(1, Math.ceil(filteredScheduleCards.length / pageSize));
    currentPage = Math.min(Math.max(1, currentPage), totalPages);
    
    // Hide all cards first
    allScheduleCards.forEach(card => card.style.display = 'none');
    
    // Show cards for current page
    const startIndex = (currentPage - 1) * pageSize;
    const endIndex = startIndex + pageSize;
    const pageCards = filteredScheduleCards.slice(startIndex, endIndex);
    
    pageCards.forEach(card => card.style.display = '');
    
    // Update pagination controls
    pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
    prevBtn.disabled = currentPage <= 1;
    nextBtn.disabled = currentPage >= totalPages;
    
    // Hide pagination if only one page
    if (totalPages <= 1) {
      paginationBar.classList.add('hidden');
    } else {
      paginationBar.classList.remove('hidden');
    }
  }
  
  // Update filtered cards based on search
  function updateFilteredCards() {
    const searchInput = document.getElementById('classScheduleSearch');
    const query = searchInput ? searchInput.value.trim().toLowerCase() : '';
    
    if (query.length === 0) {
      filteredScheduleCards = [...allScheduleCards];
    } else {
      filteredScheduleCards = allScheduleCards.filter(card => {
        const name = (card.querySelector('.class-schedule-name')?.textContent || '').toLowerCase();
        const days = (card.querySelector('.class-schedule-days')?.textContent || '').toLowerCase();
        return name.includes(query) || days.includes(query);
      });
    }
    
    currentPage = 1; // Reset to first page when filtering
    renderPage();
    
    // Show/hide no results message
    const noResults = document.getElementById('classNoResults');
    if (noResults) {
      noResults.classList.toggle('hidden', filteredScheduleCards.length > 0);
    }
  }
  
  // Event listeners
  prevBtn.addEventListener('click', () => {
    if (currentPage > 1) {
      currentPage--;
      renderPage();
    }
  });
  
  nextBtn.addEventListener('click', () => {
    const totalPages = Math.ceil(filteredScheduleCards.length / pageSize);
    if (currentPage < totalPages) {
      currentPage++;
      renderPage();
    }
  });
  
  // Override the existing search filter to work with pagination
  const classSearchEl = document.getElementById('classScheduleSearch');
  if (classSearchEl) {
    // Remove existing event listeners by cloning the element
    const newSearchEl = classSearchEl.cloneNode(true);
    classSearchEl.parentNode.replaceChild(newSearchEl, classSearchEl);
    
    // Add new event listeners that work with pagination
    newSearchEl.addEventListener('input', updateFilteredCards);
    newSearchEl.addEventListener('focus', updateFilteredCards);
    newSearchEl.addEventListener('blur', updateFilteredCards);
    
    // Update clear button functionality
    const clearBtn = document.getElementById('classSearchClear');
    if (clearBtn) {
      clearBtn.addEventListener('click', () => {
        newSearchEl.value = '';
        newSearchEl.focus();
        updateFilteredCards();
      });
    }
    
    // Update search icon and clear button visibility
    const icon = document.getElementById('classSearchIcon');
    const updateSearchUI = () => {
      const q = newSearchEl.value.trim();
      if (icon) icon.classList.toggle('hidden', q.length > 0);
      if (clearBtn) clearBtn.classList.toggle('hidden', q.length === 0);
      if (q.length > 0) {
        newSearchEl.classList.remove('pl-10');
        newSearchEl.classList.add('pl-3');
      } else {
        newSearchEl.classList.add('pl-10');
        newSearchEl.classList.remove('pl-3');
      }
    };
    
    newSearchEl.addEventListener('input', updateSearchUI);
    newSearchEl.addEventListener('focus', updateSearchUI);
    newSearchEl.addEventListener('blur', updateSearchUI);
  }
  
  // Initialize pagination on page load
  initPagination();
  
  // Re-initialize pagination when schedules are added/removed dynamically
  window.reinitializeSchedulePagination = initPagination;
})();

// ========== SECTION MANAGEMENT FUNCTIONS ==========
function loadSectionsDropdown() {
    const formData = new FormData();
    formData.append('action', 'get_sections');
    
    fetch('ManageSchedule.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('sectionName');
                if (select) {
                    // Keep the first "Select Section" option
                    select.innerHTML = '<option value="">-- Select Section --</option>';
                    
                    // Separate sections into available and used
                    const availableSections = data.sections.filter(s => s.usage_count == 0);
                    const usedSections = data.sections.filter(s => s.usage_count > 0);
                    
                    // Add available sections first
                    if (availableSections.length > 0) {
                        const availableGroup = document.createElement('optgroup');
                        availableGroup.label = 'Available Sections';
                        availableSections.forEach(section => {
                            const option = document.createElement('option');
                            option.value = section.section_name;
                            option.textContent = section.section_name;
                            availableGroup.appendChild(option);
                        });
                        select.appendChild(availableGroup);
                    }
                    
                    // Add used sections (disabled and greyed out)
                    if (usedSections.length > 0) {
                        const usedGroup = document.createElement('optgroup');
                        usedGroup.label = 'Already in Use';
                        usedSections.forEach(section => {
                            const option = document.createElement('option');
                            option.value = section.section_name;
                            option.textContent = section.section_name + ' (In Use)';
                            option.disabled = true;
                            option.style.color = '#9ca3af';
                            usedGroup.appendChild(option);
                        });
                        select.appendChild(usedGroup);
                    }
                }
            }
        })
        .catch(err => console.error('Error loading sections:', err));
}

function showManageSectionsModal() {
    document.getElementById('manageSectionsModal').classList.remove('hidden');
    loadSections();
}

function hideManageSectionsModal() {
    document.getElementById('manageSectionsModal').classList.add('hidden');
    hideAddSectionForm();
    // Reload the sections dropdown in case sections were added/edited
    loadSectionsDropdown();
}

function showAddSectionForm() {
    document.getElementById('addSectionForm').classList.remove('hidden');
    document.getElementById('newSectionName').value = '';
    document.getElementById('newSectionDesc').value = '';
    document.getElementById('newSectionName').focus();
}

function hideAddSectionForm() {
    document.getElementById('addSectionForm').classList.add('hidden');
}

function loadSections() {
    const formData = new FormData();
    formData.append('action', 'get_sections');
    
    fetch('ManageSchedule.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                displaySections(data.sections);
            } else {
                document.getElementById('sectionsListContainer').innerHTML = '<p class="text-red-500 text-center py-4">Error loading sections</p>';
            }
        })
        .catch(() => {
            document.getElementById('sectionsListContainer').innerHTML = '<p class="text-red-500 text-center py-4">Error loading sections</p>';
        });
}

function displaySections(sections) {
    const container = document.getElementById('sectionsListContainer');
    
    if (sections.length === 0) {
        container.innerHTML = '<p class="text-gray-500 text-center py-4">No sections found. Click "Add New Section" to create one.</p>';
        return;
    }
    
    let html = '';
    sections.forEach(section => {
        html += `
            <div class="section-item bg-gray-50 hover:bg-gray-100 p-4 rounded-lg border border-gray-200 transition">
                <div class="flex justify-between items-center">
                    <div class="flex-1">
                        <h4 class="font-semibold text-gray-800">${escapeHtml(section.section_name)}</h4>
                        <p class="text-xs text-gray-400 mt-1">Created: ${new Date(section.created_at).toLocaleDateString()}</p>
                    </div>
                    <div class="flex gap-2 ml-4">
                        <button onclick='editSection(${JSON.stringify(section)})' class="text-blue-600 hover:text-blue-800 font-medium text-sm">Edit</button>
                        <button onclick="deleteSection(${section.id})" class="text-red-600 hover:text-red-800 font-medium text-sm">Delete</button>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

function filterSectionsList() {
    const search = document.getElementById('sectionSearchInput').value.toLowerCase();
    const items = document.querySelectorAll('.section-item');
    
    items.forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(search) ? '' : 'none';
    });
}

function saveNewSection() {
    const name = document.getElementById('newSectionName').value.trim();
    
    if (!name) {
        alert('Please enter a section name');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'create_section');
    formData.append('section_name', name);
    formData.append('description', '');
    
    fetch('ManageSchedule.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                hideAddSectionForm();
                loadSections();
                showSuccessMessage(data.message);
            } else {
                alert(data.message);
            }
        })
        .catch(() => alert('Error saving section'));
}

function editSection(section) {
    document.getElementById('editSectionId').value = section.id;
    document.getElementById('editSectionName').value = section.section_name;
    document.getElementById('editSectionModal').classList.remove('hidden');
}

function hideEditSectionModal() {
    document.getElementById('editSectionModal').classList.add('hidden');
}

function saveEditSection() {
    const id = document.getElementById('editSectionId').value;
    const name = document.getElementById('editSectionName').value.trim();
    
    if (!name) {
        alert('Please enter a section name');
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'update_section');
    formData.append('id', id);
    formData.append('section_name', name);
    formData.append('description', '');
    
    fetch('ManageSchedule.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                hideEditSectionModal();
                loadSections();
                showSuccessMessage(data.message);
            } else {
                alert(data.message);
            }
        })
        .catch(() => alert('Error updating section'));
}

function deleteSection(id) {
    document.getElementById('deleteSectionId').value = id;
    document.getElementById('deleteSectionModal').classList.remove('hidden');
}

function hideDeleteSectionModal() {
    document.getElementById('deleteSectionModal').classList.add('hidden');
}

function confirmDeleteSection() {
    const id = document.getElementById('deleteSectionId').value;
    
    const formData = new FormData();
    formData.append('action', 'delete_section');
    formData.append('id', id);
    
    fetch('ManageSchedule.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                hideDeleteSectionModal();
                loadSections();
                showSuccessMessage(data.message);
            } else {
                alert(data.message);
            }
        })
        .catch(() => alert('Error deleting section'));
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showSuccessMessage(message) {
    const msgEl = document.getElementById('successMessage');
    const textEl = document.getElementById('successText');
    if (msgEl && textEl) {
        textEl.textContent = message;
        msgEl.classList.remove('hidden');
        setTimeout(() => msgEl.classList.add('hidden'), 3000);
    }
}
</script>

</body>
</html>
