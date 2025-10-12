<?php
session_start();
include("../StudentLogin/db_conn.php");

// Registrar only
if (!isset($_SESSION['registrar_id'])) {
    header("Location: ../StudentLogin/login.html");
    exit;
}

date_default_timezone_set('Asia/Manila');
$today = date('Y-m-d');

// Filters
$search_name = trim($_GET['search_name'] ?? '');
// Keep inputs empty by default; we will still filter to today if both are empty
$start_date  = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$end_date    = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';
$filter_section = trim($_GET['filter_section'] ?? '');
$filter_status = trim($_GET['filter_status'] ?? '');

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

// Build query for STUDENT attendance only
$sql = "SELECT ar.*, sa.first_name, sa.middle_name, sa.last_name
        FROM attendance_record ar
        JOIN student_account sa ON sa.id_number = ar.id_number
        WHERE 1=1";
$params = []; $types = '';

// Date range
if ($start_date !== '' && $end_date !== '') {
    $sql .= " AND ar.date BETWEEN ? AND ?";
    $params[] = $start_date; $types .= 's';
    $params[] = $end_date;   $types .= 's';
} else {
    // When no range is provided, default to today's attendance
    $sql .= " AND ar.date = ?";
    $params[] = $today; $types .= 's';
}

// Name filter
if ($search_name !== '') {
    $sql .= " AND (CONCAT(sa.first_name,' ',sa.middle_name,' ',sa.last_name) LIKE ?
                 OR CONCAT(sa.first_name,' ',sa.last_name) LIKE ?)";
    $like = "%$search_name%";
    $params[] = $like; $types .= 's';
    $params[] = $like; $types .= 's';
}

// Section filter
if ($filter_section !== '') {
    $sql .= " AND ar.schedule LIKE ?";
    $section_like = "$filter_section%"; // Changed to match from beginning of string
    $params[] = $section_like; $types .= 's';
}

// Status filter
if ($filter_status !== '') {
    $sql .= " AND ar.status = ?";
    $params[] = $filter_status; $types .= 's';
}

$sql .= " ORDER BY ar.date DESC, ar.id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$records = $stmt->get_result();

// Get unique sections for filter dropdown
$sections_query = "SELECT DISTINCT ar.schedule FROM attendance_record ar WHERE ar.schedule IS NOT NULL AND ar.schedule != '' ORDER BY ar.schedule";
$sections_result = $conn->query($sections_query);
$sections = [];
if ($sections_result) {
    while ($row = $sections_result->fetch_assoc()) {
        $schedule = trim($row['schedule']);
        if ($schedule !== '') {
            // Extract section name from schedule (before parentheses)
            if (preg_match('/^([^\(]+)/', $schedule, $matches)) {
                $section = trim($matches[1]);
                if (!in_array($section, $sections)) {
                    $sections[] = $section;
                }
            }
        }
    }
}

// Get unique statuses for filter dropdown
$status_query = "SELECT DISTINCT status FROM attendance_record WHERE status IS NOT NULL AND status != '' ORDER BY status";
$status_result = $conn->query($status_query);
$statuses = [];
if ($status_result) {
    while ($row = $status_result->fetch_assoc()) {
        $status = trim($row['status']);
        if ($status !== '') {
            $statuses[] = $status;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Attendance Records - Registrar</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
  <header class="bg-[#0B2C62] text-white shadow-lg">
    <div class="container mx-auto px-6 py-4">
      <div class="flex justify-between items-center">
        <div class="flex items-center space-x-3">
          <button onclick="window.location.href='RegistrarDashboard.php'" class="bg-white bg-opacity-20 hover:bg-opacity-30 p-2 rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
          </button>
          <span class="text-lg font-bold">Attendance Records</span>
        </div>
        <div class="flex items-center space-x-3">
          <img src="../images/LogoCCI.png" class="h-10 w-10 rounded-full bg-white p-1" alt="Logo">
          <div class="text-right leading-tight">
            <div class="text-sm font-bold">Cornerstone College Inc.</div>
            <div class="text-[11px] text-blue-200">Registrar Portal</div>
          </div>
        </div>
      </div>
    </div>
  </header>

  <div class="container mx-auto px-6 py-4">
    <div class="bg-white rounded-2xl shadow-lg p-5 mb-4">
      <h2 class="text-xl font-bold text-gray-800 mb-4">Filter</h2>
      <form method="get" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-8 gap-4 items-end">
          <div class="md:col-span-2">
            <label class="text-sm font-medium text-gray-700 mb-2 block">Start Date</label>
            <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" min="2025-01-01" max="<?= $today ?>" placeholder="Start date" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-[#0B2C62] focus:border-transparent" id="startDate">
          </div>
          <div class="md:col-span-2">
            <label class="text-sm font-medium text-gray-700 mb-2 block">End Date</label>
            <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" max="<?= $today ?>" placeholder="End date" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-[#0B2C62] focus:border-transparent" id="endDate">
          </div>
          <div class="md:col-span-4 flex gap-2">
            <button type="submit" class="flex-1 bg-[#0B2C62] hover:bg-blue-900 text-white px-6 py-2 rounded-lg font-medium whitespace-nowrap">Generate Report</button>
            <a href="AttendanceRecords.php" class="bg-gray-500 hover:bg-gray-600 text-white px-10 py-2 rounded-lg font-medium whitespace-nowrap">Clear</a>
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-8 gap-4 items-end">
          <div class="md:col-span-4">
            <label class="text-sm font-medium text-gray-700 mb-2 block">Search Student</label>
            <input type="text" name="search_name" value="<?= htmlspecialchars($search_name) ?>" placeholder="Enter student name..."
                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-[#0B2C62] focus:border-transparent">
          </div>
          <div class="md:col-span-2">
            <label class="text-sm font-medium text-gray-700 mb-2 block">Section</label>
            <select name="filter_section" onchange="this.form.submit()" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-[#0B2C62] focus:border-transparent">
              <option value="">All Sections</option>
              <?php foreach ($sections as $section): ?>
                <option value="<?= htmlspecialchars($section) ?>" <?= ($filter_section === $section) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($section) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="md:col-span-2">
            <label class="text-sm font-medium text-gray-700 mb-2 block">Status</label>
            <select name="filter_status" onchange="this.form.submit()" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-[#0B2C62] focus:border-transparent">
              <option value="">All Status</option>
              <?php foreach ($statuses as $status): ?>
                <option value="<?= htmlspecialchars($status) ?>" <?= ($filter_status === $status) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($status) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </form>
      <p class="mt-4 text-gray-600">
        <?php 
        $filters = [];
        if ($start_date !== '' && $end_date !== '') {
            $filters[] = "from " . date('F j, Y', strtotime($start_date)) . " to " . date('F j, Y', strtotime($end_date));
        } elseif ($start_date === '' && $end_date === '') {
            $filters[] = "for " . date('F j, Y', strtotime($today));
        }
        if ($search_name !== '') {
            $filters[] = "student: \"" . htmlspecialchars($search_name) . "\"";
        }
        if ($filter_section !== '') {
            $filters[] = "section: " . htmlspecialchars($filter_section);
        }
        if ($filter_status !== '') {
            $filters[] = "status: " . htmlspecialchars($filter_status);
        }
        
        if (!empty($filters)) {
            echo "Attendance records " . implode(", ", $filters);
        } else {
            echo "Real-time attendance tracking for " . date('F j, Y', strtotime($today));
        }
        ?>
      </p>
    </div>

    <div class="bg-white rounded-2xl shadow-lg overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-[#0B2C62] text-white">
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
        </thead>
        <tbody class="divide-y divide-gray-200">
          <?php if ($records && $records->num_rows > 0): ?>
            <?php while ($r = $records->fetch_assoc()): ?>
              <tr class="hover:bg-blue-50">
                <td class="px-6 py-3 font-medium">
                  <?= htmlspecialchars(trim(($r['first_name'] ?? '').' '.($r['middle_name'] ?? '').' '.($r['last_name'] ?? ''))) ?>
                </td>
                <td class="px-6 py-3 text-gray-800"><?= date('F j, Y', strtotime($r['date'])) ?></td>
                <td class="px-6 py-3 text-gray-800"><?= htmlspecialchars($r['day'] ?: '-') ?></td>
                <td class="px-6 py-3 text-gray-800"><?php
                  $section = 'No Section';
                  $schedStr = trim((string)($r['schedule'] ?? ''));
                  if ($schedStr !== '') {
                      if (preg_match('/^([^\(]+)/', $schedStr, $m)) { $section = trim($m[1]); }
                      elseif (stripos($schedStr, 'No Class') !== false) { $section = 'No Section'; }
                  }
                  echo htmlspecialchars($section);
                ?></td>
                <td class="px-6 py-3 text-gray-800"><?php
                  // Compute schedule time like AttendanceF using student's assigned schedule
                  $display = 'No Class Schedule';
                  $studentIdForRow = $r['id_number'] ?? $r['student_id'] ?? null;
                  $dayName = date('l', strtotime($r['date']));
                  if ($studentIdForRow) {
                      // Get base schedule and possible day override
                      $schQ = $conn->prepare("SELECT cs.start_time, cs.end_time, cs.days, cs.id AS schedule_id
                                              FROM student_account sa
                                              LEFT JOIN student_schedules ss ON sa.id_number = ss.student_id
                                              LEFT JOIN class_schedules cs ON ss.schedule_id = cs.id
                                              WHERE sa.id_number = ? LIMIT 1");
                      if ($schQ) {
                          $schQ->bind_param('s', $studentIdForRow);
                          $schQ->execute();
                          $schRes = $schQ->get_result();
                          if ($schRes && $schRes->num_rows > 0) {
                              $sd = $schRes->fetch_assoc();
                              $hasClassToday = false;
                              $start = $sd['start_time'] ?? null;
                              $end   = $sd['end_time'] ?? null;
                              if (!empty($sd['days'])) {
                                  $daysArr = array_map('trim', explode(',', $sd['days']));
                                  $hasClassToday = in_array($dayName, $daysArr, true);
                              }
                              if ($hasClassToday) {
                                  // Check for day-specific override
                                  if (!empty($sd['schedule_id'])) {
                                      $dsQ = $conn->prepare("SELECT start_time, end_time FROM day_schedules WHERE schedule_id = ? AND day_name = ? LIMIT 1");
                                      if ($dsQ) {
                                          $dsQ->bind_param('is', $sd['schedule_id'], $dayName);
                                          $dsQ->execute();
                                          $dsRes = $dsQ->get_result();
                                          if ($dsRes && $dsRes->num_rows > 0) {
                                              $ov = $dsRes->fetch_assoc();
                                              $start = $ov['start_time'];
                                              $end   = $ov['end_time'];
                                          }
                                      }
                                  }
                                  if (!empty($start) && !empty($end)) {
                                      $display = date('g:i A', strtotime($start)) . ' - ' . date('g:i A', strtotime($end));
                                  } elseif (!empty($start)) {
                                      $display = date('g:i A', strtotime($start));
                                  }
                              }
                          }
                      }
                  }
                  echo htmlspecialchars($display);
                ?></td>
                <td class="px-6 py-3 "><?= $r['time_in'] ? date('g:i A', strtotime($r['time_in'])) : '--' ?></td>
                <td class="px-6 py-3 "><?= $r['time_out'] ? date('g:i A', strtotime($r['time_out'])) : '--' ?></td>
                <td class="px-6 py-3 font-semibold <?php 
                  $status = $r['status'];
                  $color = ($status==='Present') ? 'text-green-600' : (($status==='Absent')?'text-red-600':'text-gray-600');
                  echo $color; ?>
                ">
                  <?= htmlspecialchars($r['status'] ?: '-') ?>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="7" class="px-6 py-8 text-center text-gray-500">No attendance records found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <script>
    // Date validation and dynamic min/max updates
    document.addEventListener('DOMContentLoaded', function() {
        const startDate = document.getElementById('startDate');
        const endDate = document.getElementById('endDate');
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
                endDate.min = '';
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
    });
  </script>
</body>
</html>
