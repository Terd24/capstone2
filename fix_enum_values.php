<!DOCTYPE html>
<html>
<head>
    <title>Fix ENUM Values</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-6">
        <h1 class="text-2xl font-bold mb-4">🔧 Fixing ENUM Values</h1>
        
        <?php
        require_once 'StudentLogin/db_conn.php';
        
        echo "<div class='space-y-4'>";
        
        // Fix the ENUM to include archive_login_logs and archive_attendance
        $sql = "ALTER TABLE owner_approval_requests 
                MODIFY COLUMN request_type ENUM(
                    'delete_account',
                    'restore_account',
                    'system_maintenance',
                    'data_modification',
                    'user_management',
                    'add_hr_employee',
                    'delete_hr_employee',
                    'restore_student',
                    'restore_employee',
                    'archive_student',
                    'archive_employee',
                    'archive_login_logs',
                    'archive_attendance',
                    'other'
                ) NOT NULL";
        
        if ($conn->query($sql)) {
            echo "<div class='bg-green-50 border-2 border-green-200 rounded-lg p-4'>";
            echo "<p class='text-green-800 font-semibold'>✅ Successfully updated ENUM values!</p>";
            echo "<p class='text-green-700 text-sm mt-1'>Added: archive_login_logs, archive_attendance</p>";
            echo "</div>";
        } else {
            echo "<div class='bg-red-50 border-2 border-red-200 rounded-lg p-4'>";
            echo "<p class='text-red-800 font-semibold'>❌ Failed to update ENUM values!</p>";
            echo "<p class='text-red-700 text-sm mt-1'>Error: " . $conn->error . "</p>";
            echo "</div>";
        }
        
        // Verify the fix
        echo "<h2 class='text-xl font-bold mt-6 mb-3'>Verification:</h2>";
        
        $check = $conn->query("SHOW COLUMNS FROM owner_approval_requests LIKE 'request_type'");
        if ($check && $row = $check->fetch_assoc()) {
            echo "<div class='bg-blue-50 border border-blue-200 rounded p-4'>";
            echo "<p class='font-semibold'>Updated request_type ENUM values:</p>";
            echo "<pre class='text-xs mt-2 bg-white p-2 rounded overflow-x-auto'>" . htmlspecialchars($row['Type']) . "</pre>";
            
            if (strpos($row['Type'], 'archive_login_logs') !== false) {
                echo "<p class='text-green-600 font-semibold mt-2'>✅ 'archive_login_logs' is now in the ENUM!</p>";
            }
            
            if (strpos($row['Type'], 'archive_attendance') !== false) {
                echo "<p class='text-green-600 font-semibold mt-1'>✅ 'archive_attendance' is now in the ENUM!</p>";
            }
            echo "</div>";
        }
        
        // Test insert
        echo "<h2 class='text-xl font-bold mt-6 mb-3'>Test Insert:</h2>";
        
        $test_title = "Test Archive Login Logs";
        $test_desc = "Test request to verify ENUM fix";
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
                echo "<div class='bg-green-50 border border-green-200 rounded p-4'>";
                echo "<p class='text-green-600 font-semibold'>✅ Test insert successful! ID: $insert_id</p>";
                echo "<p class='text-green-700 text-sm mt-1'>The table is now ready to accept archive_login_logs requests!</p>";
                echo "</div>";
                
                // Delete the test record
                $conn->query("DELETE FROM owner_approval_requests WHERE id = $insert_id");
                echo "<p class='text-gray-600 text-sm mt-2'>Test record deleted.</p>";
            } else {
                echo "<div class='bg-red-50 border border-red-200 rounded p-4'>";
                echo "<p class='text-red-600 font-semibold'>❌ Test insert failed!</p>";
                echo "<p class='text-red-700 text-sm mt-1'>Error: " . $test_stmt->error . "</p>";
                echo "</div>";
            }
            $test_stmt->close();
        }
        
        echo "</div>";
        
        $conn->close();
        ?>
        
        <div class="mt-8 pt-6 border-t">
            <h2 class="text-xl font-bold mb-4">✅ Fix Complete!</h2>
            <p class="text-gray-700 mb-4">The table has been updated. Now you can:</p>
            <ol class="list-decimal list-inside space-y-2 text-gray-700 mb-6">
                <li>Go to SuperAdmin Dashboard</li>
                <li>Request to clear login logs</li>
                <li>The request will now be created successfully!</li>
            </ol>
            
            <div class="flex gap-4">
                <a href="check_approval_status.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                    Check Approval Status
                </a>
                <a href="admin_login.php" class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition">
                    Login as SuperAdmin
                </a>
                <a href="TEST_ARCHIVE_NOW.html" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition">
                    Test Instructions
                </a>
            </div>
        </div>
    </div>
</body>
</html>
