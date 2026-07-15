<?php

session_start();
require_once ('db.php');
require_once ('auth.php');

requireLogin();

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: katalog.php");
    exit();
}

$stmt = $conn->prepare("SELECT judul_buku, user_id FROM resensi WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['flash'] = 'Resensi tidak ditemukan.';
    $_SESSION['flash_type'] = 'error';
    header("Location: katalog.php");
    exit();
}

$buku = $result->fetch_assoc();
$stmt->close();

if ($buku['user_id'] != $_SESSION['user_id'] && !isAdmin()) {
    $_SESSION['flash'] = 'Anda tidak memiliki hak akses untuk menghapus resensi ini.';
    $_SESSION['flash_type'] = 'error';
    header("Location: katalog.php");
    exit();
}

$del = $conn->prepare("DELETE FROM resensi WHERE id = ?");
$del->bind_param("i", $id);

if ($del->execute()) {
    $_SESSION['flash'] = 'Resensi "' . $buku['judul_buku'] . '" berhasil dihapus.';
    $_SESSION['flash_type'] = 'success';
} else {
    $_SESSION['flash'] = 'Gagal menghapus resensi. Coba lagi.';
    $_SESSION['flash_type'] = 'error';
}

$del->close();
header("Location: katalog.php");
exit();
?>