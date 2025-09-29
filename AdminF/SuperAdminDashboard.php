<?php
session_start();
// Require Super Admin login
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    header('Location: ../StudentLogin/login.php');
    exit;
}

// Utility: check if a table exists in current DB
function table_exists($conn, $table) {
    if ($stmt = $conn->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1")) {
        $stmt->bind_param('s', $table);
        $stmt->execute();
        $res = $stmt->get_result();
        $ok = $res && $res->num_rows > 0;
        $stmt->close();
        return $ok;
    }
    return false;
}

$conn = new mysqli('localhost', 'root', '', 'onecci_db');
if ($conn->connect_error) {
    die('DB connection failed: ' . $conn->connect_error);
}

date_default_timezone_set('Asia/Manila');
$today = date('Y-m-d');

function q_scalar($conn, $sql, $types = '', ...$params) {
    $val = null;
    if ($stmt = $conn->prepare($sql)) {
        if ($types) { $stmt->bind_param($types, ...$params); }
        if ($stmt->execute()) {
            $res = $stmt->get_result();
            if ($res && $row = $res->fetch_row()) { $val = $row[0]; }
        }
        $stmt->close();
    }
    return $val;
}

// Ensure login_activity table exists (best effort)
$conn->query("CREATE TABLE IF NOT EXISTS login_activity (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_type VARCHAR(20) NOT NULL,
  id_number VARCHAR(50) NOT NULL,
  username VARCHAR(100) NOT NULL,
  role VARCHAR(50) NOT NULL,
  login_time DATETIME NOT NULL,
  session_id VARCHAR(128) NULL,
  INDEX idx_login_date (login_time),
  INDEX idx_id_number (id_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Metrics
$total_students  = (int) ($conn->query("SELECT COUNT(*) FROM student_account")->fetch_row()[0] ?? 0);
$total_employees = (int) ($conn->query("SELECT COUNT(*) FROM employees")->fetch_row()[0] ?? 0);
$student_taps_td = (int) (q_scalar($conn, "SELECT COUNT(*) FROM attendance_record WHERE date = ? AND (time_in IS NOT NULL OR time_out IS NOT NULL)", 's', $today) ?? 0);
// Prefer employee_attendance; fall back to teacher_attendance
$empAttTable = table_exists($conn, 'employee_attendance') ? 'employee_attendance' : (table_exists($conn, 'teacher_attendance') ? 'teacher_attendance' : null);
if ($empAttTable) {
    $employee_taps_td= (int) (q_scalar($conn, "SELECT COUNT(*) FROM `$empAttTable` WHERE date = ? AND (time_in IS NOT NULL OR time_out IS NOT NULL)", 's', $today) ?? 0);
} else {
    $employee_taps_td = 0;
}

// Logins today
$today_logins = [];
if ($stmt = $conn->prepare("SELECT user_type, id_number, username, role, login_time FROM login_activity WHERE DATE(login_time)=? ORDER BY login_time DESC")) {
    $stmt->bind_param('s', $today);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $today_logins[] = $row;
    $stmt->close();
}

// Active users (last 15 minutes)
$active_users = [];
if ($stmt = $conn->prepare("SELECT user_type, id_number, username, role, login_time FROM login_activity WHERE login_time >= (NOW() - INTERVAL 15 MINUTE) ORDER BY login_time DESC")) {
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $active_users[] = $row;
    $stmt->close();
}

// Not logged in today
$no_login_employees = [];
$sqlEmp = "SELECT e.id_number, e.first_name, e.last_name FROM employees e WHERE NOT EXISTS (SELECT 1 FROM login_activity la WHERE la.id_number=e.id_number AND DATE(la.login_time)=?) ORDER BY e.last_name ASC LIMIT 200";
if ($stmt = $conn->prepare($sqlEmp)) {
    $stmt->bind_param('s', $today);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $no_login_employees[] = $row;
    $stmt->close();
}
$no_login_students = [];
$sqlStu = "SELECT s.id_number, s.first_name, s.last_name FROM student_account s WHERE NOT EXISTS (SELECT 1 FROM login_activity la WHERE la.id_number=s.id_number AND DATE(la.login_time)=?) ORDER BY s.last_name ASC LIMIT 200";
if ($stmt = $conn->prepare($sqlStu)) {
    $stmt->bind_param('s', $today);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $no_login_students[] = $row;
    $stmt->close();
}

// Handle create HR account
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_hr'])) {
    $emp_id = trim($_POST['employee_id'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($emp_id === '' || $username === '' || strlen($password) < 6) {
        $flash = 'Please provide Employee ID, Username, and Password (min 6 chars).';
    } else {
        // Check employee exists
        $existsEmp = q_scalar($conn, "SELECT COUNT(*) FROM employees WHERE id_number = ?", 's', $emp_id);
        if (!$existsEmp) {
            $flash = 'Employee ID not found.';
        } else {
            // Check username unique
            $unameTaken = q_scalar($conn, "SELECT COUNT(*) FROM employee_accounts WHERE username = ?", 's', $username);
            if ($unameTaken) {
                $flash = 'Username already taken.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                if ($stmt = $conn->prepare("INSERT INTO employee_accounts (employee_id, username, password, role, created_at) VALUES (?,?,?,?,NOW())")) {
                    $role = 'hr';
                    $stmt->bind_param('ssss', $emp_id, $username, $hash, $role);
                    if ($stmt->execute()) {
                        $flash = 'HR account created successfully.';
                    } else {
                        $flash = 'Failed to create account.';
                    }
                    $stmt->close();
                } else {
                    $flash = 'Server error creating account.';
                }
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Super Admin Dashboard - IT Personnel Access</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
<header class="bg-gradient-to-r from-[#0B2C62] to-[#153e86] text-white shadow">
  <div class="container mx-auto px-6 py-4 flex items-center justify-between">
    <div class="flex items-center gap-3">
      <img src="../images/LogoCCI.png" class="h-11 w-11 rounded-full bg-white p-1 shadow-sm" alt="Logo">
      <div class="leading-tight">
        <div class="text-lg font-bold">Cornerstone College Inc.</div>
        <div class="text-[11px] text-blue-200">Super Admin Dashboard - IT Personnel Access</div>
      </div>
    </div>
    <div class="flex items-center gap-3">
      <span class="hidden sm:inline text-sm opacity-90">Welcome, <?= htmlspecialchars($_SESSION['superadmin_name'] ?? 'IT Personnel') ?></span>
      <a href="../StudentLogin/logout.php" class="inline-flex items-center gap-2 bg-white/10 hover:bg-white/20 px-3 py-2 rounded-lg text-sm transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        Logout
      </a>
    </div>
  </div>
</header>

<main class="container mx-auto px-6 py-6">
  <?php if ($flash): ?>
    <div class="mb-4 bg-blue-50 border border-blue-200 text-blue-900 px-4 py-3 rounded-lg"><?= htmlspecialchars($flash) ?></div>
  <?php endif; ?>

  <!-- Super Admin (IT Personnel) System Overview -->
  <div class="mb-8 bg-gradient-to-r from-[#0B2C62] to-[#153e86] text-white rounded-2xl p-6 shadow-lg">
    <div class="flex items-center gap-3 mb-4">
      <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <h1 class="text-2xl font-bold">System-Wide Overview</h1>
    </div>
    <p class="text-blue-100 mb-2">IT Personnel Access for system maintenance, bug fixing, configuration updates, and comprehensive monitoring.</p>
    <p class="text-blue-200 text-sm mb-4">⚠️ Important actions such as deleting major records or modifying settings require School Owner approval.</p>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-white/10 rounded-lg p-4">
        <div class="text-blue-200 text-sm">System Status</div>
        <div class="text-xl font-semibold">🟢 Online</div>
      </div>
      <div class="bg-white/10 rounded-lg p-4">
        <div class="text-blue-200 text-sm">Data Usage</div>
        <div class="text-xl font-semibold">Monitoring</div>
      </div>
      <div class="bg-white/10 rounded-lg p-4">
        <div class="text-blue-200 text-sm">Server Performance</div>
        <div class="text-xl font-semibold">Optimal</div>
      </div>
      <div class="bg-white/10 rounded-lg p-4">
        <div class="text-blue-200 text-sm">Security</div>
        <div class="text-xl font-semibold">🔒 Secured</div>
      </div>
    </div>
  </div>

  <!-- IT Personnel Management Tools -->
  <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-gradient-to-r from-amber-500 to-amber-600 text-white rounded-2xl p-4 shadow-lg relative">
      <div class="absolute top-2 right-2">
        <span class="bg-red-500 text-white text-xs px-2 py-1 rounded-full">Owner Approval Required</span>
      </div>
      <div class="flex items-center gap-3 mt-4">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
        </svg>
        <div>
          <div class="font-semibold">HR Account Management</div>
          <div class="text-amber-100 text-sm">Requires School Owner Approval</div>
        </div>
      </div>
    </div>
    
    <a href="../HRF/Dashboard.php" class="bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-2xl p-4 shadow-lg hover:shadow-xl transition-all transform hover:scale-105">
      <div class="flex items-center gap-3">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H5m0 0h2M7 7h10M7 11h10M7 15h10"/>
        </svg>
        <div>
          <div class="font-semibold">Employee Management</div>
          <div class="text-blue-100 text-sm">HR System Access</div>
        </div>
      </div>
    </a>
    
    <div class="bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-2xl p-4 shadow-lg">
      <div class="flex items-center gap-3">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
        <div>
          <div class="font-semibold">System Reports</div>
          <div class="text-purple-100 text-sm">Analytics & Logs</div>
        </div>
      </div>
    </div>
    
    <div class="bg-gradient-to-r from-red-500 to-red-600 text-white rounded-2xl p-4 shadow-lg relative">
      <div class="absolute top-2 right-2">
        <span class="bg-yellow-500 text-black text-xs px-2 py-1 rounded-full">Owner Approval for Major Changes</span>
      </div>
      <div class="flex items-center gap-3 mt-4">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        <div>
          <div class="font-semibold">System Maintenance</div>
          <div class="text-red-100 text-sm">Bug Fixes & Configuration</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Key Metrics Dashboard -->
  <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl shadow p-5 border border-blue-100 flex items-center gap-4">
      <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M12 7a3 3 0 110-6 3 3 0 010 6z"/></svg>
      </div>
      <div>
        <div class="text-gray-500 text-sm">Enrollment Stats</div>
        <div class="text-3xl font-bold text-gray-800 mt-1"><?= number_format($total_students) ?></div>
      </div>
    </div>
    <div class="bg-white rounded-2xl shadow p-5 border border-blue-100 flex items-center gap-4">
      <div class="w-10 h-10 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
      </div>
      <div>
        <div class="text-gray-500 text-sm">Total Employees</div>
        <div class="text-3xl font-bold text-gray-800 mt-1"><?= number_format($total_employees) ?></div>
      </div>
    </div>
    <div class="bg-white rounded-2xl shadow p-5 border border-blue-100 flex items-center gap-4">
      <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/></svg>
      </div>
      <div>
        <div class="text-gray-500 text-sm">Tuition Payment Reports</div>
        <div class="text-3xl font-bold text-gray-800 mt-1"><?= number_format($student_taps_td) ?></div>
      </div>
    </div>
    <div class="bg-white rounded-2xl shadow p-5 border border-blue-100 flex items-center gap-4">
      <div class="w-10 h-10 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </div>
      <div>
        <div class="text-gray-500 text-sm">Attendance Logs</div>
        <div class="text-3xl font-bold text-gray-800 mt-1"><?= number_format($employee_taps_td) ?></div>
      </div>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white rounded-2xl shadow p-5 border border-blue-100 lg:col-span-2">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-800">Today's Logins</h2>
        <button onclick="location.reload()" class="text-sm bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded-md">Refresh</button>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-[#0B2C62] text-white">
            <tr>
              <th class="px-4 py-2 text-left">User Type</th>
              <th class="px-4 py-2 text-left">ID</th>
              <th class="px-4 py-2 text-left">Username</th>
              <th class="px-4 py-2 text-left">Role</th>
              <th class="px-4 py-2 text-left">Login Time</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <?php if (count($today_logins) > 0): foreach ($today_logins as $r): ?>
              <tr class="hover:bg-blue-50/50">
                <td class="px-4 py-2 text-gray-800 font-medium"><?= htmlspecialchars($r['user_type']) ?></td>
                <td class="px-4 py-2 text-gray-700"><?= htmlspecialchars($r['id_number']) ?></td>
                <td class="px-4 py-2 text-gray-700"><?= htmlspecialchars($r['username']) ?></td>
                <td class="px-4 py-2"><span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700"><?= htmlspecialchars($r['role']) ?></span></td>
                <td class="px-4 py-2 text-gray-700"><?= date('F j, Y g:i A', strtotime($r['login_time'])) ?></td>
              </tr>
            <?php endforeach; else: ?>
              <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">No logins yet today.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="bg-white rounded-2xl shadow p-5 border border-blue-100">
      <h2 class="text-lg font-semibold text-gray-800 mb-4">Active (last 15 minutes)</h2>
      <ul class="space-y-2 text-sm">
        <?php if (count($active_users) > 0): foreach ($active_users as $u): ?>
          <li class="flex justify-between items-center">
            <span class="text-gray-700"><span class="inline-block px-2 py-0.5 rounded bg-green-100 text-green-700 mr-2">•</span><?= htmlspecialchars($u['username']) ?> (<?= htmlspecialchars($u['role']) ?>)</span>
            <span class="text-gray-500 text-xs"><?= date('g:i A', strtotime($u['login_time'])) ?></span>
          </li>
        <?php endforeach; else: ?>
          <li class="text-gray-500">No active users.</li>
        <?php endif; ?>
      </ul>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
    <div class="bg-white rounded-2xl shadow p-5 border border-blue-100">
      <h2 class="text-lg font-semibold text-gray-800 mb-4">Not Logged In Today (Employees)</h2>
      <div class="max-h-80 overflow-auto">
        <ul class="text-sm space-y-1">
          <?php if (count($no_login_employees) > 0): foreach ($no_login_employees as $e): ?>
            <li class="text-gray-700 flex items-center"><span class="w-1.5 h-1.5 rounded-full bg-gray-400 mr-2"></span><?= htmlspecialchars($e['last_name'] . ', ' . $e['first_name'] . ' (' . $e['id_number'] . ')') ?></li>
          <?php endforeach; else: ?>
            <li class="text-gray-500">All employees logged in today.</li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
    <div class="bg-white rounded-2xl shadow p-5 border border-blue-100">
      <h2 class="text-lg font-semibold text-gray-800 mb-4">Not Logged In Today (Students)</h2>
      <div class="max-h-80 overflow-auto">
        <ul class="text-sm space-y-1">
          <?php if (count($no_login_students) > 0): foreach ($no_login_students as $s): ?>
            <li class="text-gray-700 flex items-center"><span class="w-1.5 h-1.5 rounded-full bg-gray-400 mr-2"></span><?= htmlspecialchars($s['last_name'] . ', ' . $s['first_name'] . ' (' . $s['id_number'] . ')') ?></li>
          <?php endforeach; else: ?>
            <li class="text-gray-500">All students logged in today.</li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </div>

  <div class="bg-white rounded-2xl shadow p-5 border border-blue-100 mt-6">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-lg font-semibold text-gray-800">Create HR Account</h2>
      <span class="text-xs text-gray-500">Requires existing Employee ID</span>
    </div>
    <form method="post" class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <input type="hidden" name="create_hr" value="1">
      <div>
        <label class="text-sm font-medium text-gray-700 mb-1 block">Employee ID</label>
        <input type="text" name="employee_id" class="w-full border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 rounded-lg px-3 py-2" required>
      </div>
      <div>
        <label class="text-sm font-medium text-gray-700 mb-1 block">Username</label>
        <input type="text" name="username" pattern="^[A-Za-z0-9_]+$" title="Letters, numbers, underscores only" class="w-full border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 rounded-lg px-3 py-2" required>
      </div>
      <div>
        <label class="text-sm font-medium text-gray-700 mb-1 block">Password</label>
        <input type="password" name="password" minlength="6" class="w-full border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 rounded-lg px-3 py-2" required>
      </div>
      <div class="md:col-span-3">
        <button type="submit" class="inline-flex items-center gap-2 bg-[#0B2C62] hover:bg-blue-900 transition text-white px-5 py-2 rounded-lg">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Create Account
        </button>
      </div>
    </form>
  </div>
</main>
</body>
</html>
