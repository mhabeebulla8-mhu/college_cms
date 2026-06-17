<?php
require_once 'db.php';
echo "<h1>Database Diagnostic</h1>";
echo "Connecting to: <b>$host</b> on port <b>$port</b> as <b>$username</b><br>";
echo "Database: <b>$dbname</b><br><br>";

if ($conn->connect_error) {
    echo "❌ Connection failed: " . $conn->connect_error;
} else {
    echo "✅ Connection successful!<br><br>";
    
    echo "<h3>Tables in '$dbname':</h3>";
    $result = $conn->query("SHOW TABLES");
    if ($result) {
        if ($result->num_rows > 0) {
            echo "<ul>";
            while($row = $result->fetch_array()) {
                echo "<li>" . $row[0] . "</li>";
            }
            echo "</ul>";
        } else {
            echo "⚠️ No tables found in database '$dbname'.";
        }
    } else {
        echo "❌ Error listing tables: " . $conn->error;
    }
}
?>
