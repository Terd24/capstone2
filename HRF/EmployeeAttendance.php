<?php
session_start();
include("../StudentLogin/db_conn.php");

// HR only
if (!((isset($_SESSION['role']) && $_SESSION['role'] === 'hr') || isset($_SESSION['hr_name']))) {
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

// Filters
$search_name = trim($_GET['search_name'] ?? '');
$start_date  = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$end_date    = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';

// Build teacher attendance query (teacher_attendance used for teachers)
$sql = "SELECT ta.*, e.first_name, e.last_name, e.id_number
        FROM teacher_attendance ta
        JOIN employees e ON e.id_number = ta.$teacher_id_column
        WHERE (ta.time_in IS NOT NULL OR ta.time_out IS NOT NULL)";
$params = []; $types = '';

if ($start_date !== '' && $end_date !== '') {
    $sql .= " AND ta.date BETWEEN ? AND ?";
    $params[] = $start_date; $types .= 's';
    $params[] = $end_date;   $types .= 's';
} else {
    $sql .= " AND ta.date = ?";
    $params[] = $today; $types .= 's';
}

if ($search_name !== '') {
    $sql .= " AND CONCAT(e.first_name, ' ', e.last_name) LIKE ?";
    $like = "%$search_name%";
    $params[] = $like; $types .= 's';
}

$sql .= " ORDER BY ta.date DESC, ta.id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$records = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Employee Attendance - HR</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
  <header class="bg-[#0B2C62] text-white shadow-lg">
    <div class="container mx-auto px-6 py-4">
      <div class="flex justify-between items-center">
        <div class="flex items-center space-x-3">
          <button onclick="window.location.href='Dashboard.php'" class="bg-white bg-opacity-20 hover:bg-opacity-30 p-2 rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
          </button>
          <span class="text-lg font-bold">Employee Attendance</span>
        </div>
        <div class="flex items-center space-x-3">
          <img src="../images/LogoCCI.png" class="h-10 w-10 rounded-full bg-white p-1" alt="Logo">
          <div class="text-right leading-tight">
            <div class="text-sm font-bold">Cornerstone College Inc.</div>
            <div class="text-[11px] text-blue-200">HR Portal</div>
          </div>
        </div>
      </div>
    </div>
  </header>

  <div class="container mx-auto px-6 py-4">
    <div class="bg-white rounded-2xl shadow-lg p-5 mb-4">
      <h2 class="text-xl font-bold text-gray-800 mb-4">Filter</h2>
      <form method="get" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        <div>
          <label class="text-sm font-medium text-gray-700 mb-2 block">Search Employee</label>
          <input type="text" name="search_name" value="<?= htmlspecialchars($search_name) ?>" placeholder="Enter employee name..."
                 class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-[#0B2C62] focus:border-transparent">
        </div>
        <div>
          <label class="text-sm font-medium text-gray-700 mb-2 block">Start Date</label>
          <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-[#0B2C62] focus:border-transparent">
        </div>
        <div>
          <label class="text-sm font-medium text-gray-700 mb-2 block">End Date</label>
          <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-[#0B2C62] focus:border-transparent">
        </div>
        <div class="flex gap-2">
          <button type="submit" class="flex-1 bg-[#0B2C62] hover:bg-blue-900 text-white px-6 py-2 rounded-lg font-medium">Generate Report</button>
          <a href="EmployeeAttendance.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium">Clear</a>
        </div>
      </form>
      <p class="mt-4 text-gray-600">
        <?php if ($start_date !== '' && $end_date !== ''): ?>
            Attendance records from <?= date('F j, Y', strtotime($start_date)) ?> to <?= date('F j, Y', strtotime($end_date)) ?>
        <?php elseif ($search_name !== ''): ?>
            Search results for "<?= htmlspecialchars($search_name) ?>" on <?= date('F j, Y', strtotime($today)) ?>
        <?php else: ?>
            Real-time attendance tracking for <?= date('F j, Y', strtotime($today)) ?>
        <?php endif; ?>
      </p>
    </div>

    <div class="bg-white rounded-2xl shadow-lg overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-[#0B2C62] text-white">
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
        </thead>
        <tbody class="divide-y divide-gray-200">
          <?php if ($records && $records->num_rows > 0): ?>
            <?php while ($r = $records->fetch_assoc()): ?>
              <tr class="hover:bg-blue-50">
                <?php
                  // Precompute schedule details and metrics per row
                  $empId = $r['employee_id'];
                  $dayName = date('l', strtotime($r['date']));
                  $shiftType = '—';
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
                      $isVariableDays = (strcasecmp(trim((string)$sd['days']), 'Variable') === 0);
                      $placeholderTimes = ($sd['start_time'] === '00:00:00' && $sd['end_time'] === '23:59:59');
                      // Check if there are day-specific schedules (with fallback if table doesn't exist)
                      $dayScheduleCount = 0;
                      try {
                        $dayScheduleCheck = $conn->prepare("SELECT COUNT(*) as count FROM employee_work_day_schedules WHERE schedule_id = ?");
                        if ($dayScheduleCheck) {
                          $dayScheduleCheck->bind_param('i', $sd['schedule_id']);
                          $dayScheduleCheck->execute();
                          $dayResult = $dayScheduleCheck->get_result();
                          if ($dayResult) {
                            $dayScheduleCount = $dayResult->fetch_assoc()['count'];
                          }
                        }
                      } catch (Exception $e) {
                        // Table doesn't exist, assume regular schedule
                        $dayScheduleCount = 0;
                      }
                      $shiftType = ($isVariableDays || $placeholderTimes || $dayScheduleCount > 0) ? 'Irregular' : 'Regular';
                      $shiftIn = $sd['start_time'];
                      $shiftOut = $sd['end_time'];
                      if (!empty($sd['days'])) {
                        $daysArr = array_map('trim', explode(',', $sd['days']));
                        $hasDay = in_array($dayName, $daysArr, true) || $shiftType === 'Variable';
                      }
                      if (!empty($sd['schedule_id'])) {
                        try {
                          $ds = $conn->prepare("SELECT start_time, end_time FROM employee_work_day_schedules WHERE schedule_id = ? AND day_name = ? LIMIT 1");
                          if ($ds) {
                            $ds->bind_param('is', $sd['schedule_id'], $dayName);
                            $ds->execute();
                            $ovr = $ds->get_result();
                            if ($ovr && $ovr->num_rows > 0) { $o=$ovr->fetch_assoc(); $shiftIn=$o['start_time']; $shiftOut=$o['end_time']; $hasDay=true; $shiftType = 'Irregular'; }
                            // Also, if any day-specific rows exist at all, treat as Irregular
                            $cntQ = $conn->prepare("SELECT COUNT(*) AS c FROM employee_work_day_schedules WHERE schedule_id = ?");
                            if ($cntQ) { $cntQ->bind_param('i', $sd['schedule_id']); $cntQ->execute(); $cntRes = $cntQ->get_result(); if ($cntRes) { $rowCnt = $cntRes->fetch_assoc(); if ((int)$rowCnt['c'] > 0) { $shiftType = 'Irregular'; } } }
                          }
                        } catch (Exception $e) {
                          // Table doesn't exist, skip day-specific schedule lookup
                        }
                      }
                    }
                  }
                  $timeIn = $r['time_in'];
                  $timeOut = $r['time_out'];
                  $reqHours = 0.0; $tardinessMin = 0; $undertimeMin = 0; $otMin = 0; $obMin = 0;
                  if ($hasDay && $shiftIn && $shiftOut) {
                    $reqHours = max(0, (strtotime($shiftOut) - strtotime($shiftIn)) / 3600.0);
                    if (!empty($timeIn)) {
                      $tardinessMin = max(0, intval((strtotime($timeIn) - strtotime($shiftIn)) / 60));
                    }
                    if (!empty($timeOut)) {
                      $undertimeMin = max(0, intval((strtotime($shiftOut) - strtotime($timeOut)) / 60));
                      $otMin = max(0, intval((strtotime($timeOut) - strtotime($shiftOut)) / 60));
                    }
                  }
                  $status = (!empty($timeIn)) ? 'Present' : '—';
                  $statusColor = ($status==='Present') ? 'text-green-600' : 'text-gray-600';
                ?>
                <td class="px-6 py-3 font-medium"><?= htmlspecialchars(trim(($r['first_name'] ?? '').' '.($r['last_name'] ?? ''))) ?></td>
                <td class="px-6 py-3 text-gray-800"><?= date('F j, Y', strtotime($r['date'])) ?></td>
                <td class="px-6 py-3 text-gray-800"><?= htmlspecialchars($r['day'] ?: '-') ?></td>
                <td class="px-6 py-3 text-gray-800"><?= htmlspecialchars($shiftType) ?></td>
                <td class="px-6 py-3 text-gray-800"><?= ($hasDay && $shiftIn) ? date('g:i A', strtotime($shiftIn)) : '--' ?></td>
                <td class="px-6 py-3 text-gray-800"><?= ($hasDay && $shiftOut) ? date('g:i A', strtotime($shiftOut)) : '--' ?></td>
                <td class="px-6 py-3 "><?= $timeIn ? date('g:i A', strtotime($timeIn)) : '--' ?></td>
                <td class="px-6 py-3 "><?= $timeOut ? date('g:i A', strtotime($timeOut)) : '--' ?></td>
                <td class="px-6 py-3 "><?= number_format($reqHours, 2) ?></td>
                <td class="px-6 py-3 "><?= $tardinessMin ?> mins</td>
                <td class="px-6 py-3 "><?= $undertimeMin ?> mins</td>
                <td class="px-6 py-3 "><?= $otMin ?> mins</td>
                <td class="px-6 py-3 "><?= $obMin ?> mins</td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="13" class="px-6 py-8 text-center text-gray-500">No attendance records found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>