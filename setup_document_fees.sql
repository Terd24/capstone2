-- Setup Document Fees System
-- Run this SQL script to create the document_fees table

-- Create document_fees table
CREATE TABLE IF NOT EXISTS document_fees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_name VARCHAR(100) NOT NULL UNIQUE,
    fee_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_document_name (document_name),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional: Insert default fees for common documents
-- Uncomment and modify as needed

-- INSERT INTO document_fees (document_name, fee_amount) VALUES
-- ('Form 137', 50.00),
-- ('Transcript of Records', 100.00),
-- ('Certificate of Enrollment', 25.00),
-- ('Good Moral Certificate', 25.00),
-- ('Diploma', 150.00),
-- ('Honorable Dismissal', 50.00)
-- ON DUPLICATE KEY UPDATE fee_amount = VALUES(fee_amount);

-- Verify table creation
SELECT 'Document fees table created successfully!' as status;
SELECT * FROM document_fees;
