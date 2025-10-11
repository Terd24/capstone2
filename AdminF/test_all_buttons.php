<?php
session_start();

// Check if user is Super Admin
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    die('⛔ Please login as Super Admin first');
}

$conn = new mysqli('localhost', 'root', '', 'onecci_db');
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test All Buttons</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">🧪 Test All 4 Buttons</h1>
        
        <!-- Session Info -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Session Information</h2>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div><strong>Role:</strong> <?= $_SESSION['role'] ?? 'NOT SET' ?></div>
                <div><strong>Name:</strong> <?= $_SESSION['superadmin_name'] ?? 'NOT SET' ?></div>
                <div><strong>ID:</strong> <?= $_SESSION['id_number'] ?? 'NOT SET' ?></div>
                <div><strong>Is SuperAdmin:</strong> <?= (strtolower($_SESSION['role']) === 'superadmin') ? '✅ YES' : '❌ NO' ?></div>
            </div>
        </div>

        <!-- Test Buttons -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <!-- Test 1: Update Configuration -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">1️⃣ Update Configuration</h3>
                <button onclick="testUpdateConfig()" class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Test Update Config
                </button>
                <div id="result1" class="mt-4 text-sm"></div>
            </div>

            <!-- Test 2: Create Backup -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">2️⃣ Create Database Backup</h3>
                <button onclick="testBackup()" class="w-full bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    Test Backup
                </button>
                <div id="result2" class="mt-4 text-sm"></div>
            </div>

            <!-- Test 3: Clear Login Logs -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">3️⃣ Clear Login Logs</h3>
                <input type="date" id="loginStart" class="border rounded px-2 py-1 mb-2 w-full" value="2024-01-01">
                <input type="date" id="loginEnd" class="border rounded px-2 py-1 mb-2 w-full" value="2024-12-31">
                <button onclick="testClearLogs()" class="w-full bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700">
                    Test Clear Logs
                </button>
                <div id="result3" class="mt-4 text-sm"></div>
            </div>

            <!-- Test 4: Clear Attendance -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold mb-4">4️⃣ Clear Attendance Records</h3>
                <input type="date" id="attendanceStart" class="border rounded px-2 py-1 mb-2 w-full" value="2024-01-01">
                <input type="date" id="attendanceEnd" class="border rounded px-2 py-1 mb-2 w-full" value="2024-12-31">
                <button onclick="testClearAttendance()" class="w-full bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                    Test Clear Attendance
                </button>
                <div id="result4" class="mt-4 text-sm"></div>
            </div>
        </div>

        <!-- Back Button -->
        <div class="mt-6">
            <a href="SuperAdminDashboard.php#system-maintenance" class="bg-gray-600 text-white px-6 py-3 rounded inline-block hover:bg-gray-700">
                ← Back to Dashboard
            </a>
        </div>
    </div>

    <script>
        function showResult(id, success, message, data = null) {
            const resultDiv = document.getElementById(id);
            const color = success ? 'text-green-600' : 'text-red-600';
            let html = `<div class="${color} font-semibold">${success ? '✅' : '❌'} ${message}</div>`;
            if (data) {
                html += `<pre class="mt-2 bg-gray-100 p-2 rounded text-xs overflow-auto">${JSON.stringify(data, null, 2)}</pre>`;
            }
            resultDiv.innerHTML = html;
        }

        function testUpdateConfig() {
            showResult('result1', null, 'Testing...');
            fetch('update_configuration.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ maintenance_mode: 'enabled' })
            })
            .then(response => response.json())
            .then(data => {
                showResult('result1', data.success, data.message, data);
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
