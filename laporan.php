<?php
session_start();
require_once ('db.php');
require_once ('auth.php');
requireLogin();

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resensiId = intval($_POST['resensi_id'] ?? 0);
    $alasan    = trim($_POST['alasan']      ?? '');
    $redirect  = $_POST['redirect']        ?? "katalog.php";

    if ($resensiId > 0 && $alasan !== '') {
        // Cek apakah sudah pernah lapor
        $st = $conn->prepare("SELECT id FROM laporan WHERE resensi_id = ? AND pelapor_id = ?");
        $st->bind_param("ii", $resensiId, $userId);
        $st->execute();
        $sudah = $st->get_result()->num_rows > 0;
        $st->close();

        if ($sudah) {
            $_SESSION['flash']      = 'Kamu sudah pernah melaporkan resensi ini.';
            $_SESSION['flash_type'] = 'info';
        } else {
            $st = $conn->prepare("INSERT INTO laporan (resensi_id, pelapor_id, alasan) VALUES (?, ?, ?)");
            $st->bind_param("iis", $resensiId, $userId, $alasan);
            $st->execute();
            $st->close();
            $_SESSION['flash']      = 'Laporan berhasil dikirim. Terima kasih!';
            $_SESSION['flash_type'] = 'success';
        }
    }
    header("Location: $redirect");
    exit();
}

header("Location: katalog.php");
exit();