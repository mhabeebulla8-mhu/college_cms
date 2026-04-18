<?php
// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$dbname = "college_cms";

// Email Secrets (For XAMPP/PHP)
// Use an App Password from https://myaccount.google.com/apppasswords
define('EMAIL_USER', 'mhabeebulla8@gmail.com');
define('EMAIL_PASS', 'kdnvnxdsmreekiei');
define('EMAIL_FROM', 'MSc/BCA College Support <mhabeebulla8@gmail.com>');

// Gemini AI Secret (For XAMPP/PHP)
define('GEMINI_API_KEY', 'your-gemini-api-key');

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
