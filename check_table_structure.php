<!DOCTYPE html>
<html>
<head>
    <title>Check Table Structure</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-6xl mx-auto bg-white rounded-lg shadow-lg p-6">
        <h1 class="text-2xl font-bold mb-4">🔍 Table Structure Check</h1>
        
        <?php
        require_once 'StudentLogin/db_conn.php';
        
        echo "<h2 class='text-xl font-bold mt-6 mb-3'>owner_approval_requests Table Structure:</h2>";
        
        $result = $conn->query("DESCRIBE owner_approval_requests");
        
        if ($result) {
            echo "<table class='w-full border text-sm'>";
            echo "<tr class='bg-gray-200'><th class='p-2'>Field</th><th class='p-2'>Type</th><th class='p-2'>Null</th><th class='p-2'>Key</th><th class='p-2'>Default</th></tr>";
            
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td class='p-2 border font-semibold'>{$row['Field']}</td>";
                echo "<td class='p-2 border'><code class='text-xs'>{$row['Type']}</code></td>";
                echo "<td class='p-2 border'>{$row['Null']}</td>";
                echo "<td class='p-2 border'>{$row['Key']}</td>";
                echo "<td class='p-2 border'>{$row['Default']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='text-red-600'>Error: " . $conn->error . "</p>";
        }
        
        echo "<h2 class='text-xl font-bold mt-6 mb-3'>Check if 'archive_login_logs' is in ENUM:</h2>";
        
        $check = $conn->query("SHOW COLUMNS FROM owner_approval_requests LIKE 'request_type'");
        if ($check && $row = $check->fetch_assoc()) {
            echo "<div class='bg-blue-50 border border-blue-200 rounded p-4'>";
            echo "<p class='font-semibold'>request_type ENUM values:</p>";
            echo "<pre class='text-xs mt-2 bg-white p-2 rounded'>" . htmlspecialchars($row['Type']) . "</pre>";
            
            if (strpos($row['Type'], 'archive_login_logs') !== false) {
                echo "<p class='text-green-600 font-semibold mt-2'>✅ 'archive_login_logs' is in the ENUM!</p>";
            } else {
                echo "<p class='text-red-600 font-semibold mt-2'>❌ 'archive_login_logs' is NOT in the ENUM!</p>";
                echo "<p class='text-red-600 text-sm mt-1'>This is the problem! The table needs to be updated.</p>";
            }
            echo "</div>";
        }
        
        echo "<h2 class='text-xl font-bold mt-6 mb-3'>Test Insert:</h2>";
        
        $test_title = "Test Archive Login Logs";
        $test_desc = "Test request";
        $test_type = 'archive_login_logs';
        $test_priority = 'medium';
        $test_requester = 'Test User';
        $test_role = 'superadmin';
        $test_module = 'Test';
        $test_target_id = 'test';
        $test_data = json_encode(['test' => 'data']);
        
        $test_stmt = $conn->prepare("INSERT INTO owner_approval_requests (request_title, request_description, request_type, priority, requester_name, requester_role, requester_module, target_id, target_data, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        
        if ($test_stmt) {
            $test_stmt->bind_param("sssssssss", $test_title, $test_desc, $test_type, $test_priority, $test_requester, $test_role, $test_module, $test_target_id, $test_data);
            
            if ($test_stmt->execute()) {
                $insert_id = $test_stmt->insert_id;
                echo "<p class='text-green-600 font-semibold'>✅ Test insert successful! ID: $insert_id</p>";
                
                // Delete the test record
                $conn->query("DELETE FROM owner_approval_requests WHERE id = $insert_id");
                echo "<p class='text-gray-600 text-sm'>Test record deleted.</p>";
            } else {
                echo "<p class='text-red-600 font-semibold'>❌ Test insert failed!</p>";
                echo "<p class='text-red-600 text-sm'>Error: " . $test_stmt->error . "</p>";
            }
            $test_stmt->close();
        } else {
            echo "<p class='text-red-600 font-semibold'>❌ Failed to prepare statement!</p>";
            echo "<p class='text-red-600 text-sm'>Error: " . $conn->error . "</p>";
        }
        
        $conn->close();
        ?>
        
        <div class="mt-6 pt-4 border-t">
            <a href="check_approval_status.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Check Approval Status</a>
            <a href="admin_login.php" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 ml-2">Back to Login</a>
        </div>
    </div>
</body>
</html>
