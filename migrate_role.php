<?php
if (!isset($_SERVER['SERVER_NAME'])) {
    $_SERVER['SERVER_NAME'] = 'localhost';
}
session_start();
require_once('db.php');

echo "<div style='font-family:sans-serif; max-width:600px; margin: 40px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);'>";
echo "<h2 style='color:#2c3e50; border-bottom: 2px solid #34495e; padding-bottom: 8px;'>Migrasi Database: Kolom Peran (Role)</h2>";

// 1. Tambah kolom 'role' jika belum ada
$checkColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
if ($checkColumn->num_rows === 0) {
    $alterQuery = "ALTER TABLE users ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'user' AFTER foto_profil";
    if ($conn->query($alterQuery) === TRUE) {
        echo "<p style='color:#27ae60;'>✔ Kolom 'role' berhasil ditambahkan ke tabel 'users'.</p>";
    } else {
        echo "<p style='color:#c0392b;'>❌ Gagal menambahkan kolom 'role': " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color:#3498db;'>ℹ Kolom 'role' sudah ada pada tabel 'users'.</p>";
}

// 2. Buat akun admin default jika belum ada
$adminUsername = 'admin';
$adminPass = 'admin123';
$checkAdmin = $conn->prepare("SELECT id FROM users WHERE username = ?");
$checkAdmin->bind_param("s", $adminUsername);
$checkAdmin->execute();
$resAdmin = $checkAdmin->get_result();

if ($resAdmin->num_rows === 0) {
    $hashedPassword = password_hash($adminPass, PASSWORD_DEFAULT);
    $insertAdmin = $conn->prepare("INSERT INTO users (username, password, role, nama_lengkap, bio) VALUES (?, ?, 'admin', 'Super Admin', 'Akun Administrator Utama Sistem')");
    $insertAdmin->bind_param("ss", $adminUsername, $hashedPassword);
    if ($insertAdmin->execute()) {
        echo "<p style='color:#27ae60;'>✔ Akun admin default berhasil dibuat!</p>";
        echo "<table style='border-collapse:collapse; width:100%; margin-top:10px;'>
                <tr style='background:#f8f9fa;'><td style='padding:8px; border:1px solid #ddd;'><b>Username</b></td><td style='padding:8px; border:1px solid #ddd;'><code>admin</code></td></tr>
                <tr style='background:#f8f9fa;'><td style='padding:8px; border:1px solid #ddd;'><b>Password</b></td><td style='padding:8px; border:1px solid #ddd;'><code>admin123</code></td></tr>
              </table>";
    } else {
        echo "<p style='color:#c0392b;'>❌ Gagal membuat akun admin default: " . $insertAdmin->error . "</p>";
    }
    $insertAdmin->close();
} else {
    // Pastikan user 'admin' memiliki role 'admin'
    $updateAdmin = $conn->prepare("UPDATE users SET role = 'admin' WHERE username = ?");
    $updateAdmin->bind_param("s", $adminUsername);
    if ($updateAdmin->execute()) {
         echo "<p style='color:#3498db;'>ℹ Akun 'admin' sudah ada dan perannya dipastikan sebagai 'admin'.</p>";
    }
    $updateAdmin->close();
}
$checkAdmin->close();

echo "<div style='margin-top:20px;'><a href='index.php' style='display:inline-block; padding:10px 20px; background:#34495e; color:#fff; text-decoration:none; border-radius:4px;'>Kembali ke Beranda</a></div>";
echo "</div>";
?>
