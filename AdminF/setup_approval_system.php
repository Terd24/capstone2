<?php
/**
 * Setup Approval System for SuperAdmin Actions
 * Creates table for tracking approval requests from SuperAdmin to Owner
 */

require_once '../StudentLogin/db_conn.php';

// Create approval_requests table
$sql = "
CREATE TABLE IF NOT EXISTS approval_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_type ENUM('restore_student', 'restore_employee', 'permanent_delete_student', 'permanent_delete_employee') NOT NULL,
    record_id VARCHAR(50) NOT NULL,
    record_name VARCHAR(255),
    record_data JSON,
    requested_by VARCHAR(100) NOT NULL,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    reviewed_by VARCHAR(100) NULL,
    reviewed_at TIMESTAMP NULL,
    review_notes TEXT NULL,
    INDEX idx_status (status),
    INDEX idx_requested_at (requested_at),
    INDEX idx_record_id (record_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

try {
    if ($conn->query($sql)) {
        echo "✓ Approval requests table created successfully<br>";
    } else {
        echo "✗ Error creating approval requests table: " . $conn->error . "<br>";
    }
    
    echo "<br>Approval system setup complete!<br>";
    echo "<br>Next steps:<br>";
    echo "1. SuperAdmin can now request approval for restore/delete actions<br>";
    echo "2. Owner will see pending requests in their dashboard<br>";
    echo "3. Owner can approve or reject requests<br>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

$conn->close();
?>
