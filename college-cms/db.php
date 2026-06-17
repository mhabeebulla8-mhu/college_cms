<?php
// Database configuration
$host = "127.0.0.1";
$port = 3307;
$username = "root";
$password = "";
$dbname = "college_cms";

// Base URL for generating links (use your local IP so mobile devices can access it)
define('SITE_URL', 'http://10.18.62.222/college-cms');

// Email Secrets (For XAMPP/PHP)
// Use an App Password from https://myaccount.google.com/apppasswords
define('EMAIL_USER', 'mhabeebulla8@gmail.com');
define('EMAIL_PASS', 'kdnvnxdsmreekiei');
define('EMAIL_FROM', 'Student Complaint Management System <mhabeebulla8@gmail.com>');

// Gemini AI Secret (For XAMPP/PHP)
define('GEMINI_API_KEY', 'your-gemini-api-key');

// Create connection
$conn = new mysqli($host, $username, $password, $dbname, $port);
$conn->set_charset('utf8mb4');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function cmsColumnExists(mysqli $conn, $table, $column) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
    ");
    $stmt->bind_param("ss", $table, $column);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return (int)$result['total'] > 0;
}

function cmsBootstrapDatabase(mysqli $conn) {
    $conn->query("
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
        )
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS valid_students (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usn VARCHAR(20) NOT NULL UNIQUE,
            name VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS otp_codes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            otp_hash VARCHAR(255) NOT NULL,
            attempts INT DEFAULT 0,
            expires_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS complaints (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            category VARCHAR(100) NOT NULL,
            subcategory VARCHAR(100) DEFAULT NULL,
            is_anonymous TINYINT(1) DEFAULT 0,
            description TEXT NOT NULL,
            file_path VARCHAR(255) DEFAULT NULL,
            priority VARCHAR(20) DEFAULT 'Medium',
            status ENUM('Pending', 'In Progress', 'Under Review', 'Resolved') DEFAULT 'Pending',
            remarks TEXT DEFAULT NULL,
            forwarded_to_main TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            reviewed_at TIMESTAMP NULL DEFAULT NULL,
            resolved_at TIMESTAMP NULL DEFAULT NULL,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    $conn->query("ALTER TABLE users MODIFY COLUMN role ENUM('student', 'admin', 'dept_admin') DEFAULT 'student'");
    $conn->query("ALTER TABLE complaints MODIFY COLUMN status ENUM('Pending', 'In Progress', 'Under Review', 'Resolved') DEFAULT 'Pending'");

    $userColumns = [
        'university_reg_no' => "ALTER TABLE users ADD COLUMN university_reg_no VARCHAR(50) DEFAULT NULL",
        'department' => "ALTER TABLE users ADD COLUMN department VARCHAR(100) DEFAULT NULL",
        'id_card_path' => "ALTER TABLE users ADD COLUMN id_card_path VARCHAR(255) DEFAULT NULL",
        'is_verified' => "ALTER TABLE users ADD COLUMN is_verified TINYINT(1) DEFAULT 0",
        'created_at' => "ALTER TABLE users ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
    ];

    foreach ($userColumns as $column => $sql) {
        if (!cmsColumnExists($conn, 'users', $column)) {
            $conn->query($sql);
        }
    }

    $complaintColumns = [
        'subcategory' => "ALTER TABLE complaints ADD COLUMN subcategory VARCHAR(100) DEFAULT NULL AFTER category",
        'is_anonymous' => "ALTER TABLE complaints ADD COLUMN is_anonymous TINYINT(1) DEFAULT 0 AFTER subcategory",
        'file_path' => "ALTER TABLE complaints ADD COLUMN file_path VARCHAR(255) DEFAULT NULL AFTER description",
        'priority' => "ALTER TABLE complaints ADD COLUMN priority VARCHAR(20) DEFAULT 'Medium' AFTER file_path",
        'remarks' => "ALTER TABLE complaints ADD COLUMN remarks TEXT DEFAULT NULL AFTER status",
        'forwarded_to_main' => "ALTER TABLE complaints ADD COLUMN forwarded_to_main TINYINT(1) DEFAULT 0 AFTER remarks",
        'reviewed_at' => "ALTER TABLE complaints ADD COLUMN reviewed_at TIMESTAMP NULL DEFAULT NULL AFTER created_at",
        'resolved_at' => "ALTER TABLE complaints ADD COLUMN resolved_at TIMESTAMP NULL DEFAULT NULL AFTER reviewed_at"
    ];

    foreach ($complaintColumns as $column => $sql) {
        if (!cmsColumnExists($conn, 'complaints', $column)) {
            $conn->query($sql);
        }
    }

    $otpColumns = [
        'attempts' => "ALTER TABLE otp_codes ADD COLUMN attempts INT DEFAULT 0 AFTER otp_hash",
        'created_at' => "ALTER TABLE otp_codes ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER expires_at"
    ];

    foreach ($otpColumns as $column => $sql) {
        if (!cmsColumnExists($conn, 'otp_codes', $column)) {
            $conn->query($sql);
        }
    }

    $countRow = $conn->query("SELECT COUNT(*) AS total FROM valid_students")->fetch_assoc();
    if ((int)$countRow['total'] === 0) {
        $stmt = $conn->prepare("INSERT INTO valid_students (usn, name) VALUES (?, ?)");
        for ($i = 1; $i <= 125; $i++) {
            $usn = "U18AS23S" . str_pad($i, 4, "0", STR_PAD_LEFT);
            $name = "Student " . $i;
            $stmt->bind_param("ss", $usn, $name);
            $stmt->execute();
        }
    }
}

cmsBootstrapDatabase($conn);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
