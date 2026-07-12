<?php
require_once('db.php');

$sql = "ALTER TABLE users ADD COLUMN nama_lengkap VARCHAR(100) NULL AFTER username";

if ($conn->query($sql) === TRUE) {
    echo "Column nama_lengkap successfully added to users table.\n";
} else {
    echo "Error: " . $conn->error . "\n";
}

$conn->close();
?>
