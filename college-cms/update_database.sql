-- Run this on your XAMPP MySQL to fix missing columns in the complaints table
USE college_cms;

ALTER TABLE complaints 
    ADD COLUMN IF NOT EXISTS subcategory VARCHAR(100) AFTER category,
    ADD COLUMN IF NOT EXISTS priority VARCHAR(20) DEFAULT 'Medium' AFTER file_path,
    ADD COLUMN IF NOT EXISTS is_anonymous TINYINT(1) DEFAULT 0 AFTER subcategory,
    ADD COLUMN IF NOT EXISTS remarks TEXT AFTER priority;
