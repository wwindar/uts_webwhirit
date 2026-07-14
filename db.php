<?php

$is_local = ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1');

if ($is_local) {
    // Konfigurasi lokal (XAMPP)
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'uts_web');
} else {
    // Konfigurasi InfinityFree (online)
    define('DB_HOST', 'sql104.infinityfree.com');
    define('DB_USER', 'if0_42410658');
    define('DB_PASS', 'windarshineta');
    define('DB_NAME', 'if0_42410658_uts_web');
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("<div style='font-family:sans-serif;padding:20px;background:#fee;border:1px solid #f00;margin:20px;border-radius:8px;'>
        <strong>Koneksi Database Gagal:</strong> " . $conn->connect_error . "
        <br><small>Pastikan MySQL berjalan dan database sudah dibuat.</small>
    </div>");
}

$conn->set_charset("utf8mb4");
?>