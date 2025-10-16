-- Create parent_notifications table for Kinder student parents
CREATE TABLE IF NOT EXISTS parent_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_id VARCHAR(50) NOT NULL,
    child_id VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    date_sent DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_read TINYINT(1) DEFAULT 0,
    INDEX idx_parent_child (parent_id, child_id),
    INDEX idx_is_read (is_read),
    INDEX idx_date_sent (date_sent)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample notification for testing (optional - remove in production)
-- INSERT INTO parent_notifications (parent_id, child_id, message) 
-- VALUES ('PARENT001', 'STUDENT001', 'Welcome to the Parent Portal! You can now request documents for your child.');
