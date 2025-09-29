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

$flash = '';
$flash_type = 'info';

// Handle maintenance actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'clear_logs':
            // Clear login activity logs older than 30 days
            if ($conn->query("SHOW TABLES LIKE 'login_activity'")->num_rows > 0) {
                $result = $conn->query("DELETE FROM login_activity WHERE login_time < DATE_SUB(NOW(), INTERVAL 30 DAY)");
                if ($result) {
                    $affected = $conn->affected_rows;
                    $flash = "Cleared $affected login log records older than 30 days.";
                    $flash_type = 'success';
                } else {
                    $flash = "Failed to clear logs: " . $conn->error;
                    $flash_type = 'error';
                }
            } else {
                $flash = "No login activity table found.";
                $flash_type = 'info';
            }
            break;
            
        case 'optimize_db':
            // Optimize all tables
            $tables_result = $conn->query("SHOW TABLES");
            $optimized = 0;
            while ($table = $tables_result->fetch_array()) {
                $table_name = $table[0];
                $conn->query("OPTIMIZE TABLE `$table_name`");
                $optimized++;
            }
            $flash = "Optimized $optimized database tables.";
            $flash_type = 'success';
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
                    $flash = "Database backup created successfully: " . basename($backup_file) . " ({$file_size} KB)";
                    $flash_type = 'success';
                } else {
                    $flash = "Failed to write backup file. Check directory permissions.";
                    $flash_type = 'error';
                }
                
            } catch (Exception $e) {
                $flash = "Backup failed: " . $e->getMessage();
                $flash_type = 'error';
            }
            break;
            
        case 'clear_cache':
            // Clear session files and temporary data
            $session_path = session_save_path() ?: sys_get_temp_dir();
            $cleared = 0;
            
            if (is_dir($session_path)) {
                $files = glob($session_path . '/sess_*');
                foreach ($files as $file) {
                    if (filemtime($file) < time() - 3600) { // Older than 1 hour
                        unlink($file);
                        $cleared++;
                    }
                }
            }
            
            $flash = "Cleared $cleared old session files.";
            $flash_type = 'success';
            break;
            
        case 'reset_failed_logins':
            // Reset failed login attempts (if such table exists)
            if ($conn->query("SHOW TABLES LIKE 'failed_logins'")->num_rows > 0) {
                $conn->query("DELETE FROM failed_logins");
                $flash = "Failed login attempts have been reset.";
                $flash_type = 'success';
            } else {
                $flash = "No failed login tracking table found.";
                $flash_type = 'info';
            }
            break;
            
        case 'update_system_config':
            $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
            $debug_mode = isset($_POST['debug_mode']) ? 1 : 0;
            
            try {
                $conn->query("INSERT INTO system_config (config_key, config_value) VALUES ('maintenance_mode', '$maintenance_mode') ON DUPLICATE KEY UPDATE config_value = '$maintenance_mode'");
                $conn->query("INSERT INTO system_config (config_key, config_value) VALUES ('debug_mode', '$debug_mode') ON DUPLICATE KEY UPDATE config_value = '$debug_mode'");
                
                $flash = "System configuration updated successfully.";
                $flash_type = 'success';
            } catch (Exception $e) {
                $flash = "Failed to update configuration: " . $e->getMessage();
                $flash_type = 'error';
            }
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
$debug_mode = false;

$config_result = $conn->query("SELECT config_key, config_value FROM system_config WHERE config_key IN ('maintenance_mode', 'debug_mode')");
if ($config_result) {
    while ($row = $config_result->fetch_assoc()) {
        if ($row['config_key'] === 'maintenance_mode') {
            $maintenance_mode = (bool) $row['config_value'];
        } elseif ($row['config_key'] === 'debug_mode') {
            $debug_mode = (bool) $row['config_value'];
        }
    }
}

// Get system statistics
$stats = [
    'total_tables' => 0,
    'total_records' => 0,
    'db_size' => 0,
    'login_records' => 0
];

$tables_result = $conn->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = DATABASE()");
if ($tables_result) {
    $stats['total_tables'] = $tables_result->fetch_assoc()['count'];
}

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
  <div class="container mx-auto px-6 py-4 flex items-center justify-between">
    <div class="flex items-center gap-3">
      <img src="../images/LogoCCI.png" class="h-11 w-11 rounded-full bg-white p-1 shadow-sm" alt="Logo">
      <div class="leading-tight">
        <div class="text-lg font-bold">System Maintenance</div>
        <div class="text-[11px] text-blue-200">Bug Fixes & Configuration Dashboard</div>
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
  <?php if ($flash): ?>
    <div class="mb-4 px-4 py-3 rounded-lg <?= $flash_type === 'success' ? 'bg-green-50 border border-green-200 text-green-900' : ($flash_type === 'error' ? 'bg-red-50 border border-red-200 text-red-900' : 'bg-blue-50 border border-blue-200 text-blue-900') ?>">
      <?= htmlspecialchars($flash) ?>
    </div>
  <?php endif; ?>

  <!-- System Status Overview -->
  <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
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
    
    <div class="bg-white rounded-2xl shadow p-5 border border-green-100">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg bg-green-100 text-green-700 flex items-center justify-center">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
        </div>
        <div>
          <div class="text-gray-500 text-sm">Total Tables</div>
          <div class="text-2xl font-bold text-gray-800"><?= number_format($stats['total_tables']) ?></div>
        </div>
      </div>
    </div>
    
    <div class="bg-white rounded-2xl shadow p-5 border border-purple-100">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
        <div>
          <div class="text-gray-500 text-sm">Login Records</div>
          <div class="text-2xl font-bold text-gray-800"><?= number_format($stats['login_records']) ?></div>
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

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- System Configuration -->
    <div class="bg-white rounded-2xl shadow p-6 border border-blue-100">
      <h3 class="text-lg font-semibold text-gray-800 mb-4">System Configuration</h3>
      <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="update_system_config">
        
        <div class="flex items-center justify-between">
          <div>
            <label class="text-sm font-medium text-gray-700">Maintenance Mode</label>
            <p class="text-xs text-gray-500">Enable to restrict system access</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" name="maintenance_mode" class="sr-only peer" <?= $maintenance_mode ? 'checked' : '' ?>>
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
          </label>
        </div>
        
        <div class="flex items-center justify-between">
          <div>
            <label class="text-sm font-medium text-gray-700">Debug Mode</label>
            <p class="text-xs text-gray-500">Enable detailed error reporting</p>
          </div>
          <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" name="debug_mode" class="sr-only peer" <?= $debug_mode ? 'checked' : '' ?>>
            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
          </label>
        </div>
        
        <button type="submit" class="w-full bg-[#0B2C62] hover:bg-blue-900 text-white px-4 py-2 rounded-lg transition">
          Update Configuration
        </button>
      </form>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-2xl shadow p-6 border border-green-100">
      <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h3>
      <div class="space-y-3">
        <form method="POST" class="inline-block w-full">
          <input type="hidden" name="action" value="clear_cache">
          <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition text-sm">
            Clear System Cache
          </button>
        </form>
        
        <form method="POST" class="inline-block w-full">
          <input type="hidden" name="action" value="optimize_db">
          <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition text-sm">
            Optimize Database
          </button>
        </form>
        
        <form method="POST" class="inline-block w-full">
          <input type="hidden" name="action" value="clear_logs">
          <button type="submit" class="w-full bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-lg transition text-sm">
            Clear Old Logs
          </button>
        </form>
        
        <form method="POST" class="inline-block w-full">
          <input type="hidden" name="action" value="reset_failed_logins">
          <button type="submit" class="w-full bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-lg transition text-sm">
            Reset Failed Logins
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Database Maintenance -->
  <div class="bg-white rounded-2xl shadow p-6 border border-red-100">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Database Maintenance</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <form method="POST" class="inline-block">
        <input type="hidden" name="action" value="backup_db">
        <button type="submit" class="w-full bg-red-500 hover:bg-red-600 text-white px-4 py-3 rounded-lg transition flex items-center justify-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/></svg>
          Create Database Backup
        </button>
      </form>
      
      <div class="bg-gray-50 rounded-lg p-4">
        <h4 class="font-medium text-gray-800 mb-2">Backup Information</h4>
        <p class="text-sm text-gray-600">Backups are stored in the /backups directory. Regular backups are recommended before major updates.</p>
      </div>
    </div>
  </div>
</main>
</body>
</html>
