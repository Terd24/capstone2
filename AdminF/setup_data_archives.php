<?php
/**
 * Setup Archive Tables for Login Logs and Attendance Records
 * Run this once to create the archive tables
 */

require_once '../StudentLogin/db_conn.php';

// Create login_logs_archive table
$login_archive_sql = "
CREATE TABLE IF NOT EXISTS login_logs_archive (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_id INT,
    username VARCHAR(100),
    login_time DATETIME,
    ip_address VARCHAR(45),
    user_agent TEXT,
    user_type VARCHAR(50),
    status VARCHAR(20),
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archived_by VARCHAR(100),
    archived_reason VARCHAR(255),
    INDEX idx_username (username),
    INDEX idx_login_time (login_time),
    INDEX idx_archived_at (archived_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

// Create attendance_archive table
$attendance_archive_sql = "
CREATE TABLE IF NOT EXISTS attendance_archive (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_id INT,
    id_number VARCHAR(50),
    name VARCHAR(255),
    date DATE,
    time_in TIME,
    time_out TIME,
    status VARCHAR(50),
    user_type VARCHAR(50),
    archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    archived_by VARCHAR(100),
    archived_reason VARCHAR(255),
    INDEX idx_id_number (id_number),
    INDEX idx_date (date),
    INDEX idx_archived_at (archived_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

try {
    // Create login logs archive table
    if ($conn->query($login_archive_sql)) {
        echo "✓ Login logs archive table created successfully<br>";
    } else {
        echo "✗ Error creating login logs archive table: " . $conn->error . "<br>";
    }
    
    // Create attendance archive table
    if ($conn->query($attendance_archive_sql)) {
        echo "✓ Attendance archive table created successfully<br>";
    } else {
        echo "✗ Error creating attendance archive table: " . $conn->error . "<br>";
    }
    
    echo "<br>Archive tables setup complete!";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

$conn->close();
?>
