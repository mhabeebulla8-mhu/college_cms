<?php
$conn = new mysqli('localhost', 'root', '', 'college_cms');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$hash = password_hash('admin123', PASSWORD_DEFAULT);
$sql = "UPDATE users SET password = '$hash' WHERE email = 'admin@college.edu'";
if ($conn->query($sql) === TRUE) {
    echo "Admin password successfully reset to: admin123";
} else {
    echo "Error updating record: " . $conn->error;
}
$conn->close();
?>
