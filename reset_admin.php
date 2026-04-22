<?php
$conn = new mysqli('127.0.0.1:3307', 'root', '', 'college_cms');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$email = 'admin@college.edu';
$name = 'System Admin';
$role = 'admin';
$hash = password_hash('admin123', PASSWORD_DEFAULT);

// Check if admin exists, if not, create them. If yes, update password.
$sql = "INSERT INTO users (name, email, password, role) 
        VALUES ('$name', '$email', '$hash', '$role') 
        ON DUPLICATE KEY UPDATE password = '$hash'";

if ($conn->query($sql) === TRUE) {
    echo "<h3>Admin Account Ready!</h3>";
    echo "<strong>Email:</strong> $email<br>";
    echo "<strong>Password:</strong> admin123<br>";
    echo "<br><a href='college-cms/login.php'>Go to Login</a>";
} else {
    echo "Error: " . $conn->error;
}
$conn->close();
?>
