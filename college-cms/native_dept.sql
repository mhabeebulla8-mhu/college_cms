-- Native integration of Department Admin features
USE college_cms;

-- 1. Upgrade Roles and Departments
ALTER TABLE users MODIFY COLUMN role ENUM('student', 'admin', 'dept_admin') DEFAULT 'student';
ALTER TABLE users ADD COLUMN IF NOT EXISTS department VARCHAR(100) DEFAULT NULL;

-- 2. Add features to Complaints
ALTER TABLE complaints ADD COLUMN IF NOT EXISTS remarks TEXT DEFAULT NULL;
ALTER TABLE complaints ADD COLUMN IF NOT EXISTS forwarded_to_main TINYINT(1) DEFAULT 0;
ALTER TABLE complaints MODIFY COLUMN status ENUM('Pending', 'In Progress', 'Under Review', 'Resolved') DEFAULT 'Pending';
ALTER TABLE complaints ADD COLUMN IF NOT EXISTS reviewed_at TIMESTAMP NULL DEFAULT NULL;
ALTER TABLE complaints ADD COLUMN IF NOT EXISTS resolved_at TIMESTAMP NULL DEFAULT NULL;

-- 3. Create Department Admins
-- All passwords are: dept123
-- (Hash: $2y$10$VN2pYlb6ILmqqEr/QkfiMOcCcA9y7c7AfFDgaP9cG05O1.lqMCq8G)

-- Anti-Sexual Harassment Cell
INSERT IGNORE INTO users (name, email, password, role, department, is_verified) 
VALUES ('Sexual Harassment Cell Admin', 'sexual-harassment@college.edu', '$2y$10$VN2pYlb6ILmqqEr/QkfiMOcCcA9y7c7AfFDgaP9cG05O1.lqMCq8G', 'dept_admin', 'Anti-Sexual Harassment Cell', 1);

-- Anti-Ragging Cell
INSERT IGNORE INTO users (name, email, password, role, department, is_verified) 
VALUES ('Anti-Ragging Cell Admin', 'ragging@college.edu', '$2y$10$VN2pYlb6ILmqqEr/QkfiMOcCcA9y7c7AfFDgaP9cG05O1.lqMCq8G', 'dept_admin', 'Anti-Ragging Cell', 1);

-- Anti-Harassment Cell
INSERT IGNORE INTO users (name, email, password, role, department, is_verified) 
VALUES ('Anti-Harassment Cell Admin', 'harassment@college.edu', '$2y$10$VN2pYlb6ILmqqEr/QkfiMOcCcA9y7c7AfFDgaP9cG05O1.lqMCq8G', 'dept_admin', 'Anti-Harassment Cell', 1);

-- Grievance Cell
INSERT IGNORE INTO users (name, email, password, role, department, is_verified) 
VALUES ('Grievance Cell Admin', 'grievance@college.edu', '$2y$10$VN2pYlb6ILmqqEr/QkfiMOcCcA9y7c7AfFDgaP9cG05O1.lqMCq8G', 'dept_admin', 'Grievance Cell', 1);

-- Hygiene/Facility Cell
INSERT IGNORE INTO users (name, email, password, role, department, is_verified) 
VALUES ('Hygiene/Facility Cell Admin', 'hygiene@college.edu', '$2y$10$VN2pYlb6ILmqqEr/QkfiMOcCcA9y7c7AfFDgaP9cG05O1.lqMCq8G', 'dept_admin', 'Hygiene/Facility Cell', 1);

-- Disciplinary Committee
INSERT IGNORE INTO users (name, email, password, role, department, is_verified) 
VALUES ('Disciplinary Committee Admin', 'discipline@college.edu', '$2y$10$VN2pYlb6ILmqqEr/QkfiMOcCcA9y7c7AfFDgaP9cG05O1.lqMCq8G', 'dept_admin', 'Disciplinary Committee', 1);
