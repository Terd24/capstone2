-- Create table for teacher subject assignments
CREATE TABLE IF NOT EXISTS teacher_subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id VARCHAR(20) NOT NULL,
    subject_name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (teacher_id) REFERENCES employees(id_number) ON DELETE CASCADE,
    UNIQUE KEY unique_teacher_subject (teacher_id, subject_name)
);

-- Add index for faster queries
CREATE INDEX idx_teacher_id ON teacher_subjects(teacher_id);
