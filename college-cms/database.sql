-- Database: college_cms

CREATE DATABASE IF NOT EXISTS college_cms;
USE college_cms;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'admin', 'dept_admin') DEFAULT 'student',
    university_reg_no VARCHAR(50),
    department VARCHAR(100) DEFAULT NULL,
    id_card_path VARCHAR(255),
    is_verified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Valid Students table (Pre-authorized USNs)
CREATE TABLE IF NOT EXISTS valid_students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usn VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- OTP Codes table for login
CREATE TABLE IF NOT EXISTS otp_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    otp_hash VARCHAR(255) NOT NULL,
    attempts INT DEFAULT 0,
    expires_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Email Verifications table (for optional use)
CREATE TABLE IF NOT EXISTS email_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    otp VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Complaints table
CREATE TABLE IF NOT EXISTS complaints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category VARCHAR(100) NOT NULL,
    subcategory VARCHAR(100),
    is_anonymous TINYINT(1) DEFAULT 0,
    description TEXT NOT NULL,
    file_path VARCHAR(255),
    priority VARCHAR(20) DEFAULT 'Medium',
    status ENUM('Pending', 'In Progress', 'Under Review', 'Resolved') DEFAULT 'Pending',
    remarks TEXT,
    forwarded_to_main TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL DEFAULT NULL,
    resolved_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Ensure existing deployments also get timeline columns/status option.
ALTER TABLE complaints
    MODIFY COLUMN status ENUM('Pending', 'In Progress', 'Under Review', 'Resolved') DEFAULT 'Pending';
ALTER TABLE complaints
    ADD COLUMN IF NOT EXISTS reviewed_at TIMESTAMP NULL DEFAULT NULL;
ALTER TABLE complaints
    ADD COLUMN IF NOT EXISTS resolved_at TIMESTAMP NULL DEFAULT NULL;
ALTER TABLE complaints
    ADD COLUMN IF NOT EXISTS forwarded_to_main TINYINT(1) DEFAULT 0;

-- Insert a default admin (Password: admin123)
-- Note: In a real app, use password_hash() in PHP to generate this.
-- For this script, we'll assume the PHP registration handles it, 
-- but here is a manual insert with a hashed password for 'admin123'
INSERT INTO users (name, email, password, role, is_verified) 
VALUES ('System Admin', 'admin@college.edu', '$2y$10$4C.I3TBvIhCPHjDczTzl0OIhP.u1/VPQ/umX1n3vqIlPtxveuvkNi', 'admin', 1)
ON DUPLICATE KEY UPDATE role='admin';

-- Department Admins (Password: dept123)
INSERT IGNORE INTO users (name, email, password, role, department, is_verified) VALUES 
('Sexual Harassment Cell Admin', 'sexual-harassment@college.edu', '$2y$10$VN2pYlb6ILmqqEr/QkfiMOcCcA9y7c7AfFDgaP9cG05O1.lqMCq8G', 'dept_admin', 'Anti-Sexual Harassment Cell', 1),
('Anti-Ragging Cell Admin', 'ragging@college.edu', '$2y$10$VN2pYlb6ILmqqEr/QkfiMOcCcA9y7c7AfFDgaP9cG05O1.lqMCq8G', 'dept_admin', 'Anti-Ragging Cell', 1),
('Anti-Harassment Cell Admin', 'harassment@college.edu', '$2y$10$VN2pYlb6ILmqqEr/QkfiMOcCcA9y7c7AfFDgaP9cG05O1.lqMCq8G', 'dept_admin', 'Anti-Harassment Cell', 1),
('Grievance Cell Admin', 'grievance@college.edu', '$2y$10$VN2pYlb6ILmqqEr/QkfiMOcCcA9y7c7AfFDgaP9cG05O1.lqMCq8G', 'dept_admin', 'Grievance Cell', 1),
('Hygiene/Facility Cell Admin', 'hygiene@college.edu', '$2y$10$VN2pYlb6ILmqqEr/QkfiMOcCcA9y7c7AfFDgaP9cG05O1.lqMCq8G', 'dept_admin', 'Hygiene/Facility Cell', 1),
('Disciplinary Committee Admin', 'discipline@college.edu', '$2y$10$VN2pYlb6ILmqqEr/QkfiMOcCcA9y7c7AfFDgaP9cG05O1.lqMCq8G', 'dept_admin', 'Disciplinary Committee', 1);
