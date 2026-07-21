<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('db.php');

echo "<h2>Checking Database Status</h2>";

$res = $conn->query("DESCRIBE users");
if ($res) {
    echo "<table border='1'><tr><th>Field</th><th>Type</th></tr>";
    $has_is_verified = false;
    while ($row = $res->fetch_assoc()) {
        echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td></tr>";
        if ($row['Field'] === 'is_verified') {
            $has_is_verified = true;
        }
    }
    echo "</table>";
    
    if (!$has_is_verified) {
        echo "<h3>Attempting to fix columns...</h3>";
        $conn->query("ALTER TABLE users ADD COLUMN is_verified TINYINT(1) DEFAULT 0");
        $conn->query("ALTER TABLE users ADD COLUMN otp_code VARCHAR(10) DEFAULT NULL");
        $conn->query("ALTER TABLE users ADD COLUMN otp_expires DATETIME DEFAULT NULL");
        $conn->query("UPDATE users SET is_verified = 1");
        echo "<p>Fix attempted. Please refresh this page to verify.</p>";
    } else {
        echo "<h3 style='color:green;'>Database is already up to date!</h3>";
    }
} else {
    echo "Error describing users table: " . $conn->error;
}
?>
