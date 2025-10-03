-- Create owner_accounts table for school owner login
CREATE TABLE IF NOT EXISTS owner_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert a default owner account (password: owner123)
-- You should change this password after first login
INSERT IGNORE INTO owner_accounts (username, password, full_name, email) 
VALUES ('owner', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'School Owner', 'owner@cornerstonecollegeinc.com');

-- Note: The password hash above is for 'owner123'
-- To create a new password hash, use: password_hash('your_password', PASSWORD_DEFAULT) in PHP
