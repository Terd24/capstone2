-- Create system_logs table for tracking export actions
CREATE TABLE IF NOT EXISTS system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    performed_by VARCHAR(100),
    performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    INDEX idx_action (action),
    INDEX idx_performed_by (performed_by),
    INDEX idx_performed_at (performed_at)
);

-- Insert sample log entry
INSERT INTO system_logs (action, details, performed_by) 
VALUES ('SYSTEM_SETUP', 'System logs table created for deleted account exports', 'System');
