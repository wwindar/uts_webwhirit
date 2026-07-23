<?php
session_start();
require_once ('db.php');
require_once ('auth.php');
requireLogin();

$userId = $_SESSION['user_id'];

// Set header
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=resensi_buku_' . time() . '.csv');
header('Pragma: no-cache');
header('Expires: 0');

$query = "SELECT id, judul_buku, penulis, genre, ulasan, rating, tgl_input FROM resensi WHERE user_id = ?";
$st = $conn->prepare($query);
$st->bind_param("i", $userId);
$st->execute();
$result = $st->get_result();

// UTF-8 BOM agar Excel/WPS membaca dengan benar
echo "\xEF\xBB\xBF";

// Header kolom
echo "ID;Judul Buku;Penulis;Genre;Ulasan;Rating;Tanggal Input\r\n";

while ($row = $result->fetch_assoc()) {
    $rowEscaped = [];
    foreach ($row as $val) {
        // Bersihkan data dari karakter baris baru dan escape kutip dua
        $valCleaned = str_replace(["\r", "\n"], ' ', $val);
        $rowEscaped[] = '"' . str_replace('"', '""', $valCleaned) . '"';
    }
    echo implode(';', $rowEscaped) . "\r\n";
}

$st->close();
exit();
?>