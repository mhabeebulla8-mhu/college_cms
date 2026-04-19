-- Native integration of Department Admin features
USE college_cms;

-- 1. Upgrade Roles and Departments
ALTER TABLE users MODIFY COLUMN role ENUM('student', 'admin', 'dept_admin') DEFAULT 'student';
ALTER TABLE users ADD COLUMN IF NOT EXISTS department VARCHAR(100) DEFAULT NULL;

-- 2. Add features to Complaints
ALTER TABLE complaints ADD COLUMN IF NOT EXISTS remarks TEXT DEFAULT NULL;
ALTER TABLE complaints ADD COLUMN IF NOT EXISTS forwarded_to_main TINYINT(1) DEFAULT 0;

-- 3. Create a test Department Admin
-- Password: dept123
INSERT IGNORE INTO users (name, email, password, role, department) 
VALUES ('Ragging Dept Head', 'ragging@college.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'dept_admin', 'Anti-Ragging Cell');
