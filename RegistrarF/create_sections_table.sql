-- Create sections table for managing section names
CREATE TABLE IF NOT EXISTS sections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    section_name VARCHAR(100) UNIQUE NOT NULL,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    INDEX idx_section_name (section_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert existing sections from class_schedules (migration)
INSERT IGNORE INTO sections (section_name, description)
SELECT DISTINCT section_name, 'Migrated from existing schedules'
FROM class_schedules
WHERE section_name IS NOT NULL AND section_name != '';
