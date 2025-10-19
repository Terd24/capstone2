<!DOCTYPE html>
<html>
<head>
    <title>Debug Approval</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-6xl mx-auto bg-white rounded-lg shadow-lg p-6">
        <h1 class="text-2xl font-bold mb-4">🐛 Debug Approval Processing</h1>
        
        <?php
        require_once 'StudentLogin/db_conn.php';
        
        echo "<h2 class='text-xl font-bold mt-6 mb-3'>Recent Approved Requests:</h2>";
        
        $result = $conn->query("SELECT * FROM owner_approval_requests WHERE status = 'approved' ORDER BY reviewed_at DESC LIMIT 5");
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $bg_color = 'bg-green-50';
                echo "<div class='$bg_color border-2 border-green-200 rounded-lg p-4 mb-4'>";
                echo "<p class='font-semibold text-green-900'>Request #{$row['id']}: {$row['request_title']}</p>";
                echo "<p class='text-sm text-gray-700'>Type: <strong>{$row['request_type']}</strong></p>";
                echo "<p class='text-sm text-gray-700'>Status: <strong>{$row['status']}</strong></p>";
                echo "<p class='text-sm text-gray-700'>Reviewed at: {$row['reviewed_at']}</p>";
                echo "<p class='text-sm text-gray-700'>Reviewed by: {$row['reviewed_by']}</p>";
                
                $target_data = json_decode($row['target_data'], true);
                echo "<p class='text-sm text-gray-700 mt-2'>Target Data:</p>";
                echo "<pre class='text-xs bg-white p-2 rounded mt-1'>" . json_encode($target_data, JSON_PRETTY_PRINT) . "</pre>";
                
                // Check if this was a login logs request
                if ($row['request_type'] === 'archive_login_logs') {
                    $start = $target_data['start_date'];
                    $end = $target_data['end_date'];
                    
                    echo "<div class='mt-3 pt-3 border-t border-green-300'>";
                    echo "<p class='font-semibold text-green-900'>Verification for this request:</p>";
                    
                    // Check login_activity count
                    $count_query = "SELECT COUNT(*) as cnt FROM login_activity WHERE DATE(login_time) BETWEEN '$start' AND '$end'";
                    $count_result = $conn->query($count_query);
                    if ($count_result) {
                        $count = $count_result->fetch_assoc()['cnt'];
                        if ($count > 0) {
                            echo "<p class='text-red-600 text-sm'>❌ Still {$count} records in login_activity for this date range!</p>";
                            echo "<p class='text-red-600 text-sm font-semibold'>The archive did NOT execute!</p>";
                        } else {
                            echo "<p class='text-green-600 text-sm'>✅ No records in login_activity for this date range (archived successfully)</p>";
                        }
                    }
                    
                    // Check login_logs_archive count
                    $archive_query = "SELECT COUNT(*) as cnt FROM login_logs_archive WHERE DATE(login_time) BETWEEN '$start' AND '$end'";
                    $archive_result = $conn->query($archive_query);
                    if ($archive_result) {
                        $archive_count = $archive_result->fetch_assoc()['cnt'];
                        echo "<p class='text-blue-600 text-sm'>📦 {$archive_count} records in login_logs_archive for this date range</p>";
                    }
                    echo "</div>";
                }
                
                echo "</div>";
            }
        } else {
            echo "<p class='text-gray-600'>No approved requests found</p>";
        }
        
        echo "<h2 class='text-xl font-bold mt-6 mb-3'>Check Apache Error Log:</h2>";
        echo "<div class='bg-yellow-50 border border-yellow-200 rounded p-4'>";
        echo "<p class='text-yellow-800 text-sm'>Check the Apache error log for messages like:</p>";
        echo "<ul class='list-disc list-inside text-yellow-800 text-sm mt-2'>";
        echo "<li>=== ARCHIVE LOGIN LOGS START ===</li>";
        echo "<li>Successfully archived X login records</li>";
        echo "<li>ERROR in archive_login_logs</li>";
        echo "</ul>";
        echo "<p class='text-yellow-800 text-sm mt-2'>Location: <code class='bg-yellow-100 px-2 py-1 rounded'>C:\\xampp\\apache\\logs\\error.log</code></p>";
        echo "</div>";
        
        $conn->close();
        ?>
        
        <div class="mt-6 pt-4 border-t">
            <a href="check_approval_status.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Check Status</a>
            <a href="admin_login.php" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 ml-2">Back to Login</a>
        </div>
    </div>
</body>
</html>
