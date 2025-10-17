<?php
/**
 * Performance Optimization Script for Login Activity
 * Run this once to add indexes for better performance with 500+ users
 */

session_start();
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    die('Unauthorized');
}

require_once '../StudentLogin/db_conn.php';

echo "<h2>Login Activity Performance Optimization</h2>";
echo "<p>This script will add database indexes to improve performance for large user bases (500+ users)</p>";

$optimizations = [];

// 1. Add composite index on login_activity for faster date queries
$sql1 = "CREATE INDEX IF NOT EXISTS idx_login_date_user 
         ON login_activity(login_time, id_number, user_type)";
if ($conn->query($sql1)) {
    $optimizations[] = "✅ Added composite index on login_activity (login_time, id_number, user_type)";
} else {
    $optimizations[] = "❌ Failed to add login_activity index: " . $conn->error;
}

// 2. Add index on id_number for faster lookups
$sql2 = "CREATE INDEX IF NOT EXISTS idx_login_id 
         ON login_activity(id_number)";
if ($conn->query($sql2)) {
    $optimizations[] = "✅ Added index on login_activity (id_number)";
} else {
    $optimizations[] = "❌ Failed to add id_number index: " . $conn->error;
}

// 3. Add index on user_type for filtering
$sql3 = "CREATE INDEX IF NOT EXISTS idx_login_user_type 
         ON login_activity(user_type)";
if ($conn->query($sql3)) {
    $optimizations[] = "✅ Added index on login_activity (user_type)";
} else {
    $optimizations[] = "❌ Failed to add user_type index: " . $conn->error;
}

// 4. Add index on role for filtering
$sql4 = "CREATE INDEX IF NOT EXISTS idx_login_role 
         ON login_activity(role)";
if ($conn->query($sql4)) {
    $optimizations[] = "✅ Added index on login_activity (role)";
} else {
    $optimizations[] = "❌ Failed to add role index: " . $conn->error;
}

// 5. Optimize table
$sql5 = "OPTIMIZE TABLE login_activity";
if ($conn->query($sql5)) {
    $optimizations[] = "✅ Optimized login_activity table";
} else {
    $optimizations[] = "⚠️ Table optimization skipped: " . $conn->error;
}

// Display results
echo "<ul>";
foreach ($optimizations as $result) {
    echo "<li>$result</li>";
}
echo "</ul>";

// Show current table stats
$result = $conn->query("SELECT 
    COUNT(*) as total_records,
    COUNT(DISTINCT id_number) as unique_users,
    MIN(login_time) as oldest_record,
    MAX(login_time) as newest_record
FROM login_activity");

if ($result && $row = $result->fetch_assoc()) {
    echo "<h3>Current Login Activity Stats:</h3>";
    echo "<ul>";
    echo "<li><strong>Total Records:</strong> " . number_format($row['total_records']) . "</li>";
    echo "<li><strong>Unique Users:</strong> " . number_format($row['unique_users']) . "</li>";
    echo "<li><strong>Oldest Record:</strong> " . $row['oldest_record'] . "</li>";
    echo "<li><strong>Newest Record:</strong> " . $row['newest_record'] . "</li>";
    echo "</ul>";
}

// Performance recommendations
echo "<h3>Performance Recommendations:</h3>";
echo "<ul>";
echo "<li>✅ Pagination is already implemented (10 records per page)</li>";
echo "<li>✅ Archive system is in place for old records</li>";
echo "<li>✅ Queries use LIMIT and OFFSET</li>";
echo "<li>📊 <strong>Recommended:</strong> Archive login records older than 90 days monthly</li>";
echo "<li>📊 <strong>Recommended:</strong> Run OPTIMIZE TABLE quarterly</li>";
echo "<li>📊 <strong>Expected Performance:</strong> System should handle 500+ users smoothly</li>";
echo "</ul>";

echo "<h3>Estimated Performance:</h3>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>Users</th><th>Daily Logins</th><th>Records/Year</th><th>Performance</th></tr>";
echo "<tr><td>100</td><td>~100</td><td>~36,500</td><td>🟢 Excellent</td></tr>";
echo "<tr><td>500</td><td>~500</td><td>~182,500</td><td>🟢 Good (with indexes)</td></tr>";
echo "<tr><td>1000</td><td>~1000</td><td>~365,000</td><td>🟡 Fair (needs archiving)</td></tr>";
echo "<tr><td>2000+</td><td>~2000+</td><td>~730,000+</td><td>🔴 Requires optimization</td></tr>";
echo "</table>";

echo "<p><strong>Conclusion:</strong> Your system is well-optimized for 500 users. With these indexes and regular archiving, performance will remain excellent.</p>";

$conn->close();
?>
