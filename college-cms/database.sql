-- Database: college_cms

CREATE DATABASE IF NOT EXISTS college_cms;
USE college_cms;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'admin') DEFAULT 'student',
    university_reg_no VARCHAR(50),
    id_card_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Complaints table
CREATE TABLE IF NOT EXISTS complaints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    file_path VARCHAR(255),
    status ENUM('Pending', 'In Progress', 'Resolved') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert a default admin (Password: admin123)
-- Note: In a real app, use password_hash() in PHP to generate this.
-- For this script, we'll use a bcrypt hash for 'admin123'.
INSERT INTO users (name, email, password, role) 
VALUES ('System Admin', 'admin@college.edu', '$2b$12$tpF3okFAuMgtka.xMuUbEOHRq29DjddhsVsHil4kxJnkbSTlP/3YK', 'admin')
ON DUPLICATE KEY UPDATE 
    name = VALUES(name),
    password = VALUES(password),
    role = VALUES(role);
