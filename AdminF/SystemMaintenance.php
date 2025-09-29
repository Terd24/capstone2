<?php
session_start();
// Require Super Admin login
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    header('Location: ../StudentLogin/login.php');
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'onecci_db');
if ($conn->connect_error) {
    die('DB connection failed: ' . $conn->connect_error);
}

// Get flash messages from session
$flash = $_SESSION['flash'] ?? '';
$flash_type = $_SESSION['flash_type'] ?? 'info';

// Clear flash messages after displaying
if (isset($_SESSION['flash'])) {
    unset($_SESSION['flash']);
    unset($_SESSION['flash_type']);
}

// Handle maintenance actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'clear_logs':
            // Clear login activity logs based on selected date range
            $start_date = $_POST['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
            $end_date = $_POST['end_date'] ?? date('Y-m-d');
            
            // Validate date range
            if (strtotime($start_date) > strtotime($end_date)) {
                $_SESSION['flash'] = "Start date cannot be later than end date.";
                $_SESSION['flash_type'] = 'error';
            } else if ($conn->query("SHOW TABLES LIKE 'login_activity'")->num_rows > 0) {
                $stmt = $conn->prepare("DELETE FROM login_activity WHERE DATE(login_time) BETWEEN ? AND ?");
                $stmt->bind_param('ss', $start_date, $end_date);
                
                if ($stmt->execute()) {
                    $affected = $conn->affected_rows;
                    $_SESSION['flash'] = "Cleared $affected login log records from $start_date to $end_date.";
                    $_SESSION['flash_type'] = 'success';
                } else {
                    $_SESSION['flash'] = "Failed to clear logs: " . $conn->error;
                    $_SESSION['flash_type'] = 'error';
                }
                $stmt->close();
            } else {
                $_SESSION['flash'] = "No login activity table found.";
                $_SESSION['flash_type'] = 'info';
            }
            
            // Redirect to prevent form resubmission
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
            break;
            
        case 'clear_attendance':
            // Clear attendance records based on selected date range
            $start_date = $_POST['attendance_start_date'] ?? date('Y-m-d', strtotime('-30 days'));
            $end_date = $_POST['attendance_end_date'] ?? date('Y-m-d');
            
            // Validate date range
            if (strtotime($start_date) > strtotime($end_date)) {
                $_SESSION['flash'] = "Start date cannot be later than end date.";
                $_SESSION['flash_type'] = 'error';
            } else if ($conn->query("SHOW TABLES LIKE 'attendance_record'")->num_rows > 0) {
                $stmt = $conn->prepare("DELETE FROM attendance_record WHERE DATE(date) BETWEEN ? AND ?");
                $stmt->bind_param('ss', $start_date, $end_date);
                
                if ($stmt->execute()) {
                    $affected = $conn->affected_rows;
                    $_SESSION['flash'] = "Cleared $affected attendance records from $start_date to $end_date.";
                    $_SESSION['flash_type'] = 'success';
                } else {
                    $_SESSION['flash'] = "Failed to clear attendance records: " . $conn->error;
                    $_SESSION['flash_type'] = 'error';
                }
                $stmt->close();
            } else {
                $_SESSION['flash'] = "No attendance record table found.";
                $_SESSION['flash_type'] = 'info';
            }
            
            // Redirect to prevent form resubmission
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
            break;
            
        case 'backup_db':
            // Create database backup using PHP (XAMPP-friendly)
            $backup_file = '../backups/backup_' . date('Y-m-d_H-i-s') . '.sql';
            $backup_dir = dirname($backup_file);
            
            if (!is_dir($backup_dir)) {
                mkdir($backup_dir, 0755, true);
            }
            
            try {
                // Get all tables
                $tables_result = $conn->query("SHOW TABLES");
                $backup_content = "-- Database Backup for onecci_db\n";
                $backup_content .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
                $backup_content .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
                
                while ($table = $tables_result->fetch_array()) {
                    $table_name = $table[0];
                    
                    // Get table structure
                    $create_result = $conn->query("SHOW CREATE TABLE `$table_name`");
                    if ($create_result && $create_row = $create_result->fetch_assoc()) {
                        $backup_content .= "-- Table structure for `$table_name`\n";
                        $backup_content .= "DROP TABLE IF EXISTS `$table_name`;\n";
                        $backup_content .= $create_row['Create Table'] . ";\n\n";
                    }
                    
                    // Get table data
                    $data_result = $conn->query("SELECT * FROM `$table_name`");
                    if ($data_result && $data_result->num_rows > 0) {
                        $backup_content .= "-- Data for table `$table_name`\n";
                        
                        while ($row = $data_result->fetch_assoc()) {
                            $columns = array_keys($row);
                            $values = array_values($row);
                            
                            // Escape values
                            $escaped_values = [];
                            foreach ($values as $value) {
                                if ($value === null) {
                                    $escaped_values[] = 'NULL';
                                } else {
                                    $escaped_values[] = "'" . $conn->real_escape_string($value) . "'";
                                }
                            }
                            
                            $backup_content .= "INSERT INTO `$table_name` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $escaped_values) . ");\n";
                        }
                        $backup_content .= "\n";
                    }
                }
                
                $backup_content .= "SET FOREIGN_KEY_CHECKS=1;\n";
                
                // Write backup file
                if (file_put_contents($backup_file, $backup_content)) {
                    $file_size = round(filesize($backup_file) / 1024, 2);
                    $_SESSION['flash'] = "Database backup created successfully: " . basename($backup_file) . " ({$file_size} KB)";
                    $_SESSION['flash_type'] = 'success';
                } else {
                    $_SESSION['flash'] = "Failed to write backup file. Check directory permissions.";
                    $_SESSION['flash_type'] = 'error';
                }
                
            } catch (Exception $e) {
                $_SESSION['flash'] = "Backup failed: " . $e->getMessage();
                $_SESSION['flash_type'] = 'error';
            }
            
            // Redirect to prevent form resubmission
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
            break;
            
        case 'update_system_config':
            $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
            
            try {
                $conn->query("INSERT INTO system_config (config_key, config_value) VALUES ('maintenance_mode', '$maintenance_mode') ON DUPLICATE KEY UPDATE config_value = '$maintenance_mode'");
                
                $_SESSION['flash'] = "System configuration updated successfully.";
                $_SESSION['flash_type'] = 'success';
            } catch (Exception $e) {
                $_SESSION['flash'] = "Failed to update configuration: " . $e->getMessage();
                $_SESSION['flash_type'] = 'error';
            }
            
            // Redirect to prevent form resubmission
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
            break;
    }
}

// Ensure system_config table exists
$conn->query("CREATE TABLE IF NOT EXISTS system_config (
    config_key VARCHAR(50) PRIMARY KEY,
    config_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Get current system configuration
$maintenance_mode = false;

$config_result = $conn->query("SELECT config_key, config_value FROM system_config WHERE config_key = 'maintenance_mode'");
if ($config_result) {
    while ($row = $config_result->fetch_assoc()) {
        if ($row['config_key'] === 'maintenance_mode') {
            $maintenance_mode = (bool) $row['config_value'];
        }
    }
}

// Get system statistics
$stats = [
    'total_records' => 0,
    'db_size' => 0,
    'login_records' => 0
];

$size_result = $conn->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb FROM information_schema.tables WHERE table_schema = DATABASE()");
if ($size_result) {
    $stats['db_size'] = $size_result->fetch_assoc()['size_mb'];
}

if ($conn->query("SHOW TABLES LIKE 'login_activity'")->num_rows > 0) {
    $login_result = $conn->query("SELECT COUNT(*) as count FROM login_activity");
    if ($login_result) {
        $stats['login_records'] = $login_result->fetch_assoc()['count'];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>System Maintenance - Super Admin Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
<header class="bg-gradient-to-r from-[#0B2C62] to-[#153e86] text-white shadow">
  <div class="container mx-auto px-6 py-6 flex items-center justify-between">
    <div class="flex items-center gap-3">
      <a href="SuperAdminDashboard.php" class="inline-flex items-center justify-center w-8 h-8 bg-white/10 hover:bg-white/20 rounded transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      </a>
      <div class="text-lg font-semibold">System Maintenance</div>
    </div>
    <div class="flex items-center gap-3">
      <img src="../images/LogoCCI.png" class="h-12 w-12 rounded-full bg-white p-1 shadow-sm" alt="Logo">
      <div class="leading-tight">
        <div class="text-lg font-bold">Cornerstone College Inc.</div>
        <div class="text-base text-blue-200">System Maintenance Portal</div>
      </div>
    </div>
  </div>
</header>

<main class="container mx-auto px-6 py-6">
  <?php if ($flash): ?>
    <div class="mb-4 px-4 py-3 rounded-lg <?= $flash_type === 'success' ? 'bg-green-50 border border-green-200 text-green-900' : ($flash_type === 'error' ? 'bg-red-50 border border-red-200 text-red-900' : 'bg-blue-50 border border-blue-200 text-blue-900') ?>">
      <?= htmlspecialchars($flash) ?>
    </div>
  <?php endif; ?>

  <!-- System Status Overview -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <div class="bg-white rounded-2xl shadow p-5 border border-blue-100">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/></svg>
        </div>
        <div>
          <div class="text-gray-500 text-sm">Database Size</div>
          <div class="text-2xl font-bold text-gray-800"><?= $stats['db_size'] ?> MB</div>
        </div>
      </div>
    </div>
    
    <div class="bg-white rounded-2xl shadow p-5 border border-amber-100">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg <?= $maintenance_mode ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' ?> flex items-center justify-center">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
          <div class="text-gray-500 text-sm">System Status</div>
          <div class="text-2xl font-bold <?= $maintenance_mode ? 'text-red-600' : 'text-green-600' ?>"><?= $maintenance_mode ? 'Maintenance' : 'Online' ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- System Configuration Section -->
  <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-200 mb-6">
    <div class="flex items-center gap-3 mb-6">
      <div class="w-10 h-10 rounded-lg bg-[#0B2C62] text-white flex items-center justify-center">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      </div>
      <div>
        <h3 class="text-2xl font-bold text-gray-800">System Configuration</h3>
        <p class="text-base text-gray-600">Control system-wide settings and database maintenance</p>
      </div>
    </div>
    
    <!-- Owner Approval Warning -->
    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
      <div class="flex items-center gap-2">
        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.314 16.5c-.77.833.192 2.5 1.732 2.5z"/>
        </svg>
        <span class="text-amber-800 font-semibold">School Owner Approval Required</span>
      </div>
      <p class="text-amber-700 text-sm mt-2">As IT Personnel, system maintenance operations require School Owner approval. All actions will be logged and may require additional authorization.</p>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Maintenance Mode -->
      <div class="flex flex-col h-full">
        <h4 class="text-xl font-semibold text-gray-800 mb-4">Maintenance Mode</h4>
        <form method="POST" class="space-y-4 flex-1 flex flex-col">
          <input type="hidden" name="action" value="update_system_config">
          
          <div class="bg-gray-50 p-4 rounded-lg flex-1">
            <div class="flex items-center justify-between h-full">
              <div>
                <label class="text-lg font-semibold text-gray-800">System Maintenance</label>
                <p class="text-base text-gray-600">Restrict system access for maintenance</p>
                <div class="mt-3 text-sm text-gray-500">
                  <p>• Prevents user logins during maintenance</p>
                  <p>• Displays maintenance message to users</p>
                </div>
              </div>
              <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="maintenance_mode" class="sr-only peer" <?= $maintenance_mode ? 'checked' : '' ?>>
                <div class="w-12 h-6 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#0B2C62]"></div>
              </label>
            </div>
          </div>
          
          <button type="submit" class="w-full bg-[#0B2C62] hover:bg-blue-900 text-white px-6 py-3 rounded-lg transition font-semibold">
            Update Configuration
          </button>
        </form>
      </div>
      
      <!-- Database Backup -->
      <div class="flex flex-col h-full">
        <h4 class="text-xl font-semibold text-gray-800 mb-4">Database Backup</h4>
        <form method="POST" class="space-y-4 flex-1 flex flex-col">
          <input type="hidden" name="action" value="backup_db">
          
          <div class="bg-gray-50 p-4 rounded-lg flex-1">
            <div class="flex items-start gap-2">
              <div class="w-6 h-6 rounded bg-blue-100 text-blue-600 flex items-center justify-center flex-shrink-0 mt-0.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/></svg>
              </div>
              <div>
                <h5 class="font-semibold text-gray-800 text-base">Backup Information</h5>
                <div class="space-y-1 text-sm text-gray-600 mt-1">
                  <p>• Includes all tables and data</p>
                  <p>• Regular backups recommended before updates</p>
                  <p>• Creates timestamped backup files</p>
                </div>
              </div>
            </div>
          </div>
          
          <button type="submit" class="w-full bg-[#0B2C62] hover:bg-blue-900 text-white px-6 py-3 rounded-lg transition font-semibold">
            <div class="flex items-center justify-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/></svg>
              Create Database Backup
            </div>
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Data Management Section -->
  <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-200 mb-6">
    <div class="flex items-center gap-3 mb-6">
      <div class="w-10 h-10 rounded-lg bg-[#0B2C62] text-white flex items-center justify-center">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
      </div>
      <div>
        <h3 class="text-2xl font-bold text-gray-800">Data Management</h3>
        <p class="text-base text-gray-600">Clear old records and manage system data</p>
      </div>
    </div>
    
    <!-- Owner Approval Warning -->
    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
      <div class="flex items-center gap-2">
        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.314 16.5c-.77.833.192 2.5 1.732 2.5z"/>
        </svg>
        <span class="text-amber-800 font-semibold">School Owner Approval Required</span>
      </div>
      <p class="text-amber-700 text-sm mt-2">As IT Personnel, data management operations require School Owner approval. Data deletion actions will be logged and may require additional authorization.</p>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Clear Login Logs -->
      <div>
        <h4 class="text-xl font-semibold text-gray-800 mb-4">Clear Login Logs</h4>
        <form method="POST" class="space-y-4">
          <input type="hidden" name="action" value="clear_logs">
          
          <div class="bg-gray-50 p-4 rounded-lg space-y-3">
            <div>
              <label class="block text-base font-semibold text-gray-800 mb-2">Start Date:</label>
              <input type="date" name="start_date" value="<?= date('Y-m-d', strtotime('-30 days')) ?>" 
                     class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                     max="<?= date('Y-m-d') ?>">
            </div>
            
            <div>
              <label class="block text-base font-semibold text-gray-800 mb-2">End Date:</label>
              <input type="date" name="end_date" value="<?= date('Y-m-d') ?>" 
                     class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                     max="<?= date('Y-m-d') ?>">
            </div>
            
            <p class="text-sm text-gray-600">All login records between these dates will be deleted</p>
          </div>
          
          <button type="button" onclick="showConfirmModal()" class="w-full bg-[#0B2C62] hover:bg-blue-900 text-white px-6 py-3 rounded-lg transition font-semibold">
            <div class="flex items-center justify-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              Clear Login Logs
            </div>
          </button>
        </form>
      </div>
      
      <!-- Clear Attendance Records -->
      <div>
        <h4 class="text-xl font-semibold text-gray-800 mb-4">Clear Attendance Records</h4>
        <form method="POST" class="space-y-4">
          <input type="hidden" name="action" value="clear_attendance">
          
          <div class="bg-gray-50 p-4 rounded-lg space-y-3">
            <div>
              <label class="block text-base font-semibold text-gray-800 mb-2">Start Date:</label>
              <input type="date" name="attendance_start_date" value="<?= date('Y-m-d', strtotime('-30 days')) ?>" 
                     class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                     max="<?= date('Y-m-d') ?>">
            </div>
            
            <div>
              <label class="block text-base font-semibold text-gray-800 mb-2">End Date:</label>
              <input type="date" name="attendance_end_date" value="<?= date('Y-m-d') ?>" 
                     class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                     max="<?= date('Y-m-d') ?>">
            </div>
            
            <p class="text-sm text-gray-600">All attendance records between these dates will be deleted</p>
          </div>
          
          <button type="button" onclick="showAttendanceConfirmModal()" class="w-full bg-[#0B2C62] hover:bg-blue-900 text-white px-6 py-3 rounded-lg transition font-semibold">
            <div class="flex items-center justify-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 0h6m-6 0l-2 13a2 2 0 002 2h6a2 2 0 002-2L14 7"/></svg>
              Clear Attendance Records
            </div>
          </button>
        </form>
      </div>
    </div>
  </div>
</main>

<!-- Custom Confirmation Modal -->
<div id="confirmModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
  <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 transform transition-all">
    <div class="p-6">
      <!-- Header -->
      <div class="flex items-center gap-3 mb-4">
        <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
          <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
          </svg>
        </div>
        <div>
          <h3 class="text-lg font-bold text-gray-900">Confirm Log Deletion</h3>
          <p class="text-sm text-gray-600">This action cannot be undone</p>
        </div>
      </div>
      
      <!-- Content -->
      <div class="mb-6">
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
          <p class="text-sm text-red-800 font-medium mb-2">⚠️ You are about to permanently delete:</p>
          <p class="text-sm text-red-700">All login records from <span id="modalStartDate" class="font-semibold"></span> to <span id="modalEndDate" class="font-semibold"></span></p>
        </div>
        
        <div class="bg-gray-50 rounded-lg p-3">
          <p class="text-xs text-gray-600">
            <strong>Note:</strong> This will permanently remove all login activity logs within the selected date range. 
            This data cannot be recovered once deleted.
          </p>
        </div>
      </div>
      
      <!-- Actions -->
      <div class="flex gap-3">
        <button onclick="hideConfirmModal()" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-3 rounded-lg transition font-semibold">
          <div class="flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            Cancel
          </div>
        </button>
        <button onclick="confirmDelete()" class="flex-1 bg-red-600 hover:bg-red-700 text-white px-4 py-3 rounded-lg transition font-semibold">
          <div class="flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            Yes, Delete Logs
          </div>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Attendance Confirmation Modal -->
<div id="attendanceConfirmModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
  <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 transform transition-all">
    <div class="p-6">
      <!-- Header -->
      <div class="flex items-center gap-3 mb-4">
        <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center">
          <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
          </svg>
        </div>
        <div>
          <h3 class="text-lg font-bold text-gray-900">Confirm Attendance Deletion</h3>
          <p class="text-sm text-gray-600">This action cannot be undone</p>
        </div>
      </div>
      
      <!-- Content -->
      <div class="mb-6">
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
          <p class="text-sm text-red-800 font-medium mb-2">⚠️ You are about to permanently delete:</p>
          <p class="text-sm text-red-700">All attendance records from <span id="attendanceModalStartDate" class="font-semibold"></span> to <span id="attendanceModalEndDate" class="font-semibold"></span></p>
        </div>
        
        <div class="bg-gray-50 rounded-lg p-3">
          <p class="text-xs text-gray-600">
            <strong>Note:</strong> This will permanently remove all attendance records within the selected date range. 
            This data cannot be recovered once deleted.
          </p>
        </div>
      </div>
      
      <!-- Actions -->
      <div class="flex gap-3">
        <button onclick="hideAttendanceConfirmModal()" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-3 rounded-lg transition font-semibold">
          <div class="flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            Cancel
          </div>
        </button>
        <button onclick="confirmAttendanceDelete()" class="flex-1 bg-red-600 hover:bg-red-700 text-white px-4 py-3 rounded-lg transition font-semibold">
          <div class="flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 0h6m-6 0l-2 13a2 2 0 002 2h6a2 2 0 002-2L14 7"/>
            </svg>
            Yes, Delete Records
          </div>
        </button>
      </div>
    </div>
  </div>
</div>

<script>
function showConfirmModal() {
    const startDate = document.querySelector('input[name="start_date"]').value;
    const endDate = document.querySelector('input[name="end_date"]').value;
    
    // Format dates for better display
    const startFormatted = new Date(startDate).toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
    const endFormatted = new Date(endDate).toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
    
    document.getElementById('modalStartDate').textContent = startFormatted;
    document.getElementById('modalEndDate').textContent = endFormatted;
    document.getElementById('confirmModal').classList.remove('hidden');
    
    // Prevent body scroll
    document.body.style.overflow = 'hidden';
}

function hideConfirmModal() {
    document.getElementById('confirmModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

function confirmDelete() {
    // Submit the form
    document.querySelector('form[method="POST"]').submit();
}

// Attendance modal functions
function showAttendanceConfirmModal() {
    const startDate = document.querySelector('input[name="attendance_start_date"]').value;
    const endDate = document.querySelector('input[name="attendance_end_date"]').value;
    
    // Format dates for better display
    const startFormatted = new Date(startDate).toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
    const endFormatted = new Date(endDate).toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
    
    document.getElementById('attendanceModalStartDate').textContent = startFormatted;
    document.getElementById('attendanceModalEndDate').textContent = endFormatted;
    document.getElementById('attendanceConfirmModal').classList.remove('hidden');
    
    // Prevent body scroll
    document.body.style.overflow = 'hidden';
}

function hideAttendanceConfirmModal() {
    document.getElementById('attendanceConfirmModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

function confirmAttendanceDelete() {
    // Submit the attendance form
    document.querySelector('form input[name="action"][value="clear_attendance"]').closest('form').submit();
}

// Close modal when clicking outside
document.getElementById('confirmModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideConfirmModal();
    }
});

// Close attendance modal when clicking outside
document.getElementById('attendanceConfirmModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideAttendanceConfirmModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        hideConfirmModal();
        hideAttendanceConfirmModal();
    }
});

// Auto-hide notification after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const notification = document.querySelector('.mb-4.px-4.py-3.rounded-lg');
    if (notification) {
        setTimeout(function() {
            notification.style.transition = 'opacity 0.5s ease-out';
            notification.style.opacity = '0';
            setTimeout(function() {
                notification.style.display = 'none';
            }, 500);
        }, 5000);
    }
});
</script>
</body>
</html>
