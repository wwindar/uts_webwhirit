<?php
require_once('db.php');
$r = $conn->query("ALTER TABLE users ADD COLUMN genre_favorit VARCHAR(200) NULL AFTER bio");
echo $r ? "OK: Kolom genre_favorit berhasil ditambahkan." : "Error: " . $conn->error;
$conn->close();
?>
