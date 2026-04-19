-- Merge script for Department Admin functionality

USE college_cms;

-- 1. Create Department Admins table
CREATE TABLE IF NOT EXISTS department_admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    category_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Create Subcategories table (if needed for the new UI)
CREATE TABLE IF NOT EXISTS subcategories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    subcategory_name VARCHAR(100) NOT NULL
);

-- 3. Update Complaints table with new columns expected by department admin panel
ALTER TABLE complaints ADD COLUMN IF NOT EXISTS subcategory_id INT;
ALTER TABLE complaints ADD COLUMN IF NOT EXISTS subject VARCHAR(255);
ALTER TABLE complaints ADD COLUMN IF NOT EXISTS is_anonymous TINYINT(1) DEFAULT 0;
ALTER TABLE complaints ADD COLUMN IF NOT EXISTS remarks TEXT;

-- 4. Insert some sample data
-- Password is md5('dept123') = 'd5668e1694f79607f23c72782e4431ef'
INSERT IGNORE INTO department_admins (name, username, email, password, category_id) 
VALUES ('Anti-Ragging Head', 'ragging_admin', 'ragging@college.edu', 'd5668e1694f79607f23c72782e4431ef', 1);

-- Map the categories to IDs (Anti-Ragging = 1, etc.)
-- You might need to update your complaints submission form later to use these IDs.
INSERT IGNORE INTO subcategories (category_id, subcategory_name) VALUES 
(1, 'Physical Ragging'),
(1, 'Verbal Abuse'),
(2, 'Harassment'),
(3, 'Facility Issue');
