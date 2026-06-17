-- Run this to add all department admins and necessary schema updates
USE college_cms;

-- 1. Ensure schema supports Department Admins
ALTER TABLE users MODIFY COLUMN role ENUM('student', 'admin', 'dept_admin') DEFAULT 'student';
ALTER TABLE users ADD COLUMN IF NOT EXISTS department VARCHAR(100) DEFAULT NULL;

-- 2. Insert/Update Department Admins
-- Default Password for all: dept123
INSERT INTO users (name, email, password, role, department, is_verified) VALUES 
('Sexual Harassment Cell Admin', 'sexual-harassment@college.edu', '$2y$10$VN2pYlb6ILmqqEr/QkfiMOcCcA9y7c7AfFDgaP9cG05O1.lqMCq8G', 'dept_admin', 'Anti-Sexual Harassment Cell', 1),
('Anti-Ragging Cell Admin', 'ragging@college.edu', '$2y$10$VN2pYlb6ILmqqEr/QkfiMOcCcA9y7c7AfFDgaP9cG05O1.lqMCq8G', 'dept_admin', 'Anti-Ragging Cell', 1),
('Anti-Harassment Cell Admin', 'harassment@college.edu', '$2y$10$VN2pYlb6ILmqqEr/QkfiMOcCcA9y7c7AfFDgaP9cG05O1.lqMCq8G', 'dept_admin', 'Anti-Harassment Cell', 1),
('Grievance Cell Admin', 'grievance@college.edu', '$2y$10$VN2pYlb6ILmqqEr/QkfiMOcCcA9y7c7AfFDgaP9cG05O1.lqMCq8G', 'dept_admin', 'Grievance Cell', 1),
('Hygiene/Facility Cell Admin', 'hygiene@college.edu', '$2y$10$VN2pYlb6ILmqqEr/QkfiMOcCcA9y7c7AfFDgaP9cG05O1.lqMCq8G', 'dept_admin', 'Hygiene/Facility Cell', 1),
('Disciplinary Committee Admin', 'discipline@college.edu', '$2y$10$VN2pYlb6ILmqqEr/QkfiMOcCcA9y7c7AfFDgaP9cG05O1.lqMCq8G', 'dept_admin', 'Disciplinary Committee', 1)
ON DUPLICATE KEY UPDATE 
    password = VALUES(password),
    role = VALUES(role), 
    department = VALUES(department),
    is_verified = 1;
