-- Telecare Services Database Schema
-- Contact Form Submissions Storage

CREATE DATABASE IF NOT EXISTS telecare_db 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE telecare_db;

-- Form submissions table
CREATE TABLE IF NOT EXISTS form_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_type ENUM('contact', 'referral', 'complaint') NOT NULL,
    data JSON NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    status ENUM('new', 'in_progress', 'completed', 'archived') DEFAULT 'new',
    assigned_to VARCHAR(100) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    submitted_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_form_type (form_type),
    INDEX idx_status (status),
    INDEX idx_submitted_at (submitted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin users table (for managing submissions)
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'staff', 'viewer') DEFAULT 'staff',
    is_active BOOLEAN DEFAULT TRUE,
    last_login DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity log table
CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    submission_id INT DEFAULT NULL,
    action VARCHAR(50) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE SET NULL,
    FOREIGN KEY (submission_id) REFERENCES form_submissions(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_submission_id (submission_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Email queue table (for reliable email delivery)
CREATE TABLE IF NOT EXISTS email_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    submission_id INT NOT NULL,
    recipient_email VARCHAR(100) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    last_attempt DATETIME DEFAULT NULL,
    sent_at DATETIME DEFAULT NULL,
    error_message TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (submission_id) REFERENCES form_submissions(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create default admin user (password: Admin@2026! - CHANGE THIS!)
INSERT INTO admin_users (username, email, password_hash, full_name, role) 
VALUES (
    'admin',
    'admin@telecareservices.org',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- Hash of 'Admin@2026!'
    'System Administrator',
    'admin'
);

-- Sample queries for common operations

-- Get all new submissions
-- SELECT * FROM form_submissions WHERE status = 'new' ORDER BY submitted_at DESC;

-- Get contact form submissions from last 7 days
-- SELECT * FROM form_submissions 
-- WHERE form_type = 'contact' 
-- AND submitted_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
-- ORDER BY submitted_at DESC;

-- Get submission statistics
-- SELECT 
--     form_type,
--     status,
--     COUNT(*) as count
-- FROM form_submissions
-- GROUP BY form_type, status;

-- Get pending emails
-- SELECT * FROM email_queue WHERE status = 'pending' ORDER BY created_at ASC LIMIT 10;
