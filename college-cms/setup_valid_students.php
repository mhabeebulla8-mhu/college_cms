<?php
require_once 'db.php';

echo "<h2>Database Setup: valid_students table</h2>";

// 1. Create the table
$sql = "CREATE TABLE IF NOT EXISTS valid_students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usn VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "✅ Table 'valid_students' created successfully (or already exists).<br>";
} else {
    die("❌ Error creating table: " . $conn->error);
}

// 2. Insert USNs from 1 to 125 for the batch U18AS23S
echo "Populating USNs from U18AS23S0001 to U18AS23S0125...<br>";

$stmt = $conn->prepare("INSERT IGNORE INTO valid_students (usn, name) VALUES (?, ?)");
$count = 0;
for ($i = 1; $i <= 125; $i++) {
    $usn = "U18AS23S" . str_pad($i, 4, "0", STR_PAD_LEFT);
    $name = "Student " . $i;
    $stmt->bind_param("ss", $usn, $name);
    if ($stmt->execute()) {
        if ($conn->affected_rows > 0) {
            $count++;
        }
    }
}

echo "✅ Successfully added $count new USNs to the authorized list.<br>";
echo "Total authorized students: " . $conn->query("SELECT COUNT(*) FROM valid_students")->fetch_row()[0] . "<br>";

echo "<br><strong>You can now use any USN from U18AS23S0001 to U18AS23S0125 to register.</strong>";


echo "<br><a href='register.php'>Go to Registration Page</a>";
?>
