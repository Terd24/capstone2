<?php
// Dashboard Data Processing
// This file handles all database queries and data processing for the dashboard

// Database connection should be available from the parent file
// $conn should be available from the parent file

// Utility function to check if table exists
function table_exists($conn, $table) {
    if ($stmt = $conn->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1")) {
        $stmt->bind_param('s', $table);
        $stmt->execute();
        $res = $stmt->get_result();
        $exists = $res && $res->num_rows > 0;
        $stmt->close();
        return $exists;
    }
    return false;
}

// Utility function for scalar queries
function q_scalar($conn, $sql, $types = '', ...$params) {
    $val = null;
    if ($stmt = $conn->prepare($sql)) {
        if ($types) { 
            $stmt->bind_param($types, ...$params); 
        }
        if ($stmt->execute()) {
            $res = $stmt->get_result();
            if ($res && $row = $res->fetch_row()) { 
                $val = $row[0]; 
            }
        }
        $stmt->close();
    }
    return $val;
}

// Get current date
date_default_timezone_set('Asia/Manila');
$today = date('Y-m-d');

// Ensure login_activity table exists
$conn->query("CREATE TABLE IF NOT EXISTS login_activity (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_type VARCHAR(20) NOT NULL,
  id_number VARCHAR(50) NOT NULL,
  username VARCHAR(100) NOT NULL,
  role VARCHAR(50) NOT NULL,
  login_time DATETIME NOT NULL,
  session_id VARCHAR(128) NULL,
  INDEX idx_login_date (login_time),
  INDEX idx_id_number (id_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// 1. BASIC STATISTICS
$total_students = (int) ($conn->query("SELECT COUNT(*) FROM student_account")->fetch_row()[0] ?? 0);
$total_employees = 0;
if (table_exists($conn, 'employees')) {
    // Count only teachers from employee_accounts table
    if (table_exists($conn, 'employee_accounts')) {
        $total_employees = (int) (q_scalar($conn, "SELECT COUNT(*) FROM employee_accounts WHERE role = 'teacher'") ?? 0);
    } else {
        // Fallback: count employees with role 'teacher' if employee_accounts doesn't exist
        $total_employees = (int) (q_scalar($conn, "SELECT COUNT(*) FROM employees WHERE role = 'teacher'") ?? 0);
    }
}

// 2. ENROLLMENT BY GRADE LEVEL
$enrollment_by_grade = [];
if (table_exists($conn, 'student_account')) {
    $grade_result = $conn->query("SELECT grade_level, COUNT(*) as count FROM student_account GROUP BY grade_level ORDER BY grade_level");
    if ($grade_result) {
        while ($row = $grade_result->fetch_assoc()) {
            $enrollment_by_grade[] = $row;
        }
    }
}

// 3. PAYMENT STATISTICS
$total_payments_today = 0;
$total_revenue_today = 0;
$pending_balances = 0;
$total_revenue_month = 0;

if (table_exists($conn, 'student_payments')) {
    try {
        // Get column information
        $columns_result = $conn->query("SHOW COLUMNS FROM student_payments");
        $available_columns = [];
        if ($columns_result) {
            while ($col = $columns_result->fetch_assoc()) {
                $available_columns[] = $col['Field'];
            }
        }
        
        // Determine correct date and amount columns
        $payment_date_col = null;
        $amount_col = null;
        
        // Find date column
        foreach (['payment_date', 'created_at', 'date_paid', 'timestamp', 'date'] as $col) {
            if (in_array($col, $available_columns)) {
                $payment_date_col = $col;
                break;
            }
        }
        
        // Find amount column
        foreach (['amount_paid', 'amount', 'payment_amount', 'total_amount'] as $col) {
            if (in_array($col, $available_columns)) {
                $amount_col = $col;
                break;
            }
        }
        
        // Execute queries if columns found
        if ($payment_date_col && $amount_col) {
            $total_payments_today = (int) (q_scalar($conn, "SELECT COUNT(*) FROM student_payments WHERE DATE($payment_date_col) = ?", 's', $today) ?? 0);
            $total_revenue_today = (float) (q_scalar($conn, "SELECT SUM($amount_col) FROM student_payments WHERE DATE($payment_date_col) = ?", 's', $today) ?? 0);
            $total_revenue_month = (float) (q_scalar($conn, "SELECT SUM($amount_col) FROM student_payments WHERE MONTH($payment_date_col) = MONTH(CURDATE()) AND YEAR($payment_date_col) = YEAR(CURDATE())", '') ?? 0);
        }
    } catch (Exception $e) {
        error_log("Payment statistics error: " . $e->getMessage());
    }
}

// 4. ATTENDANCE STATISTICS
$student_attendance_today = 0;
$student_present_today = 0;
$student_absent_today = $total_students;

if (table_exists($conn, 'attendance_record')) {
    try {
        $student_attendance_today = (int) (q_scalar($conn, "SELECT COUNT(*) FROM attendance_record WHERE date = ? AND (time_in IS NOT NULL OR time_out IS NOT NULL)", 's', $today) ?? 0);
        $student_present_today = (int) (q_scalar($conn, "SELECT COUNT(*) FROM attendance_record WHERE date = ? AND time_in IS NOT NULL", 's', $today) ?? 0);
        $student_absent_today = $total_students - $student_present_today;
    } catch (Exception $e) {
        error_log("Student attendance error: " . $e->getMessage());
    }
}

// Teacher Attendance (only teachers, not all employees)
$employee_attendance_today = 0;
$employee_present_today = 0;
$employee_absent_today = $total_employees;

// Use teacher_attendance table if available, otherwise try employee_attendance with teacher filter
if (table_exists($conn, 'teacher_attendance')) {
    try {
        $employee_attendance_today = (int) (q_scalar($conn, "SELECT COUNT(*) FROM teacher_attendance WHERE date = ? AND (time_in IS NOT NULL OR time_out IS NOT NULL)", 's', $today) ?? 0);
        $employee_present_today = (int) (q_scalar($conn, "SELECT COUNT(*) FROM teacher_attendance WHERE date = ? AND time_in IS NOT NULL", 's', $today) ?? 0);
        $employee_absent_today = $total_employees - $employee_present_today;
    } catch (Exception $e) {
        error_log("Teacher attendance error: " . $e->getMessage());
    }
} elseif (table_exists($conn, 'employee_attendance') && table_exists($conn, 'employee_accounts')) {
    try {
        // Join with employee_accounts to filter only teachers
        $employee_attendance_today = (int) (q_scalar($conn, "SELECT COUNT(*) FROM employee_attendance ea JOIN employee_accounts acc ON ea.teacher_id = acc.employee_id WHERE ea.date = ? AND acc.role = 'teacher' AND (ea.time_in IS NOT NULL OR ea.time_out IS NOT NULL)", 's', $today) ?? 0);
        $employee_present_today = (int) (q_scalar($conn, "SELECT COUNT(*) FROM employee_attendance ea JOIN employee_accounts acc ON ea.teacher_id = acc.employee_id WHERE ea.date = ? AND acc.role = 'teacher' AND ea.time_in IS NOT NULL", 's', $today) ?? 0);
        $employee_absent_today = $total_employees - $employee_present_today;
    } catch (Exception $e) {
        error_log("Teacher attendance error: " . $e->getMessage());
    }
}

// 5. SYSTEM HEALTH
$db_size = 0;
$table_count = 0;
$total_records = 0;

try {
    $db_info = $conn->query("SELECT 
        ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS db_size_mb,
        COUNT(*) as table_count
        FROM information_schema.tables 
        WHERE table_schema = DATABASE()");
    
    if ($db_info && $row = $db_info->fetch_assoc()) {
        $db_size = $row['db_size_mb'] ?? 0;
        $table_count = $row['table_count'] ?? 0;
    }
} catch (Exception $e) {
    error_log("Database info error: " . $e->getMessage());
}

// Count total records in main tables
$main_tables = ['student_account', 'attendance_record', 'grades_record'];
foreach ($main_tables as $table) {
    if (table_exists($conn, $table)) {
        try {
            $count = (int) ($conn->query("SELECT COUNT(*) FROM `$table`")->fetch_row()[0] ?? 0);
            $total_records += $count;
        } catch (Exception $e) {
            error_log("Table count error for $table: " . $e->getMessage());
        }
    }
}

// 6. LOGIN ACTIVITY
$today_logins = [];
$active_users = [];

// Get today's logins with names
if (table_exists($conn, 'login_activity')) {
    try {
        // Query to get login activity with names from appropriate tables
        $login_query = "
            SELECT 
                la.user_type, 
                la.id_number, 
                la.username, 
                la.role, 
                la.login_time,
                CASE 
                    WHEN la.user_type = 'student' THEN CONCAT(s.first_name, ' ', s.last_name)
                    WHEN la.user_type = 'employee' THEN CONCAT(e.first_name, ' ', e.last_name)
                    ELSE la.username
                END as full_name
            FROM login_activity la
            LEFT JOIN student_account s ON la.id_number = s.id_number AND la.user_type = 'student'
            LEFT JOIN employees e ON la.id_number = e.id_number AND la.user_type = 'employee'
            WHERE DATE(la.login_time) = ?
            ORDER BY la.login_time DESC 
            LIMIT 50
        ";
        
        if ($stmt = $conn->prepare($login_query)) {
            $stmt->bind_param('s', $today);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $today_logins[] = $row;
            }
            $stmt->close();
        }
        
        // Get active users (last 15 minutes)
        if ($stmt = $conn->prepare("SELECT user_type, id_number, username, role, login_time FROM login_activity WHERE login_time >= (NOW() - INTERVAL 15 MINUTE) ORDER BY login_time DESC")) {
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $active_users[] = $row;
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        error_log("Login activity error: " . $e->getMessage());
    }
}

// 7. DELETED RECORDS
$deleted_students = [];
$deleted_employees = [];

// Get deleted students
if (table_exists($conn, 'student_account')) {
    try {
        $stmt = $conn->prepare("SELECT id_number, first_name, last_name, middle_name, grade_level, academic_track, deleted_at, deleted_by, deleted_reason FROM student_account WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC LIMIT 100");
        if ($stmt) {
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $deleted_students[] = $row;
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        // Table might not have soft delete columns yet
        error_log("Deleted students error: " . $e->getMessage());
    }
}

// Get deleted employees
if (table_exists($conn, 'employees')) {
    try {
        $stmt = $conn->prepare("SELECT id_number, first_name, last_name, middle_name, position, department, deleted_at, deleted_by, deleted_reason FROM employees WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC LIMIT 100");
        if ($stmt) {
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $deleted_employees[] = $row;
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        // Table might not have soft delete columns yet
        error_log("Deleted employees error: " . $e->getMessage());
    }
}

// 8. SERVER PERFORMANCE
$server_uptime = 0;
$connections = 0;

try {
    $uptime_result = $conn->query("SHOW STATUS LIKE 'Uptime'");
    if ($uptime_result && $row = $uptime_result->fetch_assoc()) {
        $server_uptime = (int) $row['Value'];
    }
    
    $connections_result = $conn->query("SHOW STATUS LIKE 'Threads_connected'");
    if ($connections_result && $row = $connections_result->fetch_assoc()) {
        $connections = (int) $row['Value'];
    }
} catch (Exception $e) {
    error_log("Server performance error: " . $e->getMessage());
}

// Format server uptime
function formatUptime($seconds) {
    $days = floor($seconds / 86400);
    $hours = floor(($seconds % 86400) / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    
    if ($days > 0) {
        return "{$days}d {$hours}h {$minutes}m";
    } elseif ($hours > 0) {
        return "{$hours}h {$minutes}m";
    } else {
        return "{$minutes}m";
    }
}

$formatted_uptime = formatUptime($server_uptime);
?>
