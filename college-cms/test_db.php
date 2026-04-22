<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "127.0.0.1:3307";
$username = "root";
$password = "";
$dbname = "college_cms";

echo "Testing connection to $host...<br>";

$conn = new mysqli($host, $username, $password);

if ($conn->connect_error) {
    die("CRITICAL: Server connection failed: " . $conn->connect_error);
}
echo "SUCCESS: Connected to MySQL server.<br>";

if ($conn->select_db($dbname)) {
    echo "SUCCESS: Database '$dbname' found.<br>";
} else {
    echo "ERROR: Database '$dbname' NOT found. You need to create it in phpMyAdmin.<br>";
}
?>
