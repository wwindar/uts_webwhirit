<?php
require_once('db.php');

echo "=== DAFTAR SEMUA USER ===\n\n";

$result = $conn->query("SELECT id, username, nama_lengkap, email, nomor_telepon, created_at FROM users ORDER BY id ASC");

while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . "\n";
    echo "Username: " . $row['username'] . "\n";
    echo "Nama Lengkap: " . ($row['nama_lengkap'] ?: '-') . "\n";
    echo "Email: " . ($row['email'] ?: '-') . "\n";
    echo "No. Telp: " . ($row['nomor_telepon'] ?: '-') . "\n";
    echo "Dibuat: " . $row['created_at'] . "\n";
    echo "----------------------------\n";
}

$conn->close();
?>
