<?php
session_start();

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    die('⛔ Please login as Super Admin first at: <a href="../admin_login.php">admin_login.php</a>');
}

$conn = new mysqli('localhost', 'root', '', 'onecci_db');
if ($conn->connect_error) {
    die('Database connection failed');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Final Test - All Buttons</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">🎯 Final Test - All 4 Buttons</h1>
        
        <!-- Current Status -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Current Maintenance Status</h2>
            <?php
            $result = $conn->query("SELECT config_value FROM system_config WHERE config_key = 'maintenance_mode'");
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $isEnabled = ($row['config_value'] == '1');
                $statusText = $isEnabled ? '🔴 ENABLED' : '🟢 DISABLED';
                $statusColor = $isEnabled ? 'text-red-600' : 'text-green-600';
                echo "<p class='text-2xl font-bold $statusColor'>$statusText</p>";
            } else {
                echo "<p class='text-gray-600'>Not configured yet</p>";
            }
            ?>
        </div>

        <!-- Test Buttons -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            
            <!-- Test 1: Update Configuration -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">1️⃣ Update Configuration</h3>
                <div class="space-y-2 mb-4">
                    <button onclick="testUpdateConfig('enabled')" class="w-full bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                        Enable Maintenance
                    </button>
                    <button onclick="testUpdateConfig('disabled')" class="w-full bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                        Disable Maintenance
                    </button>
                </div>
                <div id="result1" class="text-sm"></div>
            </div>

            <!-- Test 2: Create Backup -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">2️⃣ Create Database Backup</h3>
                <button onclick="testBackup()" class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Create Backup
                </button>
                <div id="result2" class="mt-4 text-sm"></div>
            </div>

            <!-- Test 3: Clear Login Logs -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">3️⃣ Clear Login Logs</h3>
                <input type="date" id="loginStart" class="border rounded px-2 py-1 mb-2 w-full" value="2024-01-01">
                <input type="date" id="loginEnd" class="border rounded px-2 py-1 mb-2 w-full" value="2024-12-31">
                <button onclick="testClearLogs()" class="w-full bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700">
                    Clear Login Logs
                </button>
                <div id="result3" class="mt-4 text-sm"></div>
            </div>

            <!-- Test 4: Clear Attendance -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">4️⃣ Clear Attendance Records</h3>
                <input type="date" id="attendanceStart" class="border rounded px-2 py-1 mb-2 w-full" value="2024-01-01">
                <input type="date" id="attendanceEnd" class="border rounded px-2 py-1 mb-2 w-full" value="2024-12-31">
                <button onclick="testClearAttendance()" class="w-full bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">
                    Clear Attendance
                </button>
                <div id="result4" class="mt-4 text-sm"></div>
            </div>
        </div>

        <!-- Instructions -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
            <h3 class="font-semibold text-blue-900 mb-2">📋 Test Instructions:</h3>
            <ol class="list-decimal list-inside text-blue-800 space-y-1">
                <li>Test each button above</li>
                <li>All should show ✅ Success</li>
                <li>If maintenance is enabled, try logging in as student (should see maintenance page)</li>
                <li>If all tests pass, go to the dashboard and try the real buttons</li>
            </ol>
        </div>

        <!-- Navigation -->
        <div class="flex gap-4">
            <a href="SuperAdminDashboard.php#system-maintenance" class="bg-gray-600 text-white px-6 py-3 rounded hover:bg-gray-700">
                ← Back to Dashboard
            </a>
            <a href="../StudentLogin/login.php" target="_blank" class="bg-blue-600 text-white px-6 py-3 rounded hover:bg-blue-700">
                Test Student Login →
            </a>
            <button onclick="location.reload()" class="bg-green-600 text-white px-6 py-3 rounded hover:bg-green-700">
                🔄 Refresh Status
            </button>
        </div>
    </div>

    <script>
        function showResult(id, success, message, data = null) {
            const resultDiv = document.getElementById(id);
            const color = success ? 'text-green-600' : 'text-red-600';
            let html = `<div class="${color} font-semibold">${success ? '✅' : '❌'} ${message}</div>`;
            if (data) {
                html += `<pre class="mt-2 bg-gray-100 p-2 rounded text-xs overflow-auto max-h-40">${JSON.stringify(data, null, 2)}</pre>`;
            }
            resultDiv.innerHTML = html;
        }

        function testUpdateConfig(mode) {
            showResult('result1', null, 'Testing...');
            fetch('update_configuration.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ maintenance_mode: mode })
            })
            .then(response => response.json())
            .then(data => {
                showResult('result1', data.success, data.message, data);
                if (data.success) {
                    setTimeout(() => location.reload(), 1500);
                }
            })
            .catch(error => {
                showResult('result1', false, 'Error: ' + error.message);
            });
        }

        function testBackup() {
            showResult('result2', null, 'Creating backup...');
            fetch('create_backup.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            })
            .then(response => response.json())
            .then(data => {
                showResult('result2', data.success, data.message, data);
            })
            .catch(error => {
                showResult('result2', false, 'Error: ' + error.message);
            });
        }

        function testClearLogs() {
            const startDate = document.getElementById('loginStart').value;
            const endDate = document.getElementById('loginEnd').value;
            
            showResult('result3', null, 'Clearing logs...');
            fetch('clear_login_logs.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ start_date: startDate, end_date: endDate })
            })
            .then(response => response.json())
            .then(data => {
                showResult('result3', data.success, data.message, data);
            })
            .catch(error => {
                showResult('result3', false, 'Error: ' + error.message);
            });
        }

        function testClearAttendance() {
            const startDate = document.getElementById('attendanceStart').value;
            const endDate = document.getElementById('attendanceEnd').value;
            
            showResult('result4', null, 'Clearing attendance...');
            fetch('clear_attendance_records.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ start_date: startDate, end_date: endDate })
            })
            .then(response => response.json())
            .then(data => {
                showResult('result4', data.success, data.message, data);
            })
            .catch(error => {
                showResult('result4', false, 'Error: ' + error.message);
            });
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
