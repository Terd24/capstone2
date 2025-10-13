<?php
session_start();
// Require Super Admin login
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    header('Location: ../StudentLogin/login.php');
    exit;
}

require_once '../StudentLogin/db_conn.php';

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

// Generate comprehensive system reports
$reports = [];

// 1. User Activity Report
$reports['user_activity'] = [];
if (table_exists($conn, 'login_activity')) {
    $result = $conn->query("SELECT DATE(login_time) as date, COUNT(*) as logins FROM login_activity WHERE login_time >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(login_time) ORDER BY date DESC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $reports['user_activity'][] = $row;
        }
    }
}

// 2. Database Statistics
$reports['database'] = [];
$db_stats = $conn->query("SELECT 
    table_name,
    table_rows,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
    FROM information_schema.tables 
    WHERE table_schema = DATABASE() 
    ORDER BY (data_length + index_length) DESC 
    LIMIT 10");
if ($db_stats) {
    while ($row = $db_stats->fetch_assoc()) {
        $reports['database'][] = $row;
    }
}

// 3. System Performance Metrics
$reports['performance'] = [];
$perf_queries = [
    'Uptime' => "SHOW STATUS LIKE 'Uptime'",
    'Connections' => "SHOW STATUS LIKE 'Threads_connected'",
    'Queries' => "SHOW STATUS LIKE 'Questions'",
    'Slow_queries' => "SHOW STATUS LIKE 'Slow_queries'"
];

foreach ($perf_queries as $metric => $query) {
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        $reports['performance'][$metric] = $row['Value'];
    }
}

// 4. Error Log Analysis (if available)
$reports['errors'] = [];
$error_log_path = ini_get('error_log');
if ($error_log_path && file_exists($error_log_path)) {
    $lines = file($error_log_path);
    $recent_errors = array_slice($lines, -10); // Last 10 errors
    foreach ($recent_errors as $error) {
        if (trim($error)) {
            $reports['errors'][] = trim($error);
        }
    }
}

// 5. Student/Employee Statistics
$reports['stats'] = [
    'total_students' => (int) ($conn->query("SELECT COUNT(*) FROM student_account")->fetch_row()[0] ?? 0),
    'total_employees' => (int) ($conn->query("SELECT COUNT(*) FROM employees")->fetch_row()[0] ?? 0),
    'active_today' => 0
];

if (table_exists($conn, 'login_activity')) {
    $reports['stats']['active_today'] = (int) (q_scalar($conn, "SELECT COUNT(DISTINCT id_number) FROM login_activity WHERE DATE(login_time) = ?", 's', $today) ?? 0);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>System Reports - Super Admin Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
<header class="bg-gradient-to-r from-[#0B2C62] to-[#153e86] text-white shadow">
  <div class="container mx-auto px-6 py-4 flex items-center justify-between">
    <div class="flex items-center gap-3">
      <img src="../images/LogoCCI.png" class="h-11 w-11 rounded-full bg-white p-1 shadow-sm" alt="Logo">
      <div class="leading-tight">
        <div class="text-lg font-bold">System Reports</div>
        <div class="text-[11px] text-blue-200">Analytics & Logs Dashboard</div>
      </div>
    </div>
    <div class="flex items-center gap-3">
      <a href="SuperAdminDashboard.php" class="inline-flex items-center gap-2 bg-white/10 hover:bg-white/20 px-3 py-2 rounded-lg text-sm transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to Dashboard
      </a>
      <a href="../StudentLogin/logout.php" class="inline-flex items-center gap-2 bg-white/10 hover:bg-white/20 px-3 py-2 rounded-lg text-sm transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        Logout
      </a>
    </div>
  </div>
</header>

<main class="container mx-auto px-6 py-6">
  <!-- System Overview Cards -->
  <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl shadow p-5 border border-blue-100">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M12 7a3 3 0 110-6 3 3 0 010 6z"/></svg>
        </div>
        <div>
          <div class="text-gray-500 text-sm">Total Users</div>
          <div class="text-2xl font-bold text-gray-800"><?= number_format($reports['stats']['total_students'] + $reports['stats']['total_employees']) ?></div>
        </div>
      </div>
    </div>
    
    <div class="bg-white rounded-2xl shadow p-5 border border-green-100">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg bg-green-100 text-green-700 flex items-center justify-center">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
          <div class="text-gray-500 text-sm">Active Today</div>
          <div class="text-2xl font-bold text-gray-800"><?= number_format($reports['stats']['active_today']) ?></div>
        </div>
      </div>
    </div>
    
    <div class="bg-white rounded-2xl shadow p-5 border border-purple-100">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/></svg>
        </div>
        <div>
          <div class="text-gray-500 text-sm">DB Tables</div>
          <div class="text-2xl font-bold text-gray-800"><?= count($reports['database']) ?></div>
        </div>
      </div>
    </div>
    
    <div class="bg-white rounded-2xl shadow p-5 border border-amber-100">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
          <div class="text-gray-500 text-sm">Uptime</div>
          <div class="text-2xl font-bold text-gray-800"><?= isset($reports['performance']['Uptime']) ? gmdate('H:i:s', $reports['performance']['Uptime']) : 'N/A' ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- User Activity Chart -->
    <div class="bg-white rounded-2xl shadow p-6 border border-blue-100">
      <h3 class="text-lg font-semibold text-gray-800 mb-4">Login Activity (Last 7 Days)</h3>
      <canvas id="activityChart" width="400" height="200"></canvas>
    </div>

    <!-- System Performance -->
    <div class="bg-white rounded-2xl shadow p-6 border border-purple-100">
      <h3 class="text-lg font-semibold text-gray-800 mb-4">System Performance</h3>
      <div class="space-y-4">
        <?php foreach ($reports['performance'] as $metric => $value): ?>
          <div class="flex justify-between items-center">
            <span class="text-gray-700 capitalize"><?= str_replace('_', ' ', $metric) ?></span>
            <span class="font-medium text-gray-900"><?= number_format($value) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Database Statistics -->
  <div class="bg-white rounded-2xl shadow p-6 border border-blue-100 mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Database Statistics</h3>
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="bg-[#0B2C62] text-white">
          <tr>
            <th class="px-4 py-2 text-left">Table Name</th>
            <th class="px-4 py-2 text-left">Rows</th>
            <th class="px-4 py-2 text-left">Size (MB)</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <?php foreach ($reports['database'] as $table): ?>
            <tr class="hover:bg-blue-50/50">
              <td class="px-4 py-2 font-medium text-gray-800"><?= htmlspecialchars($table['table_name']) ?></td>
              <td class="px-4 py-2 text-gray-700"><?= number_format($table['table_rows']) ?></td>
              <td class="px-4 py-2 text-gray-700"><?= $table['size_mb'] ?> MB</td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Error Logs -->
  <?php if (!empty($reports['errors'])): ?>
  <div class="bg-white rounded-2xl shadow p-6 border border-red-100">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent Error Logs</h3>
    <div class="space-y-2 max-h-64 overflow-y-auto">
      <?php foreach ($reports['errors'] as $error): ?>
        <div class="bg-red-50 border border-red-200 rounded p-3 text-sm text-red-800">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</main>

<script>
// Activity Chart
const ctx = document.getElementById('activityChart').getContext('2d');
const activityData = <?= json_encode($reports['user_activity']) ?>;

new Chart(ctx, {
    type: 'line',
    data: {
        labels: activityData.map(item => item.date),
        datasets: [{
            label: 'Daily Logins',
            data: activityData.map(item => item.logins),
            borderColor: '#0B2C62',
            backgroundColor: 'rgba(11, 44, 98, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>
</body>
</html>
