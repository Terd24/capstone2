<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Get current page from URL parameter or default to dashboard
$current_page = $_GET['page'] ?? 'dashboard';

// Also check for hash-based navigation (JavaScript will handle this)
$hash_page = '';
if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '#') !== false) {
    $hash_part = substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], '#') + 1);
    if ($hash_part === 'deleted-items') {
        $hash_page = 'deleted-items';
    }
}

// Require Super Admin login
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    header('Location: ../StudentLogin/login.php');
    exit;
}

// Prevent caching (so back button after logout doesn't show dashboard)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

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

// Enhanced Metrics for System-Wide Overview

// 1. ENROLLMENT STATISTICS
$total_students = (int) ($conn->query("SELECT COUNT(*) FROM student_account")->fetch_row()[0] ?? 0);
$total_employees = (int) ($conn->query("SELECT COUNT(*) FROM employees")->fetch_row()[0] ?? 0);

// Enrollment by Grade Level
$enrollment_by_grade = [];
$grade_result = $conn->query("SELECT grade_level, COUNT(*) as count FROM student_account GROUP BY grade_level ORDER BY grade_level");
if ($grade_result) {
    while ($row = $grade_result->fetch_assoc()) {
        $enrollment_by_grade[] = $row;
    }
}

// Enrollment by Program/Track
$enrollment_by_program = [];
$program_result = $conn->query("SELECT academic_track, COUNT(*) as count FROM student_account GROUP BY academic_track ORDER BY count DESC");
if ($program_result) {
    while ($row = $program_result->fetch_assoc()) {
        $enrollment_by_program[] = $row;
    }
}

// 2. TUITION PAYMENT REPORTS
$total_payments_today = 0;
$total_revenue_today = 0;
$pending_balances = 0;
$total_revenue_month = 0;

// Check if payment tables exist and get correct column names
if (table_exists($conn, 'student_payments')) {
    // Check what columns exist in student_payments table
    $payment_date_col = null;
    $amount_col = null;
    
    $columns_result = $conn->query("SHOW COLUMNS FROM student_payments");
    $available_columns = [];
    if ($columns_result) {
        while ($col = $columns_result->fetch_assoc()) {
            $available_columns[] = $col['Field'];
        }
        
        // Determine correct date column
        if (in_array('payment_date', $available_columns)) {
            $payment_date_col = 'payment_date';
        } elseif (in_array('created_at', $available_columns)) {
            $payment_date_col = 'created_at';
        } elseif (in_array('date_paid', $available_columns)) {
            $payment_date_col = 'date_paid';
        } elseif (in_array('timestamp', $available_columns)) {
            $payment_date_col = 'timestamp';
        } elseif (in_array('date', $available_columns)) {
            $payment_date_col = 'date';
        }
        
        // Determine correct amount column
        if (in_array('amount_paid', $available_columns)) {
            $amount_col = 'amount_paid';
        } elseif (in_array('amount', $available_columns)) {
            $amount_col = 'amount';
        } elseif (in_array('payment_amount', $available_columns)) {
            $amount_col = 'payment_amount';
        } elseif (in_array('total_amount', $available_columns)) {
            $amount_col = 'total_amount';
        }
    }
    
    // Only run queries if we found the required columns
    if ($payment_date_col && $amount_col) {
        try {
            $total_payments_today = (int) (q_scalar($conn, "SELECT COUNT(*) FROM student_payments WHERE DATE($payment_date_col) = ?", 's', $today) ?? 0);
            $total_revenue_today = (float) (q_scalar($conn, "SELECT SUM($amount_col) FROM student_payments WHERE DATE($payment_date_col) = ?", 's', $today) ?? 0);
            $total_revenue_month = (float) (q_scalar($conn, "SELECT SUM($amount_col) FROM student_payments WHERE MONTH($payment_date_col) = MONTH(CURDATE()) AND YEAR($payment_date_col) = YEAR(CURDATE())", '') ?? 0);
        } catch (Exception $e) {
            // Fallback to 0 if queries fail
            $total_payments_today = 0;
            $total_revenue_today = 0;
            $total_revenue_month = 0;
        }
    } else {
        // Table exists but doesn't have expected columns - try alternative approach
        try {
            // Just count all records as fallback
            $total_payments_today = (int) (q_scalar($conn, "SELECT COUNT(*) FROM student_payments", '') ?? 0);
            
            // Try to get any numeric column for revenue
            if (!empty($available_columns)) {
                foreach (['amount_paid', 'amount', 'payment_amount', 'total_amount'] as $col) {
                    if (in_array($col, $available_columns)) {
                        $total_revenue_today = (float) (q_scalar($conn, "SELECT SUM($col) FROM student_payments", '') ?? 0);
                        $total_revenue_month = $total_revenue_today; // Use same value as fallback
                        break;
                    }
                }
            }
        } catch (Exception $e) {
            $total_payments_today = 0;
        }
    }
}

if (table_exists($conn, 'student_fee_items')) {
    try {
        // Check columns in student_fee_items table
        $fee_columns_result = $conn->query("SHOW COLUMNS FROM student_fee_items");
        $fee_columns = [];
        if ($fee_columns_result) {
            while ($col = $fee_columns_result->fetch_assoc()) {
                $fee_columns[] = $col['Field'];
            }
        }
        
        // Build query based on available columns
        if (in_array('amount_due', $fee_columns) && in_array('paid', $fee_columns)) {
            $pending_balances = (float) (q_scalar($conn, "SELECT SUM(amount_due - paid) FROM student_fee_items WHERE amount_due > paid", '') ?? 0);
        } elseif (in_array('amount_due', $fee_columns) && in_array('amount_paid', $fee_columns)) {
            $pending_balances = (float) (q_scalar($conn, "SELECT SUM(amount_due - amount_paid) FROM student_fee_items WHERE amount_due > amount_paid", '') ?? 0);
        } elseif (in_array('fee_amount', $fee_columns)) {
            $pending_balances = (float) (q_scalar($conn, "SELECT SUM(fee_amount) FROM student_fee_items", '') ?? 0);
        } elseif (in_array('amount', $fee_columns)) {
            $pending_balances = (float) (q_scalar($conn, "SELECT SUM(amount) FROM student_fee_items", '') ?? 0);
        } else {
            $pending_balances = 0; // Fallback if columns don't match expected structure
        }
    } catch (Exception $e) {
        $pending_balances = 0; // Fallback on any error
    }
}

// 3. ATTENDANCE LOGS
$student_attendance_today = 0;
$student_present_today = 0;
$student_absent_today = $total_students;

if (table_exists($conn, 'attendance_record')) {
    try {
        $student_attendance_today = (int) (q_scalar($conn, "SELECT COUNT(*) FROM attendance_record WHERE date = ? AND (time_in IS NOT NULL OR time_out IS NOT NULL)", 's', $today) ?? 0);
        $student_present_today = (int) (q_scalar($conn, "SELECT COUNT(*) FROM attendance_record WHERE date = ? AND time_in IS NOT NULL", 's', $today) ?? 0);
        $student_absent_today = $total_students - $student_present_today;
    } catch (Exception $e) {
        // Fallback values already set above
    }
}

// Employee Attendance
$empAttTable = table_exists($conn, 'employee_attendance') ? 'employee_attendance' : (table_exists($conn, 'teacher_attendance') ? 'teacher_attendance' : null);
$employee_attendance_today = 0;
$employee_present_today = 0;
if ($empAttTable) {
    $employee_attendance_today = (int) (q_scalar($conn, "SELECT COUNT(*) FROM `$empAttTable` WHERE date = ? AND (time_in IS NOT NULL OR time_out IS NOT NULL)", 's', $today) ?? 0);
    $employee_present_today = (int) (q_scalar($conn, "SELECT COUNT(*) FROM `$empAttTable` WHERE date = ? AND time_in IS NOT NULL", 's', $today) ?? 0);
}
$employee_absent_today = $total_employees - $employee_present_today;

// 4. SYSTEM HEALTH MONITORING
$db_size = 0;
$table_count = 0;
$total_records = 0;

// Database size and health
$db_info = $conn->query("SELECT 
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS db_size_mb,
    COUNT(*) as table_count
    FROM information_schema.tables 
    WHERE table_schema = DATABASE()");
if ($db_info && $row = $db_info->fetch_assoc()) {
    $db_size = $row['db_size_mb'];
    $table_count = $row['table_count'];
}

// Total records across main tables
$main_tables = ['student_account', 'attendance_record', 'grades_record'];
foreach ($main_tables as $table) {
    if (table_exists($conn, $table)) {
        $count = (int) ($conn->query("SELECT COUNT(*) FROM `$table`")->fetch_row()[0] ?? 0);
        $total_records += $count;
    }
}

// Server performance indicators
$server_uptime = 0;
$connections = 0;
$uptime_result = $conn->query("SHOW STATUS LIKE 'Uptime'");
if ($uptime_result && $row = $uptime_result->fetch_assoc()) {
    $server_uptime = (int) $row['Value'];
}

$connections_result = $conn->query("SHOW STATUS LIKE 'Threads_connected'");
if ($connections_result && $row = $connections_result->fetch_assoc()) {
    $connections = (int) $row['Value'];
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

// DELETED RECORDS DATA
$deleted_students = [];

// Get deleted students
try {
    $stmt = $conn->prepare("SELECT id_number, first_name, last_name, middle_name, grade_level, academic_track, deleted_at, deleted_by, deleted_reason FROM student_account WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC");
    if ($stmt) {
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $deleted_students[] = $row;
        }
        $stmt->close();
    }
} catch (Exception $e) {
    // Table might not have soft delete columns yet
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
<body class="min-h-screen bg-gray-50 flex">
<!-- Sidebar -->
<div id="sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-gradient-to-b from-[#0B2C62] to-[#153e86] text-white transform -translate-x-full transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0">
  <div class="flex items-center justify-between h-16 px-6 border-b border-white/10">
    <div class="flex items-center gap-3">
      <img src="../images/LogoCCI.png" class="h-8 w-8 rounded-full bg-white p-1" alt="Logo">
      <div class="leading-tight">
        <div class="font-bold text-sm">Cornerstone College</div>
        <div class="text-xs text-blue-200">Super Admin</div>
      </div>
    </div>
    <button onclick="toggleSidebar()" class="lg:hidden">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
      </svg>
    </button>
  </div>
  
  <nav class="mt-8 px-4">
    <div class="space-y-2">
      <!-- Dashboard -->
      <a href="#dashboard" onclick="showSection('dashboard', event)" class="nav-item <?= $current_page === 'dashboard' ? 'active' : '' ?> flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
        </svg>
        <span>Dashboard</span>
      </a>
      
      <!-- Management Tools -->
      <div class="pt-4">
        <div class="text-xs font-semibold text-blue-200 uppercase tracking-wider px-4 mb-2">Management</div>
        <a href="#hr-accounts" onclick="showSection('hr-accounts', event)" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
          </svg>
          <span>HR Accounts</span>
        </a>
        <a href="#system-maintenance" onclick="showSection('system-maintenance', event)" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
          <span>System Maintenance</span>
        </a>
        <a href="#deleted-items" onclick="showSection('deleted-items', event)" class="nav-item flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-white/10 transition">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1-1H8a1 1 0 00-1 1v3M4 7h16"/>
          </svg>
          <span>Deleted Items</span>
        </a>
        
        <!-- User Info & Logout -->
        <div class="mt-6 pt-4 border-t border-white/10">
          <div class="flex items-center gap-3 mb-3 px-4">
            <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center">
              <span class="text-sm font-semibold"><?= substr($_SESSION['superadmin_name'] ?? 'IT', 0, 2) ?></span>
            </div>
            <div class="flex-1 min-w-0">
              <div class="text-sm font-medium truncate"><?= htmlspecialchars($_SESSION['superadmin_name'] ?? 'IT Personnel') ?></div>
              <div class="text-xs text-blue-200">Super Administrator</div>
            </div>
          </div>
          <a href="../StudentLogin/logout.php" class="flex items-center gap-2 w-full px-4 py-2 text-sm hover:bg-white/10 rounded-lg transition mx-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            Logout
          </a>
        </div>
      </div>
      
    </div>
  </nav>
</div>

<!-- Mobile menu overlay -->
<div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden hidden" onclick="toggleSidebar()"></div>

<!-- Main Content -->
<div class="flex-1 lg:ml-0">
  <!-- Top Header -->
  <header class="bg-white shadow-sm border-b border-gray-200">
    <div class="flex items-center justify-between px-6 py-4">
      <div class="flex items-center gap-4">
        <button onclick="toggleSidebar()" class="lg:hidden p-2 rounded-md hover:bg-gray-100">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
          </svg>
        </button>
        <h1 id="page-title" class="text-2xl font-bold text-gray-900">
          <?php 
          $page_titles = [
            'dashboard' => 'Dashboard',
            'deleted-items' => 'Deleted Items Management'
          ];
          echo $page_titles[$current_page] ?? 'Dashboard';
          ?>
        </h1>
      </div>
      <div class="flex items-center gap-4">
        <button onclick="location.reload()" class="p-2 rounded-md hover:bg-gray-100 text-gray-600">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
          </svg>
        </button>
      </div>
    </div>
  </header>

  <main class="p-6">
    <!-- Dashboard Section -->
    <div id="dashboard-section" class="section <?= $current_page === 'dashboard' ? 'active' : 'hidden' ?>">
      <!-- System Status Overview -->
      <div class="mb-6 bg-gradient-to-r from-[#0B2C62] to-[#153e86] text-white rounded-xl p-6">
        <div class="flex items-center gap-3 mb-4">
          <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <h2 class="text-xl font-bold">System Status</h2>
        </div>
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


  <!-- Enhanced Key Metrics Dashboard -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <!-- Enrollment Statistics -->
    <div class="bg-white rounded-2xl shadow p-5 border border-blue-100">
      <div class="flex items-center gap-3 mb-3">
        <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M12 7a3 3 0 110-6 3 3 0 010 6z"/></svg>
        </div>
        <div>
          <div class="text-gray-500 text-sm">Total Enrollment</div>
          <div class="text-2xl font-bold text-gray-800"><?= number_format($total_students) ?></div>
        </div>
      </div>
    </div>

    <!-- Tuition Payment Reports -->
    <div class="bg-white rounded-2xl shadow p-5 border border-green-100">
      <div class="flex items-center gap-3 mb-3">
        <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/></svg>
        </div>
        <div>
          <div class="text-gray-500 text-sm">Payments Today</div>
          <div class="text-2xl font-bold text-gray-800"><?= number_format($total_payments_today) ?></div>
        </div>
      </div>
      <div class="text-xs text-gray-600">
        <div class="flex justify-between"><span>Revenue:</span><span>₱<?= number_format($total_revenue_today, 2) ?></span></div>
        <div class="flex justify-between"><span>Pending:</span><span class="text-red-600">₱<?= number_format($pending_balances, 2) ?></span></div>
      </div>
    </div>

    <!-- Attendance Logs -->
    <div class="bg-white rounded-2xl shadow p-5 border border-amber-100">
      <div class="flex items-center gap-3 mb-3">
        <div class="w-10 h-10 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
          <div class="text-gray-500 text-sm">Present Today</div>
          <div class="text-2xl font-bold text-gray-800"><?= number_format($student_present_today) ?></div>
        </div>
      </div>
      <div class="text-xs text-gray-600">
        <div class="flex justify-between"><span>Students:</span><span><?= $student_present_today ?>/<?= $total_students ?></span></div>
        <div class="flex justify-between"><span>Employees:</span><span><?= $employee_present_today ?>/<?= $total_employees ?></span></div>
      </div>
    </div>

    <!-- System Health -->
    <div class="bg-white rounded-2xl shadow p-5 border border-purple-100">
      <div class="flex items-center gap-3 mb-3">
        <div class="w-10 h-10 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
          <div class="text-gray-500 text-sm">System Health</div>
          <div class="text-2xl font-bold text-green-600">🟢 Optimal</div>
        </div>
      </div>
      <div class="text-xs text-gray-600">
        <div class="flex justify-between"><span>DB Size:</span><span><?= $db_size ?> MB</span></div>
        <div class="flex justify-between"><span>Records:</span><span><?= number_format($total_records) ?></span></div>
      </div>
    </div>
  </div>

  <!-- Detailed Analytics Section -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Enrollment Breakdown -->
    <div class="bg-white rounded-2xl shadow p-5 border border-blue-100">
      <h3 class="text-lg font-semibold text-gray-800 mb-4">Enrollment by Grade Level</h3>
      <div class="max-h-64 overflow-y-auto space-y-2">
        <?php foreach ($enrollment_by_grade as $grade): ?>
          <div class="flex justify-between items-center">
            <span class="text-gray-700"><?= htmlspecialchars($grade['grade_level'] ?: 'Not Set') ?></span>
            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-sm font-medium"><?= number_format($grade['count']) ?></span>
          </div>
        <?php endforeach; ?>
        <?php if (empty($enrollment_by_grade)): ?>
          <div class="text-gray-500 text-center py-4">No enrollment data available</div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Payment Analytics -->
    <div class="bg-white rounded-2xl shadow p-5 border border-green-100">
      <h3 class="text-lg font-semibold text-gray-800 mb-4">Financial Overview</h3>
      <div class="space-y-3">
        <div class="flex justify-between items-center">
          <span class="text-gray-700">Today's Revenue</span>
          <span class="text-green-600 font-bold">₱<?= number_format($total_revenue_today, 2) ?></span>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-gray-700">Monthly Revenue</span>
          <span class="text-blue-600 font-bold">₱<?= number_format($total_revenue_month, 2) ?></span>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-gray-700">Pending Balances</span>
          <span class="text-red-600 font-bold">₱<?= number_format($pending_balances, 2) ?></span>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-gray-700">Payments Today</span>
          <span class="text-gray-800 font-medium"><?= number_format($total_payments_today) ?></span>
        </div>
      </div>
    </div>

    <!-- System Performance -->
    <div class="bg-white rounded-2xl shadow p-5 border border-purple-100">
      <h3 class="text-lg font-semibold text-gray-800 mb-4">System Performance</h3>
      <div class="space-y-3">
        <div class="flex justify-between items-center">
          <span class="text-gray-700">Database Size</span>
          <span class="text-gray-800 font-medium"><?= $db_size ?> MB</span>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-gray-700">Total Tables</span>
          <span class="text-gray-800 font-medium"><?= number_format($table_count) ?></span>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-gray-700">Total Records</span>
          <span class="text-gray-800 font-medium"><?= number_format($total_records) ?></span>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-gray-700">Server Uptime</span>
          <span class="text-green-600 font-medium"><?= gmdate('H:i:s', $server_uptime) ?></span>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-gray-700">Active Connections</span>
          <span class="text-blue-600 font-medium"><?= number_format($connections) ?></span>
        </div>
      </div>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white rounded-2xl shadow p-5 border border-blue-100 lg:col-span-2">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-800">Today's Logins</h2>
        <button onclick="location.reload()" class="text-sm bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded-md">Refresh</button>
      </div>
      <div class="overflow-x-auto max-h-80 overflow-y-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-[#0B2C62] text-white sticky top-0">
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
      <div class="max-h-80 overflow-y-auto">
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
    
    <!-- Other sections will be added here -->
    <div id="enrollment-section" class="section hidden">
      <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Enrollment Analytics</h2>
        <div class="max-h-80 overflow-y-auto space-y-4">
          <?php foreach ($enrollment_by_grade as $grade): ?>
            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
              <span class="text-gray-700 font-medium"><?= htmlspecialchars($grade['grade_level'] ?: 'Not Set') ?></span>
              <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium"><?= number_format($grade['count']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    
    <div id="financial-section" class="section hidden">
      <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Financial Overview</h2>
        <div class="max-h-80 overflow-y-auto">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-4">
            <div class="flex justify-between items-center p-4 bg-green-50 rounded-lg">
              <span class="text-gray-700">Today's Revenue</span>
              <span class="text-green-600 font-bold text-lg">₱<?= number_format($total_revenue_today, 2) ?></span>
            </div>
            <div class="flex justify-between items-center p-4 bg-blue-50 rounded-lg">
              <span class="text-gray-700">Monthly Revenue</span>
              <span class="text-blue-600 font-bold text-lg">₱<?= number_format($total_revenue_month, 2) ?></span>
            </div>
          </div>
          <div class="space-y-4">
            <div class="flex justify-between items-center p-4 bg-red-50 rounded-lg">
              <span class="text-gray-700">Pending Balances</span>
              <span class="text-red-600 font-bold text-lg">₱<?= number_format($pending_balances, 2) ?></span>
            </div>
            <div class="flex justify-between items-center p-4 bg-gray-50 rounded-lg">
              <span class="text-gray-700">Payments Today</span>
              <span class="text-gray-800 font-bold text-lg"><?= number_format($total_payments_today) ?></span>
            </div>
          </div>
          </div>
        </div>
      </div>
    </div>
    
    <div id="attendance-section" class="section hidden">
      <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Attendance Overview</h2>
        <div class="max-h-80 overflow-y-auto">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-4">
            <h3 class="font-semibold text-gray-800">Students</h3>
            <div class="flex justify-between items-center p-4 bg-green-50 rounded-lg">
              <span class="text-gray-700">Present Today</span>
              <span class="text-green-600 font-bold"><?= $student_present_today ?>/<?= $total_students ?></span>
            </div>
          </div>
          <div class="space-y-4">
            <h3 class="font-semibold text-gray-800">Employees</h3>
            <div class="flex justify-between items-center p-4 bg-green-50 rounded-lg">
              <span class="text-gray-700">Present Today</span>
              <span class="text-green-600 font-bold"><?= $employee_present_today ?>/<?= $total_employees ?></span>
            </div>
          </div>
          </div>
        </div>
      </div>
    </div>
    
    <div id="system-section" class="section hidden">
      <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">System Health</h2>
        <div class="max-h-80 overflow-y-auto">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-4">
            <div class="flex justify-between items-center p-4 bg-gray-50 rounded-lg">
              <span class="text-gray-700">Database Size</span>
              <span class="text-gray-800 font-medium"><?= $db_size ?> MB</span>
            </div>
            <div class="flex justify-between items-center p-4 bg-gray-50 rounded-lg">
              <span class="text-gray-700">Total Tables</span>
              <span class="text-gray-800 font-medium"><?= number_format($table_count) ?></span>
            </div>
          </div>
          <div class="space-y-4">
            <div class="flex justify-between items-center p-4 bg-gray-50 rounded-lg">
              <span class="text-gray-700">Total Records</span>
              <span class="text-gray-800 font-medium"><?= number_format($total_records) ?></span>
            </div>
            <div class="flex justify-between items-center p-4 bg-green-50 rounded-lg">
              <span class="text-gray-700">Server Uptime</span>
              <span class="text-green-600 font-medium"><?= gmdate('H:i:s', $server_uptime) ?></span>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div id="activity-section" class="section hidden">
      <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">Activity Logs</h2>
        <div class="overflow-x-auto">
          <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left font-medium text-gray-900">User Type</th>
                <th class="px-4 py-3 text-left font-medium text-gray-900">Username</th>
                <th class="px-4 py-3 text-left font-medium text-gray-900">Role</th>
                <th class="px-4 py-3 text-left font-medium text-gray-900">Login Time</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <?php if (count($today_logins) > 0): foreach ($today_logins as $r): ?>
                <tr class="hover:bg-gray-50">
                  <td class="px-4 py-3 text-gray-800"><?= htmlspecialchars($r['user_type']) ?></td>
                  <td class="px-4 py-3 text-gray-700"><?= htmlspecialchars($r['username']) ?></td>
                  <td class="px-4 py-3">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                      <?= htmlspecialchars($r['role']) ?>
                    </span>
                  </td>
                  <td class="px-4 py-3 text-gray-700"><?= date('M j, Y g:i A', strtotime($r['login_time'])) ?></td>
                </tr>
              <?php endforeach; else: ?>
                <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">No logins yet today.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Deleted Items Section -->
    <div id="deleted-items-section" class="section <?= $current_page === 'deleted-items' ? 'active' : 'hidden' ?>" style="<?= $current_page === 'deleted-items' ? 'display: block !important; background: lightgreen; padding: 20px; margin: 10px;' : '' ?>">
      
      <!-- SIMPLE TEST -->
      <?php if ($current_page === 'deleted-items'): ?>
        <div style="background: red; color: white; padding: 20px; margin: 10px; font-size: 24px; font-weight: bold;">
          🔴 DELETED ITEMS SECTION IS WORKING! 🔴
        </div>
        <div style="background: blue; color: white; padding: 15px; margin: 10px;">
          <p><strong>Current page:</strong> <?= $current_page ?></p>
          <p><strong>Deleted students:</strong> <?= count($deleted_students) ?></p>
        </div>
      <?php endif; ?>
      
      <!-- Debug Info -->
      <?php if ($current_page === 'deleted-items'): ?>
        <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-4">
          <p class="font-bold">Debug Info:</p>
          <p>Current page: <?= $current_page ?></p>
          <p>Deleted students count: <?= count($deleted_students) ?></p>
          <p>Section should be: <?= $current_page === 'deleted-items' ? 'VISIBLE (active)' : 'HIDDEN' ?></p>
        </div>
      <?php endif; ?>
      
      <!-- Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-gradient-to-r from-red-500 to-red-600 text-white rounded-xl p-6 shadow-lg">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
              <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
              </svg>
            </div>
            <div>
              <div class="text-red-100 text-sm">Deleted Students</div>
              <div class="text-2xl font-bold"><?= count($deleted_students) ?></div>
            </div>
          </div>
        </div>
        
      </div>

      <!-- Deleted Students Table -->
      <?php if (count($deleted_students) > 0): ?>
      <div class="bg-white rounded-xl shadow-lg mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/>
            </svg>
            Deleted Students (<?= count($deleted_students) ?>)
          </h3>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student Info</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Program</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deleted Info</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <?php foreach ($deleted_students as $student): ?>
              <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                      <span class="text-red-600 font-medium text-sm">
                        <?= strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)) ?>
                      </span>
                    </div>
                    <div class="ml-4">
                      <div class="text-sm font-medium text-gray-900">
                        <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                      </div>
                      <div class="text-sm text-gray-500">ID: <?= htmlspecialchars($student['id_number']) ?></div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-900"><?= htmlspecialchars($student['academic_track'] ?: 'N/A') ?></div>
                  <div class="text-sm text-gray-500"><?= htmlspecialchars($student['grade_level'] ?: 'N/A') ?></div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-900">
                    <div class="font-medium">Deleted: <?= date('M j, Y g:i A', strtotime($student['deleted_at'])) ?></div>
                    <div class="text-gray-500">By: <?= htmlspecialchars($student['deleted_by'] ?: 'Unknown') ?></div>
                    <?php if ($student['deleted_reason']): ?>
                      <div class="text-gray-500 text-xs mt-1">Reason: <?= htmlspecialchars($student['deleted_reason']) ?></div>
                    <?php endif; ?>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  <div class="flex gap-2">
                    <button onclick="restoreStudent('<?= htmlspecialchars($student['id_number']) ?>')" 
                            class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-xs transition-colors">
                      Restore
                    </button>
                    <button onclick="requestPermanentDelete('<?= htmlspecialchars($student['id_number']) ?>', 'student')" 
                            class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-xs transition-colors">
                      Delete Permanently
                    </button>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php else: ?>
      <div class="bg-white rounded-xl shadow-lg mb-6 p-8 text-center">
        <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/>
        </svg>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No Deleted Students</h3>
        <p class="text-gray-500">No student records have been deleted.</p>
      </div>
      <?php endif; ?>

    </div>

    <!-- HR Accounts Section -->
    <div id="hr-accounts-section" class="section hidden">
      <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center gap-3 mb-6">
          <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
          </svg>
          <h2 class="text-2xl font-bold text-gray-900">HR Accounts Management</h2>
        </div>
        
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
          <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-amber-800 font-medium">HR Account Management</p>
          </div>
          <p class="text-amber-700 text-sm mt-2">Manage HR department accounts, create new HR users, and configure HR permissions.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl p-6 shadow-lg">
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                </svg>
              </div>
              <div>
                <div class="text-blue-100 text-sm">Total HR Accounts</div>
                <div class="text-2xl font-bold">5</div>
              </div>
            </div>
          </div>

          <div class="bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl p-6 shadow-lg">
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
              <div>
                <div class="text-green-100 text-sm">Active Accounts</div>
                <div class="text-2xl font-bold">4</div>
              </div>
            </div>
          </div>

          <div class="bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-xl p-6 shadow-lg">
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
              </div>
              <div>
                <div class="text-purple-100 text-sm">Permissions</div>
                <div class="text-2xl font-bold">Admin</div>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-8">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Quick Actions</h3>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <button class="bg-amber-600 hover:bg-amber-700 text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
              </svg>
              Create New HR Account
            </button>
            <button class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
              </svg>
              Manage Permissions
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- System Maintenance Section -->
    <div id="system-maintenance-section" class="section hidden">
      <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center gap-3 mb-6">
          <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
          <h2 class="text-2xl font-bold text-gray-900">System Maintenance</h2>
        </div>
        
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
          <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
            <p class="text-red-800 font-medium">System Maintenance Mode</p>
          </div>
          <p class="text-red-700 text-sm mt-2">Configure system maintenance settings, enable/disable maintenance mode, and manage system configurations.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div class="bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl p-6 shadow-lg">
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
              <div>
                <div class="text-green-100 text-sm">System Status</div>
                <div class="text-2xl font-bold">Online</div>
              </div>
            </div>
          </div>

          <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl p-6 shadow-lg">
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                </svg>
              </div>
              <div>
                <div class="text-blue-100 text-sm">Database</div>
                <div class="text-2xl font-bold">Healthy</div>
              </div>
            </div>
          </div>

          <div class="bg-gradient-to-r from-orange-500 to-orange-600 text-white rounded-xl p-6 shadow-lg">
            <div class="flex items-center gap-4">
              <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
              <div>
                <div class="text-orange-100 text-sm">Last Backup</div>
                <div class="text-2xl font-bold">2h ago</div>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-8">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Maintenance Actions</h3>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <button class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
              </svg>
              Enable Maintenance Mode
            </button>
            <button class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
              </svg>
              Create System Backup
            </button>
            <button class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
              </svg>
              Clear System Cache
            </button>
            <button class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 00-2-2z"/>
              </svg>
              View System Logs
            </button>
          </div>
        </div>
      </div>
    </div>

  </main>
</div>

<!-- Mobile menu overlay -->
<div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden hidden" onclick="toggleSidebar()"></div>

<style>
.nav-item.active {
  background-color: rgba(255, 255, 255, 0.1);
}
.section.hidden {
  display: none;
}
.section.active {
  display: block;
}
</style>

<script>
function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebar-overlay');
  
  sidebar.classList.toggle('-translate-x-full');
  overlay.classList.toggle('hidden');
}

function showSection(sectionName, clickEvent = null) {
  console.log('Showing section:', sectionName);
  
  // Hide all sections
  document.querySelectorAll('.section').forEach(section => {
    section.classList.remove('active');
    section.classList.add('hidden');
    section.style.display = 'none';
  });
  
  // Show selected section
  const targetSection = document.getElementById(sectionName + '-section');
  console.log('Target section:', targetSection);
  
  if (targetSection) {
    targetSection.classList.remove('hidden');
    targetSection.classList.add('active');
    targetSection.style.display = 'block';
    console.log('Section should now be visible');
  } else {
    console.error('Section not found:', sectionName + '-section');
  }
  
  // Update nav items
  document.querySelectorAll('.nav-item').forEach(item => {
    item.classList.remove('active');
  });
  
  // Add active class to clicked nav item (if event exists)
  if (clickEvent && clickEvent.target) {
    const navItem = clickEvent.target.closest('.nav-item');
    if (navItem) {
      navItem.classList.add('active');
    }
  } else {
    // If no event (called from initialization), find the correct nav item
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
      const href = item.getAttribute('href');
      if ((sectionName === 'dashboard' && href === '#dashboard') || 
          (sectionName === 'deleted-items' && href === '#deleted-items') ||
          (sectionName === 'hr-accounts' && href === '#hr-accounts') ||
          (sectionName === 'system-maintenance' && href === '#system-maintenance')) {
        item.classList.add('active');
      }
    });
  }
  
  // Update page title
  const titles = {
    'dashboard': 'Dashboard',
    'deleted-items': 'Deleted Items Management',
    'hr-accounts': 'HR Accounts Management',
    'system-maintenance': 'System Maintenance',
    'enrollment': 'Enrollment Analytics', 
    'financial': 'Financial Overview',
    'attendance': 'Attendance Overview',
    'system': 'System Health',
    'activity': 'Activity Logs'
  };
  
  document.getElementById('page-title').textContent = titles[sectionName] || 'Dashboard';
  
  // Close sidebar on mobile after selection
  if (window.innerWidth < 1024) {
    toggleSidebar();
  }
}

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(event) {
  const sidebar = document.getElementById('sidebar');
  const sidebarToggle = event.target.closest('[onclick="toggleSidebar()"]');
  
  if (!sidebar.contains(event.target) && !sidebarToggle && window.innerWidth < 1024) {
    if (!sidebar.classList.contains('-translate-x-full')) {
      toggleSidebar();
    }
  }
});

// Restore deleted student
function restoreStudent(studentId) {
  if (confirm('Are you sure you want to restore this student record?')) {
    fetch('restore_record.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        action: 'restore',
        record_type: 'student',
        record_id: studentId
      })
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert('Student record restored successfully!');
        location.reload();
      } else {
        alert('Error: ' + data.message);
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('An error occurred while restoring the record.');
    });
  }
}

// Restore deleted employee
function restoreEmployee(employeeId) {
  if (confirm('Are you sure you want to restore this employee record?')) {
    fetch('restore_record.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        action: 'restore',
        record_type: 'employee',
        record_id: employeeId
      })
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert('Employee record restored successfully!');
        location.reload();
      } else {
        alert('Error: ' + data.message);
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('An error occurred while restoring the record.');
    });
  }
}

// Request permanent deletion
function requestPermanentDelete(recordId, recordType) {
  const reason = prompt('Please provide a reason for permanent deletion:');
  if (reason && reason.trim()) {
    if (confirm('This will send a request to the School Owner for approval. Continue?')) {
      fetch('request_permanent_delete.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          action: 'request_permanent_delete',
          record_type: recordType,
          record_id: recordId,
          reason: reason.trim()
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert('Permanent deletion request sent to School Owner for approval.');
          location.reload();
        } else {
          alert('Error: ' + data.message);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while sending the request.');
      });
    }
  }
}

// Handle hash-based navigation
document.addEventListener('DOMContentLoaded', function() {
  // Check URL hash on page load
  const hash = window.location.hash.substring(1); // Remove the #
  console.log('URL hash:', hash);
  
  if (hash === 'deleted-items') {
    showSection('deleted-items');
  } else if (hash === 'dashboard') {
    showSection('dashboard');
  } else {
    // Default based on PHP current_page
    const currentPage = '<?= $current_page ?>';
    showSection(currentPage);
  }
});

// Listen for hash changes
window.addEventListener('hashchange', function() {
  const hash = window.location.hash.substring(1);
  console.log('Hash changed to:', hash);
  
  if (hash === 'deleted-items') {
    showSection('deleted-items');
  } else if (hash === 'dashboard') {
    showSection('dashboard');
  }
});

// ===== PREVENT BACK BUTTON AFTER LOGOUT =====
window.addEventListener("pageshow", function(event) {
  if (event.persisted || (performance.navigation.type === 2)) window.location.reload();
});
</script>

</body>
</html>