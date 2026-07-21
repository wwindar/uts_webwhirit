<?php
require_once('db.php');

// Add columns to users table
$queries = [
    "ALTER TABLE users ADD COLUMN is_verified TINYINT(1) DEFAULT 0",
    "ALTER TABLE users ADD COLUMN otp_code VARCHAR(10) DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN otp_expires DATETIME DEFAULT NULL",
    // Update existing users so they can still login
    "UPDATE users SET is_verified = 1"
];

$successCount = 0;
foreach ($queries as $sql) {
    if ($conn->query($sql)) {
        $successCount++;
        echo "<p>Success: $sql</p>";
    } else {
        echo "<p>Error or already exists: " . $conn->error . " ($sql)</p>";
    }
}

echo "<h3>Migration Finished. $successCount queries executed successfully.</h3>";
?>
