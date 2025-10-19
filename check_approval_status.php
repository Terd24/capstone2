<!DOCTYPE html>
<html>
<head>
    <title>Check Approval Status</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-6">
        <h1 class="text-2xl font-bold mb-4">🔍 Approval Status Check</h1>
        
        <?php
        require_once 'StudentLogin/db_conn.php';
        
        echo "<h2 class='text-xl font-bold mt-6 mb-3'>Recent Archive Login Logs Requests:</h2>";
        
        $result = $conn->query("SELECT * FROM owner_approval_requests WHERE request_type = 'archive_login_logs' ORDER BY requested_at DESC LIMIT 5");
        
        if ($result && $result->num_rows > 0) {
            echo "<table class='w-full border'>";
            echo "<tr class='bg-gray-200'><th class='p-2'>ID</th><th class='p-2'>Title</th><th class='p-2'>Status</th><th class='p-2'>Target Data</th><th class='p-2'>Requested At</th><th class='p-2'>Reviewed At</th></tr>";
            
            while ($row = $result->fetch_assoc()) {
                $status_color = $row['status'] == 'approved' ? 'bg-green-100' : ($row['status'] == 'rejected' ? 'bg-red-100' : 'bg-yellow-100');
                echo "<tr class='$status_color'>";
                echo "<td class='p-2 border'>{$row['id']}</td>";
                echo "<td class='p-2 border'>{$row['request_title']}</td>";
                echo "<td class='p-2 border font-bold'>{$row['status']}</td>";
                echo "<td class='p-2 border'><pre class='text-xs'>" . htmlspecialchars($row['target_data']) . "</pre></td>";
                echo "<td class='p-2 border'>{$row['requested_at']}</td>";
                echo "<td class='p-2 border'>{$row['reviewed_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='text-red-600'>No archive_login_logs requests found</p>";
        }
        
        echo "<h2 class='text-xl font-bold mt-6 mb-3'>Login Activity Count (Today):</h2>";
        $today = date('Y-m-d');
        $count_result = $conn->query("SELECT COUNT(*) as cnt FROM login_activity WHERE DATE(login_time) = '$today'");
        if ($count_result) {
            $count = $count_result->fetch_assoc()['cnt'];
            echo "<p class='text-lg'>Total login records for today ($today): <strong>$count</strong></p>";
        }
        
        echo "<h2 class='text-xl font-bold mt-6 mb-3'>Login Logs Archive Count:</h2>";
        $archive_count = $conn->query("SELECT COUNT(*) as cnt FROM login_logs_archive");
        if ($archive_count) {
            $count = $archive_count->fetch_assoc()['cnt'];
            echo "<p class='text-lg'>Total archived login records: <strong>$count</strong></p>";
        }
        
        echo "<h2 class='text-xl font-bold mt-6 mb-3'>Recent Login Activity (Today):</h2>";
        $logins = $conn->query("SELECT * FROM login_activity WHERE DATE(login_time) = '$today' ORDER BY login_time DESC LIMIT 10");
        if ($logins && $logins->num_rows > 0) {
            echo "<table class='w-full border text-sm'>";
            echo "<tr class='bg-gray-200'><th class='p-2'>ID</th><th class='p-2'>Username</th><th class='p-2'>Role</th><th class='p-2'>Login Time</th><th class='p-2'>Logout Time</th></tr>";
            
            while ($login = $logins->fetch_assoc()) {
                echo "<tr>";
                echo "<td class='p-2 border'>{$login['id']}</td>";
                echo "<td class='p-2 border'>{$login['username']}</td>";
                echo "<td class='p-2 border'>{$login['role']}</td>";
                echo "<td class='p-2 border'>{$login['login_time']}</td>";
                echo "<td class='p-2 border'>" . ($login['logout_time'] ?? 'Active') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='text-green-600'>No login records for today (already archived!)</p>";
        }
        
        $conn->close();
        ?>
        
        <div class="mt-6 pt-4 border-t">
            <a href="admin_login.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Back to Login</a>
            <a href="AdminF/SuperAdminDashboard.php" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 ml-2">SuperAdmin Dashboard</a>
        </div>
    </div>
</body>
</html>
