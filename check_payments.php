<?php
// Check student_payments table structure and data
$conn = new mysqli("localhost", "root", "", "onecci_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>🔍 Student Payments Table Analysis</h2>";

// Check if table exists
$table_check = $conn->query("SHOW TABLES LIKE 'student_payments'");
if ($table_check && $table_check->num_rows > 0) {
    echo "<p style='color: green;'>✅ student_payments table exists</p>";
    
    // Show table structure
    echo "<h3>Table Structure:</h3>";
    $structure = $conn->query("DESCRIBE student_payments");
    if ($structure) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        while ($row = $structure->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Show total records
    $total_result = $conn->query("SELECT COUNT(*) as total FROM student_payments");
    if ($total_result) {
        $total = $total_result->fetch_assoc()['total'];
        echo "<h3>Total Records: $total</h3>";
    }
    
    // Show sample data (last 5 records)
    echo "<h3>Sample Data (Last 5 Records):</h3>";
    $sample = $conn->query("SELECT * FROM student_payments ORDER BY id DESC LIMIT 5");
    if ($sample && $sample->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        $first_row = true;
        while ($row = $sample->fetch_assoc()) {
            if ($first_row) {
                echo "<tr>";
                foreach (array_keys($row) as $col) {
                    echo "<th>" . htmlspecialchars($col) . "</th>";
                }
                echo "</tr>";
                $first_row = false;
            }
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ No payment records found</p>";
    }
    
    // Show today's payments
    $today = date('Y-m-d');
    echo "<h3>Today's Payments ($today):</h3>";
    
    // Try different date column possibilities
    $date_columns = ['payment_date', 'created_at', 'date_paid', 'timestamp', 'date'];
    $amount_columns = ['amount_paid', 'amount', 'payment_amount', 'total_amount'];
    
    $found_date_col = null;
    $found_amount_col = null;
    
    // Check which columns exist
    $columns_result = $conn->query("SHOW COLUMNS FROM student_payments");
    $available_columns = [];
    if ($columns_result) {
        while ($col = $columns_result->fetch_assoc()) {
            $available_columns[] = $col['Field'];
        }
    }
    
    foreach ($date_columns as $date_col) {
        if (in_array($date_col, $available_columns)) {
            $found_date_col = $date_col;
            break;
        }
    }
    
    foreach ($amount_columns as $amount_col) {
        if (in_array($amount_col, $available_columns)) {
            $found_amount_col = $amount_col;
            break;
        }
    }
    
    echo "<p><strong>Detected Date Column:</strong> " . ($found_date_col ?: 'NONE FOUND') . "</p>";
    echo "<p><strong>Detected Amount Column:</strong> " . ($found_amount_col ?: 'NONE FOUND') . "</p>";
    
    if ($found_date_col && $found_amount_col) {
        $today_count = $conn->query("SELECT COUNT(*) as count FROM student_payments WHERE DATE($found_date_col) = '$today'");
        $today_sum = $conn->query("SELECT SUM($found_amount_col) as total FROM student_payments WHERE DATE($found_date_col) = '$today'");
        
        if ($today_count) {
            $count = $today_count->fetch_assoc()['count'];
            echo "<p><strong>Payments Today:</strong> $count</p>";
        }
        
        if ($today_sum) {
            $sum = $today_sum->fetch_assoc()['total'] ?? 0;
            echo "<p><strong>Revenue Today:</strong> ₱" . number_format($sum, 2) . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Cannot calculate today's payments - missing required columns</p>";
    }
    
} else {
    echo "<p style='color: red;'>❌ student_payments table does not exist</p>";
    
    // Check for alternative payment tables
    echo "<h3>Looking for alternative payment tables:</h3>";
    $tables_result = $conn->query("SHOW TABLES LIKE '%payment%'");
    if ($tables_result && $tables_result->num_rows > 0) {
        while ($table = $tables_result->fetch_array()) {
            echo "<p>Found table: " . htmlspecialchars($table[0]) . "</p>";
        }
    } else {
        echo "<p>No payment-related tables found</p>";
    }
}

$conn->close();
?>

<p><a href="AdminF/SuperAdminDashboard.php">← Back to SuperAdmin Dashboard</a></p>
