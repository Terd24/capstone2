<!DOCTYPE html>
<html>
<head>
    <title>Fix Old Requests</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-6">
        <h1 class="text-2xl font-bold mb-4">🔧 Fixing Old Approval Requests</h1>
        
        <?php
        require_once 'StudentLogin/db_conn.php';
        
        echo "<div class='space-y-4'>";
        
        // First, ensure the ENUM has the correct values
        echo "<h2 class='text-xl font-bold mt-6 mb-3'>Step 1: Update ENUM Values</h2>";
        
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
            echo "<div class='bg-green-50 border border-green-200 rounded p-3'>";
            echo "<p class='text-green-800'>✅ ENUM values updated successfully!</p>";
            echo "</div>";
        } else {
            echo "<div class='bg-red-50 border border-red-200 rounded p-3'>";
            echo "<p class='text-red-800'>❌ Failed: " . $conn->error . "</p>";
            echo "</div>";
        }
        
        // Find requests with empty request_type that have "Archive Login Logs" in title
        echo "<h2 class='text-xl font-bold mt-6 mb-3'>Step 2: Fix Old Requests</h2>";
        
        $find_query = "SELECT id, request_title, request_type, target_data FROM owner_approval_requests 
                       WHERE (request_type = '' OR request_type IS NULL) 
                       AND request_title LIKE '%Archive Login Logs%'";
        
        $find_result = $conn->query($find_query);
        
        if ($find_result && $find_result->num_rows > 0) {
            echo "<p class='text-yellow-800 mb-2'>Found " . $find_result->num_rows . " requests with empty request_type:</p>";
            
            while ($row = $find_result->fetch_assoc()) {
                echo "<div class='bg-yellow-50 border border-yellow-200 rounded p-3 mb-2'>";
                echo "<p class='text-sm'>Request #{$row['id']}: {$row['request_title']}</p>";
                echo "<p class='text-xs text-gray-600'>Current request_type: '{$row['request_type']}'</p>";
                
                // Update this request
                $update_sql = "UPDATE owner_approval_requests SET request_type = 'archive_login_logs' WHERE id = {$row['id']}";
                if ($conn->query($update_sql)) {
                    echo "<p class='text-green-600 text-sm mt-1'>✅ Fixed! Set to 'archive_login_logs'</p>";
                } else {
                    echo "<p class='text-red-600 text-sm mt-1'>❌ Failed to update: " . $conn->error . "</p>";
                }
                
                echo "</div>";
            }
        } else {
            echo "<div class='bg-blue-50 border border-blue-200 rounded p-3'>";
            echo "<p class='text-blue-800'>No old requests found with empty request_type</p>";
            echo "</div>";
        }
        
        // Also fix attendance requests
        $find_attendance = "SELECT id, request_title, request_type FROM owner_approval_requests 
                            WHERE (request_type = '' OR request_type IS NULL) 
                            AND request_title LIKE '%Archive Attendance%'";
        
        $attendance_result = $conn->query($find_attendance);
        
        if ($attendance_result && $attendance_result->num_rows > 0) {
            echo "<p class='text-yellow-800 mt-4 mb-2'>Found " . $attendance_result->num_rows . " attendance requests with empty request_type:</p>";
            
            while ($row = $attendance_result->fetch_assoc()) {
                $update_sql = "UPDATE owner_approval_requests SET request_type = 'archive_attendance' WHERE id = {$row['id']}";
                if ($conn->query($update_sql)) {
                    echo "<p class='text-green-600 text-sm'>✅ Fixed request #{$row['id']}</p>";
                }
            }
        }
        
        // Verify the fix
        echo "<h2 class='text-xl font-bold mt-6 mb-3'>Step 3: Verification</h2>";
        
        $verify = $conn->query("SELECT id, request_title, request_type, status FROM owner_approval_requests WHERE request_title LIKE '%Archive Login Logs%' ORDER BY id DESC LIMIT 5");
        
        if ($verify && $verify->num_rows > 0) {
            echo "<table class='w-full border text-sm'>";
            echo "<tr class='bg-gray-200'><th class='p-2'>ID</th><th class='p-2'>Title</th><th class='p-2'>Type</th><th class='p-2'>Status</th></tr>";
            
            while ($row = $verify->fetch_assoc()) {
                $type_color = !empty($row['request_type']) ? 'text-green-600' : 'text-red-600';
                echo "<tr>";
                echo "<td class='p-2 border'>{$row['id']}</td>";
                echo "<td class='p-2 border'>{$row['request_title']}</td>";
                echo "<td class='p-2 border $type_color font-semibold'>{$row['request_type']}</td>";
                echo "<td class='p-2 border'>{$row['status']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        echo "</div>";
        
        $conn->close();
        ?>
        
        <div class="mt-8 pt-6 border-t">
            <h2 class="text-xl font-bold mb-4">✅ Fix Complete!</h2>
            <p class="text-gray-700 mb-4">Old requests have been fixed. Now:</p>
            <ol class="list-decimal list-inside space-y-2 text-gray-700 mb-6">
                <li>Go to Owner Dashboard</li>
                <li>Find the approved requests</li>
                <li>Click "Re-process" or create a new request</li>
                <li>The archive should now work!</li>
            </ol>
            
            <div class="flex gap-4">
                <a href="debug_approval.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
                    Check Debug Page
                </a>
                <a href="admin_login.php" class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700">
                    Login as Owner
                </a>
            </div>
        </div>
    </div>
</body>
</html>
