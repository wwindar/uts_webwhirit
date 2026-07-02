<?php
session_start();
require_once ('db.php');
require_once ('auth.php');
requireLogin();

$userId = $_SESSION['user_id'];

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=resensi_buku_' . time() . '.csv');

$output = fopen('php://output', 'w');

// Header kolom
fputcsv($output, ['ID', 'Judul Buku', 'Penulis', 'Genre', 'Ulasan', 'Rating', 'Tanggal Input']);

$query = "SELECT id, judul_buku, penulis, genre, ulasan, rating, tgl_input FROM resensi WHERE user_id = ?";
$st = $conn->prepare($query);
$st->bind_param("i", $userId);
$st->execute();
$result = $st->get_result();

while ($row = $result->fetch_assoc()) {
    fputcsv($output, $row);
}

$st->close();
fclose($output);
exit();